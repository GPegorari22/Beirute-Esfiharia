<?php
session_start();
include 'conexao.php';

// segurança: só admin
if(!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin'){
    header("Location: ../index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $email = $_POST['email'] ?? '';
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $perfil = $_POST['perfil'] ?? 'funcionario';
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    // Inserir no banco de dados
    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, perfil, ativo) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$nome, $email, $senha, $perfil, $ativo])) {
        header("Location: ../admin/funcionarios.php?sucesso=1");
    } else {
        header("Location: ../admin/funcionarios.php?erro=Erro ao criar funcionário.");
    }
    exit();
}
?>