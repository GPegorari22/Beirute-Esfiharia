<?php
session_start();
include 'conexao.php';

if(!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin'){
    header("Location: ../index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $estoque = filter_input(INPUT_POST, 'estoque', FILTER_VALIDATE_INT);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("INSERT INTO ingredientes (nome, estoque, ativo) VALUES (?, ?, ?)");
        if($stmt->execute([$nome, $estoque, $ativo])) {
            header("Location: ../admin/cadastro_ingrediente.php?sucesso=1");
        } else {
            header("Location: ../admin/cadastro_ingrediente.php?erro=1");
        }
    } catch(PDOException $e) {
        header("Location: ../admin/cadastro_ingrediente.php?erro=2");
    }
    exit();
}