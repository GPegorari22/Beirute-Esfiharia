<?php
/**
 * Script de limpeza — apaga TODAS as linhas relacionadas a PEDIDOS e PRODUTOS.
 * Use com MUITO cuidado. Requer confirmação explicitamente via ?confirm=1 (se acessado via web)
 * Ou rode via CLI `php limpar_pedidos_produtos.php`.
 * Antes de rodar: faça BACKUP do banco (ex.: mysqldump).
 */

if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['confirm']) || $_GET['confirm'] !== '1') {
        echo "Este script apaga dados sensíveis. Para confirmar, chame ?confirm=1\n";
        exit;
    }
}

require_once __DIR__ . '/conexao.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    echo "Falha ao carregar conexão PDO.\n";
    exit(1);
}

try {
    // Safety: listar tabelas que serão afetadas (ordem de exclusão)
    $tables = [
        'historico_itens_pedido',
        'itens_carrinho',
        'produto_ingredientes',
        'historico_pedidos',
        'notificacoes',
        'pedidos',
        'produtos'
    ];

    echo "Iniciando limpeza (vai apagar todos os registros das tabelas listadas)\n";
    echo "Tabelas: " . implode(', ', $tables) . "\n";

    // Confirm once more when running interactively in CLI
    if (php_sapi_name() === 'cli') {
        echo "Digite SIM para confirmar: ";
        $handle = fopen('php://stdin', 'r');
        $line = trim(fgets($handle));
        if (strtoupper($line) !== 'SIM') {
            echo "Abortando — confirmação não recebida.\n";
            exit;
        }
    }

    // Fazer backup em memória: opcional — aqui apenas mostramos contagens antes de apagar
    foreach ($tables as $t) {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "Antes: $t => $count registros\n";
    }

    // Desabilitar verificação de FK, apagar e reativar
    $pdo->beginTransaction();
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

    foreach ($tables as $t) {
        // utilizar DELETE em vez de TRUNCATE por compatibilidade com transações
        $pdo->exec("DELETE FROM `$t`");
        // resetar auto-increment
        $pdo->exec("ALTER TABLE `$t` AUTO_INCREMENT = 1");
        echo "Limpo: $t\n";
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    $pdo->commit();

    echo "Limpeza concluída com sucesso.\n";
    foreach ($tables as $t) {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "Depois: $t => $count registros\n";
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Erro: " . $e->getMessage() . "\n";
    exit(1);
}

?>
