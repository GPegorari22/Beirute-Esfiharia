<?php
require_once 'conexao.php';

header('Content-Type: text/html; charset=utf-8');
echo '<h2>Last product debug</h2>';

try {
    $stmt = $pdo->query("SELECT * FROM produtos ORDER BY id DESC LIMIT 1");
    $last = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo '<pre style="color:red;">DB error: ' . htmlspecialchars($e->getMessage()) . '</pre>';
    exit;
}

if (!$last) {
    echo '<p>No products found in DB.</p>';
    exit;
}

echo '<pre>' . htmlspecialchars(print_r($last, true)) . '</pre>';

$img = $last['imagem'] ?? '';
$paths = [];
$paths['img_produtos'] = realpath(__DIR__ . '/../img/produtos/' . $img);
$paths['img_root'] = realpath(__DIR__ . '/../img/' . $img);

echo '<h3>Checking files</h3>';
foreach ($paths as $k => $p) {
    if ($p && file_exists($p)) {
        echo '<div style="color:green;">Found at ' . htmlspecialchars($k) . ': ' . htmlspecialchars($p) . '</div>';
    } else {
        echo '<div style="color:orange;">Not found at ' . htmlspecialchars($k) . '</div>';
    }
}

echo '<h3>Quick test URLs</h3>';
echo '<ul>';
echo '<li><code>/Beirute-delivery/img/produtos/' . htmlspecialchars($img) . '</code></li>';
echo '<li><code>/Beirute-delivery/img/' . htmlspecialchars($img) . '</code></li>';
echo '</ul>';

echo '<p>Abra esta URL no navegador para confirmar: <a href="../backend/upload_probe.php" target="_blank">upload_probe</a></p>';

?>
