<?php

/* Página de exclusão de peças, apenas o supervisor e adminstrador podem excluir*/

require_once 'includes/auth.php';
Auth::exigirLogin('supervisor'); 

require_once 'includes/funcoes.php';

// verifica se o ID da peça foi passado corretamente via GET
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = intval($_GET['id']);
$peca = Funcoes::buscarPeca($id);

// se a peça não existir no banco, volta pro dashboard
if (!$peca) {
    header('Location: index.php?msg=nao_encontrado');
    exit;
}

$erro = '';

// testa se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $responsavel = trim($_POST['responsavel'] ?? $_SESSION['usuario_nome']);
    $numeroChamado = trim($_POST['numero_chamado'] ?? '');
    
    if (empty($responsavel)) {
        $erro = "Por favor, informe o responsável pela exclusão.";
    } else {
        try {
            $db = Conexao::getConexao();
            
            // limpa os registros históricos associados
            $sqlHistorico = "DELETE FROM historico WHERE peca_id = :id";
            $stmtHistorico = $db->prepare($sqlHistorico);
            $stmtHistorico->execute([':id' => $id]);
            
            // exclui a peça
            $sql = "DELETE FROM pecas WHERE id = :id";
            $stmt = $db->prepare($sql);
            
            if ($stmt->execute([':id' => $id])) {
                header('Location: index.php?msg=excluido_sucesso');
                exit;
            } else {
                $erro = "Erro ao excluir peça no banco de dados.";
            }
        } catch (PDOException $e) {
            $erro = "Erro de banco de dados: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NOME; ?> - Excluir Peça</title>
    
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h2><i class="fas fa-trash-alt"></i> Excluir Peça</h2>
            <p class="subtitle">Confirmação de exclusão de item do inventário</p>
        </header>

        <!-- topo/menu -->
        <nav class="navbar">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <div class="user-menu">
                <span class="user-info">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>
                </span>
                <a href="logout.php" style="margin-left: auto; background: #e74c3c; color: white;">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </nav>

        <main>
            <div class="confirmation-card">
                <div class="confirmation-icon" style="text-align: center; font-size: 3rem; color: #e74c3c; margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                
                <h2 style="text-align: center; color: #c0392b;">Confirmação de Exclusão</h2>
                
                <div class="confirmation-details">
                    <p style="text-align: center;">Você está prestes a excluir a seguinte peça do sistema:</p>
                    
                    <div class="item-to-delete" style="background: #fdf2f2; border: 1px solid #f5c6cb; border-radius: 8px; padding: 1.2rem; margin: 1.5rem 0;">
                        <h3 style="margin-top: 0; color: #721c24;"><?php echo htmlspecialchars($peca['nome']); ?></h3>
                        <div class="item-info">
                            <p><strong>ID:</strong> #<?php echo $peca['id']; ?></p>
                            <p><strong>Categoria:</strong> <?php echo htmlspecialchars($peca['categoria_nome'] ?? 'Não informada'); ?></p>
                            <p><strong>Quantidade em estoque:</strong> <?php echo $peca['quantidade']; ?> unidade(s)</p>
                            <p><strong>Número de Série:</strong> <?php echo !empty($peca['numero_serie']) ? htmlspecialchars($peca['numero_serie']) : 'Não informado'; ?></p>
                        </div>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-grid" style="grid-template-columns: 1fr 1fr; gap: 1rem; margin: 1.5rem 0; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                            <div class="form-group">
                                <label for="responsavel" style="font-weight: 600;">
                                    <i class="fas fa-user"></i> Responsável pela exclusão *
                                </label>
                                <input type="text" id="responsavel" name="responsavel" 
                                       value="<?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>" 
                                       readonly 
                                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background-color: #e9ecef;">
                                <small>Preenchido automaticamente</small>
                            </div>

                            <div class="form-group">
                                <label for="numero_chamado" style="font-weight: 600;">
                                    <i class="fas fa-file-alt"></i> Número do Chamado
                                </label>
                                <input type="text" id="numero_chamado" name="numero_chamado" 
                                       placeholder="Ex: CH-1234 (opcional)" 
                                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                                <small>Vincule a um chamado de descarte ou baixa</small>
                            </div>
                        </div>
                        
                        <?php if (!empty($erro)): ?>
                            <div class="alert error">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($erro); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="warning-message" style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                            <h4 style="margin-top: 0;"><i class="fas fa-exclamation-circle"></i> Atenção!</h4>
                            <ul style="margin-bottom: 0; padding-left: 1.2rem;">
                                <li>Esta ação removerá <strong>TODAS as <?php echo $peca['quantidade']; ?> unidades</strong> e os históricos associados a este item.</li>
                                <li>Esta operação é permanente e não poderá ser desfeita.</li>
                            </ul>
                        </div>
                        
                        <div class="confirmation-actions" style="display: flex; gap: 1rem; justify-content: flex-end;">
                            <a href="visualizar.php?id=<?php echo $id; ?>" class="btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn-delete" style="background: #dc3545; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 4px; cursor: pointer;" onclick="return confirmarOperacao()">
                                <i class="fas fa-check"></i> Confirmar Exclusão
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <footer>
            <p>SIGESEI <?php echo date('Y'); ?></p>
            <p class="footer-info">
                Usuário: <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?> | 
                Nível: <?php echo htmlspecialchars($_SESSION['usuario_nivel'] ?? ''); ?>
            </p>
        </footer>
    </div>

    <script>
    function confirmarOperacao() {
        const responsavel = document.getElementById('responsavel').value;
        
        if (!responsavel.trim()) {
            alert('Por favor, informe o responsável pela exclusão.');
            return false;
        }
        
        return confirm('ATENÇÃO: Esta ação removerá COMPLETAMENTE a peça do sistema. Deseja realmente confirmar a exclusão?');
    }
    </script>
</body>
</html>
