<?php

/* Página de usuários*/

require_once 'includes/auth.php';
Auth::exigirLogin('admin');

require_once 'includes/funcoes.php';

// Obter dados do usuário atual
$usuarioAtual = Auth::usuarioAtual();

$db = Conexao::getConexao();

// Processar ações
$acao = $_GET['acao'] ?? '';
$id = intval($_GET['id'] ?? 0);
$mensagem = '';

// Listar usuários
$sql = "SELECT * FROM usuarios ORDER BY nome";
$stmt = $db->prepare($sql);
$stmt->execute();
$usuarios = $stmt->fetchAll();

// Processar ativação/desativação
if ($acao === 'ativar' || $acao === 'desativar') {
    if ($id > 0 && $id != $usuarioAtual['id']) {
        $novoStatus = $acao === 'ativar' ? 1 : 0;
        
        $sql = "UPDATE usuarios SET ativo = :ativo WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':ativo' => $novoStatus, ':id' => $id]);
        
        $mensagem = "Usuário " . ($acao === 'ativar' ? 'ativado' : 'desativado') . " com sucesso!";
        header('Location: usuarios.php?msg=' . urlencode($mensagem));
        exit;
    } else {
        $mensagem = "Não é possível modificar seu próprio usuário!";
    }
}

// Processar exclusão
if ($acao === 'excluir') {
    if ($id > 0 && $id != $usuarioAtual['id']) {
        $sql = "DELETE FROM usuarios WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        $mensagem = "Usuário excluído com sucesso!";
        header('Location: usuarios.php?msg=' . urlencode($mensagem));
        exit;
    } else {
        $mensagem = "Não é possível excluir seu próprio usuário!";
    }
}

// Processar edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'editar') {
        $id = intval($_POST['id']);
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $nivel = trim($_POST['nivel']);
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        // Se forneceu senha, atualizar também
        if (!empty($_POST['senha'])) {
            $senha_hash = password_hash($_POST['senha'], PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET nome = :nome, email = :email, nivel = :nivel, 
                    senha = :senha, ativo = :ativo WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':nivel' => $nivel,
                ':senha' => $senha_hash,
                ':ativo' => $ativo,
                ':id' => $id
            ]);
        } else {
            $sql = "UPDATE usuarios SET nome = :nome, email = :email, nivel = :nivel, 
                    ativo = :ativo WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':nivel' => $nivel,
                ':ativo' => $ativo,
                ':id' => $id
            ]);
        }
        
        $mensagem = "Usuário atualizado com sucesso!";
        header('Location: usuarios.php?msg=' . urlencode($mensagem));
        exit;
        
    } elseif ($_POST['acao'] === 'novo') {
        $nome = trim($_POST['nome']);
        $email = trim($_POST['email']);
        $senha = trim($_POST['senha']);
        $nivel = trim($_POST['nivel']);
        
        // Verificar se email já existe
        $sqlCheck = "SELECT id FROM usuarios WHERE email = :email";
        $stmtCheck = $db->prepare($sqlCheck);
        $stmtCheck->execute([':email' => $email]);
        
        if ($stmtCheck->fetch()) {
            $mensagem = "Erro: Este e-mail já está cadastrado!";
        } else {
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO usuarios (nome, email, senha, nivel, ativo, data_cadastro) 
                    VALUES (:nome, :email, :senha, :nivel, 1, NOW())";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':senha' => $senha_hash,
                ':nivel' => $nivel
            ]);
            
            $mensagem = "Usuário cadastrado com sucesso!";
            header('Location: usuarios.php?msg=' . urlencode($mensagem));
            exit;
        }
    }
}

// Mensagem de feedback
if (isset($_GET['msg'])) {
    $mensagem = $_GET['msg'];
}

// Buscar usuário para edição 
$usuarioEditar = null;
if ($acao === 'editar' && $id > 0) {
    $sql = "SELECT * FROM usuarios WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $id]);
    $usuarioEditar = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - SIGESEI</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }
        
        .modal h3 {
            margin-bottom: 1.5rem;
            color: #2c3e50;
            border-bottom: 2px solid #f8f9fa;
            padding-bottom: 10px;
        }
        
        .modal-close {
            float: right;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .modal-close:hover {
            color: #e74c3c;
        }
        
        .user-actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-small {
            padding: 4px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: none;
            cursor: pointer;
        }
        
        .btn-small.edit {
            background: #f39c12;
            color: white;
        }
        
        .btn-small.activate {
            background: #2ecc71;
            color: white;
        }
        
        .btn-small.deactivate {
            background: #e74c3c;
            color: white;
        }
        
        .btn-small.delete {
            background: #c0392b;
            color: white;
        }
        
        .btn-small:hover {
            opacity: 0.9;
        }
        
        .password-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        
        .usuario-info {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h2><i class="fas fa-users"></i> Gerenciar Usuários</h2>
            <p class="subtitle">Controle de acesso ao sistema</p>
        </header>

        <nav class="navbar">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
	<a href="logout.php" style="margin-left: auto; background: #e74c3c; color: white;"><i class="fas fa-sign-out-alt"></i> Sair </a>
	</nav>

        <main>
            <!-- Informações do usuário logado -->
            <div class="usuario-info">
                <p>
                    <strong><i class="fas fa-user"></i> Você está logado como:</strong> 
                    <?php echo htmlspecialchars($usuarioAtual['nome']); ?> 
                    <span style="color: #3498db;">(<?php echo $usuarioAtual['nivel']; ?>)</span>
                </p>
            </div>

            <!-- Mensagens de feedback -->
            <?php if (!empty($mensagem)): ?>
                <div class="alert <?php echo strpos($mensagem, 'Erro') !== false ? 'error' : 'success'; ?>">
                    <i class="fas <?php echo strpos($mensagem, 'Erro') !== false ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i> 
                    <?php echo htmlspecialchars($mensagem); ?>
                </div>
            <?php endif; ?>

            <!-- Formulário de Novo Usuário / Edição -->
            <div class="form-container">
                <h2>
                    <i class="fas <?php echo $usuarioEditar ? 'fa-user-edit' : 'fa-user-plus'; ?>"></i>
                    <?php echo $usuarioEditar ? 'Editar Usuário' : 'Novo Usuário'; ?>
                </h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="acao" value="<?php echo $usuarioEditar ? 'editar' : 'novo'; ?>">
                    <?php if ($usuarioEditar): ?>
                        <input type="hidden" name="id" value="<?php echo $usuarioEditar['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nome"><i class="fas fa-user"></i> Nome Completo *</label>
                            <input type="text" id="nome" name="nome" required 
                                   value="<?php echo $usuarioEditar ? htmlspecialchars($usuarioEditar['nome']) : ''; ?>"
                                   placeholder="Nome do usuário">
                        </div>

                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> E-mail *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo $usuarioEditar ? htmlspecialchars($usuarioEditar['email']) : ''; ?>"
                                   placeholder="usuario@empresa.com">
                        </div>

                        <div class="form-group">
                            <label for="nivel"><i class="fas fa-shield-alt"></i> Nível de Acesso *</label>
                            <select id="nivel" name="nivel" required>
                                <option value="">Selecione o nível</option>
                                <option value="tecnico" <?php echo ($usuarioEditar && $usuarioEditar['nivel'] == 'tecnico') ? 'selected' : ''; ?>>Técnico</option>
                                <option value="supervisor" <?php echo ($usuarioEditar && $usuarioEditar['nivel'] == 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                                <option value="admin" <?php echo ($usuarioEditar && $usuarioEditar['nivel'] == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="senha">
                                <i class="fas fa-lock"></i> 
                                <?php echo $usuarioEditar ? 'Nova Senha (deixe em branco para não alterar)' : 'Senha *'; ?>
                            </label>
                            <input type="password" id="senha" name="senha" 
                                   <?php echo !$usuarioEditar ? 'required' : ''; ?>
                                   placeholder="<?php echo $usuarioEditar ? 'Nova senha (opcional)' : 'Mínimo 6 caracteres'; ?>">
                        </div>
                        
                        <?php if ($usuarioEditar): ?>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="ativo" value="1" 
                                       <?php echo $usuarioEditar['ativo'] ? 'checked' : ''; ?>>
                                Usuário Ativo
                            </label>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <?php if ($usuarioEditar): ?>
                            <a href="usuarios.php" class="btn-secondary">
                                <i class="fas fa-times"></i> Cancelar Edição
                            </a>
                        <?php endif; ?>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> 
                            <?php echo $usuarioEditar ? 'Salvar Alterações' : 'Cadastrar Usuário'; ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lista de Usuários -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Usuários Cadastrados</h2>
                    <span class="badge"><?php echo count($usuarios); ?> usuários</span>
                </div>

                <div class="table-responsive">
                    <table class="pecas-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>E-mail</th>
                                <th>Nível</th>
                                <th>Status</th>
                                <th>Cadastro</th>
                                <th>Último Login</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td>#<?php echo $usuario['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($usuario['nome']); ?></strong>
                                    <?php if ($usuario['id'] == $usuarioAtual['id']): ?>
                                        <br><small><i class="fas fa-user-check"></i> Você</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                                <td>
                                    <span class="estado-badge 
                                        <?php echo $usuario['nivel'] == 'admin' ? 'estado-novo' : 
                                               ($usuario['nivel'] == 'supervisor' ? 'estado-reparado' : 'estado-usado'); ?>">
                                        <?php echo ucfirst($usuario['nivel']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="estado-badge <?php echo $usuario['ativo'] ? 'estado-novo' : 'estado-danificado'; ?>">
                                        <?php echo $usuario['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($usuario['data_cadastro'])); ?></td>
                                <td>
                                    <?php echo $usuario['ultimo_login'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_login'])) : 'Nunca'; ?>
                                </td>
                                <td class="user-actions">
                                    <?php if ($usuario['id'] != $usuarioAtual['id']): ?>
                                        <a href="usuarios.php?acao=editar&id=<?php echo $usuario['id']; ?>" 
                                           class="btn-small edit" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        
                                        <?php if ($usuario['ativo']): ?>
                                            <a href="usuarios.php?acao=desativar&id=<?php echo $usuario['id']; ?>" 
                                               class="btn-small deactivate" title="Desativar"
                                               onclick="return confirm('Tem certeza que deseja desativar este usuário?')">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="usuarios.php?acao=ativar&id=<?php echo $usuario['id']; ?>" 
                                               class="btn-small activate" title="Ativar"
                                               onclick="return confirm('Ativar este usuário?')">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="usuarios.php?acao=excluir&id=<?php echo $usuario['id']; ?>" 
                                           class="btn-small delete" title="Excluir"
                                           onclick="return confirm('ATENÇÃO: Esta ação não pode ser desfeita!\n\nTem certeza que deseja excluir permanentemente este usuário?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="btn-small" style="background: #3498db;" title="Usuário atual">
                                            <i class="fas fa-user"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <footer>
            <p>SIGESEI <?php echo date('Y'); ?></p>
            <p class="footer-info">
                Usuário: <?php echo htmlspecialchars($usuarioAtual['nome']); ?> | 
                Nível: <?php echo $usuarioAtual['nivel']; ?>
            </p>
        </footer>
    </div>

    <script>
    // Validação do formulário
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const nome = this.querySelector('#nome').value.trim();
                const email = this.querySelector('#email').value.trim();
                const nivel = this.querySelector('#nivel').value;
                const senha = this.querySelector('#senha').value;
                const isEdit = this.querySelector('input[name="acao"]').value === 'editar';
                
                if (!nome || !email || !nivel) {
                    e.preventDefault();
                    alert('Por favor, preencha todos os campos obrigatórios.');
                    return false;
                }
                
                if (!isEdit && senha.length < 6) {
                    e.preventDefault();
                    alert('A senha deve ter no mínimo 6 caracteres.');
                    return false;
                }
                
                if (isEdit && senha && senha.length < 6) {
                    e.preventDefault();
                    alert('A nova senha deve ter no mínimo 6 caracteres.');
                    return false;
                }
                
                // Validação de email simples
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    e.preventDefault();
                    alert('Por favor, insira um e-mail válido.');
                    return false;
                }
                
                return true;
            });
        });
        
        // Auto-focus no campo de nome ao editar
        <?php if ($usuarioEditar): ?>
        document.getElementById('nome').focus();
        <?php endif; ?>
    });
    </script>
</body>
</html>
