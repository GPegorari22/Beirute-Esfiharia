<?php
session_start();
require_once '../backend/conexao.php';

$user = [
  'id' => $_SESSION['id'] ?? null,
  'nome' => $_SESSION['nome'] ?? 'Usuário Exemplo',
  'email' => $_SESSION['email'] ?? 'usuario@exemplo.com',
  'telefone' => $_SESSION['telefone'] ?? '(99) 99999-9999',
  'data_registro' => null
];

// se houver id, buscar dados atualizados no banco (opcional)
if (!empty($user['id']) && isset($pdo)) {
    // busca campos básicos incluindo imagem_perfil
    $stmt = $pdo->prepare("SELECT nome, email, telefone, imagem_perfil FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$user['id']]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $user['nome'] = $row['nome'] ?? $user['nome'];
        $user['email'] = $row['email'] ?? $user['email'];
        $user['telefone'] = $row['telefone'] ?? $user['telefone'];

        // pega imagem do DB e atualiza sessão para persistir entre login/logout
        $user['imagem_perfil'] = $row['imagem_perfil'] ?? null;
        if (!empty($user['imagem_perfil'])) {
            $_SESSION['imagem_perfil'] = $user['imagem_perfil'];
        }
    }
}

// fallback caso não exista imagem definida
$img = $_SESSION['imagem_perfil'] ?? ($user['imagem_perfil'] ?? 'avatar-default.png');

// ao exibir a img, verificar se o arquivo existe e usar fallback se não existir
$img_path = __DIR__ . '/../img/foto-perfil/' . $img;
if (!file_exists($img_path) || empty($img)) {
    $img = 'avatar-default.png';
}

if (!$user['data_registro']) {
    $user['data_registro'] = date('d/m/Y');
}

// ==================== BUSCAR ENDEREÇO DO USUÁRIO ====================
$endereco_exibicao = 'Nenhum endereço cadastrado';
$telefone_exibicao = $user['telefone']; // valor padrão vindo da sessão/consulta ao usuário
if (!empty($user['id']) && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT rua, numero, complemento, bairro, cidade, estado, cep, telefone
            FROM enderecos
            WHERE id_usuario = ? AND principal = 1
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$user['id']]);
        $endereco = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($endereco) {
            // monta string do endereço (como já fazia)
            $endereco_exibicao = $endereco['rua'];
            if ($endereco['numero']) $endereco_exibicao .= ', ' . $endereco['numero'];
            if ($endereco['complemento']) $endereco_exibicao .= ' - ' . $endereco['complemento'];
            $endereco_exibicao .= ' — ' . $endereco['bairro'];
            if ($endereco['cidade']) $endereco_exibicao .= ', ' . $endereco['cidade'];
            if ($endereco['estado']) $endereco_exibicao .= ' - ' . $endereco['estado'];
            if ($endereco['cep']) $endereco_exibicao .= ' (' . $endereco['cep'] . ')';

            // se o endereço tem telefone, usa para exibir (faz formatação simples)
            if (!empty($endereco['telefone'])) {
                $tel = preg_replace('/\D+/', '', $endereco['telefone']);
                if (strlen($tel) === 11) {
                    $telefone_exibicao = sprintf('(%s) %s-%s', substr($tel,0,2), substr($tel,2,5), substr($tel,7));
                } elseif (strlen($tel) === 10) {
                    $telefone_exibicao = sprintf('(%s) %s-%s', substr($tel,0,2), substr($tel,2,4), substr($tel,6));
                } else {
                    $telefone_exibicao = $endereco['telefone'];
                }
            }
        }
    } catch (Exception $e) {
        error_log('Erro ao buscar endereço: ' . $e->getMessage());
    }
}

// ==================== BUSCAR PEDIDOS DO USUÁRIO ====================
$pedidos = [];
if (!empty($user['id']) && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.id,
                p.valor_total,
                p.metodo_pagamento,
                p.status_pedido,
                p.data_pedido,
                e.rua,
                e.numero,
                e.bairro,
                e.cidade,
                e.estado
            FROM pedidos p
            LEFT JOIN enderecos e ON p.id_endereco = e.id
            WHERE p.id_usuario = ? AND p.status_pedido != 'cancelado'
            ORDER BY p.data_pedido DESC
            LIMIT 20
        ");
        $stmt->execute([$user['id']]);
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Erro ao buscar pedidos: ' . $e->getMessage());
    }
}

// Função para retornar a cor do status
function getStatusColor($status) {
    $cores = [
        'pendente' => '#ffc107',
        'confirmado' => '#2196f3',
        'em_preparo' => '#ff9800',
        'saiu_para_entrega' => '#9c27b0',
        'entregue' => '#4caf50',
        'cancelado' => '#f44336'
    ];
    return $cores[$status] ?? '#000000ff';
}

// Função para traduzir status
function traduzirStatus($status) {
    $traducoes = [
        'pendente' => 'Pendente',
        'confirmado' => 'Confirmado',
        'em_preparo' => 'Em preparo',
        'saiu_para_entrega' => 'Saiu para entrega',
        'entregue' => 'Entregue',
        'cancelado' => 'Cancelado'
    ];
    return $traducoes[$status] ?? ucfirst($status);
}

include '../includes/header.php';
?>

<main>
  <div class="profile-container" role="main">
    <h1 class="profile-title">Perfil do Usuário</h1>

    <section class="dados-usuario">
      <section class="profile-top">
        <!-- card principal -->
        <article class="profile-main-card">
          <div class="profile-avatar" aria-hidden="true">
              <?php
                $img = $_SESSION['imagem_perfil'] ?? ($user['imagem_perfil'] ?? 'avatar-default.png');
              ?>
            <!-- imagem clicável para alterar foto (substitui o botão) -->
            <img id="avatarImg" src="../img/foto-perfil/<?= htmlspecialchars($img) ?>" alt="Foto de perfil de <?= htmlspecialchars($user['nome']) ?>"
                 style="width:120px;height:120px;object-fit:cover;border-radius:50%;display:block;margin:0 auto;cursor:pointer;"
                 role="button" tabindex="0" title="Clique para alterar a foto">
            <div style="text-align:center;margin-top:10px;">
              <span id="uploadStatus" style="display:block;margin-top:8px;font-size:13px;color:#415a6f;"></span>
            </div>
             <!-- input escondido (já usado pelo JS para upload) -->
             <form id="formFoto" style="display:none;" enctype="multipart/form-data">
               <input id="inputFoto" name="foto" type="file" accept="image/*">
             </form>
           </div>
          <div class="profile-info">
            <h2><?= htmlspecialchars($user['nome']) ?></h2>
            <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
            <p><strong>Telefone:</strong> <?= htmlspecialchars($user['telefone']) ?></p>
            <p><strong>Registrado em:</strong> <?= htmlspecialchars($user['data_registro']) ?></p>
          <button class="btn btn-edit" id="btnEditar" title="Editar perfil">
              <i class="fa-solid fa-pen-to-square"></i> Editar perfil
            </button>
        </article>
      </section>
      <!-- seção Meus dados -->
      <section class="meusdados">
        <h2 class="profile-section-title">Meus dados</h2>
        <div class="profile-data-grid">
          <article class="info-block">
            <div class="info-left">
              <h5>Nome completo</h5>
              <p id="info-nome"><?= htmlspecialchars($user['nome']) ?></p>
            </div>
            <div class="info-edit">
              <button class="btn btn-edit small-edit" data-field="nome"><i class="fa-solid fa-pen"></i></button>
            </div>
          </article>
          <article class="info-block">
            <div class="info-left">
              <h5>Telefone</h5>
              <p id="info-telefone"><?= htmlspecialchars($telefone_exibicao) ?></p>
            </div>
            <div class="info-edit">
              <button class="btn btn-edit small-edit" data-field="telefone"><i class="fa-solid fa-pen"></i></button>
            </div>
          </article>
          <article class="info-block">
            <div class="info-left">
              <h5>Endereço</h5>
              <p id="info-endereco"><?= htmlspecialchars($endereco_exibicao) ?></p>
            </div>
            <div class="info-edit">
              <button class="btn btn-edit small-edit" data-field="endereco"><i class="fa-solid fa-pen"></i></button>
            </div>
          </article>
          <article class="info-block">
            <div class="info-left">
              <h5>Preferências</h5>
              <p id="info-preferencias">Sem glúten: Não • Picante: Médio</p>
            </div>
            <div class="info-edit">
              <button class="btn btn-edit small-edit" data-field="preferencias"><i class="fa-solid fa-pen"></i></button>
            </div>
          </article>
        </div>
      </section>
    </section>

    <div class="profile-actions">
            <a href="cardapio.php" class="btn btn-primary" title="Fazer novo pedido">
              <i class="fa-solid fa-shopping-cart"></i> Fazer pedido
            </a>

            <a href="../backend/logout.php" class="btn btn-logout" title="Fazer logout">
              <i class="fa-solid fa-sign-out-alt"></i> Sair da conta</a>
          </div>
        </div>

    <!-- ==================== SEÇÃO MEUS PEDIDOS ==================== -->
    <section class="meus-pedidos-section">
      <h2 class="profile-section-title">Meus Pedidos</h2>

      <?php if (empty($pedidos)): ?>
        <div class="pedidos-vazio">
          <div style="font-size:48px;margin-bottom:12px;color:#ccc;">📦</div>
          <p style="color:#666;font-size:16px;font-weight:600;">Você ainda não fez nenhum pedido</p>
          <p style="color:#999;font-size:14px;margin-bottom:20px;">Comece agora e aproveite nossos deliciosos pratos!</p>
          <a href="cardapio.php" class="btn btn-primary" style="text-decoration:none;display:inline-block;">
            <i class="fa-solid fa-shopping-cart"></i> Ver cardápio
          </a>
        </div>
      <?php else: ?>
        <div class="pedidos-lista">
          <?php
            // preparar consulta de itens do pedido (reaproveitável)
            $stmtItensPedido = null;
            if (isset($pdo)) {
                $stmtItensPedido = $pdo->prepare("
                    SELECT hip.id_pedido, hip.id_produto, hip.quantidade, hip.preco_unitario,
                           p.nome, p.descricao, p.imagem
                    FROM historico_itens_pedido hip
                    LEFT JOIN produtos p ON p.id = hip.id_produto
                    WHERE hip.id_pedido = ?
                ");
            }
          ?>
          <?php foreach ($pedidos as $pedido): ?>
            <div class="pedido-card">
              <div class="pedido-header">
                <div class="pedido-id-data">
                  <h4 class="pedido-id">Pedido #<?= (int)$pedido['id'] ?></h4>
                  <span class="pedido-data">
                    <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?>
                  </span>
                </div>
                <div class="pedido-status">
                  <span class="badge-status" style="background-color: <?= getStatusColor($pedido['status_pedido']) ?>;">
                    <?= traduzirStatus($pedido['status_pedido']) ?>
                  </span>
                </div>
              </div>

              <div class="pedido-body">
                <div class="pedido-info-grid">
                  <div class="pedido-info-item">
                    <strong>Endereço:</strong>
                    <p>
                      <?php 
                        $endereco = $pedido['rua'] ?? 'Não informado';
                        if ($pedido['numero']) $endereco .= ', ' . $pedido['numero'];
                        if ($pedido['bairro']) $endereco .= ' - ' . $pedido['bairro'];
                        if ($pedido['cidade']) $endereco .= ', ' . $pedido['cidade'];
                        echo htmlspecialchars($endereco);
                      ?>
                    </p>
                  </div>

                  <div class="pedido-info-item">
                    <strong>Valor Total:</strong>
                    <p class="pedido-valor">R$ <?= number_format((float)$pedido['valor_total'], 2, ',', '.') ?></p>
                  </div>

                  <div class="pedido-info-item">
                    <strong>Forma de Pagamento:</strong>
                    <p><?= ucfirst($pedido['metodo_pagamento']) ?></p>
                  </div>
                </div>
              </div>

              <!-- ==== Produtos do pedido exibidos diretamente ==== -->
              <div style="background:#fff;border-radius:10px;padding:12px;border:1px solid rgba(0,0,0,0.06);margin-top:12px;">
                <h3 style="margin-top:0;">Produtos</h3>
                <?php
                  $itensPedido = [];
                  if ($stmtItensPedido) {
                      $stmtItensPedido->execute([(int)$pedido['id']]);
                      $itensPedido = $stmtItensPedido->fetchAll(PDO::FETCH_ASSOC);
                  }
                ?>
                <?php if (empty($itensPedido)): ?>
                    <p style="color:#666;">Nenhum item registrado para este pedido.</p>
                <?php else: ?>
                    <table style="width:100%;border-collapse:collapse;">
                        <thead>
                            <tr style="text-align:left;border-bottom:1px solid rgba(0,0,0,0.06);">
                                <th style="padding:8px 6px;">Produto</th>
                                <th style="padding:8px 6px;width:90px;">Qtd</th>
                                <th style="padding:8px 6px;width:120px;text-align:right;">Preço unit.</th>
                                <th style="padding:8px 6px;width:120px;text-align:right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($itensPedido as $it):
                                $q = (int)$it['quantidade'];
                                $pu = (float)$it['preco_unitario'];
                                $sub = $q * $pu;
                                $img = $it['imagem'] ?? '';
                                $imgPath = $img ? '/Beirute-delivery/img/produtos/' . $img : '/Beirute-delivery/img/produtos/placeholder.png';
                            ?>
                                <tr>
                                    <td style="padding:10px 6px;border-bottom:1px solid rgba(0,0,0,0.04);">
                                        <div style="display:flex;gap:10px;align-items:center;">
                                            <img src="<?= htmlspecialchars($imgPath) ?>" alt="" style="width:56px;height:56px;object-fit:cover;border-radius:8px;">
                                            <div>
                                                <div style="font-weight:600;"><?= htmlspecialchars($it['nome'] ?? 'Produto') ?></div>
                                                <?php if (!empty($it['descricao'])): ?>
                                                    <div style="font-size:13px;color:#666;max-width:420px;"><?= htmlspecialchars($it['descricao']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding:10px 6px;vertical-align:middle;"><?= $q ?></td>
                                    <td style="padding:10px 6px;vertical-align:middle;text-align:right;">R$ <?= number_format($pu,2,',','.') ?></td>
                                    <td style="padding:10px 6px;vertical-align:middle;text-align:right;">R$ <?= number_format($sub,2,',','.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
              </div>

              <div style="margin-top:14px;display:flex;gap:8px;justify-content:flex-end;">
                <?php if ($pedido['status_pedido'] !== 'cancelado' && $pedido['status_pedido'] !== 'entregue'): ?>
                  <button class="btn btn-pedido-cancelar" onclick="cancelarPedido(<?= (int)$pedido['id'] ?>)"> <i class="fa-solid fa-trash" aria-hidden="true" style="color:#c62828;"></i>
                   Cancelar 
                  </button>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <?php include('../includes/footer.php'); ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', function(){

  // dados PHP seguros para o JS
  const PHP_USER = <?php echo json_encode([
      'nome' => $user['nome'] ?? '',
      'email' => $user['email'] ?? '',
      'telefone' => $user['telefone'] ?? ''
  ], JSON_UNESCAPED_UNICODE); ?>;

  // --- Modal de edição de campo (pequeno) ---
  document.querySelectorAll('.small-edit').forEach(btn=>{
    btn.addEventListener('click', function(e){
      const field = this.dataset.field;
      if (!field) return;
      const el = document.getElementById('info-' + field) || null;
      const current = el ? el.innerText.trim() : '';
      document.getElementById('campo').value = field;
      document.getElementById('valor').value = current;
      document.getElementById('modal-editar').style.display = 'flex';
    });
  });

  const cancelBtn = document.getElementById('cancelEdit');
  if (cancelBtn) cancelBtn.addEventListener('click', ()=> document.getElementById('modal-editar').style.display = 'none');

  const formEditar = document.getElementById('form-editar');
  if (formEditar) {
    formEditar.addEventListener('submit', function(e){
      e.preventDefault();
      const campo = document.getElementById('campo').value;
      const valor = document.getElementById('valor').value.trim();
      if (!campo) return;

      fetch('../backend/atualizar_perfil.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json; charset=utf-8'},
        body: JSON.stringify({ campo, valor })
      })
      .then(r => r.json())
      .then(json => {
        if (json && json.success) {
          const el = document.getElementById('info-' + campo);
          if (el) el.innerText = valor;
          if (campo === 'nome') {
            const h2 = document.querySelector('.profile-info h2');
            if (h2) h2.innerText = valor;
          }
          document.getElementById('modal-editar').style.display = 'none';
          alert('Alteração salva.');
        } else {
          alert(json.message || 'Erro ao salvar.');
        }
      })
      .catch(err => {
        console.error(err);
        alert('Erro de conexão.');
      });
    });
  }

  // --- Botão "Editar perfil" abre modal completo ---
  const btnEditar = document.getElementById('btnEditar');
  if (btnEditar) {
    btnEditar.addEventListener('click', function(){
      openFullEditModal();
    });
  }

  function openFullEditModal(){
    let modal = document.getElementById('modal-full-edit');
    if (!modal) {
      modal = document.createElement('div');
      modal.id = 'modal-full-edit';
      modal.style = 'display:flex;position:fixed;inset:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:12000;';
      modal.innerHTML = `
        <div style="width:600px;max-width:96%;background:#fff;border-radius:12px;padding:18px;">
          <h3 style="margin:0 0 8px 0;color:var(--azul-escuro)">Editar perfil</h3>
          <form id="form-full-edit">
            <div style="display:grid;gap:10px;margin-top:12px;">
              <label>Nome
                <input name="nome" id="full-nome" type="text" style="padding:10px;border-radius:8px;border:1px solid #ddd;width:100%;">
              </label>
              <label>Email
                <input name="email" id="full-email" type="email" style="padding:10px;border-radius:8px;border:1px solid #ddd;width:100%;">
              </label>
              <label>Telefone
                <input name="telefone" id="full-telefone" type="text" style="padding:10px;border-radius:8px;border:1px solid #ddd;width:100%;">
              </label>

             <!-- Novo: Endereço -->
              <label>Endereço
                <textarea name="endereco" id="full-endereco" rows="3" style="padding:10px;border-radius:8px;border:1px solid #ddd;width:100%;resize:vertical;"></textarea>
              </label>

             <!-- Novo: Preferências -->
              <label>Preferências
                <textarea name="preferencias" id="full-preferencias" rows="2" style="padding:10px;border-radius:8px;border:1px solid #ddd;width:100%;resize:vertical;"></textarea>
              </label>

            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:18px;">
              <button type="button" id="full-cancel" class="btn" style="background:transparent;border:1px solid #ddd;">Cancelar</button>
              <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
          </form>
        </div>
      `;
      document.body.appendChild(modal);

      document.getElementById('full-cancel').addEventListener('click', ()=> modal.style.display = 'none');

      document.getElementById('form-full-edit').addEventListener('submit', function(e){
        e.preventDefault();

        // captura campos e envia um request por campo (backend espera {campo, valor})
        const updates = [];
        const nome = document.getElementById('full-nome').value.trim();
        const email = document.getElementById('full-email').value.trim();
        const telefone = document.getElementById('full-telefone').value.trim();

        if (nome && nome !== (document.getElementById('info-nome').innerText.trim())) updates.push({campo:'nome', valor:nome});
        if (email && email !== (PHP_USER.email || '')) updates.push({campo:'email', valor:email});
        if (telefone && telefone !== (PHP_USER.telefone || '')) updates.push({campo:'telefone', valor:telefone});

        if (updates.length === 0) {
          alert('Nenhuma alteração detectada.');
          return;
        }

        // enviar todas as atualizações em paralelo
        Promise.all(updates.map(u =>
          fetch('../backend/atualizar_perfil.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json; charset=utf-8'},
            body: JSON.stringify(u)
          }).then(r => r.json())
        ))
        .then(results => {
          const failed = results.find(r => !r || !r.success);
          if (failed) {
            alert(failed.message || 'Alguma atualização falhou.');
          } else {
            // recarrega para refletir mudanças na sessão
            location.reload();
          }
        })
        .catch(err => {
          console.error(err);
          alert('Erro de conexão.');
        });
      });
    }

    // preenche campos
    document.getElementById('full-nome').value = document.getElementById('info-nome').innerText.trim();
    document.getElementById('full-email').value = (PHP_USER.email || '');
    document.getElementById('full-telefone').value = (PHP_USER.telefone || '');

    modal.style.display = 'flex';
  }

  // --- Upload de foto ---
  const inputFoto = document.getElementById('inputFoto');
  const fotoBtnSpan = document.querySelector('#formFoto .small-edit');
  if (inputFoto) {
    inputFoto.addEventListener('change', function(){
      const file = this.files[0];
      if (!file) return;
      const allowed = ['image/jpeg','image/png','image/webp'];
      if (!allowed.includes(file.type)) { alert('Formato inválido. Use JPG/PNG/WEBP'); return; }
      if (file.size > 2 * 1024 * 1024) { alert('Arquivo muito grande. Máx 2MB'); return; }

      const fd = new FormData();
      fd.append('foto', file);

      if (fotoBtnSpan) { fotoBtnSpan.dataset.disabled = '1'; fotoBtnSpan.textContent = 'Enviando...'; }

      fetch('../backend/atualizar_perfil.php', {
        method: 'POST',
        body: fd
      })
      .then(r => r.json())
      .then(json => {
        if (fotoBtnSpan) { fotoBtnSpan.dataset.disabled = ''; fotoBtnSpan.textContent = 'Alterar foto'; }
        if (json && json.success) {
          const avatar = document.getElementById('avatarImg');
          avatar.src = `../img/foto-perfil/${json.imagem}?t=${Date.now()}`;
          alert('Foto atualizada com sucesso.');
        } else {
          alert(json.message || 'Erro ao enviar foto.');
        }
      })
      .catch(err => {
        console.error(err);
        if (fotoBtnSpan) { fotoBtnSpan.dataset.disabled = ''; fotoBtnSpan.textContent = 'Alterar foto'; }
        alert('Erro de conexão.');
      });
    });
  }

  // --- Cancelar pedido ---
  window.cancelarPedido = function(pedidoId) {
    if (!confirm('Tem certeza que deseja cancelar este pedido?')) return;
    fetch('../backend/cancelar_pedido.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json; charset=utf-8'},
      body: JSON.stringify({ pedido_id: pedidoId })
    })
    .then(r => r.json())
    .then(json => {
      if (json && json.success) {
        alert('✅ Pedido cancelado com sucesso!');
        location.reload();
      } else {
        alert('❌ ' + (json.message || 'Erro ao cancelar pedido'));
      }
    })
    .catch(err => {
      console.error(err);
      alert('❌ Erro de conexão');
    });
  };

  // tornar a imagem clicável/teclável para abrir o seletor (substitui o botão)
  const avatarImgEl = document.getElementById('avatarImg');
  if (avatarImgEl && inputFoto) {
      avatarImgEl.style.cursor = 'pointer';
      avatarImgEl.addEventListener('click', function () {
          inputFoto.click();
      });
      // acessibilidade: abrir com Enter / Espaço
      avatarImgEl.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
              e.preventDefault();
              inputFoto.click();
          }
      });
  }

});
</script>