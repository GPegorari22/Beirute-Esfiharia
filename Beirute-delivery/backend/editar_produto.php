<?php
include('conexao.php');
session_start();

// Verifica autenticação
if(!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin'){
    header("Location: ../index.html");
    exit();
}

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/admin.php');
    exit();
}

// Recebe e sanitiza os dados
$id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
$nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS);
$preco = filter_input(INPUT_POST, 'preco', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$categoria = filter_input(INPUT_POST, 'categoria', FILTER_SANITIZE_SPECIAL_CHARS);
$descricao = filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_SPECIAL_CHARS);
$ativo = isset($_POST['ativo']) ? 1 : 0; // Converte checkbox em 1 ou 0

try {
    // Inicia a transação
    $pdo->beginTransaction();

    // Verifica se o produto existe e pega a imagem atual
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        throw new Exception('Produto não encontrado');
    }

    // Processa upload de imagem se houver
    $imagem = $produto['imagem']; // Mantém a imagem atual por padrão
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $upload_dir = rtrim(__DIR__ . '/../img/produtos/', "/\\") . DIRECTORY_SEPARATOR;

        // Verifica/cria diretório e permissões
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
                throw new Exception('Não foi possível criar pasta de uploads');
            }
        }
        if (!is_writable($upload_dir)) {
            @chmod($upload_dir, 0755);
            if (!is_writable($upload_dir)) {
                throw new Exception('Pasta de upload sem permissão de escrita');
            }
        }

        // Gera nome único para o arquivo e verifica extensão
        $tmp = $_FILES['imagem']['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            throw new Exception('Arquivo temporário inválido (não é upload válido)');
        }

        $origName = basename($_FILES['imagem']['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $permitidos = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        if (!in_array($ext, $permitidos, true)) {
            throw new Exception('Tipo de arquivo não permitido');
        }

        // Preferir usar o nome original enviado (base name) para manter nomes previsíveis
        // usar o nome original enviado (apenas basename para evitar diretórios)
        $orig_name = basename($_FILES['imagem']['name']);
        $base = $orig_name; // mantemos o nome original sem sanitização
        // garantir extensão correta
        $baseExt = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        if ($baseExt !== $ext) {
            // força a extensão detectada a partir do nome original
            $base = pathinfo($base, PATHINFO_FILENAME) . '.' . $ext;
        }

        // evitar sobrescrever: se o arquivo já existir, acrescentar sufixo numerado
        $novo_nome = $base;
        $i = 1;
        while (file_exists($upload_dir . $novo_nome)) {
            $nameOnly = pathinfo($base, PATHINFO_FILENAME);
            $novo_nome = $nameOnly . '-' . $i . '.' . $ext;
            $i++;
        }
        $caminho_arquivo = $upload_dir . $novo_nome;

        // Tentar mover; se falhar, tentar copy + unlink e logar
        if (!move_uploaded_file($tmp, $caminho_arquivo)) {
            $copyOk = @copy($tmp, $caminho_arquivo);
            if ($copyOk) {
                @unlink($tmp);
                error_log("[editar_produto] move_uploaded_file falhou, mas copy funcionou: $caminho_arquivo");
            } else {
                $err = error_get_last();
                error_log("[editar_produto] Falha ao mover arquivo '$tmp' -> '$caminho_arquivo' lastError=" . json_encode($err));
                throw new Exception('Erro ao mover arquivo enviado');
            }
        }

        // Remove imagem antiga se existir e for diferente
        if (!empty($produto['imagem']) && file_exists($upload_dir . $produto['imagem'])) {
            @unlink($upload_dir . $produto['imagem']);
        }

        $imagem = $novo_nome;

        // copiar também para ../img/ por compatibilidade
        $copyToRoot = rtrim(__DIR__ . '/../img/', "/\\") . DIRECTORY_SEPARATOR . $novo_nome;
        if (!file_exists(dirname($copyToRoot))) @mkdir(dirname($copyToRoot), 0755, true);
        if (!@copy($caminho_arquivo, $copyToRoot)) {
            error_log("[editar_produto] Falha ao copiar imagem para root img: $copyToRoot");
        }
    }

    // Atualiza o produto
    $sql = "UPDATE produtos SET nome = ?, preco = ?, categoria = ?, descricao = ?, imagem = ?, ativo = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $resultado = $stmt->execute([$nome, $preco, $categoria, $descricao, $imagem, $ativo, $id]);

    if (!$resultado) {
        throw new Exception('Erro ao atualizar produto');
    }

    // Confirma a transação
    $pdo->commit();
    
    // Redireciona com sucesso
    header('Location: ../admin/admin.php?sucesso=atualizado');
    exit();

} catch (Exception $e) {
    // Reverte a transação em caso de erro
    $pdo->rollBack();
    
    // Log do erro
    error_log("Erro na atualização: " . $e->getMessage());

   exit();    header('Location: ../admin/editar_produto.php?id=' . $id . '&erro=' . urlencode($e->getMessage()));    // Redireciona com erro        
    // Redireciona com erro
    header('Location: ../admin/editar_produto.php?id=' . $id . '&erro=' . urlencode($e->getMessage()));
    exit();
}
?>
