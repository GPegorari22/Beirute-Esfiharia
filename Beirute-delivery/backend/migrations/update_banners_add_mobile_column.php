<?php
/**
 * Migração: adiciona coluna `imagem_mobile` na tabela banners para suportar imagem exclusiva para dispositivos móveis
 * Execute via navegador ou CLI: http://localhost/Beirute-delivery/backend/migrations/update_banners_add_mobile_column.php
 */
require_once __DIR__ . '/../conexao.php';

if (!isset($pdo) && isset($conexao)) $pdo = $conexao;

header('Content-Type: text/plain; charset=utf-8');
echo "Verificando tabela `banners`...\n";

$checks = [
    'imagem_mobile' => "ALTER TABLE banners ADD COLUMN imagem_mobile VARCHAR(255) DEFAULT NULL"
];

try {
    foreach ($checks as $col => $sql) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'banners' AND COLUMN_NAME = ?");
        $stmt->execute([$col]);
        $exists = (int)$stmt->fetchColumn() > 0;
        if ($exists) {
            echo "- Coluna '{$col}' já existe.\n";
            continue;
        }

        echo "- Adicionando coluna '{$col}'... ";
        $pdo->exec($sql);
        echo "feito.\n";
    }

    echo "\nMigração concluída sem erros.\n";
    echo "Verifique se seus forms e código já estão usando a coluna adicionada.\n";
} catch (PDOException $e) {
    echo "Erro ao executar migração: " . $e->getMessage() . "\n";
    exit(1);
}

?>
