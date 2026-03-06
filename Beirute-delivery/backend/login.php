<?php
session_start();
include('conexao.php');

// Verifica se email e senha vieram pelo POST
if (!isset($_POST['email']) || !isset($_POST['senha'])) {
    echo "Requisição inválida.";
    exit();
}

$email = trim($_POST['email']);
$senha = $_POST['senha'];

// Busca usuário
$sql = "SELECT * FROM usuarios WHERE email = ? LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);

if ($stmt->rowCount() === 0) {
    echo "Usuário não encontrado!";
    exit();
}

$dados = $stmt->fetch(PDO::FETCH_ASSOC);

// Valida senha
if (!password_verify($senha, $dados['senha'])) {
    echo "Senha incorreta!";
    exit();
}

// Salva sessão
$_SESSION['id'] = $dados['id'];
$_SESSION['nome'] = $dados['nome'];
$_SESSION['perfil'] = $dados['perfil'];

// ======== REDIRECIONAMENTO SEGURO ========

$defaultRedirect = '../usuario/inicio.php';
$return = $defaultRedirect;

if (!empty($_POST['return'])) {
    $candidate = $_POST['return'];
    $parsed = parse_url($candidate);

    $allow = false;

    // permitir URLs relativas
    if (empty($parsed['host'])) {
        $allow = true;
        $return = $candidate;
    } 
    // permitir URLs com mesmo host
    else if (isset($_SERVER['HTTP_HOST']) && $parsed['host'] === $_SERVER['HTTP_HOST']) {
        $allow = true;
        $return = $candidate;
    }
}

header("Location: $return");
exit();
?>