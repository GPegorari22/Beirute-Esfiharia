<?php

session_start();
include '../backend/conexao.php';
if(!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin'){
    header("Location: ../index.html");
    exit();
}

$mensagem = '';
$tipo_mensagem = '';

// Processar upload e cadastro
$uploadedDesktop = false;
$uploadedMobile = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $ordem = (int)($_POST['ordem'] ?? 0);
    
    // Atualizar imagem desktop de um banner existente
    if (isset($_POST['update_desktop']) && isset($_POST['banner_id']) && isset($_FILES['imagem_update'])) {
        $id = (int)$_POST['banner_id'];
        $arquivoUpdate = $_FILES['imagem_update'];
        if ($arquivoUpdate['error'] === 0) {
            $ext = strtolower(pathinfo($arquivoUpdate['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $permitidas) && $arquivoUpdate['size'] <= 5 * 1024 * 1024) {
                $nome_arquivo_update = 'banner_' . time() . '.' . $ext;
                $caminho_destino_update = '../img/Banner/' . $nome_arquivo_update;
                if (!is_dir('../img/Banner')) mkdir('../img/Banner', 0755, true);
                if (move_uploaded_file($arquivoUpdate['tmp_name'], $caminho_destino_update)) {
                    try {
                        // Apagar arquivo antigo
                        $stmt = $pdo->prepare('SELECT imagem FROM banners WHERE id = ?');
                        $stmt->execute([$id]);
                        $old = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($old && !empty($old['imagem'])) {
                            $file = '../img/Banner/' . $old['imagem'];
                            if (file_exists($file)) unlink($file);
                        }
                        $stmt = $pdo->prepare('UPDATE banners SET imagem = ? WHERE id = ?');
                        $stmt->execute([$nome_arquivo_update, $id]);
                        $_SESSION['banner_msg'] = '✅ Imagem desktop atualizada!';
                        $_SESSION['banner_tipo'] = 'success';
                    } catch (PDOException $e) {
                        unlink($caminho_destino_update);
                        $_SESSION['banner_msg'] = '❌ Erro ao atualizar imagem: ' . $e->getMessage();
                        $_SESSION['banner_tipo'] = 'error';
                    }
                }
            }
        }
        header('Location: cadastro_banner.php');
        exit();
    }

    // Atualizar imagem mobile de um banner existente
    if (isset($_POST['update_mobile']) && isset($_POST['banner_id']) && isset($_FILES['imagem_mobile_update'])) {
        $id = (int)$_POST['banner_id'];
        $arquivoUpdate = $_FILES['imagem_mobile_update'];
        if ($arquivoUpdate['error'] === 0) {
            $ext = strtolower(pathinfo($arquivoUpdate['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $permitidas) && $arquivoUpdate['size'] <= 5 * 1024 * 1024) {
                $nome_arquivo_update = 'banner_mobile_' . time() . '.' . $ext;
                $caminho_destino_update = '../img/Banner/' . $nome_arquivo_update;
                if (!is_dir('../img/Banner')) mkdir('../img/Banner', 0755, true);
                if (move_uploaded_file($arquivoUpdate['tmp_name'], $caminho_destino_update)) {
                    try {
                        // Apagar arquivo antigo
                        $stmt = $pdo->prepare('SELECT imagem_mobile FROM banners WHERE id = ?');
                        $stmt->execute([$id]);
                        $old = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($old && !empty($old['imagem_mobile'])) {
                            $file = '../img/Banner/' . $old['imagem_mobile'];
                            if (file_exists($file)) unlink($file);
                        }
                        $stmt = $pdo->prepare('UPDATE banners SET imagem_mobile = ? WHERE id = ?');
                        $stmt->execute([$nome_arquivo_update, $id]);
                        $_SESSION['banner_msg'] = '✅ Imagem mobile atualizada!';
                        $_SESSION['banner_tipo'] = 'success';
                    } catch (PDOException $e) {
                        unlink($caminho_destino_update);
                        $_SESSION['banner_msg'] = '❌ Erro ao atualizar imagem mobile: ' . $e->getMessage();
                        $_SESSION['banner_tipo'] = 'error';
                    }
                }
            }
        }
        header('Location: cadastro_banner.php');
        exit();
    }
    
    // Validar arquivo enviado (desktop)
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === 0) {
        $arquivo = $_FILES['imagem'];
        $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

        // Validar extensão e tamanho
        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $permitidas)) {
            $mensagem = "❌ Formato de arquivo não permitido! Use: JPG, PNG, GIF ou WEBP";
            $tipo_mensagem = 'error';
        } elseif ($arquivo['size'] > 5 * 1024 * 1024) { // 5MB
            $mensagem = "❌ Arquivo muito grande! Máximo 5MB";
            $tipo_mensagem = 'error';
        } else {
            // Gerar nome único e preparar destino
            $nome_arquivo = 'banner_' . time() . '.' . $ext;
            $caminho_destino = '../img/Banner/' . $nome_arquivo;

            if (!is_dir('../img/Banner')) {
                mkdir('../img/Banner', 0755, true);
            }

            // Tentar mover o arquivo primeiro
            if (!move_uploaded_file($arquivo['tmp_name'], $caminho_destino)) {
                $mensagem = "❌ Erro ao enviar arquivo!";
                $tipo_mensagem = 'error';
            } else {
                // Inserir registro no banco (imagem_mobile NULL inicialmente)
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO banners (titulo, descricao, imagem, imagem_mobile, ativo, ordem) VALUES (:titulo, :descricao, :imagem, NULL, :ativo, :ordem)"
                    );
                    $stmt->execute([
                        ':titulo' => $titulo,
                        ':descricao' => $descricao,
                        ':imagem' => $nome_arquivo,
                        ':ativo' => $ativo,
                        ':ordem' => $ordem
                    ]);
                    $uploadedDesktop = true;
                    $mensagem = "✅ Banner cadastrado com sucesso!";
                    $tipo_mensagem = 'success';
                } catch (PDOException $e) {
                    // Se falhar no DB, remover o arquivo enviado
                    if (file_exists($caminho_destino)) unlink($caminho_destino);
                    $mensagem = "❌ Erro ao salvar no banco: " . $e->getMessage();
                    $tipo_mensagem = 'error';
                }
            }
        }
    } else {
        $mensagem = "❌ Selecione uma imagem!";
        $tipo_mensagem = 'error';
    }

    // Processar upload de imagem mobile (opcional)
    if (isset($_FILES['imagem_mobile']) && $_FILES['imagem_mobile']['error'] === 0 && $uploadedDesktop) {
        $arquivoM = $_FILES['imagem_mobile'];
        $extM = strtolower(pathinfo($arquivoM['name'], PATHINFO_EXTENSION));
        
        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($extM, $permitidas) && $arquivoM['size'] <= 5 * 1024 * 1024) {
            $nome_arquivo_mobile = 'banner_mobile_' . time() . '.' . $extM;
            $caminho_mobile = '../img/Banner/' . $nome_arquivo_mobile;
            if (move_uploaded_file($arquivoM['tmp_name'], $caminho_mobile)) {
                // Atualizar o registro do banner criado para salvar a imagem mobile
                try {
                    $lastId = $pdo->lastInsertId();
                    $stmtUpd = $pdo->prepare("UPDATE banners SET imagem_mobile = ? WHERE id = ?");
                    $stmtUpd->execute([$nome_arquivo_mobile, $lastId]);
                    $uploadedMobile = true;
                } catch (PDOException $e) {
                    // se algo falhar, apague a imagem e siga sem mobile
                    unlink($caminho_mobile);
                }
            }
        }
    }
}

// Deletar banner
if (isset($_GET['deletar'])) {
    $id = (int)$_GET['deletar'];
    try {
        $stmt = $pdo->prepare("SELECT imagem, imagem_mobile FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        $banner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($banner) {
            if (!empty($banner['imagem'])) {
                $arquivo = '../img/Banner/' . $banner['imagem'];
                if (file_exists($arquivo)) unlink($arquivo);
            }
            if (!empty($banner['imagem_mobile'])) {
                $arquivoMobile = '../img/Banner/' . $banner['imagem_mobile'];
                if (file_exists($arquivoMobile)) unlink($arquivoMobile);
            }
            
            $stmt = $pdo->prepare("DELETE FROM banners WHERE id = ?");
            $stmt->execute([$id]);
            $mensagem = "✅ Banner deletado!";
            $tipo_mensagem = 'success';
            
            // Redirect para evitar resubmissão
            header("Location: cadastro_banner.php?msg=deletado");
            exit();
        }
    } catch (PDOException $e) {
        $mensagem = "❌ Erro ao deletar: " . $e->getMessage();
        $tipo_mensagem = 'error';
    }
}

// Alternar ativo/inativo - AGORA COM REDIRECT
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    try {
        $stmt = $pdo->prepare("SELECT ativo FROM banners WHERE id = ?");
        $stmt->execute([$id]);
        $banner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($banner) {
            $novo_status = $banner['ativo'] == 1 ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE banners SET ativo = ? WHERE id = ?");
            $stmt->execute([$novo_status, $id]);
            
            // Usar session para mostrar mensagem após redirect
            $_SESSION['banner_msg'] = $novo_status == 1 ? "✅ Banner ativado!" : "✅ Banner desativado!";
            $_SESSION['banner_tipo'] = 'success';
            
            // Redirect ESSENCIAL para evitar cache
            header("Location: cadastro_banner.php?atualizado=1");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['banner_msg'] = "❌ Erro ao atualizar: " . $e->getMessage();
        $_SESSION['banner_tipo'] = 'error';
        header("Location: cadastro_banner.php");
        exit();
    }
}

// Atualizar ordem dos banners
if (isset($_POST['atualizar_ordem'])) {
    try {
        $ordens = $_POST['ordem'] ?? [];
        
        foreach ($ordens as $id => $ordem) {
            $stmt = $pdo->prepare("UPDATE banners SET ordem = ? WHERE id = ?");
            $stmt->execute([(int)$ordem, (int)$id]);
        }
        
        $_SESSION['banner_msg'] = "✅ Ordem dos banners atualizada!";
        $_SESSION['banner_tipo'] = 'success';
        
        // Redirect após salvar
        header("Location: cadastro_banner.php?atualizado=1");
        exit();
    } catch (PDOException $e) {
        $_SESSION['banner_msg'] = "❌ Erro ao atualizar ordem: " . $e->getMessage();
        $_SESSION['banner_tipo'] = 'error';
        header("Location: cadastro_banner.php");
        exit();
    }
}

// Capturar mensagem da session
if (isset($_SESSION['banner_msg'])) {
    $mensagem = $_SESSION['banner_msg'];
    $tipo_mensagem = $_SESSION['banner_tipo'];
    
    // Limpar a session para não mostrar novamente
    unset($_SESSION['banner_msg']);
    unset($_SESSION['banner_tipo']);
}

// Buscar banners
try {
    $stmt = $pdo->query("SELECT * FROM banners ORDER BY ordem ASC, data_cadastro DESC");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $banners = [];
    $mensagem = "❌ Erro ao buscar banners: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Banners — Beirute</title>
    <link rel="stylesheet" href="../css/adm.css">
    <link rel="stylesheet" href="../css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <h1>Gerenciar Banners do Carrossel</h1>
        
        <?php if ($mensagem): ?>
            <div class="alerts-section" style="background-color: <?php echo $tipo_mensagem === 'success' ? '#d4edda' : '#f8d7da'; ?>; border-left-color: <?php echo $tipo_mensagem === 'success' ? '#28a745' : '#dc3545'; ?>;">
                <p><?php echo $mensagem; ?></p>
            </div>
        <?php endif; ?>
        
        <div class="admin-form">
            <h2>Adicionar Novo Banner</h2>
            <form method="POST" enctype="multipart/form-data">
                <label>Título (opcional)</label>
                <input type="text" name="titulo" placeholder="Ex: Banner Promoção">
                
                <label>Descrição (opcional)</label>
                <textarea name="descricao" placeholder="Ex: Promoção especial de verão"></textarea>

                <label>Ordem de Exibição</label>
                <input type="number" name="ordem" min="1" placeholder="Ex: 1" value="1" required>
                
                <div class="container">
                        <div class="folder">
                            <div class="front-side">
                            <div class="tip"></div>
                            <div class="cover"></div>
                            </div>
                            <div class="back-side cover"></div>
                        </div>
                        <label class="custom-file-upload">
                            <input class="title" type="file" name="imagem" accept="image/*" required/>
                            Escolher Imagem (desktop)
                        </label>

                        <label class="custom-file-upload" style="margin-left:10px;">
                            <input class="title" type="file" name="imagem_mobile" accept="image/*" />
                            Escolher Imagem (mobile) - opcional
                        </label>
                        </div>
                
                <label>
                    <input type="checkbox" name="ativo" checked> Ativo
                </label>
                
                <button type="submit">Adicionar Banner</button>
            </form>
        </div>
        
        <div class="banners-table">
            <h3>Banners Cadastrados</h3>
            <form method="POST">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                                <th>Imagem (desktop)</th>
                                <th>Imagem (mobile)</th>
                            <th>Ordem</th>
                            <th>Status</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($banners as $banner): ?>
                            <tr>
                                <td><?php echo $banner['id']; ?></td>
                                <td><?php echo htmlspecialchars($banner['titulo'] ?? 'N/A'); ?></td>
                                    <td>
                                    <?php if (!empty($banner['imagem'])): ?>
                                        <img src="../img/Banner/<?php echo htmlspecialchars($banner['imagem']); ?>" 
                                             alt="Banner" style="max-width: 80px; height: auto; border-radius: 8px;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($banner['imagem_mobile'])): ?>
                                        <img src="../img/Banner/<?php echo htmlspecialchars($banner['imagem_mobile']); ?>" 
                                             alt="Banner Mobile" style="max-width: 80px; height: auto; border-radius: 8px;">
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <input type="number" name="ordem[<?php echo $banner['id']; ?>]" 
                                           value="<?php echo $banner['ordem'] ?? 0; ?>" 
                                           min="1" style="width: 70px; padding: 8px; border: 2px solid #B78B46; border-radius: 5px;">
                                </td>
                                <td>
                                    <span style="color: <?php echo $banner['ativo'] ? '#28a745' : '#dc3545'; ?>; font-weight: 600;">
                                        <?php echo $banner['ativo'] ? '✓ Ativo' : '✗ Inativo'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($banner['data_cadastro'])); ?></td>
                                <td class="btn-group">
                                    <!-- Toggle active/inactive -->
                                    <a href="cadastro_banner.php?toggle=<?php echo $banner['id']; ?>" 
                                       class="btn-action btn-toggle" 
                                       title="<?php echo $banner['ativo'] ? 'Desativar' : 'Ativar'; ?>"
                                       onclick="return confirm('Tem certeza?');">
                                        <?php echo $banner['ativo'] ? '⚫ Desativar' : '⚪ Ativar'; ?>
                                    </a>

                                    <!-- Small form to update desktop image -->
                                    <form method="POST" enctype="multipart/form-data" style="display:inline-block; margin-left:6px;">
                                        <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>" />
                                        <label class="custom-file-upload small">
                                            <input type="file" name="imagem_update" accept="image/*" style="display:none;" onchange="this.form.submit()" />
                                            ✏️ Atualizar Desktop
                                        </label>
                                        <input type="hidden" name="update_desktop" value="1" />
                                    </form>

                                    <!-- Small form to update mobile image -->
                                    <form method="POST" enctype="multipart/form-data" style="display:inline-block; margin-left:6px;">
                                        <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>" />
                                        <label class="custom-file-upload small">
                                            <input type="file" name="imagem_mobile_update" accept="image/*" style="display:none;" onchange="this.form.submit()" />
                                            📱 Atualizar Mobile
                                        </label>
                                        <input type="hidden" name="update_mobile" value="1" />
                                    </form>

                                    <!-- Delete button -->
                                    <a href="cadastro_banner.php?deletar=<?php echo $banner['id']; ?>" 
                                       onclick="return confirm('Tem certeza que deseja deletar este banner?');" 
                                       class="btn-action btn-delete">
                                        🗑️ Deletar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if (!empty($banners)): ?>
                    <button type="submit" name="atualizar_ordem" class="btn-salvar-ordem">💾 Salvar Ordem</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html>