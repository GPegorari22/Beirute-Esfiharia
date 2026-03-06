<?php
session_start();
include __DIR__ . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // aceitar diferentes nomes de campos (formulário antigo/novo)
    $nome = trim($_POST['nome'] ?? $_POST['Nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmaSenha'] ?? $_POST['confirmar_senha'] ?? $_POST['confirmarSenha'] ?? '';

    // detectar se é requisição AJAX (fetch)
    $isAjax = false;
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        $isAjax = true;
    } elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        $isAjax = true;
    }

    if (!$nome || !$email || !$telefone || !$senha || !$confirmar) {
        $msg = 'Preencha todos os campos.';
        if ($isAjax) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success'=>false,'message'=>$msg]); exit; }
        $_SESSION['msg'] = $msg; $_SESSION['msg_type'] = 'error'; header('Location: ../usuario/cadastro-interface.php'); exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = 'E-mail inválido.';
        if ($isAjax) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success'=>false,'message'=>$msg]); exit; }
        $_SESSION['msg'] = $msg; $_SESSION['msg_type'] = 'error'; header('Location: ../usuario/cadastro-interface.php'); exit;
    }

    if ($senha !== $confirmar) {
        $msg = 'As senhas não coincidem.';
        if ($isAjax) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success'=>false,'message'=>$msg]); exit; }
        $_SESSION['msg'] = $msg; $_SESSION['msg_type'] = 'error'; header('Location: ../usuario/cadastro-interface.php'); exit;
    }

    if (strlen($senha) < 6) {
        $msg = 'A senha deve ter no mínimo 6 caracteres.';
        if ($isAjax) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success'=>false,'message'=>$msg]); exit; }
        $_SESSION['msg'] = $msg; $_SESSION['msg_type'] = 'error'; header('Location: ../usuario/cadastro-interface.php'); exit;
    }

    try {
        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (nome, email, telefone, senha, perfil, ativo, data_cadastro)
                VALUES (:nome, :email, :telefone, :senha, :perfil, 1, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nome' => $nome,
            ':email' => $email,
            ':telefone' => $telefone,
            ':senha' => $hash,
            ':perfil' => 'comum' // ou 'comum' conforme desejar
        ]);

        $successMsg = 'Cadastro realizado com sucesso. Faça login.';
        if ($isAjax) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success'=>true,'message'=>$successMsg]); exit; }
        $_SESSION['msg'] = $successMsg; $_SESSION['msg_type'] = 'success';
    } catch (PDOException $e) {
        // log para debug (remova em produção)
        file_put_contents(__DIR__ . '/debug_cadastro.log', date('c') . ' ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        $errMsg = 'Erro ao cadastrar. Tente novamente.';
        if ($isAjax) { header('Content-Type: application/json; charset=utf-8'); echo json_encode(['success'=>false,'message'=>$errMsg]); exit; }
        $_SESSION['msg'] = $errMsg; $_SESSION['msg_type'] = 'error';
    }

    header('Location: ../usuario/cadastro-interface.php');
    exit;
}

// se não for POST
header('Location: ../usuario/cadastro-interface.php');
exit;
?>