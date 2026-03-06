<?php
session_start();
include('conexao.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $produtoId = $_POST['produto_id'];
    $ingredientes = $_POST['ingredientes'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        // Remove todos os ingredientes atuais do produto
        $stmt = $pdo->prepare("DELETE FROM produto_ingredientes WHERE id_produto = ?");
        $stmt->execute([$produtoId]);
        
        // Adiciona os novos ingredientes selecionados
        foreach ($ingredientes as $ingredienteId) {
            $quantidade = $_POST['quantidade_' . $ingredienteId] ?? 1;
            $stmt = $pdo->prepare("INSERT INTO produto_ingredientes (id_produto, id_ingrediente, quantidade) VALUES (?, ?, ?)");
            $stmt->execute([$produtoId, $ingredienteId, $quantidade]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>