<?php

/* Página de movimentação de estoque de peças  */


require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';


Auth::exigirLogin('tecnico');

// Obtém os dados do usuário autenticado para registro de responsável
$usuario = Auth::usuarioAtual();

// Recebe o ID da peça via parâmetro GET
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$peca = Funcoes::buscarPeca($id);

// Redireciona se a peça não for encontrada no banco de dados
if (!$peca) {
    header('Location: index.php?msg=nao_encontrada');
    exit;
}

$erro = '';
$sucesso = '';

// Processamento da movimentação via formulário POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // tratamento dos dados de entrada
    $tipo = trim($_POST['tipo'] ?? '');
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $motivo = trim($_POST['motivo'] ?? '');
    $numeroChamado = trim($_POST['numero_chamado'] ?? ''); // Número do chamado vinculado

    // Validações dos campos obrigatórios e das regras de negócio
    if (empty($tipo) || $quantidade <= 0 || empty($motivo)) {
        $erro = 'Por favor, preencha todos os campos obrigatórios corretamente.';
    } elseif (in_array($tipo, ['saida', 'uso', 'descarte']) && $quantidade > $peca['quantidade']) {
        $erro = 'A quantidade solicitada é maior do que o estoque disponível atualmente (' . $peca['quantidade'] . ' unidade(s)).';
    } else {
        // Registra a movimentação e atualiza o saldo do estoque
        $resultado = Funcoes::registrarMovimentacao(
            $id,
            $tipo,
            $quantidade,
            $motivo,
            $usuario['nome'],
            $numeroChamado
        );

        if ($resultado) {
            header('Location: visualizar.php?id=' . $id . '&msg=movimentacao_sucesso');
            exit;
        } else {
            $erro = 'Ocorreu um erro ao registrar a movimentação no banco de dados.';
        }
    }
}

// Preenche o tipo inicial caso seja passado pela URL (ex: movimentar.php?id=1&tipo=saida)
$tipoPadrao = $_GET['tipo'] ?? 'saida';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NOME; ?> - Movimentar Peça</title>

    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Cabeçalho da aplicação -->
        <header>
            <h2><i class="fas fa-exchange-alt"></i> Movimentação de Estoque</h2>
            <p class="subtitle">Registre entradas, saídas ou descartes do item</p>
        </header>

        <!-- Menu de Navegação -->
        <nav class="navbar">
            <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="visualizar.php?id=<?php echo $id; ?>"><i class="fas fa-arrow-left"></i> Voltar para Peça</a>
            <a href="historico.php?id=<?php echo $id; ?>"><i class="fas fa-history"></i> Histórico</a>
            
            <div class="user-menu">
                <span class="user-info">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($usuario['nome']); ?>
                </span>
                <a href="logout.php" style="margin-left: auto; background: #e74c3c; color: white;">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </nav>

        <main>
            <!-- Exibição de alertas de erro -->
            <?php if (!empty($erro)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <!-- Informações resumidas do item selecionado -->
            <div class="peca-header">
                <h2><?php echo htmlspecialchars($peca['nome']); ?></h2>
                <p>Estoque atual: <strong><?php echo $peca['quantidade']; ?> unidade(s)</strong></p>
            </div>

            <!-- Formulário de Entrada/Saída/Uso -->
            <div class="form-container">
                <form method="POST" action="">
                    <!-- Tipo de movimentação -->
                    <div class="form-group">
                        <label for="tipo">Tipo de Movimentação *</label>
                        <select name="tipo" id="tipo" required class="form-control">
                            <option value="saida" <?php echo $tipoPadrao === 'saida' ? 'selected' : ''; ?>>Saída (Retirada de Estoque)</option>
                            <option value="entrada" <?php echo $tipoPadrao === 'entrada' ? 'selected' : ''; ?>>Entrada (Adicionar ao Estoque)</option>
                            <option value="uso" <?php echo $tipoPadrao === 'uso' ? 'selected' : ''; ?>>Uso Interno / Manutenção</option>
                            <option value="descarte" <?php echo $tipoPadrao === 'descarte' ? 'selected' : ''; ?>>Descarte (Peça Danificada)</option>
                            <option value="ajuste" <?php echo $tipoPadrao === 'ajuste' ? 'selected' : ''; ?>>Ajuste de Inventário</option>
                        </select>
                    </div>

                    <!-- Quantidade a ser alterada -->
                    <div class="form-group">
                        <label for="quantidade">Quantidade *</label>
                        <input type="number" name="quantidade" id="quantidade" min="1" value="1" required class="form-control">
                    </div>

                    <!-- Campo: Número do Chamado -->
                    <div class="form-group">
                        <label for="numero_chamado">
                            <i class="fas fa-file-alt"></i> Número do Chamado / Ticket (Opcional)
                        </label>
                        <input 
                            type="text" 
                            name="numero_chamado" 
                            id="numero_chamado" 
                            class="form-control" 
                            placeholder="Ex: CH-2026-8941" 
                            value="<?php echo htmlspecialchars($_POST['numero_chamado'] ?? ''); ?>">
                        <small class="form-text">Informe o número do chamado do suporte atrelado a este movimento.</small>
                    </div>

                    <!-- Motivo -->
                    <div class="form-group">
                        <label for="motivo">Motivo / Observações *</label>
                        <textarea name="motivo" id="motivo" rows="4" required class="form-control" placeholder="Descreva a razão desta movimentação ou detalhes do chamado..."><?php echo htmlspecialchars($_POST['motivo'] ?? ''); ?></textarea>
                    </div>

                    <!-- Botões de ação -->
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Confirmar Movimentação
                        </button>
                        <a href="visualizar.php?id=<?php echo $id; ?>" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </main>


        <footer>
            <p>SIGESEI <?php echo date('Y'); ?></p>
            <p class="footer-info">
                Usuário: <?php echo htmlspecialchars($usuario['nome']); ?> | 
                Nível: <?php echo htmlspecialchars($usuario['nivel'] ?? ''); ?>
            </p>
        </footer>
    </div>
</body>
</html>
