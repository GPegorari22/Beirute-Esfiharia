<?php
session_start();
require_once 'conexao.php';
if (!isset($pdo) && isset($conexao)) $pdo = $conexao;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email && $senha) {
        // selecionar colunas existentes no seu esquema
        $stmt = $pdo->prepare("SELECT id, nome, email, senha, perfil FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $stored = $usuario['senha'] ?? '';

            // aceita senha em texto plano ou hash (password_verify)
            $validPass = false;
            if ($stored !== '') {
                if (password_verify($senha, $stored)) {
                    $validPass = true;
                } else {
                    // fallback para comparar texto plano (se seu banco ainda armazena senhas sem hash)
                    // usa hash_equals para reduzir risco de timing attack
                    if (hash_equals($stored, $senha)) $validPass = true;
                }
            }

            if ($validPass && ($usuario['perfil'] ?? '') === 'admin') {
                $_SESSION['id'] = $usuario['id'];
                $_SESSION['nome'] = $usuario['nome'];
                $_SESSION['perfil'] = $usuario['perfil'];

                header('Location: ../admin/admin.php');
                exit;
            }
        }

        $_SESSION['login_error'] = 'Email ou senha inválidos, ou você não tem acesso de administrador.';
        header('Location: ../html/login-admin.html');
        exit;
    } else {
        $_SESSION['login_error'] = 'Por favor, preencha todos os campos.';
        header('Location: ../html/login-admin.html');
        exit;
    }
} else {
    header('Location: ../html/login-admin.html');
    exit;
}
?>