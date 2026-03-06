<?php
session_start();
$msg = $_SESSION['msg'] ?? '';
$msg_type = $_SESSION['msg_type'] ?? '';
unset($_SESSION['msg'], $_SESSION['msg_type']);
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro</title>
    <link rel="stylesheet" href="../css/cadastro.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">
</head>

<body>
    <?php if ($msg): ?>
        <div class="flash-message <?php echo ($msg_type === 'success') ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <form action="../backend/cadastro.php" method="POST" class="login-form">
        <h2>Cadastro</h2>
        <section class="login-info">
            <label for="Nome">Nome</label>
            <input type="text" id="Nome" name="Nome" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>

            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone" required>

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>

            <label for="confirmar_senha">Confirmar Senha</label>
            <input type="password" id="confirmar_senha" name="confirmar_senha" required>
        </section>
        <input type="submit" value="Cadastrar">
    </form>


</body>

</html>