<?php
    session_start();
    include '../backend/conexao.php';
    if(!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin'){
        header("Location: index.html");
        exit();
    }

    // obter id do produto a editar
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if($id <= 0){
        header('Location: admin.php');
        exit();
    }

    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    if(!$produto){
        header('Location: admin.php');
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../css/adm.css">
</head>
<body>
    <form action="../backend/editar_produto.php" method="POST" enctype="multipart/form-data" class="admin-form edit-form">
        <h1>Editar Produto</h1>
        <section>
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($produto['id']); ?>">
            <label for="nome">Nome do Produto</label>
            <input class="input" type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($produto['nome']); ?>" required>
            <label for="preco">Preço</label>
            <input class="input" type="text" id="preco" name="preco" value="<?php echo htmlspecialchars($produto['preco']); ?>" required>
            <label for="categoria">Categoria</label>
            <select class="input" name="categoria" id="categoria" required>
                <option value="tradicionais" <?php if($produto['categoria'] == 'tradicionais') echo 'selected'; ?>>Tradicionais</option>
                <option value="especiais" <?php if($produto['categoria'] == 'especiais') echo 'selected'; ?>>Especiais</option>
                <option value="vegetarianas" <?php if($produto['categoria'] == 'vegetarianas') echo 'selected'; ?>>Vegetarianas</option>
                <option value="doces" <?php if($produto['categoria'] == 'doces') echo 'selected'; ?>>Doces</option>
                <option value="combos" <?php if($produto['categoria'] == 'combos') echo 'selected'; ?>>Combos</option>
                <option value="bebidas" <?php if($produto['categoria'] == 'bebidas') echo 'selected'; ?>>Bebidas</option>
            </select>
            <label for="descricao">Descrição</label>
            <textarea class="input" id="descricao" name="descricao" required><?php echo htmlspecialchars($produto['descricao']); ?></textarea>
            
               <div class="image-row">
                    <div class="container">
                        <div class="folder">
                            <div class="front-side">
                                <div class="tip"></div>
                                <div class="cover"></div>
                            </div>
                            <div class="back-side cover"></div>
                        </div>
                    </div>

                    <div class="current-image">
                        <p>Imagem atual:</p>
                        <?php if(!empty($produto['imagem'])): ?>
                            <img src="../img/produtos/<?php echo htmlspecialchars($produto['imagem']); ?>" 
                                 alt="<?php echo htmlspecialchars($produto['nome']); ?>"
                                 style="max-width: 200px;">
                        <?php endif; ?>
                    </div>

                    <div class="file-select">
                        <label class="custom-file-upload">
                            <input class="title" type="file" name="imagem" accept="image/*" />
                            Escolher Imagem
                        </label>
                    </div>
                </div>

            </section>

        <input type="submit" value="Editar">
    </form>
</body>
</html>