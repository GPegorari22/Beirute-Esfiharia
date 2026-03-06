<?php
/**
 * Migração segura: adiciona colunas faltantes em `enderecos` (destinatario, telefone, estado, observacoes)
 * Execute este arquivo apenas uma vez via navegador ou CLI (ex: http://localhost/Beirute-delivery/backend/migrations/update_enderecos_schema.php)
 * Faça backup do banco antes em produção.
 */
require_once __DIR__ . '/../conexao.php';

if (!isset($pdo) && isset($conexao)) $pdo = $conexao;

header('Content-Type: text/plain; charset=utf-8');
echo "Verificando tabela `enderecos`...\n";

$checks = [
    'destinatario' => "ALTER TABLE enderecos ADD COLUMN destinatario VARCHAR(255) DEFAULT NULL",
    'telefone'     => "ALTER TABLE enderecos ADD COLUMN telefone VARCHAR(30) DEFAULT NULL",
    'estado'       => "ALTER TABLE enderecos ADD COLUMN estado VARCHAR(2) DEFAULT NULL",
    'observacoes'  => "ALTER TABLE enderecos ADD COLUMN observacoes TEXT DEFAULT NULL"
];

try {
    foreach ($checks as $col => $sql) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'enderecos' AND COLUMN_NAME = ?");
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
    echo "Verifique se seus forms e código já estão usando as colunas adicionadas.\n";
} catch (PDOException $e) {
    echo "Erro ao executar migração: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nPróximo passo: recarregue a página 'finalizar-pedido.php' e tente salvar o endereço novamente.\n";

?>
