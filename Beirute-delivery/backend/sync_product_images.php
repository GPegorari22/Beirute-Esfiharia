<?php
require_once 'conexao.php';

// Script útil para sincronizar os nomes de imagem na tabela produtos
// com arquivos existentes em img/produtos/. Uso manual.
// Acesse: backend/sync_product_images.php (dry-run)
// Para aplicar as mudanças use: backend/sync_product_images.php?apply=1

function normalize($s){
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    return preg_replace('/[^a-z0-9]+/', '', $s);
}

echo "<h2>Sync product images — dry run</h2>";
$apply = isset($_GET['apply']) && $_GET['apply'] == '1';
if ($apply) echo "<div style='color:green;'>APPLY MODE: changes will be written to DB</div>";

$imgDir = realpath(__DIR__ . '/../img/produtos');
if (!is_dir($imgDir)) { echo "<div style='color:red;'>img/produtos not found</div>"; exit; }

$files = scandir($imgDir);
$fileNames = array_filter($files, function($f){ return !in_array($f, ['.', '..']); });

$stmt = $pdo->query("SELECT id, nome, imagem FROM produtos ORDER BY id");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updated = 0; $checked = 0;
echo '<table border="1" cellpadding="6" style="border-collapse:collapse;">';
echo '<tr><th>ID</th><th>Nome</th><th>DB imagem</th><th>Found file</th><th>Action</th></tr>';
foreach($rows as $r){
    $checked++;
    $id = $r['id'];
    $nome = $r['nome'];
    $dbImg = $r['imagem'] ?: '';
    $dbPath = $dbImg ? ($imgDir . DIRECTORY_SEPARATOR . $dbImg) : '';
    $exists = $dbImg && file_exists($dbPath);

    $found = '';
    if (!$exists) {
        $normName = normalize($nome);
        foreach($fileNames as $f){
            $fn = pathinfo($f, PATHINFO_FILENAME);
            if (strpos(normalize($fn), $normName) !== false) {
                $found = $f; break;
            }
        }
    }

    echo '<tr>';
    echo '<td>'.htmlspecialchars($id).'</td>';
    echo '<td>'.htmlspecialchars($nome).'</td>';
    echo '<td>'.htmlspecialchars($dbImg).' ' . ($exists ? '<span style="color:green">(exists)</span>' : '<span style="color:orange">(missing)</span>') . '</td>';
    echo '<td>'.htmlspecialchars($found ?: '-') .'</td>';
    if ($found && $apply){
        // update DB
        $up = $pdo->prepare("UPDATE produtos SET imagem = ? WHERE id = ?");
        $ok = $up->execute([$found, $id]);
        if ($ok) { echo '<td style="color:green;">Updated to '.htmlspecialchars($found).'</td>'; $updated++; }
        else echo '<td style="color:red;">DB update failed</td>';
    } else if ($found) {
        echo '<td style="color:blue;">Would update to '.htmlspecialchars($found).'</td>';
    } else {
        echo '<td>-</td>';
    }
    echo '</tr>';
}
echo '</table>';
echo "<p>Checked: $checked products. Updated: $updated</p>";

echo "<p>To apply updates run this page with ?apply=1</p>";

?>
