<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/conexao.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$userId = (int) $_SESSION['id'];

try {
    // Upload de foto (multipart/form-data)
    if (!empty($_FILES['foto']) && is_uploaded_file($_FILES['foto']['tmp_name'])) {
        $file = $_FILES['foto'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload: ' . $file['error']);
        }

        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            throw new Exception('Arquivo muito grande. Máx 2MB.');
        }

        $allowed = ['jpg','jpeg','png','webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            throw new Exception('Formato inválido. (jpg, png, webp)');
        }

        $dir = __DIR__ . '/../img/foto-perfil';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
        $dest = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new Exception('Falha ao mover arquivo.');
        }

        // Atualizar DB
        $stmt = $pdo->prepare("UPDATE usuarios SET imagem_perfil = :img WHERE id = :id");
        $stmt->execute([':img' => $filename, ':id' => $userId]);

        // Atualizar session (se usa)
        $_SESSION['imagem_perfil'] = $filename;

        echo json_encode(['success' => true, 'message' => 'Foto atualizada', 'imagem' => $filename]);
        exit;
    }

    // Atualização de campos via JSON
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;

    if (!empty($data['campo']) && isset($data['valor'])) {
        $campo = $data['campo'];
        $valor = trim($data['valor']);

        // campos permitidos
        $permitidos = ['nome','telefone','email'];
        if (!in_array($campo, $permitidos)) {
            echo json_encode(['success' => false, 'message' => 'Campo não permitido']);
            exit;
        }

        // validações simples
        if ($campo === 'email' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email inválido']);
            exit;
        }

        if ($campo === 'email') {
            // checar duplicidade
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id <> ? LIMIT 1");
            $check->execute([$valor, $userId]);
            if ($check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email já está em uso']);
                exit;
            }
        }

        $stmt = $pdo->prepare("UPDATE usuarios SET {$campo} = :valor WHERE id = :id");
        $stmt->execute([':valor' => $valor, ':id' => $userId]);

        // atualizar session se necessário
        if ($campo === 'nome') $_SESSION['nome'] = $valor;
        if ($campo === 'email') $_SESSION['email'] = $valor;
        if ($campo === 'telefone') $_SESSION['telefone'] = $valor;

        echo json_encode(['success' => true, 'message' => 'Atualizado com sucesso', 'campo' => $campo, 'valor' => $valor]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Nenhum dado recebido']);
} catch (Exception $e) {
    error_log('atualizar_perfil: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>