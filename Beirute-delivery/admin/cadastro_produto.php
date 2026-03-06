<?php
    session_start();
    include '../backend/conexao.php';
    include '../includes/sidebar.php';

    if(!isset($_SESSION['nome']) || $_SESSION['perfil'] !== 'admin'){
        header("Location: index.html");
        exit();
    }


    // Modificar a consulta para usar o nome correto da tabela
    $consulta = $pdo->query("
        SELECT p.*, GROUP_CONCAT(
            CONCAT(i.nome, ' (', pi.quantidade_por_produto, ')')
            SEPARATOR ', '
        ) as ingredientes_lista,
        GROUP_CONCAT(
            CONCAT(i.id, ':', COALESCE(pi.quantidade_por_produto,0))
            SEPARATOR ','
        ) as ingredientes_data
        FROM produtos p
        LEFT JOIN produto_ingredientes pi ON p.id = pi.id_produto
        LEFT JOIN ingredientes i ON pi.id_ingrediente = i.id
        GROUP BY p.id
    ");

    // Buscar ingredientes ativos
    $stmt = $pdo->query("SELECT id, nome, estoque FROM ingredientes WHERE ativo = 1 ORDER BY nome");
    $ingredientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // adicionar antes do HTML do formulário (após includes)
    $imgDir = realpath(__DIR__ . '/../img/produtos');
    $availableImages = [];
    $allowed_ext = ['jpg','jpeg','png','gif','webp'];
    if ($imgDir && is_dir($imgDir)) {
        foreach (scandir($imgDir) as $f) {
            if (in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $allowed_ext, true)) {
                if (!in_array($f, ['.', '..'])) $availableImages[] = $f;
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração — Beirute</title>
    <link rel="stylesheet" href="../css/adm.css">
    <link rel="stylesheet" href="../css/sidebar.css">
     <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .ingredientes-cell { max-width: 320px; vertical-align: top; }
        .ingredientes-preview {
            display: block;
            max-height: 3.6em; /* ~3 linhas */
            overflow: hidden;
            line-height: 1.2em;
            white-space: normal;
            word-wrap: break-word;
        }
        .ingredientes-cell.expanded .ingredientes-preview { max-height: none; }
        .ver-mais {
            background: none;
            border: none;
            color: #007bff;
            cursor: pointer;
            padding: 0;
            margin-top: 6px;
            font-size: 0.9em;
        }
        /* ajuste responsivo */
        @media (max-width: 800px) {
            .ingredientes-cell { max-width: 180px; }
        }
    </style>
</head>
<body>

   
    <form action="../backend/cadastrar_produto.php" method="POST" enctype="multipart/form-data" class="admin-form">
        <h1>Cadastrar Produtos</h1>

        <label>Nome</label>
        <input type="text" name="nome" required>

        <label>Preço</label>
        <input type="text" name="preco" required>

        <label>Categoria</label>
        <select name="categoria" required>
            <option value="tradicionais">Tradicionais</option>
            <option value="especiais">Especiais</option>
            <option value="vegetarianas">Vegetarianas</option>
            <option value="doces">Doces</option>
            <option value="combos">Combos</option>
            <option value="bebidas">Bebidas</option>
        </select>

        <label>Descrição</label>
        <textarea name="descricao" required></textarea>

        <!-- Seção: adicionar ingredientes ao cadastrar produto -->
        <fieldset style="margin-top:12px; border:1px solid #ddd; padding:8px;">
            <legend>Ingredientes</legend>
            <div id="ingredientes-list" style="display:flex; flex-wrap:wrap; gap:8px; max-height:260px; overflow:auto; padding:4px;">
                <?php foreach($ingredientes as $ingrediente): ?>
                    <label style="display:flex; align-items:center; gap:6px; width:calc(50% - 8px);">
                        <input class="create-ingrediente" type="checkbox" name="ingredientes[]" value="<?php echo $ingrediente['id']; ?>" data-id="<?php echo $ingrediente['id']; ?>">
                        <span style="flex:1;"><?php echo htmlspecialchars($ingrediente['nome']); ?></span>
                        <input type="number" name="quantidade_<?php echo $ingrediente['id']; ?>" id="qtd-<?php echo $ingrediente['id']; ?>" min="1" value="1" class="qtd-ingrediente" style="width:64px; text-align:center;" disabled>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>

        <label>Imagem do produto</label>
<input type="file" name="imagem" accept="image/*" required>

        <input type="submit" value="Cadastrar">
    </form>

        <div class="produtos-table">
            <h3>Produtos Cadastrados</h3>
            <table>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Preço</th>
                <th>Categoria</th>
                <th>Descrição</th>
                <th>Imagem</th>
                <th>Ativo</th>
                <th>Ingredientes</th>  <!-- Nova coluna -->
                <th>Ações</th>
            </tr>
            <?php while($row = $consulta->fetch(PDO::FETCH_ASSOC)){ ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                    <td><?php echo htmlspecialchars($row['nome']); ?></td>
                    <td><?php echo htmlspecialchars($row['preco']); ?></td>
                    <td><?php echo htmlspecialchars($row['categoria']); ?></td>
                    <td><?php echo htmlspecialchars($row['descricao']); ?></td>
                    <td>
                        <?php if (!empty($row['imagem'])): ?>
                            <img src="../img/produtos/<?php echo htmlspecialchars($row['imagem']); ?>" 
                                 alt="<?php echo htmlspecialchars($row['nome']); ?>" 
                                 width="100"
                                 onerror="this.src='../img/placeholder.jpg'">
                        <?php else: ?>
                            <span>Sem imagem</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $row['ativo'] ? 'Sim' : 'Não'; ?></td>
                    <td class="ingredientes-cell">
                        <?php if (!empty($row['ingredientes_lista'])): 
                            $ingArr = explode(', ', $row['ingredientes_lista']);
                            $countIng = count($ingArr);
                            $previewArr = array_slice($ingArr, 0, 3);
                            $previewText = htmlspecialchars(implode(', ', $previewArr));
                        ?>
                            <div class="ingredientes-preview" aria-label="Ingredientes">
                                <?php echo $countIng > 3 ? $previewText . ', ...' : $previewText; ?>
                            </div>
                            <?php if ($countIng > 3): ?>
                                <button type="button" class="ver-mais" data-full="<?php echo htmlspecialchars($row['ingredientes_lista']); ?>">
                                    Ver mais (<?php echo $countIng; ?>)
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <span>Nenhum ingrediente cadastrado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="actions">
                            <button type="button" onclick="abrirModal(
                                '<?php echo $row['id']; ?>', 
                                '<?php echo addslashes(htmlspecialchars($row['nome'])); ?>', 
                                '<?php echo addslashes(htmlspecialchars($row['preco'])); ?>', 
                                '<?php echo addslashes(htmlspecialchars($row['categoria'])); ?>', 
                                '<?php echo addslashes(htmlspecialchars($row['descricao'])); ?>',
                                '<?php echo $row['ativo']; ?>',
                                '<?php echo addslashes(htmlspecialchars($row['ingredientes_data'])); ?>'
                            )" class="btn-action btn-edit icon-left">Editar</button>
                            <a class="btn-action btn-delete icon-left" href="../backend/deletar_produto.php?id=<?php echo htmlspecialchars($row['id']); ?>" onclick="return confirm('Tem certeza que deseja excluir o produto <?php echo addslashes(htmlspecialchars($row['nome'])); ?>?');">Deletar</a>
                        </div>
                    </td>
                </tr>
            <?php } ?>
        </table>

<!-- Modal de edição de produto -->
<div id="modal-editar" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
                <form action="../backend/editar_produto.php" 
      method="POST" 
      enctype="multipart/form-data" 
      class="admin-form">
            <h1>Editar Produto</h1>
            <input type="hidden" name="id" id="edit-id">
            
            <div>
                <label for="edit-nome">Nome</label>
                <input type="text" id="edit-nome" name="nome" required>
            </div>
            
            <div>
                <label for="edit-preco">Preço</label>
                <input type="text" id="edit-preco" name="preco" required>
            </div>
            
            <div>
                <label for="edit-categoria">Categoria</label>
                <select id="edit-categoria" name="categoria" required>
                    <option value="tradicionais">Tradicionais</option>
                    <option value="especiais">Especiais</option>
                    <option value="vegetarianas">Vegetarianas</option>
                    <option value="doces">Doces</option>
                    <option value="combos">Combos</option>
                    <option value="bebidas">Bebidas</option>
                </select>
            </div>
            
            <div>
                <label for="edit-descricao">Descrição</label>
                <textarea id="edit-descricao" name="descricao" required></textarea>
            </div>

            <!-- NOVO: edição de ingredientes -->
            <fieldset style="margin-top:12px; border:1px solid #ddd; padding:8px;">
                <legend>Ingredientes</legend>
                <div id="edit-ingredientes-list" style="display:flex; flex-wrap:wrap; gap:8px; max-height:260px; overflow:auto; padding:4px;">
                    <?php foreach($ingredientes as $ingrediente): ?>
                        <label style="display:flex; align-items:center; gap:6px; width:calc(50% - 8px);">
                            <input type="checkbox" class="edit-ingrediente" name="ingredientes[]" value="<?php echo $ingrediente['id']; ?>" data-id="<?php echo $ingrediente['id']; ?>">
                            <span style="flex:1;"><?php echo htmlspecialchars($ingrediente['nome']); ?></span>
                            <input type="number" name="quantidade_<?php echo $ingrediente['id']; ?>" id="edit-qtd-<?php echo $ingrediente['id']; ?>" min="1" value="1" class="qtd-ingrediente" style="width:64px; text-align:center;" disabled>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>
            <!-- FIM: edição de ingredientes -->

            <div class="checkbox-row">
                <label for="edit-ativo">
                    <input type="checkbox" id="edit-ativo" name="ativo">
                    Produto Ativo
                </label>
            </div>
            
            <div class="button-row">
                <button type="submit"><p>Salvar Alterações</p></button>
                <button type="button" class="cancel-btn"><p>Cancelar</p></button>
            </div>
        </form>
    </div>
</div>

<!-- incluir script de controle do modal -->
<script src="../js/produtos.js"></script>
<script>
/**
 * abrirModal agora recebe:
 * abrirModal(id, nome, preco, categoria, descricao, ativo, ingredientesData)
 * ingredientesData formato: "id:quantidade,id:quantidade,..." (padrão preenchido pela consulta)
 */
function abrirModal(id, nome, preco, categoria, descricao, ativo, ingredientesData) {
    // preencher campos básicos
    document.getElementById('edit-id').value = id;
    document.getElementById('edit-nome').value = nome;
    document.getElementById('edit-preco').value = preco;
    document.getElementById('edit-categoria').value = categoria;
    document.getElementById('edit-descricao').value = descricao;
    document.getElementById('edit-ativo').checked = (parseInt(ativo,10) ? true : false);

    // limpar marcas anteriores
    document.querySelectorAll('#edit-ingredientes-list .edit-ingrediente').forEach(function(chk){
        chk.checked = false;
        var iid = chk.getAttribute('data-id');
        var qtdInput = document.getElementById('edit-qtd-' + iid);
        var btnInc = document.querySelector('.qty-incr[data-id="'+iid+'"]');
        var btnDec = document.querySelector('.qty-decr[data-id="'+iid+'"]');
        if(qtdInput){
            qtdInput.value = 1;
            qtdInput.disabled = true;
        }
        if(btnInc) btnInc.disabled = true;
        if(btnDec) btnDec.disabled = true;
    });

    // popular com dados atuais do produto
    if(ingredientesData){
        // exemplo: "2:3,5:1,7:2"
        var map = {};
        ingredientesData.split(',').forEach(function(pair){
            var parts = pair.split(':');
            if(parts.length === 2){
                var iid = parts[0].trim();
                var q = parseInt(parts[1],10) || 1;
                if(iid) map[iid] = q;
            }
        });

        for(var iid in map){
            var chk = document.querySelector('#edit-ingredientes-list .edit-ingrediente[data-id="'+iid+'"]');
            var qtdInput = document.getElementById('edit-qtd-' + iid);
            var btnInc = document.querySelector('.qty-incr[data-id="'+iid+'"]');
            var btnDec = document.querySelector('.qty-decr[data-id="'+iid+'"]');
            if(chk){
                chk.checked = true;
                if(qtdInput){
                    qtdInput.value = map[iid];
                    qtdInput.disabled = false;
                }
                if(btnInc) btnInc.disabled = false;
                if(btnDec) btnDec.disabled = false;
            }
        }
    }

    // abrir modal (mesma lógica do modal existente)
    var modal = document.getElementById('modal-editar');
    modal.style.display = 'block';
}

// habilitar/desabilitar input quantidade ao marcar/desmarcar ingrediente
document.addEventListener('DOMContentLoaded', function(){
    function setQtyControlsEnabled(iid, enabled){
        var qtdInput = document.getElementById('edit-qtd-' + iid);
        var btnInc = document.querySelector('.qty-incr[data-id="'+iid+'"]');
        var btnDec = document.querySelector('.qty-decr[data-id="'+iid+'"]');
        if(qtdInput) qtdInput.disabled = !enabled;
        if(btnInc) btnInc.disabled = !enabled;
        if(btnDec) btnDec.disabled = !enabled;
        if(!enabled && qtdInput) qtdInput.value = 1;
    }

    document.querySelectorAll('#edit-ingredientes-list .edit-ingrediente').forEach(function(chk){
        chk.addEventListener('change', function(){
            var iid = chk.getAttribute('data-id');
            setQtyControlsEnabled(iid, chk.checked);
        });
    });

    // plus / minus buttons
    document.querySelectorAll('.qty-incr').forEach(function(btn){
        btn.addEventListener('click', function(){
            var iid = btn.getAttribute('data-id');
            var input = document.getElementById('edit-qtd-' + iid);
            if(!input || input.disabled) return;
            input.value = Math.max(parseInt(input.value || '1',10) + 1, parseInt(input.getAttribute('min') || '1',10));
        });
    });
    document.querySelectorAll('.qty-decr').forEach(function(btn){
        btn.addEventListener('click', function(){
            var iid = btn.getAttribute('data-id');
            var input = document.getElementById('edit-qtd-' + iid);
            if(!input || input.disabled) return;
            var min = parseInt(input.getAttribute('min') || '1',10);
            var val = Math.max(parseInt(input.value || '1',10) - 1, min);
            input.value = val;
        });
    });

    // garantir que apenas ingredientes marcados enviem quantidade:
    // antes de submeter, desativa inputs de quantidade de ingredientes desmarcados (eles já estão disabled, mas reforça)
    var formEditar = document.getElementById('form-editar');
    if(formEditar){
        formEditar.addEventListener('submit', function(){
            document.querySelectorAll('#edit-ingredientes-list .edit-ingrediente').forEach(function(chk){
                var iid = chk.getAttribute('data-id');
                var input = document.getElementById('edit-qtd-' + iid);
                if(input && !chk.checked){
                    input.disabled = true;
                }
            });
        });
    }

    // ja existia lógica de fechar modal; manter comportamento
    var modal = document.getElementById('modal-editar');
    var closeBtn = modal.querySelector('.close');
    if(closeBtn){
        closeBtn.addEventListener('click', function(){ modal.style.display = 'none'; });
    }
    document.querySelectorAll('.cancel-btn').forEach(function(btn){
        btn.addEventListener('click', function(){ modal.style.display = 'none'; });
    });
    window.addEventListener('click', function(e){
        if(e.target === modal) modal.style.display = 'none';
    });
});

// Habilitar/desabilitar quantidade na tela de cadastro ao marcar ingrediente
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('#ingredientes-list .create-ingrediente').forEach(function(chk){
        chk.addEventListener('change', function(){
            var iid = chk.getAttribute('data-id');
            var qtdInput = document.getElementById('qtd-' + iid);
            if(qtdInput){
                qtdInput.disabled = !chk.checked;
                if(!chk.checked) qtdInput.value = 1;
            }
        });
    });
});

/* Habilita os botões "Ver mais" para expandir/colapsar a lista de ingredientes */
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.ver-mais').forEach(function(btn){
        // guardar estado/texte original
        btn._originalText = btn.textContent;

        btn.addEventListener('click', function(){
            var full = btn.getAttribute('data-full') || '';
            var cell = btn.closest('.ingredientes-cell');
            if(!cell) return;
            var preview = cell.querySelector('.ingredientes-preview');

            if(!cell.classList.contains('expanded')){
                // expandir: salvar preview atual e mostrar lista completa
                if(preview){
                    preview._saved = preview.innerHTML;
                    preview.innerHTML = htmlspecialcharsDecode(full);
                }
                btn.textContent = 'Ver menos';
                cell.classList.add('expanded');
            } else {
                // recolher: restaurar preview salvo
                if(preview){
                    preview.innerHTML = preview._saved || preview.innerHTML;
                }
                btn.textContent = btn._originalText;
                cell.classList.remove('expanded');
            }
        });
    });

    // função simples para decodificar entidades HTML (caso o data-full tenha sido escapado)
    function htmlspecialcharsDecode(str){
        var txt = document.createElement('textarea');
        txt.innerHTML = str;
        return txt.value;
    }
});
</script>
</body>
</html>