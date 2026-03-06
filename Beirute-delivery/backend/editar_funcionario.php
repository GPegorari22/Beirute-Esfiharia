<?php
session_start();
include 'conexao.php';

// Verificação de segurança
if (!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $perfil = filter_input(INPUT_POST, 'perfil', FILTER_SANITIZE_STRING);
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Validações básicas
    if (!$id || !$nome || !$email || !in_array($perfil, ['admin', 'funcionario'])) {
        echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
        exit();
    }

    try {
        $sql = "UPDATE usuarios SET 
                nome = :nome, 
                email = :email, 
                perfil = :perfil, 
                ativo = :ativo";
        
        $params = [
            ':id' => $id,
            ':nome' => $nome,
            ':email' => $email,
            ':perfil' => $perfil,
            ':ativo' => $ativo
        ];

        // Se uma nova senha foi fornecida
        if (!empty($_POST['senha'])) {
            $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $sql .= ", senha = :senha";
            $params[':senha'] = $senha;
        }

        $sql .= " WHERE id = :id AND perfil IN ('admin', 'funcionario')";
        
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($params)) {
            header("Location: ../admin/funcionarios.php?");
            exit(); // Importante adicionar exit após o redirect
        } else {
            header("Location: ../admin/funcionarios.php?erro=1");
            exit();
        }

    } catch (PDOException $e) {
        error_log("Erro ao atualizar funcionário: " . $e->getMessage());
        header("Location: ../admin/funcionarios.php?erro=2");
        exit();
    }
    
    exit();
}

// Se não for POST, retorna erro
echo json_encode([
    'success' => false,
    'message' => 'Método não permitido'
]);