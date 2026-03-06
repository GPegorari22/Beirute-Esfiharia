<?php 
session_start();
require "conexao.php";

if (!isset($pdo) || !($pdo instanceof PDO)) {
    http_response_code(500);
    echo "Erro na conexão com o banco de dados.";
    exit();
}

if (!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin') {
    header("Location: ../index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/cadastro_produto.php");
    exit();
}

$nome_produto = trim($_POST['nome'] ?? '');
$preco_raw = trim($_POST['preco'] ?? '0');
$preco = floatval(str_replace(',', '.', $preco_raw));
$categoria = trim($_POST['categoria'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');

if ($nome_produto === '' || $categoria === '' || $descricao === '') {
    header("Location: ../admin/cadastro_produto.php?erro=missing_fields");
    exit();
}

/*---------------------------------------------
  UPLOAD DA IMAGEM — NOME ORIGINAL SEM SANITIZAÇÃO
----------------------------------------------*/

if (!isset($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
    header("Location: ../admin/cadastro_produto.php?erro=imagem");
    exit();
}

$imagem = $_FILES['imagem'];
$imagem_tmp = $imagem['tmp_name'];

// validar se é imagem
$info = @getimagesize($imagem_tmp);
if ($info === false) {
    header("Location: ../admin/cadastro_produto.php?erro=imagem_invalida");
    exit();
}

// pegar nome original COMPLETO
$nome_original = $imagem['name'];

// manter e salvar o nome original (sem sanitização)
$nome_final = $nome_original;

// validar extensão
$ext = strtolower(pathinfo($nome_final, PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png','gif','webp'];

if (!in_array($ext, $allowed)) {
    header("Location: ../admin/cadastro_produto.php?erro=extensao");
    exit();
}

// diretório final
$uploadDir = realpath(__DIR__ . '/../img/produtos/') . DIRECTORY_SEPARATOR;
$destino = $uploadDir . $nome_final;

// evitar sobrescrever imagem existente — manter o nome original e só acrescentar sufixo numérico
if (file_exists($destino)) {
    $base = pathinfo($nome_final, PATHINFO_FILENAME);
    $ext = pathinfo($nome_final, PATHINFO_EXTENSION);
    $i = 1;
    do {
        $novo = $base . '-' . $i . ($ext !== '' ? '.' . $ext : '');
        $i++;
    } while (file_exists($uploadDir . $novo));
    $nome_final = $novo;
    $destino = $uploadDir . $nome_final;
}

// mover arquivo
if (!move_uploaded_file($imagem_tmp, $destino)) {
    header("Location: ../admin/cadastro_produto.php?erro=upload");
    exit();
}

// nome final salvo no banco
$imagem_nome = $nome_final;

/*---------------------------------------------
  INSERIR PRODUTO
----------------------------------------------*/

try {
    $sql = "INSERT INTO produtos (nome, preco, categoria, descricao, imagem) 
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nome_produto, $preco, $categoria, $descricao, $imagem_nome]);

    $id_produto = $pdo->lastInsertId();

    /*---------------------------------------------
      INSERIR INGREDIENTES
    ----------------------------------------------*/
    if(isset($_POST['ingredientes']) && is_array($_POST['ingredientes'])) {
        foreach($_POST['ingredientes'] as $id_ingrediente) {

            $quantidade = filter_input(INPUT_POST, 'quantidade_'.$id_ingrediente, FILTER_VALIDATE_INT);

            if($quantidade > 0) {
                $stmt = $pdo->prepare(
                    "INSERT INTO produto_ingredientes 
                     (id_produto, id_ingrediente, quantidade_por_produto) 
                     VALUES (?, ?, ?)"
                );
                $stmt->execute([$id_produto, $id_ingrediente, $quantidade]);
            }
        }
    }

    header("Location: ../admin/cadastro_produto.php?sucesso=1");
    exit();

} catch (Exception $e) {
    header("Location: ../admin/cadastro_produto.php?erro=excecao");
    exit();
}

?>