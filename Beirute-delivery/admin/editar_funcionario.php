<?php
session_start();
include '../backend/conexao.php';

// Verificar se é admin
if (!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin') {
    header('Location: ../index.html');
    exit();
}

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $perfil = filter_input(INPUT_POST, 'perfil', FILTER_SANITIZE_STRING);
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Validar perfil (apenas admin e funcionario são permitidos)
    if (!in_array($perfil, ['admin', 'funcionario'])) {
        header('Location: ../admin/funcionarios.php?erro=Perfil inválido');
        exit();
    }

    try {
        // Preparar a atualização
        $sql = "UPDATE usuarios SET nome = :nome, email = :email, perfil = :perfil, ativo = :ativo";
        
        // Se uma nova senha foi fornecida, incluí-la na atualização
        if (!empty($_POST['senha'])) {
            $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $sql .= ", senha = :senha";
        }
        
        $sql .= " WHERE id = :id AND perfil IN ('admin', 'funcionario')";
        
        $stmt = $pdo->prepare($sql);
        
        // Bind dos parâmetros básicos
        $params = [
            ':id' => $id,
            ':nome' => $nome,
            ':email' => $email,
            ':perfil' => $perfil,
            ':ativo' => $ativo
        ];
        
        // Adicionar senha aos parâmetros se fornecida
        if (!empty($_POST['senha'])) {
            $params[':senha'] = $senha;
        }
        
        // Executar a atualização
        if ($stmt->execute($params)) {
            header('Location: ../admin/funcionarios.php?sucesso=1');
        } else {
            header('Location: ../admin/funcionarios.php?erro=Falha ao atualizar');
        }
        
    } catch (PDOException $e) {
        error_log("Erro ao atualizar funcionário: " . $e->getMessage());
        header('Location: ../admin/funcionarios.php?erro=Erro ao atualizar');
    }
    
    exit();
}

// Se não for POST, verificar se foi fornecido um ID válido
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header('Location: ../admin/funcionarios.php?erro=ID inválido');
    exit();
}

try {
    // Buscar dados do funcionário
    $stmt = $pdo->prepare("SELECT id, nome, email, perfil, ativo FROM usuarios WHERE id = :id AND perfil IN ('admin', 'funcionario')");
    $stmt->execute([':id' => $id]);
    $funcionario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$funcionario) {
        header('Location: ../admin/funcionarios.php?erro=Funcionário não encontrado');
        exit();
    }
    
} catch (PDOException $e) {
    error_log("Erro ao buscar funcionário: " . $e->getMessage());
    header('Location: ../admin/funcionarios.php?erro=Erro ao buscar dados');
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Funcionário</title>
    <link rel="stylesheet" href="../css/adm.css">
</head>
<body id="editar-body">
    <div class="content-wrapper">
        
        <form method="POST" class="admin-form">
            <h1>Editar Funcionário</h1>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($funcionario['id']); ?>">
            
            <div>
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($funcionario['nome']); ?>" required>
            </div>
            
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($funcionario['email']); ?>" required>
            </div>
            
            <div>
                <label for="senha">Nova Senha (deixe em branco para manter a atual):</label>
                <input type="password" id="senha" name="senha">
            </div>
            
            <div>
                <label for="perfil">Perfil:</label>
                <select id="perfil" name="perfil" required>
                    <option value="admin" <?php echo $funcionario['perfil'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                    <option value="funcionario" <?php echo $funcionario['perfil'] === 'funcionario' ? 'selected' : ''; ?>>Funcionário</option>
                </select>
            </div>
            
            <div class="checkbox-row">
                <label>
                    <input type="checkbox" name="ativo" value="1" <?php echo $funcionario['ativo'] ? 'checked' : ''; ?>>
                    Ativo
                </label>
            </div>
            
            <div class="button-row">
                <button type="submit">Salvar Alterações</button>
                <a href="../admin/funcionarios.php" class="button">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>