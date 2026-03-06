<?php
require_once '../backend/conexao.php';

// Atualizar a consulta de total de ativos
$sql_total_ativos = "SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1 AND perfil = 'comum'";
$result_ativos = $pdo->query($sql_total_ativos);
$total_ativos = $result_ativos->fetch(PDO::FETCH_ASSOC)['total'];

// Atualizar a consulta de top clientes
$sql_top_clientes = "SELECT u.nome, COUNT(p.id) as total_pedidos 
                     FROM usuarios u 
                     LEFT JOIN pedidos p ON u.id = p.id_usuario 
                     WHERE u.perfil = 'comum'
                     GROUP BY u.id 
                     ORDER BY total_pedidos DESC 
                     LIMIT 3";
$result_top = $pdo->query($sql_top_clientes);

// Atualizar a consulta de todos os clientes
$sql_clientes = "SELECT 
                    u.id,
                    u.nome,
                    u.email,
                    COUNT(p.id) as total_pedidos,
                    MAX(p.data_pedido) as ultimo_pedido
                 FROM usuarios u
                 LEFT JOIN pedidos p ON u.id = p.id_usuario
                 WHERE u.perfil = 'comum'
                 GROUP BY u.id
                 ORDER BY u.id DESC";
$result_clientes = $pdo->query($sql_clientes);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Clientes - Beirute</title>
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="../css/usuarios.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <div class="container">
            <h1>Clientes</h1>
            
            <div class="card-total">
                <h2>Clientes ativos</h2>
                <div class="numero-grande"><?php echo $total_ativos; ?></div>
            </div>

            <div class="titulo-secao">Clientes com mais pedidos</div>
            <div class="top-clientes">
                <?php while($top = $result_top->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="cliente-card">
                    <div class="cliente-foto"></div>
                    <h3><?php echo $top['nome']; ?></h3>
                    <p><?php echo $top['total_pedidos']; ?> pedidos</p>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="titulo-secao">Clientes Cadastrados</div>
            <table class="tabela-dados">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Pedido</th>
                        <th>Total</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($cliente = $result_clientes->fetch(PDO::FETCH_ASSOC)): ?>
                    <tr>
                        <td>#<?php echo str_pad($cliente['id'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo $cliente['nome']; ?></td>
                        <td><span class="badge">Último Pedido</span></td>
                        <td><?php echo $cliente['total_pedidos']; ?> pedidos</td>
                        <td><?php echo $cliente['email']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>