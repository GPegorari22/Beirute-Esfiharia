<?php
// Script de utilitário (execução manual) para mover imagens de img/ para img/produtos/
// Uso: abra no navegador enquanto o XAMPP está rodando (ex: http://localhost/Beirute-delivery/backend/fix_product_images.php)
// Ele varre os nomes de imagem na tabela produtos e move arquivos da pasta img/ para img/produtos quando necessário.

require_once 'conexao.php';

// checa permissões
function fmt($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

echo "<h2>Fix product images — mover imagens para img/produtos/</h2>";

$baseImg = realpath(__DIR__ . '/../img');
$prodDir = realpath(__DIR__ . '/../img/produtos') ?: (__DIR__ . '/../img/produtos');
if (!is_dir($prodDir)) mkdir($prodDir, 0755, true);

try {
    $sql = $pdo->query("SELECT id, imagem FROM produtos WHERE imagem IS NOT NULL AND imagem != ''");
    $rows = $sql->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "<div style='color:red;'>Erro ao consultar DB: " . fmt($e->getMessage()) . "</div>";
    exit;
}

echo "<p>Total produtos com imagem: " . count($rows) . "</p>";

$moved = 0; $copied = 0; $skipped = 0; $notfound = 0;
echo "<ol>";
foreach ($rows as $r) {
    $img = $r['imagem'];
    if (!$img) { $skipped++; continue; }
    $src = $baseImg . DIRECTORY_SEPARATOR . $img;
    $dst = rtrim($prodDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $img;
    if (file_exists($dst)) { echo "<li>produto {$r['id']}: já existe em produtos/ — " . fmt($img) . "</li>"; continue; }
    if (file_exists($src)) {
        // tenta mover
        if (@rename($src, $dst)) { echo "<li>produto {$r['id']}: movido " . fmt($img) . "</li>"; $moved++; }
        else if (@copy($src, $dst)) { echo "<li>produto {$r['id']}: copiado (rename falhou) " . fmt($img) . "</li>"; $copied++; }
        else { echo "<li style='color:orange;'>produto {$r['id']}: falha ao mover/copiar " . fmt($img) . "</li>"; }
    } else {
        echo "<li style='color:gray;'>produto {$r['id']}: não encontrado em img/ — " . fmt($img) . "</li>"; $notfound++;
    }
}
echo "</ol>";

echo "<p>Resumo: moved=$moved, copied=$copied, skipped=$skipped, notfound=$notfound</p>";

echo "<p>Nota: este script é seguro para rodar uma vez — ele só move arquivos que existem em <code>img/</code> para <code>img/produtos/</code> quando o destino não existe.</p>";

?>
