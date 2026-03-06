<?php
session_start();
include '../backend/conexao.php';
include '../includes/sidebar.php';

// segurança: só admin
if(!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin'){
    header("Location: ../index.html");
    exit();
}

// helpers (mantive os mesmos helpers)
function tableExists(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}
function columnExists(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->execute([$table, $column]);
    return (bool)$stmt->fetchColumn();
}
function ensureUsersColumns(PDO $pdo){
    if (!tableExists($pdo, 'usuarios')) {
        $pdo->exec("CREATE TABLE usuarios (id INT AUTO_INCREMENT PRIMARY KEY) ENGINE=InnoDB");
    }
    $add = [];
    if (!columnExists($pdo, 'usuarios', 'nome')) $add[] = "ADD COLUMN nome VARCHAR(150) DEFAULT ''";
    if (!columnExists($pdo, 'usuarios', 'email')) $add[] = "ADD COLUMN email VARCHAR(150) DEFAULT ''";
    if (!columnExists($pdo, 'usuarios', 'senha')) $add[] = "ADD COLUMN senha VARCHAR(255) DEFAULT ''";
    if (!columnExists($pdo, 'usuarios', 'perfil')) $add[] = "ADD COLUMN perfil VARCHAR(50) DEFAULT 'cliente'"; // default cliente
    if (!columnExists($pdo, 'usuarios', 'ativo')) $add[] = "ADD COLUMN ativo TINYINT(1) DEFAULT 1";
    if (!columnExists($pdo, 'usuarios', 'data_cadastro')) $add[] = "ADD COLUMN data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP";
    if ($add) {
        $sql = "ALTER TABLE usuarios " . implode(", ", $add);
        $pdo->exec($sql);
    }
}
try {
    ensureUsersColumns($pdo);
} catch (Exception $e) {
    error_log("ensureUsersColumns: " . $e->getMessage());
}

// leitura do filtro
$perfilFiltro = isset($_GET['perfil']) ? trim($_GET['perfil']) : 'todos';
$params = [];
$sql = "SELECT id, nome, email, perfil, ativo, data_cadastro FROM usuarios WHERE perfil IN ('admin', 'funcionario')";

if ($perfilFiltro !== 'todos') {
    $sql .= " AND perfil = :perfil";
    $params[':perfil'] = $perfilFiltro;
}
$sql .= " ORDER BY data_cadastro DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// perfis possíveis (exibir no filtro) — adaptado para os três perfis principais
$perfis = [
    'admin' => 'Administrador',
    'funcionario' => 'Funcionário',
    'cliente' => 'Cliente'
];

?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gerenciar Funcionários</title>
<link rel="stylesheet" href="../css/adm.css">
<link rel="stylesheet" href="../css/sidebar.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
</head>
<body>
<div class="content-wrapper">
    <h1>Gerenciar Funcionários</h1>
    <section class="filters">
        <form method="GET" id="filtroForm">
            <label for="perfil">Filtrar por perfil:</label>
            <select name="perfil" id="perfil" onchange="document.getElementById('filtroForm').submit()">
                <option value="todos" <?php if($perfilFiltro==='todos') echo 'selected'; ?>>Todos</option>
                <?php foreach($perfis as $k=>$v): ?>
                    <option value="<?php echo $k; ?>" <?php if($perfilFiltro===$k) echo 'selected'; ?>><?php echo $v; ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </section>

    <section class="list">
        <table class="produtos-table">
            <thead>
                <tr><th>ID</th><th>Nome</th><th>Email</th><th>Perfil</th><th>Ativo</th><th>Cadastrado</th><th>Ações</th></tr>
            </thead>
            <tbody>
            <?php if(empty($usuarios)): ?>
                <tr><td colspan="6">Nenhum funcionário encontrado.</td></tr>
            <?php else: ?>
                <?php foreach($usuarios as $u): ?>
                    <tr>
                        <td><?php echo (int)$u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['nome'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                        <td><?php
                            $p = $u['perfil'] ?? 'cliente';
                            echo htmlspecialchars($perfis[$p] ?? ucfirst($p));
                        ?></td>
                        <td><?php echo ((isset($u['ativo']) && $u['ativo']) ? 'Sim' : 'Não'); ?></td>
                        <td><?php echo htmlspecialchars($u['data_cadastro'] ?? ''); ?></td>
                        <td>
                        <div class="actions">
                            <button onclick="abrirModal('<?php echo $u['id']; ?>', 
                           '<?php echo htmlspecialchars($u['nome']); ?>', 
                           '<?php echo htmlspecialchars($u['email']); ?>', 
                           '<?php echo htmlspecialchars($u['perfil']); ?>', 
                           '<?php echo $u['ativo']; ?>')" 
        class="btn-action btn-edit icon-left">Editar</button>
                    </div>
                </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </section>

    <section class="create-user">
        <?php if(isset($_GET['sucesso'])): ?>
            <p class="msg-sucesso">Funcionário criado com sucesso.</p>
        <?php elseif(isset($_GET['erro'])): ?>
            <p class="msg-erro"><?php echo htmlspecialchars($_GET['erro']); ?></p>
        <?php endif; ?>
        <form action="../backend/criar_usuario.php" method="POST" class="admin-form">
            <h1>Criar Funcionário</h1>
            <div>
                <label for="nome">Nome</label>
                <input id="nome" name="nome" required>
            </div>
            <div>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required>
            </div>
            <div>
                <label for="senha">Senha</label>
                <input id="senha" name="senha" type="password" required>
            </div>
            <div>
                <label for="perfil_criar">Perfil</label>
                <select id="perfil_criar" name="perfil" required>
                    <option value="funcionario">Funcionário</option>
                    <option value="admin">Administrador</option>
                </select>
            </div>
            <div class="checkbox-row">
                <label><p class="ativo">Ativo</p><input type="checkbox" name="ativo" value="1" checked></label>
            </div>
            <button type="submit">Criar Funcionário</button>
        </form>
    </section>
</div>

<div id="modal-editar" class="modal">
    <div class="modal-content"><!-- admin-form reaproveitado para usar mesmo CSS -->
        <span class="close">&times;</span>
        <form id="form-editar" action="../backend/editar_funcionario.php" method="POST" class="admin-form">
            <h1>Editar Funcionário</h1>
            <input type="hidden" name="id" id="edit-id">
            
            <div>
                <label for="edit-nome">Nome</label>
                <input type="text" id="edit-nome" name="nome" required>
            </div>
            
            <div>
                <label for="edit-email">Email</label>
                <input type="email" id="edit-email" name="email" required>
            </div>
            
            <div>
                <label for="edit-senha">Nova Senha (opcional)</label>
                <input type="password" id="edit-senha" name="senha">
            </div>
            
            <div>
                <label for="edit-perfil">Perfil</label>
                <select id="edit-perfil" name="perfil" required>
                    <option value="admin">Administrador</option>
                    <option value="funcionario">Funcionário</option>
                </select>
            </div>
            
            <div class="checkbox-row">
                <label>
                    <input type="checkbox" id="edit-ativo" name="ativo" value="1">
                    <p class="ativo">Ativo</p>
                </label>
            </div>
            
            <div class="button-row">
                <button type="submit"><p>Salvar Alterações</p></button>
                <button type="button" class="cancel-btn"><p>Cancelar</p></button>
            </div>
        </form>
    </div>
</div>
<script src="../js/funcionarios.js"></script>
</body>
</html>