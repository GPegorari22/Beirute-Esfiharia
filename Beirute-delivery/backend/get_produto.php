<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
include 'conexao.php';

if(!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin'){
    echo json_encode(['error' => 'Acesso negado']);
    exit();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if($id <= 0){
    echo json_encode(['error' => 'ID inválido']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, nome, preco, categoria, descricao, imagem FROM produtos WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$produto){
        echo json_encode(['error' => 'Produto não encontrado']);
        exit();
    }

    // buscar ingredientes ligados ao produto (se tabela produto_ingredientes existir)
    $ingredientesLinked = [];
    $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'produto_ingredientes'");
    $check->execute();
    if ($check->fetchColumn()) {
        $q = $pdo->prepare("SELECT id_ingrediente AS id, quantidade_por_produto FROM produto_ingredientes WHERE id_produto = ?");
        $q->execute([$id]);
        $ingredientesLinked = $q->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'produto' => $produto,
        'ingredientes' => $ingredientesLinked
    ], JSON_UNESCAPED_UNICODE);

} catch(PDOException $e) {
    echo json_encode(['error' => 'Erro no banco']);
    exit();
}