<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexao.php';

try {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Digite pelo menos 2 caracteres']);
        exit;
    }

    $sql = "SELECT id, nome, descricao, preco, categoria, imagem FROM produtos WHERE ativo = 1 AND nome LIKE :busca ORDER BY nome ASC LIMIT 30";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':busca' => "%$q%"]);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$produtos) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Nenhum produto encontrado']);
        exit;
    }

    echo json_encode(['sucesso' => true, 'produtos' => $produtos]);
} catch (PDOException $e) {
    // para dev: log e resposta genérica
    error_log($e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no servidor']);
}
?>