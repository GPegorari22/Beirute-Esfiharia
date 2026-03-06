<?php
    session_start();
    include 'backend/conexao.php';
    if(!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin'){
        header("Location: index.html");
        exit();
    }
    $consulta = $pdo->query("SELECT * FROM produtos");
?>