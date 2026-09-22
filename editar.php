<?php

/*Página de edição de peças*/

require_once 'includes/auth.php';
Auth::exigirLogin('supervisor'); // Apenas supervisores podem editar

require_once 'includes/funcoes.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];
$peca = Funcoes::buscarPeca($id);

if (!$peca) {
    header('Location: index.php?msg=nao_encontrado');
    exit;
}

// Inicializar variáveis
$erro = '';
$sucesso = false;

// Processar atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Coletar dados do formulário
        $dados = [
            ':nome' => trim($_POST['nome']),
            ':descricao' => trim($_POST['descricao']),
            ':categoria_id' => intval($_POST['categoria_id']),
            ':quantidade' => intval($_POST['quantidade']), 
            ':estado' => trim($_POST['estado']),
            ':localizacao' => trim($_POST['localizacao']),
            ':numero_serie' => trim($_POST['numero_serie']),
            ':data_aquisicao' => trim($_POST['data_aquisicao']),
            ':observacoes' => trim($_POST['observacoes'])
        ];
        
        // Validar dados
        if (empty($dados[':nome'])) {
            throw new Exception("O nome da peça é obrigatório.");
        }
        
        if ($dados[':categoria_id'] <= 0) {
            throw new Exception("Selecione uma categoria válida.");
        }
        
        if ($dados[':quantidade'] < 0) {
            throw new Exception("A quantidade não pode ser negativa.");
        }
        
        // Verificar se a quantidade mudou para registrar no histórico
        $quantidadeAntiga = $peca['quantidade'];
        $quantidadeNova = $dados[':quantidade'];
        $responsavel = trim($_POST['responsavel'] ?? $_SESSION['usuario_nome']);
        
        // Atualizar a peça
        if (Funcoes::atualizarPeca($id, $dados)) {
            
            // Se quantidade mudou, registrar no histórico
            if ($quantidadeNova != $quantidadeAntiga) {
                $tipo = $quantidadeNova > $quantidadeAntiga ? 'entrada' : 'saida';
                $diferenca = abs($quantidadeNova - $quantidadeAntiga);
                $motivo = 'Ajuste via edição do cadastro';
                
                Funcoes::registrarMovimentacao(
                    $id,
                    $tipo,
                    $diferenca,
                    $responsavel,
                    "Quantidade alterada de {$quantidadeAntiga} para {$quantidadeNova}",
                    $motivo,
                    $quantidadeAntiga,
                    $quantidadeNova
                );
            }
            
            $sucesso = true;
            
            // Redirecionar após sucesso
            header('Location: visualizar.php?id=' . $id . '&msg=sucesso_atualizacao');
            exit;
            
        } else {
            throw new Exception("Erro ao atualizar peça no banco de dados.");
        }
        
    } catch (Exception $e) {
        $erro = $e->getMessage();
    }
}

$categorias = Funcoes::listarCategorias();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NOME; ?> - Editar a Peça</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h2><i class="fas fa-edit"></i> Editar a Peça</h2>
            <p class="subtitle">Atualize os dados da peça no inventário</p>
        </header>

        <nav class="navbar">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <div class="user-menu">
                <span class="user-info">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>
                </span>
 <a href="logout.php" style="margin-left: auto; background: #e74c3c; color: white;"><i class="fas fa-sign-out-alt"></i> Sair</a>	

   </div>
        </nav>

        <main>
            <?php if ($sucesso): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> Peça atualizada com sucesso! Redirecionando...
                </div>
            <?php endif; ?>

            <?php if (!empty($erro)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <div class="form-container">
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nome"><i class="fas fa-tag"></i> Nome da Peça *</label>
                            <input type="text" id="nome" name="nome" required 
                                   value="<?php echo htmlspecialchars($peca['nome']); ?>"
                                   placeholder="Ex: Fonte ATX 500W">
                        </div>

                        <div class="form-group">
                            <label for="categoria_id"><i class="fas fa-tags"></i> Categoria *</label>
                            <select id="categoria_id" name="categoria_id" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>" 
                                        <?php echo $categoria['id'] == $peca['categoria_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>


                        <div class="form-group">
                            <label for="quantidade"><i class="fas fa-boxes"></i> Quantidade *</label>
                            <input type="number" id="quantidade" name="quantidade" required 
                                   min="0" value="<?php echo $peca['quantidade']; ?>"
                                   onchange="atualizarDiferenca()">
                            <small>Quantidade atual em estoque</small>
                        </div>

                        <div class="form-group">
                            <label for="estado"><i class="fas fa-clipboard-check"></i> Estado *</label>
                            <select id="estado" name="estado" required>
                                <option value="">Selecione o estado</option>
                                <option value="novo" <?php echo $peca['estado'] == 'novo' ? 'selected' : ''; ?>>Novo</option>
                                <option value="usado" <?php echo $peca['estado'] == 'usado' ? 'selected' : ''; ?>>Usado</option>
                                <option value="reparado" <?php echo $peca['estado'] == 'reparado' ? 'selected' : ''; ?>>Reparado</option>
                                <option value="danificado" <?php echo $peca['estado'] == 'danificado' ? 'selected' : ''; ?>>Danificado</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="numero_serie"><i class="fas fa-barcode"></i> Número de Série</label>
                            <input type="text" id="numero_serie" name="numero_serie" 
                                   value="<?php echo htmlspecialchars($peca['numero_serie']); ?>"
                                   placeholder="Opcional">
                        </div>

                        <div class="form-group">
                            <label for="localizacao"><i class="fas fa-map-marker-alt"></i> Localização</label>
                            <input type="text" id="localizacao" name="localizacao" 
                                   value="<?php echo htmlspecialchars($peca['localizacao']); ?>"
                                   placeholder="Ex: Armário A, Prateleira 3">
                        </div>

                        <div class="form-group">
                            <label for="data_aquisicao"><i class="fas fa-calendar-alt"></i> Data de Aquisição</label>
                            <input type="date" id="data_aquisicao" name="data_aquisicao"
                                   value="<?php echo $peca['data_aquisicao']; ?>">
                        </div>

                        <div class="form-group full-width">
                            <label for="descricao"><i class="fas fa-align-left"></i> Descrição</label>
                            <textarea id="descricao" name="descricao" rows="3" 
                                      placeholder="Descreva a peça, modelo, características..."><?php echo htmlspecialchars($peca['descricao']); ?></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="observacoes"><i class="fas fa-sticky-note"></i> Observações</label>
                            <textarea id="observacoes" name="observacoes" rows="2" 
                                      placeholder="Observações adicionais..."><?php echo htmlspecialchars($peca['observacoes']); ?></textarea>
                        </div>

                        <!-- Campo para registrar quem alterou a quantidade -->
                        <div class="form-group">
                            <label for="responsavel"><i class="fas fa-user"></i> Responsável pela Alteração</label>
                            <input type="text" id="responsavel" name="responsavel" 
                                   value="<?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>"
                                   placeholder="Seu nome (para histórico de quantidade)"
                                   readonly
                                   style="background-color: #f8f9fa;">
                            <small>Preenchido automaticamente</small>
                        </div>
                    </div>

                    <!-- Preview da alteração de quantidade -->
                    <div id="preview-quantidade" class="preview-movimentacao" style="display: none;">
                        <h4><i class="fas fa-exchange-alt"></i> Alteração de Quantidade</h4>
                        <div id="preview-content">
                            <p>Digite uma nova quantidade para visualizar a alteração</p>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn-secondary" onclick="resetarQuantidade()">
                            <i class="fas fa-redo"></i> Limpar Alterações
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <footer>
            <p>SIGESEI <?php echo date('Y'); ?></p>
            <p class="footer-info">Usuário: <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?> | Nível: <?php echo $_SESSION['usuario_nivel']; ?></p>
        </footer>
    </div>

    <script>
    const quantidadeOriginal = <?php echo $peca['quantidade']; ?>;
    
    function atualizarDiferenca() {
        const quantidadeInput = document.getElementById('quantidade');
        const quantidadeAtual = parseInt(quantidadeInput.value) || 0;
        const previewDiv = document.getElementById('preview-quantidade');
        const previewContent = document.getElementById('preview-content');
        
        if (quantidadeAtual === quantidadeOriginal) {
            previewDiv.style.display = 'none';
            return;
        }
        
        previewDiv.style.display = 'block';
        
        const diferenca = quantidadeAtual - quantidadeOriginal;
        
        if (diferenca > 0) {
            previewContent.innerHTML = `
                <div class="preview-item">
                    <span>Quantidade Original:</span> <strong>${quantidadeOriginal}</strong>
                </div>
                <div class="preview-item">
                    <span>Nova Quantidade:</span> <strong>${quantidadeAtual}</strong>
                </div>
                <div class="preview-item">
                    <span>Diferença:</span> <strong style="color:green">+${diferenca}</strong>
                </div>
                <div class="alert info">
                    <i class="fas fa-info-circle"></i> 
                    Será registrada uma <strong>ENTRADA</strong> de ${diferenca} unidade(s) no histórico
                </div>
            `;
        } else {
            previewContent.innerHTML = `
                <div class="preview-item">
                    <span>Quantidade Original:</span> <strong>${quantidadeOriginal}</strong>
                </div>
                <div class="preview-item">
                    <span>Nova Quantidade:</span> <strong>${quantidadeAtual}</strong>
                </div>
                <div class="preview-item">
                    <span>Diferença:</span> <strong style="color:red">${diferenca}</strong>
                </div>
                <div class="alert info">
                    <i class="fas fa-info-circle"></i> 
                    Será registrada uma <strong>SAÍDA</strong> de ${Math.abs(diferenca)} unidade(s) no histórico
                </div>
            `;
        }
    }
    
    function resetarQuantidade() {
        document.getElementById('quantidade').value = quantidadeOriginal;
        document.getElementById('preview-quantidade').style.display = 'none';
    }
    
    // Inicializar quando a página carregar
    document.addEventListener('DOMContentLoaded', function() {
        // Verificar se a quantidade foi alterada manualmente na URL
        const quantidadeInput = document.getElementById('quantidade');
        if (parseInt(quantidadeInput.value) !== quantidadeOriginal) {
            atualizarDiferenca();
        }
        
        // Adicionar validação de formulário
        document.querySelector('form').addEventListener('submit', function(e) {
            const quantidade = parseInt(document.getElementById('quantidade').value) || 0;
            const quantidadeAlterada = quantidade !== quantidadeOriginal;
            
            if (quantidade < 0) {
                e.preventDefault();
                alert('A quantidade não pode ser negativa.');
                document.getElementById('quantidade').focus();
                return false;
            }
            
            return true;
        });
    });
    </script>
</body>
</html>
