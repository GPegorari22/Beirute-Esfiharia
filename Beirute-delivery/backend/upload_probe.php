<?php
// Página de diagnóstico para testar upload e verificar por que moves falham.
// Use no navegador: http://localhost/Beirute-delivery/backend/upload_probe.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: text/plain; charset=utf-8');
    $out = [];
    $out[] = "PHP upload probe — " . date('Y-m-d H:i:s');
    $out[] = "sys_get_temp_dir=" . sys_get_temp_dir();
    $out[] = "upload_max_filesize=" . ini_get('upload_max_filesize');
    $out[] = "post_max_size=" . ini_get('post_max_size');

    if (!isset($_FILES['imagem'])) {
        $out[] = "NO FILES['imagem'] RECEIVED.\n";
        echo implode("\n", $out);
        exit;
    }

    $f = $_FILES['imagem'];
    $out[] = "orig_name=" . ($f['name'] ?? '');
    $out[] = "tmp_name=" . ($f['tmp_name'] ?? '');
    $out[] = "size=" . ($f['size'] ?? '');
    $out[] = "error=" . ($f['error'] ?? '');

    $tmp = $f['tmp_name'];
    $allowed_ext = ['jpg','jpeg','png','gif','webp'];
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $out[] = "ext=" . $ext;

    $uploadDir = rtrim(__DIR__ . '/../img/produtos/', "/\\") . DIRECTORY_SEPARATOR;
    $dest = $uploadDir . uniqid('probe_', true) . '.' . $ext;

    $out[] = "uploadDir=" . $uploadDir;
    $out[] = "is_dir=" . (is_dir($uploadDir) ? '1' : '0');
    $out[] = "is_writable=" . (is_writable($uploadDir) ? '1' : '0');

    if (!is_uploaded_file($tmp)) {
        $out[] = "is_uploaded_file=0 — tmp not uploaded file";
    } else {
        $out[] = "is_uploaded_file=1";
    }

    $moved = @move_uploaded_file($tmp, $dest);
    $out[] = "move_uploaded_file returned=" . ($moved ? '1' : '0');
    if (!$moved) {
        $out[] = "last_error=" . json_encode(error_get_last());
        // try copy fallback
        $copyOk = @copy($tmp, $dest);
        $out[] = "copy returned=" . ($copyOk ? '1' : '0');
        if ($copyOk) @unlink($tmp);
    }

    $out[] = "dest_exists=" . (file_exists($dest) ? '1' : '0');
    if (file_exists($dest)) $out[] = "dest_size=" . filesize($dest);

    echo implode("\n", $out);
    exit;
}

// HTML form
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Upload Probe</title></head>
<body>
<h1>Upload Probe</h1>
<p>Escolha uma imagem pequena e envie. Será impresso um relatório do que o servidor vê.</p>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="imagem" accept="image/*" required>
    <button type="submit">Enviar</button>
</form>
</body>
</html>
