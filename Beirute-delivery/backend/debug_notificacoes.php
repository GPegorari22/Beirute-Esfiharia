<?php
/**
 * Endpoint de depuração: mostra últimas notificações e contagem por usuário.
 * Acesso rápido via browser enquanto diagnosticamos por que as notificações não aparecem.
 * Uso: http://localhost/Beirute-delivery/backend/debug_notificacoes.php
 */
require_once __DIR__ . '/conexao.php';

if (!isset($pdo) && isset($conexao)) $pdo = $conexao;

header('Content-Type: text/plain; charset=utf-8');
echo "DEBUG NOTIFICACOES\n";
try {
    $stmt = $pdo->query("SELECT id, id_usuario, id_pedido, mensagem, tipo, lida, data_criacao FROM notificacoes ORDER BY data_criacao DESC LIMIT 50");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nÚltimas notificações (até 50):\n";
    foreach ($rows as $r) {
        printf("ID:%s | user:%s | pedido:%s | lida:%s | tipo:%s | %s\n", $r['id'], $r['id_usuario'], $r['id_pedido'] ?? '-', $r['lida'] ? '1' : '0', $r['tipo'] ?? '-', $r['mensagem']);
    }

    $stmt2 = $pdo->query("SELECT id_usuario, COUNT(*) as qtd, SUM(lida=0) as nao_lidas FROM notificacoes GROUP BY id_usuario ORDER BY nao_lidas DESC LIMIT 50");
    $byUser = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo "\nContagem de notificações por usuário (até 50):\n";
    foreach ($byUser as $u) printf("user:%s total:%s nao_lidas:%s\n", $u['id_usuario'], $u['qtd'], $u['nao_lidas']);

} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}

?>
