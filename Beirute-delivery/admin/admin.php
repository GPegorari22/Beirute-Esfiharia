<?php
session_start();
include '../backend/conexao.php';
if(!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin'){
    header("Location: ../index.html");
    exit();
}
// helpers seguros para checar existência
function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}
function columnExists(PDO 
$pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->execute([$table, $column]);
    return (bool)$stmt->fetchColumn();
}
function firstExistingColumn(PDO $pdo, string $table, array $candidates) {
    foreach ($candidates as $c) {
        if (columnExists($pdo, $table, $c)) return $c;
    }
    return null;
}

// inicializa variáveis usadas na view (evita "Undefined variable")
$total_pedidos = 0;
$em_andamento = 0;
$clientes_ativos = 0;
$receita_dia = 0.0;
$produtos_populares = [];
$labels = [];
$data_chart = [];
$labels_pizza = [];
$data_pizza = [];

// Total de pedidos e receita do dia (se tabela/colunas existirem)
try {
    if (tableExists($pdo, 'pedidos')) {
        // total de pedidos
        $row = $pdo->query("SELECT COUNT(*) AS total FROM pedidos")->fetch(PDO::FETCH_ASSOC);
        $total_pedidos = (int)($row['total'] ?? 0);

        // receita do dia: procura colunas possíveis
        $col_valor = firstExistingColumn($pdo, 'pedidos', ['total','valor','valor_total','preco_total']);
        if ($col_valor) {
            $stmt = $pdo->prepare("SELECT SUM($col_valor) AS soma FROM pedidos WHERE DATE(data_pedido) = CURDATE()");
            $stmt->execute();
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            $receita_dia = (float)($r['soma'] ?? 0.0);
        }

        // pedidos "em andamento" — tenta detectar coluna de status e valores comuns
        $col_status = firstExistingColumn($pdo, 'pedidos', ['status','situacao','estado']);
        if ($col_status) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) AS total 
                FROM pedidos 
                WHERE ($col_status LIKE :a OR $col_status LIKE :b OR $col_status LIKE :c)
            ");
            $stmt->execute([
                ':a' => '%andament%',
                ':b' => '%pendente%',
                ':c' => '%process%'
            ]);
            $r2 = $stmt->fetch(PDO::FETCH_ASSOC);
            $em_andamento = (int)($r2['total'] ?? 0);
        }
    }
} catch (Exception $e) {
    error_log("Erro pedidos/receita: " . $e->getMessage());
}

// Clientes ativos (se tabela existir)
try {
    if (tableExists($pdo, 'usuarios')) {
        $col_ativo = firstExistingColumn($pdo, 'usuarios', ['ativo','status','ativo_usuario']);
        if ($col_ativo) {
            // tenta usar valor 1 ou string 'ativo'
            $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE $col_ativo = :val");
            $stmt->execute([':val' => 1]);
            $clientes_ativos = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            if ($clientes_ativos === 0) {
                $stmt = $pdo->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE $col_ativo LIKE :val");
                $stmt->execute([':val' => '%ativo%']);
                $clientes_ativos = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            }
        } else {
            // fallback: conta todos os usuários se não há coluna de ativo
            $row = $pdo->query("SELECT COUNT(*) AS total FROM usuarios")->fetch(PDO::FETCH_ASSOC);
            $clientes_ativos = (int)($row['total'] ?? 0);
        }
    }
} catch (Exception $e) {
    error_log("Erro clientes: " . $e->getMessage());
}

// Produtos populares (agregação robusta a partir da tabela de itens disponível)
try {
    $produtos_populares = [];

    // possibilidades de nomes para tabela de itens
    $candidates = [
        'itens_pedido','itens_pedidos','pedido_itens',
        'itens_carrinho','itens_pedidos_historico','historico_itens_pedido'
    ];
    $itemsTable = null;
    foreach ($candidates as $t) {
        if (tableExists($pdo, $t)) { $itemsTable = $t; break; }
    }

    if ($itemsTable && tableExists($pdo, 'produtos')) {
        // colunas prováveis
        $prodCol = firstExistingColumn($pdo, $itemsTable, ['id_produto','produto_id','id_produtos','produto','produto_fk','produtoId']);
        $qtyCol  = firstExistingColumn($pdo, $itemsTable, ['quantidade','qtd','qtde','quantidade_item','quant']);

        if (!$prodCol) $prodCol = 'id_produto';

        // montar SQL: se existir coluna de quantidade, somar; caso contrário contar ocorrências
        if ($qtyCol) {
            $sql = "
                SELECT p.id, p.nome, p.imagem, COALESCE(SUM(ip.{$qtyCol}),0) AS total_pedidos
                FROM {$itemsTable} ip
                JOIN produtos p ON p.id = ip.{$prodCol}
                GROUP BY p.id
                ORDER BY total_pedidos DESC
                LIMIT 3
            ";
        } else {
            $sql = "
                SELECT p.id, p.nome, p.imagem, COUNT(*) AS total_pedidos
                FROM {$itemsTable} ip
                JOIN produtos p ON p.id = ip.{$prodCol}
                GROUP BY p.id
                ORDER BY total_pedidos DESC
                LIMIT 3
            ";
        }

        $produtos_populares = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } elseif (tableExists($pdo, 'produtos')) {
        // fallback: últimos produtos adicionados
        $produtos_populares = $pdo->query("SELECT id, nome, imagem FROM produtos ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $produtos_populares = [];
    }
} catch (Exception $e) {
    error_log("Erro produtos populares: " . $e->getMessage());
    $produtos_populares = [];
}

// Dados por categoria para o gráfico de pizza (conta itens pedidos por categoria)
try {
    $labels_pizza = [];
    $data_pizza = [];

    // procura a tabela de itens mais provável entre vários nomes possíveis
    $candidates = [
        'itens_pedido','itens_pedidos','pedido_itens',
        'itens_carrinho','itens_pedidos_historico','historico_itens_pedido'
    ];
    $itemsTable = null;
    foreach ($candidates as $t) {
        if (tableExists($pdo, $t)) { $itemsTable = $t; break; }
    }

    if ($itemsTable && tableExists($pdo, 'produtos')) {
        // colunas prováveis
        $prodCol = firstExistingColumn($pdo, $itemsTable, ['id_produto','produto_id','id_produtos','produto']);
        $qtyCol = firstExistingColumn($pdo, $itemsTable, ['quantidade','qtd','qtde','quantidade_item']);
        $linkCol = firstExistingColumn($pdo, $itemsTable, ['id_pedido','pedido_id','id_carrinho','carrinho_id']);

        // tenta garantir que vamos contar só itens que pertencem a pedidos confirmados (quando possível)
        $joinPedidos = '';
        if ($linkCol && tableExists($pdo, 'pedidos')) {
            if (in_array($linkCol, ['id_pedido','pedido_id']) && columnExists($pdo, 'pedidos', 'id')) {
                $joinPedidos = " JOIN pedidos ped ON ped.id = ip.$linkCol ";
            } elseif (in_array($linkCol, ['id_carrinho','carrinho_id']) && columnExists($pdo, 'pedidos', 'id_carrinho')) {
                $joinPedidos = " JOIN pedidos ped ON ped.id_carrinho = ip.$linkCol ";
            }
        }

        if (!$prodCol) {
            // fallback para caso a coluna do produto tenha nome inesperado: tenta inferir pela existência de uma coluna que referencia produtos
            $prodCol = firstExistingColumn($pdo, $itemsTable, ['produto','produto_fk','produtoId']) ?? 'id_produto';
        }

        if ($qtyCol) {
            $selectQty = "COALESCE(SUM(ip.$qtyCol),0) AS total_qty";
        } else {
            $selectQty = "COUNT(*) AS total_qty";
        }

        $sql = "
            SELECT p.categoria, {$selectQty}
            FROM {$itemsTable} ip
            JOIN produtos p ON p.id = ip.{$prodCol}
            {$joinPedidos}
            GROUP BY p.categoria
            ORDER BY total_qty DESC
        ";

        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif (tableExists($pdo, 'historico_itens_pedido') && tableExists($pdo, 'produtos') && columnExists($pdo, 'produtos', 'categoria')) {
        $stmt = $pdo->query("
            SELECT p.categoria, COALESCE(SUM(h.quantidade),0) AS total_qty
            FROM historico_itens_pedido h
            JOIN produtos p ON p.id = h.id_produto
            GROUP BY p.categoria
            ORDER BY total_qty DESC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } elseif (tableExists($pdo, 'produtos') && columnExists($pdo, 'produtos', 'categoria')) {
        $stmt = $pdo->query("SELECT categoria, COUNT(*) AS total_qty FROM produtos GROUP BY categoria ORDER BY total_qty DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $rows = [];
    }

    foreach ($rows as $r) {
        $labels_pizza[] = ucfirst($r['categoria']);
        $data_pizza[] = (int) ($r['total_qty'] ?? $r['total'] ?? 0);
    }
} catch (Exception $e) {
    error_log("Erro categorias (pizza): " . $e->getMessage());
    $labels_pizza = [];
    $data_pizza = [];
}

// melhor dia da semana & últimos 7 dias (se tabela pedidos existir)
try {
    if (tableExists($pdo, 'pedidos') && columnExists($pdo, 'pedidos', 'data_pedido')) {
        // mapeamento de dias (usado no alerta)
        $dias_semana = [
            1 => 'Domingo',
            2 => 'Segunda',
            3 => 'Terça',
            4 => 'Quarta',
            5 => 'Quinta',
            6 => 'Sexta',
            7 => 'Sábado'
        ];

        // melhor dia da semana (baseado no campo data_pedido)
        $stmt = $pdo->query("
            SELECT DAYOFWEEK(data_pedido) AS dia_semana, COUNT(*) AS total_pedidos
            FROM pedidos
            GROUP BY DAYOFWEEK(data_pedido)
            ORDER BY total_pedidos DESC
            LIMIT 1
        ");
        $melhor_dia = $stmt->fetch(PDO::FETCH_ASSOC);

        // últimos 7 dias (hoje inclusive) -> agregação por data
        $stmt2 = $pdo->prepare("
            SELECT DATE(data_pedido) AS dia, COUNT(*) AS total
            FROM pedidos
            WHERE DATE(data_pedido) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(data_pedido)
            ORDER BY DATE(data_pedido) ASC
        ");
        $stmt2->execute();
        $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // criar mapa dia => total para preencher zeros ausentes
        $map = [];
        foreach ($rows as $r) {
            $map[$r['dia']] = (int)$r['total'];
        }

        // montar labels (dias da semana em pt-BR) e dados na mesma ordem (6 dias atrás ... hoje)
        $weekDays = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];
        $labels = [];
        $data_chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            // nome do dia em pt
            $labels[] = $weekDays[(int)date('w', strtotime($day))];
            $data_chart[] = $map[$day] ?? 0;
        }
    }
} catch (Exception $e) {
    error_log("Erro gráfico pedidos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração — Beirute</title>
    <link rel="stylesheet" href="../css/adm.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <!-- Overview Section -->
        <div class="overview-container">
            <div class="overview-header">
                <h2>Overview</h2>
            </div>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $total_pedidos; ?></h3>
                    <p>Pedidos</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $em_andamento; ?></h3>
                    <p>Em andamento</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $clientes_ativos; ?></h3>
                    <p>Clientes ativos</p>
                </div>
                <div class="stat-card">
                    <h3>R$ <?php echo number_format($receita_dia, 2, ',', '.'); ?></h3>
                    <p>Receita do dia</p>
                </div>
            </div>

            <div class="charts-container">
                <!-- Gráfico existente -->
                <div class="chart-box">
                    <canvas id="pedidosChart"></canvas>
                </div>
                <!-- Novo gráfico de pizza -->
                <div class="chart-box">
                    <canvas id="categoriasChart" height="300"></canvas>
                </div>
            </div>

            <div class="popular-products">
                <h3>Mais pedidos</h3>
                <div class="products-grid">
                    <?php foreach($produtos_populares as $produto): 
                        // garante chaves existentes e tipos corretos
                        $nome = htmlspecialchars($produto['nome'] ?? ' — ');
                        $imagemArquivo = $produto['imagem'] ?? '';
                        $totalPedidos = isset($produto['total_pedidos']) ? (int)$produto['total_pedidos'] : 0;

                        // caminho seguro da imagem com fallback
                        $imgPathRel = '../img/produtos/';
                        $imgSrc = (!empty($imagemArquivo) && file_exists($imgPathRel . $imagemArquivo))
                            ? $imgPathRel . $imagemArquivo
                            : $imgPathRel . 'placeholder.png';
                    ?>
                    <div class="product-card">
                        <img src="<?php echo $imgSrc; ?>" alt="<?php echo $nome; ?>">
                        <h4><?php echo $nome; ?></h4>
                        <p><strong><?php echo $totalPedidos; ?></strong> pedidos</p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Gráfico de Barras
        (function(){
            const ctx = document.getElementById('pedidosChart');
            if (!ctx) return;

            const labels = <?php echo json_encode($labels, JSON_UNESCAPED_UNICODE); ?>;
            const data = <?php echo json_encode($data_chart); ?>;

            new Chart(ctx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pedidos',
                        data: data,
                        backgroundColor: '#b78b46', // Dourado
                        borderColor: '#b78b46',
                        borderWidth: 0,
                        borderRadius: 4,
                        barThickness: 40 // Barras mais grossas
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: {
                            display: true,
                            text: 'Pedidos',
                            color: '#fff',
                            font: {
                                size: 24
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { 
                                display: false,
                                drawBorder: false
                            },
                            ticks: { 
                                color: '#fff',
                                font: {
                                    size: 14
                                }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(255, 255, 255, 0.1)',
                                drawBorder: false
                            },
                            ticks: { 
                                precision: 0,
                                color: '#fff',
                                font: {
                                    size: 14
                                }
                            }
                        }
                    }
                }
            });
        })();

        // Gráfico de Pizza
        (function(){
            const ctxPizza = document.getElementById('categoriasChart');
            if (!ctxPizza) return;

            let labelsPizza = <?php echo json_encode($labels_pizza, JSON_UNESCAPED_UNICODE); ?>;
            let dataPizza = <?php echo json_encode($data_pizza); ?>;

            // normaliza caso arrays estejam nulos
            labelsPizza = Array.isArray(labelsPizza) ? labelsPizza : [];
            dataPizza = Array.isArray(dataPizza) ? dataPizza.map(v => parseInt(v)||0) : [];

            const total = dataPizza.reduce((a,b)=>a+b,0);

            if (total === 0) {
                labelsPizza = ['Sem pedidos'];
                dataPizza = [1];
            }

            const bgColors = [
                '#b78b46',
                '#2d5f8a',
                '#7f9aa8',
                '#d4b886',
                '#c96b44',
                '#6aa37a'
            ];

            new Chart(ctxPizza.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: labelsPizza,
                    datasets: [{
                        data: dataPizza,
                        backgroundColor: bgColors.slice(0, labelsPizza.length),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: '#fff',
                                font: { size: 14 },
                                padding: 20
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value * 100) / total);
                                    return `${label}: ${percentage}%`;
                                }
                            }
                        }
                    }
                }
            });
        })();
    </script>
</body>
</html>