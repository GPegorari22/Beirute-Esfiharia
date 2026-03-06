<?php
session_start();
include 'conexao.php';

if(!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin'){
    header("Location: ../index.html");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco = floatval($_POST['preco']);
    $categoria = $_POST['categoria'];
    
    // Processar upload da imagem
    $imagem = $_FILES['imagem'];

    // Usar o nome original enviado exatamente como veio (sem basename/sanitização)
    $upload_dir = rtrim(__DIR__ . '/../imagens', "/\\") . DIRECTORY_SEPARATOR;
    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);

    // manter o nome original EXATAMENTE como enviado
    $nome_imagem = $imagem['name'];

    // evitar sobrescrever — acrescenta sufixo numérico se necessário
    if (file_exists($upload_dir . $nome_imagem)) {
        $base = pathinfo($nome_imagem, PATHINFO_FILENAME);
        $ext = pathinfo($nome_imagem, PATHINFO_EXTENSION);
        $i = 1;
        do {
            $novo_nome = $base . '-' . $i . ($ext !== '' ? '.' . $ext : '');
            $i++;
        } while (file_exists($upload_dir . $novo_nome));
        $nome_imagem = $novo_nome;
    }

    // caminho absoluto destino (usar caminho do servidor)
    $destino = $upload_dir . $nome_imagem;

    if (move_uploaded_file($imagem['tmp_name'], $destino)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, preco, categoria, imagem) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $descricao, $preco, $categoria, $nome_imagem]);

            $_SESSION['mensagem'] = "Produto cadastrado com sucesso!";
            header("Location: ../cadastro_produto.php");
            exit();
        } catch(PDOException $e) {
            // remove o arquivo salvo em caso de erro no banco
            if (file_exists($destino)) unlink($destino);
            $_SESSION['erro'] = "Erro ao cadastrar produto: " . $e->getMessage();
            header("Location: ../cadastro_produto.php");
            exit();
        }
    } else {
        $_SESSION['erro'] = "Erro ao fazer upload da imagem.";
        header("Location: ../cadastro_produto.php");
        exit();
    }
}