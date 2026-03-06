<?php
require_once 'conexao.php';
header('Content-Type: text/plain; charset=utf-8');
echo "Top 3 - Mais pedidos\n";

try {
    $sql = "SELECT id, nome, imagem, quantidade_vendida FROM produtos WHERE ativo = 1 ORDER BY quantidade_vendida DESC LIMIT 3";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "DB error: " . $e->getMessage() . "\n";
    exit(1);
}

if (!$rows) {
    echo "No results\n";
    exit(0);
}

foreach ($rows as $r) {
    $img = $r['imagem'] ?? '';
    echo "ID: {$r['id']} | Nome: {$r['nome']}\n";
    echo "  imagem field: " . ($img === '' ? '(empty)' : $img) . "\n";
    $p1 = realpath(__DIR__ . '/../img/produtos/' . $img);
    $p2 = realpath(__DIR__ . '/../img/' . $img);
    echo "  produtos path: " . ($p1 ? $p1 : '(not found)') . "\n";
    echo "  img path: " . ($p2 ? $p2 : '(not found)') . "\n";
    echo "\n";
}

?>
