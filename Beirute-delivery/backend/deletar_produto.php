<?php
session_start();
include('conexao.php');

if(!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin'){
    header('Location: ../index.html');
    exit();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if($id <= 0){
    header('Location: ../admin.php');
    exit();
}

try {
    // Iniciar transação
    $pdo->beginTransaction();

    // obter nome da imagem para apagar
    $stmt = $pdo->prepare("SELECT imagem FROM produtos WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row && !empty($row['imagem'])){
        // tentar apagar em ambos os caminhos que o projeto usa: img/produtos/ e imagens/
        $paths = [
            __DIR__ . '/../img/produtos/' . $row['imagem'],
            __DIR__ . '/../imagens/' . $row['imagem']
        ];
        foreach ($paths as $path) {
            if (file_exists($path)) { @unlink($path); }
        }
    }

    // Deletar registros dependentes que referenciam este produto
    // ordem: historico_itens_pedido, itens_carrinho, produto_ingredientes
    $stmt = $pdo->prepare("DELETE FROM historico_itens_pedido WHERE id_produto = ?");
    $stmt->execute([$id]);

    $stmt = $pdo->prepare("DELETE FROM itens_carrinho WHERE id_produto = ?");
    $stmt->execute([$id]);

    $stmt = $pdo->prepare("DELETE FROM produto_ingredientes WHERE id_produto = ?");
    $stmt->execute([$id]);

    // Depois, deletar o produto
    $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
    $stmt->execute([$id]);

    // Confirmar transação
    $pdo->commit();

    header('Location: ../admin/cadastro_produto.php?deletado=1');
    exit();

} catch (PDOException $e) {
    // Em caso de erro, desfaz todas as alterações
    $pdo->rollBack();
    echo "Erro ao deletar produto: " . $e->getMessage();
}
