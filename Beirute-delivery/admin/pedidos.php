<?php
session_start();
require '../backend/conexao.php';
include '../includes/sidebar.php';
// segurança: só admin
if(!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin'){
    header("Location: ../index.html");
    exit();
}
// Conexão (XAMPP padrão)
$pdo = new PDO('mysql:host=127.0.0.1;dbname=beirute;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

// Ações rápidas (confirmar / cancelar / alterar status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['acao']) && !empty($_POST['id_pedido'])) {
    $id = (int) $_POST['id_pedido'];
    $acao = $_POST['acao'];

    // buscar usuário do pedido ANTES de atualizar
    $stmt_user = $pdo->prepare("SELECT id_usuario FROM pedidos WHERE id = ?");
    $stmt_user->execute([$id]);
    $pedido_user = $stmt_user->fetch(PDO::FETCH_ASSOC);
    $id_usuario = $pedido_user['id_usuario'] ?? null;

    if ($acao === 'confirmar') {
        $stmt = $pdo->prepare("UPDATE pedidos SET status_pedido = 'em preparo', data_atualizacao = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        
        // criar notificação
        if ($id_usuario) {
            $stmt_notif = $pdo->prepare("
                INSERT INTO notificacoes (id_usuario, id_pedido, mensagem, tipo, lida)
                VALUES (?, ?, 'Seu pedido foi confirmado e está sendo preparado!', 'pedido_preparando', 0)
            ");
            $stmt_notif->execute([$id_usuario, $id]);
        }
    } elseif ($acao === 'alterar_status' && !empty($_POST['novo_status'])) {
        $novo = $_POST['novo_status'];
        $allowed = ['pendente','em preparo','saiu para entrega','entregue','cancelado'];
        
        if (in_array($novo, $allowed, true)) {
            $stmt = $pdo->prepare("UPDATE pedidos SET status_pedido = ?, data_atualizacao = NOW() WHERE id = ?");
            $stmt->execute([$novo, $id]);

            // criar notificação baseada no novo status
            if ($id_usuario) {
                $mensagens = [
                    'em preparo' => 'Seu pedido está sendo preparado pela cozinha!',
                    'saiu para entrega' => 'Seu pedido saiu para entrega! 🚚',
                    'entregue' => 'Seu pedido foi entregue! Agradecemos a preferência! 🎉',
                    'cancelado' => 'Seu pedido foi cancelado.',
                    'pendente' => 'Seu pedido está pendente de confirmação.'
                ];
                $tipos = [
                    'em preparo' => 'pedido_preparando',
                    'saiu para entrega' => 'pedido_pronto',
                    'entregue' => 'pedido_entregue',
                    'cancelado' => 'pedido_cancelado',
                    'pendente' => 'pedido_confirmado'
                ];
                
                $msg = $mensagens[$novo] ?? 'Seu pedido foi atualizado.';
                $tipo = $tipos[$novo] ?? 'pedido_confirmado';
                
                $stmt_notif = $pdo->prepare("
                    INSERT INTO notificacoes (id_usuario, id_pedido, mensagem, tipo, lida)
                    VALUES (?, ?, ?, ?, 0)
                ");
                $stmt_notif->execute([$id_usuario, $id, $msg, $tipo]);
            }
        }
    }

    header('Location: pedidos.php');
    exit;
}

// Estatísticas
$totalPedidos = (int) $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
$emAndamento = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status_pedido IN ('em preparo','saiu para entrega')")->fetchColumn();
$entregues = (int) $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status_pedido = 'entregue'")->fetchColumn();
$receitaHoje = (float) $pdo->query("SELECT IFNULL(SUM(valor_total),0) FROM pedidos WHERE DATE(data_pedido)=CURDATE()")->fetchColumn();

// Lista de pedidos (últimos 20)
$stmt = $pdo->query("
    SELECT p.*, u.nome AS cliente
    FROM pedidos p
    LEFT JOIN usuarios u ON p.id_usuario = u.id
    ORDER BY p.data_pedido DESC
    LIMIT 20
");
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para cada pedido, buscar itens (usamos historico_itens_pedido quando disponível; fallback em itens_carrinho)
function buscarItens(PDO $pdo, $id_pedido) {
    $itens = [];
    // tenta historico_itens_pedido
    $s = $pdo->prepare("
        SELECT hip.quantidade, hip.preco_unitario, pr.nome
        FROM historico_itens_pedido hip
        LEFT JOIN produtos pr ON hip.id_produto = pr.id
        WHERE hip.id_pedido = ?
    ");
    $s->execute([$id_pedido]);
    $itens = $s->fetchAll(PDO::FETCH_ASSOC);
    if (!$itens) {
        // fallback: tentar localizar pelo carrinho vinculado ao pedido
        $s2 = $pdo->prepare("
            SELECT ic.quantidade, ic.preco_unitario, pr.nome
            FROM pedidos p
            JOIN itens_carrinho ic ON ic.id_carrinho = p.id_carrinho
            LEFT JOIN produtos pr ON pr.id = ic.id_produto
            WHERE p.id = ?
        ");
        $s2->execute([$id_pedido]);
        $itens = $s2->fetchAll(PDO::FETCH_ASSOC);
    }
    return $itens ?: [];
}

// ====== NOVO: consultar DB para popular os gráficos ======
$weekDays = ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'];
$labels = $counts = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = $weekDays[(int)date('w', strtotime($date))];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE DATE(data_pedido) = ?");
    $stmt->execute([$date]);
    $counts[] = (int) $stmt->fetchColumn();
}

// Agrupar por categoria (soma de quantidades)
// Primeiro dados do historico_itens_pedido
$totalsByCat = [];
$stmt = $pdo->query("
    SELECT pr.categoria, SUM(hip.quantidade) AS total
    FROM historico_itens_pedido hip
    JOIN produtos pr ON hip.id_produto = pr.id
    GROUP BY pr.categoria
");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $totalsByCat[$r['categoria']] = ($totalsByCat[$r['categoria']] ?? 0) + (int)$r['total'];
}

// Complemento: itens_carrinho vinculados a pedidos (caso não haja historico)
$stmt2 = $pdo->query("
    SELECT pr.categoria, SUM(ic.quantidade) AS total
    FROM itens_carrinho ic
    JOIN produtos pr ON ic.id_produto = pr.id
    JOIN carrinhos c ON ic.id_carrinho = c.id
    JOIN pedidos p ON p.id_carrinho = c.id
    GROUP BY pr.categoria
");
foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $totalsByCat[$r['categoria']] = ($totalsByCat[$r['categoria']] ?? 0) + (int)$r['total'];
}

// Garantir categorias padrão se vazio (evita erro no Chart.js)
if (empty($totalsByCat)) {
    $totalsByCat = ['tradicionais' => 0, 'especiais' => 0, 'bebidas' => 0];
}

$catLabels = $catData = $catColors = [];
$colorMap = [
    'tradicionais' => '#19344f',
    'especiais'    => '#b78b46',
    'vegetarianas' => '#7fbf7f',
    'doces'        => '#ff9a56',
    'combos'       => '#8c6b3f',
    'bebidas'      => '#f5e3b3'
];
foreach ($totalsByCat as $cat => $val) {
    $catLabels[] = ucfirst($cat);
    $catData[] = (int)$val;
    $catColors[] = $colorMap[$cat] ?? '#cccccc';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Dashboard — Pedidos</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="../css/adm.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Função para abrir modal com dados do pedido (items json embutido no data-items)
    function abrirModal(btn){
        const modal = document.getElementById('modal-itens');
        const nome = btn.getAttribute('data-nome') || 'Pedido';
        const numero = btn.getAttribute('data-numero') || '';
        const itensJson = btn.getAttribute('data-items') || '[]';
        const itens = JSON.parse(atob(itensJson)); // decode base64 para evitar problemas com quotes

        // --- novo: ler observação (base64) ---
        const obsB64 = btn.getAttribute('data-observacao') || '';
        function b64DecodeUnicode(str) {
            try {
                // decodifica utf-8 corretamente
                return decodeURIComponent(Array.prototype.map.call(atob(str), function(c){
                    return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                }).join(''));
            } catch(e){
                return atob(str || '') || '';
            }
        }
        const observacao = obsB64 ? b64DecodeUnicode(obsB64) : '';
        // --- fim novo ---

        document.getElementById('modal-title').textContent = nome + ' ' + numero;
        const body = document.getElementById('modal-body-itens');
        const obsContainer = document.getElementById('modal-observacao');
        if(obsContainer){
            obsContainer.innerHTML = observacao ? '<strong>Observação:</strong> <div style="margin-top:6px; white-space:pre-wrap;">' + observacao + '</div>' : '';
        }
        body.innerHTML = '';
        if(itens.length === 0){
            body.innerHTML = '<p>Nenhum item registrado.</p>';
        } else {
            const table = document.createElement('table');
            table.style.width='100%';
            table.style.borderCollapse='collapse';
            table.innerHTML = '<thead><tr><th>Produto</th><th style="text-align:center">Qtd</th><th style="text-align:right">Unit.</th><th style="text-align:right">Subtotal</th></tr></thead>';
            const tbody = document.createElement('tbody');
            itens.forEach(it=>{
                const tr = document.createElement('tr');
                tr.innerHTML = '<td>'+ (it.nome ?? '—') +'</td><td style="text-align:center">'+it.quantidade+'</td><td style="text-align:right">R$ '+parseFloat(it.preco_unitario).toFixed(2)+'</td><td style="text-align:right">R$ '+(parseFloat(it.preco_unitario)*it.quantidade).toFixed(2)+'</td>';
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            body.appendChild(table);
        }
        modal.style.display = 'flex';
    }
    function fecharModal(){
        document.getElementById('modal-itens').style.display = 'none';
    }
    window.addEventListener('click', function(e){
        const modal = document.getElementById('modal-itens');
        if(e.target === modal) fecharModal();
    });
    </script>
    <style>
    /* Removidas as definições locais de .cards/.card para herdar o CSS de adm.css (mesmo visual do admin.php) */
    /* Pequena adaptação local específica para esta página (mantida apenas o que não conflita) */
    .table-wrap{ background:#f6ead0; padding:14px; border-radius:10px; }
    .pedidos-table{ width:100%; border-collapse:collapse; }
    .pedidos-table th, .pedidos-table td{ padding:10px; text-align:left; border-bottom:1px solid rgba(25,52,79,0.06); }
    .acoes .btn{ margin-right:6px; }
    .btn{ padding:6px 10px; border-radius:8px; color:white; text-decoration:none; font-weight:600; border:none; cursor:pointer; }
    .btn-confirm{ background:#2a68a0; }
    .btn-alter{ background:#b78b46; color:#fff; }
    .btn-cancel{ background:#d9534f; }
    .status-badge{ padding:6px 10px; border-radius:20px; background:#19344f; color:#f5e3b3; font-weight:700; }
    </style>
</head>
<body>
    <div class="content-wrapper">
        <h1>Pedidos</h1>

        <div class="overview-container">
            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?= $totalPedidos ?></h3>
                    <p>Pedidos</p>
                </div>
                <div class="stat-card">
                    <h3><?= $emAndamento ?></h3>
                    <p>Em andamento</p>
                </div>
                <div class="stat-card">
                    <h3><?= $entregues ?></h3>
                    <p>Entregues</p>
                </div>
                <div class="stat-card">
                    <h3>R$ <?= number_format($receitaHoje,2,',','.') ?></h3>
                    <p>Receita do dia</p>
                </div>
            </div>

            <!-- gráficos (placeholders / integrado com adm.css) -->
            <div class="charts-container" style="margin-top:14px;">
                <div class="chart-box">
                    <div class="chart-title">Pedidos (últimos dias)</div>
                    <canvas id="barChart" style="width:100%;height:100%"></canvas>
                </div>
                <div class="chart-box">
                    <div class="chart-title">Categorias</div>
                    <canvas id="pieChart" style="width:100%;height:100%"></canvas>
                </div>
            </div>
        </div>

        <div class="overview-header" style="margin-top:10px;">
            <h2 style="background:#133349;padding:10px;border-radius:8px;color:#fff;">Gerenciar Pedido</h2>
        </div>

        <div class="table-wrap" style="margin-top:12px;">
            <h3 style="margin-bottom:12px;color:#133349;">Em andamento</h3>
            <table class="pedidos-table">
                <thead>
                    <tr>
                        <th>Nº pedido</th>
                        <th>Cliente</th>
                        <th>Itens</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pedidos as $p):
                        $itens = buscarItens($pdo, $p['id']);
                        // encode items in base64 to avoid HTML quoting issues
                        $items_b64 = base64_encode(json_encode($itens));
                    ?>
                    <tr>
                        <td>#<?= htmlspecialchars($p['id']) ?></td>
                        <td><?= htmlspecialchars($p['cliente'] ?: 'Cliente não informado') ?></td>
                        <td><button class="btn btn-view" data-items="<?= $items_b64 ?>" data-observacao="<?= base64_encode($p['observacoes'] ?? '') ?>" data-nome="Pedido" data-numero="#<?= $p['id'] ?>" onclick="abrirModal(this)">Ver itens</button></td>
                        <td>R$ <?= number_format($p['valor_total'],2,',','.') ?></td>
                        <td><span class="status-badge"><?= htmlspecialchars($p['status_pedido'] ?: 'pendente') ?></span></td>
                        <td class="acoes">
                            <form style="display:none" method="post">
                                <input type="hidden" name="id_pedido" value="<?= $p['id'] ?>">
                                <input type="hidden" name="acao" value="confirmar">
                                <button class="btn btn-confirm" type="submit">Confirmar</button>
                            </form>

                            <form style="display:inline" method="post">
                                <input type="hidden" name="id_pedido" value="<?= $p['id'] ?>">
                                <input type="hidden" name="acao" value="alterar_status">
                                <select name="novo_status" onchange="this.form.submit()" style="border-radius:6px;padding:6px;">
                                    <option value="">Alterar status</option>
                                    <option value="pendente">Pendente</option>
                                    <option value="em preparo">Em preparo</option>
                                    <option value="saiu para entrega">Saiu para entrega</option>
                                    <option value="entregue">Entregue</option>
                                    <option value="cancelado">Cancelado</option>
                                </select>
                            </form>

                            <form style="display:none" method="post" onsubmit="return confirm('Confirmar cancelamento do pedido #<?= $p['id'] ?>?')">
                                <input type="hidden" name="id_pedido" value="<?= $p['id'] ?>">
                                <input type="hidden" name="acao" value="cancelar">
                                <button class="btn btn-cancel" type="submit">Cancelar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- Modal de itens -->
    <div id="modal-itens" class="modal" style="display:none;">
        <div class="modal-content modal-pedidos admin-form">
            <span class="close" onclick="fecharModal()">×</span>
            <div class="admin-form-inner">
                <h1 id="modal-title">Itens do pedido</h1>

                <!-- novo: observação -->
                <div id="modal-observacao" style="margin-top:8px;color:#133349;font-style:italic;"></div>

                <div id="modal-body-itens" style="margin-top:10px;"></div>
                <div class="button-row" style="margin-top:18px;">
                    <button class="cancel-btn" onclick="fecharModal()">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- dados do PHP para o JS -->
    <script>
        const barLabels = <?= json_encode($labels, JSON_UNESCAPED_UNICODE) ?>;
        const barData = <?= json_encode($counts) ?>;
        const pieLabels = <?= json_encode($catLabels, JSON_UNESCAPED_UNICODE) ?>;
        const pieData = <?= json_encode($catData) ?>;
        const pieColors = <?= json_encode($catColors) ?>;
    </script>

    <!-- Chart.js via CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Gráfico de barras a partir do BD
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: barLabels,
            datasets: [{
                label: 'Pedidos',
                backgroundColor: '#b78b46',
                borderRadius: 6,
                data: barData
            }]
        },
        options: { plugins:{legend:{display:false}} , scales:{y:{beginAtZero:true}}}
    });

    // Pizza (categorias) a partir do BD
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
        type:'pie',
        data:{
            labels: pieLabels,
            datasets:[{
                data: pieData,
                backgroundColor: pieColors
            }]
        },
        options:{ plugins:{legend:{position:'bottom'}}}
    });
    </script>
</body>
</html>