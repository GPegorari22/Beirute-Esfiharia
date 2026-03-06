<?php
session_start();
include '../backend/conexao.php';
include '../includes/sidebar.php';

if(!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin'){
    header("Location: index.html");
    exit();
}

$consulta = $pdo->query("SELECT * FROM ingredientes ORDER BY nome");
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Ingredientes — Beirute</title>
    <link rel="stylesheet" href="../css/adm.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <form action="../backend/cadastrar_ingrediente.php" method="POST" class="admin-form">
        <h1>Cadastrar Ingredientes</h1>
        <section>
            <label for="nome">Nome do Ingrediente</label>
            <input class="input" type="text" id="nome" name="nome" required>
            
            <label for="estoque">Quantidade em Estoque</label>
            <input class="input" type="number" id="estoque" name="estoque" required min="0">
            
            <div class="checkbox-row">
                <label>
                    <input type="checkbox" name="ativo" value="1" checked>
                    Ativo
                </label>
            </div>
        </section>
        <input type="submit" value="Cadastrar">
    </form>

    <div class="produtos-table">
        <h3>Ingredientes Cadastrados</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Estoque</th>
                <th>Status</th>
                <th>Data Cadastro</th>
                <th>Ações</th>
            </tr>
            <?php while($row = $consulta->fetch(PDO::FETCH_ASSOC)){ ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                    <td><?php echo htmlspecialchars($row['estoque']); ?></td>
                    <td><?php echo $row['ativo'] ? 'Ativo' : 'Inativo'; ?></td>
                    <td><?php echo date('d/m/Y H:i', strtotime($row['data_cadastro'])); ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn-action btn-edit icon-left" href="editar_ingrediente.php?id=<?php echo htmlspecialchars($row['id']); ?>">Editar</a>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </table>
    </div>
</body>
</html>