<?php

/* Página de movimentação de estoque de peças  */

require_once 'includes/funcoes.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tipo_param = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$peca = Funcoes::buscarPeca($id);

if (!$peca) {
    header('Location: index.php?msg=nao_encontrada');
    exit;
}

// Processar movimentação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo_movimentacao'];
    $quantidade = intval($_POST['quantidade']);
    $responsavel = $_POST['responsavel'];
    $motivo = $_POST['motivo'];
    $observacoes = $_POST['observacoes'];
    $numero_chamado = $_POST['numero_chamado'] ?? '';
    
    $nova_quantidade = $peca['quantidade'];
    $erro = '';
    
    // Calcular nova quantidade baseada no tipo
    switch ($tipo) {
        case 'entrada':
            $nova_quantidade += $quantidade;
            break;
        case 'saida':
        case 'uso':
        case 'descarte':
            if ($quantidade > $peca['quantidade']) {
                $erro = "Quantidade insuficiente em estoque! Disponível: {$peca['quantidade']}";
            } else {
                $nova_quantidade -= $quantidade;
            }
            break;
        case 'ajuste':
            $nova_quantidade = $quantidade;
            break;
    }
    
    if (empty($erro)) {
        // Atualizar quantidade da peça
        $db = Conexao::getConexao();
        $sql = "UPDATE pecas SET quantidade = :quantidade WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':quantidade' => $nova_quantidade, ':id' => $id]);
        
        // Registrar no histórico
        $sql = "INSERT INTO historico (peca_id, tipo_movimentacao, quantidade, 
                quantidade_anterior, quantidade_nova, responsavel, motivo, observacoes, numero_chamado) 
                VALUES (:peca_id, :tipo, :quantidade, :quantidade_anterior, 
                :quantidade_nova, :responsavel, :motivo, :observacoes, :numero_chamado)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':peca_id' => $id,
            ':tipo' => $tipo,
            ':quantidade' => $quantidade,
            ':quantidade_anterior' => $peca['quantidade'],
            ':quantidade_nova' => $nova_quantidade,
            ':responsavel' => $responsavel,
            ':motivo' => $motivo,
            ':observacoes' => $observacoes,
            ':numero_chamado' => $numero_chamado
        ]);
        
        header("Location: visualizar.php?id=$id&msg=movimentacao_sucesso");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGESEI - Movimentar Estoque</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .movimentacao-container {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .peca-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 1rem;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-label {
            font-weight: 600;
            color: #7f8c8d;
        }
        
        .info-value {
            color: #2c3e50;
        }
        
        .quantidade-destaque {
            font-size: 1.3rem;
            font-weight: bold;
            color: #3498db !important;
        }
        
        .preview-movimentacao {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }
        
        .preview-item {
            display: flex;
            justify-content: space-between;
            margin: 10px 0;
            padding: 10px;
            background: white;
            border-radius: 4px;
        }
        
        .descricao-tipo {
            display: block;
            margin-top: 5px;
            color: #7f8c8d;
            font-style: italic;
        }
        
        .chamado-requerido {
            color: #e74c3c;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h2><i class="fas fa-exchange-alt"></i> Movimentar Estoque</h2>
	    <p class="subtitle">Controle de entrada, saída e ajuste de peças</p>
        </header>

        <nav class="navbar">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
	    <a href="logout.php" style="margin-left: auto; background: #e74c3c; color: white;"><i class="fas fa-sign-out-alt"></i> Sair</a>
</nav>

        <main>
            <div class="movimentacao-container">
                <div class="peca-info">
                    <h3> <?php echo htmlspecialchars($peca['nome']); ?></h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Categoria:</span>
                            <span class="info-value"><?php echo htmlspecialchars($peca['categoria_nome']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Estoque Atual:</span>
                            <span class="info-value quantidade-destaque"><?php echo $peca['quantidade']; ?> unidades</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Número de Série:</span>
                            <span class="info-value"><?php echo !empty($peca['numero_serie']) ? htmlspecialchars($peca['numero_serie']) : 'Não informado'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Localização:</span>
                            <span class="info-value"><?php echo !empty($peca['localizacao']) ? htmlspecialchars($peca['localizacao']) : 'Não informada'; ?></span>
                        </div>
                    </div>
                </div>

                <?php if (isset($erro)): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $erro; ?>
                    </div>
                <?php endif; ?>

                <div class="movimentacao-form">
                    <form method="POST" action="">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="tipo_movimentacao"><i class="fas fa-cogs"></i> Tipo de Movimentação *</label>
                                <select id="tipo_movimentacao" name="tipo_movimentacao" required onchange="atualizarCampos()">
                                    <option value="">Selecione...</option>
                                    <option value="entrada" <?php echo $tipo_param == 'entrada' ? 'selected' : ''; ?>>Entrada (Adicionar ao estoque)</option>
                                    <option value="saida" <?php echo $tipo_param == 'saida' ? 'selected' : ''; ?>>Saída (Retirada para uso)</option>
                                    <option value="uso" <?php echo $tipo_param == 'uso' ? 'selected' : ''; ?>>Uso em Manutenção</option>
                                    <option value="descarte" <?php echo $tipo_param == 'descarte' ? 'selected' : ''; ?>>Descarte/Danificado</option>
                                    <option value="ajuste" <?php echo $tipo_param == 'ajuste' ? 'selected' : ''; ?>>Ajuste de Estoque</option>
                                </select>
                                <small id="tipo_descricao" class="descricao-tipo"></small>
                            </div>

                            <div class="form-group">
                                <label for="quantidade"><i class="fas fa-boxes"></i> Quantidade *</label>
                                <input type="number" id="quantidade" name="quantidade" required 
                                       min="1" value="1" oninput="atualizarPreview()">
                            </div>

                            <div class="form-group">
                                <label for="responsavel"><i class="fas fa-user"></i> Responsável *</label>
                                <input type="text" id="responsavel" name="responsavel" required 
                                       placeholder="Seu nome">
                            </div>

                            <div class="form-group">
                                <label for="motivo"><i class="fas fa-question-circle"></i> Motivo *</label>
                                <input type="text" id="motivo" name="motivo" required 
                                       placeholder="Ex: Manutenção preventiva, novo lote...">
                            </div>

                            
                            <div class="form-group">
                                <label for="numero_chamado">
                                    <i class="fas fa-file-alt"></i> Número do Chamado
                                    <span id="chamado_obrigatorio" class="chamado-requerido" style="display: none;"> *</span>
                                </label>
                                <input type="text" id="numero_chamado" name="numero_chamado" 
                                       placeholder="Número do chamado de serviço">
                                <small id="chamado_descricao" class="descricao-tipo">
                                    Obrigatório para retiradas e usos
                                </small>
                            </div>

                            <div class="form-group full-width">
                                <label for="observacoes"><i class="fas fa-sticky-note"></i> Observações</label>
                                <textarea id="observacoes" name="observacoes" rows="3" 
                                          placeholder="Detalhes adicionais..."></textarea>
                            </div>
                        </div>

                        <div class="preview-movimentacao">
                            <h4><i class="fas fa-calculator"></i> Resumo da Movimentação</h4>
                            <div id="preview-content">
                                <p>Selecione um tipo de movimentação para visualizar o resultado</p>
                            </div>
                        </div>

                        <div class="form-actions">
                            <a href="visualizar.php?id=<?php echo $id; ?>" class="btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-check"></i> Confirmar Movimentação
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <footer>
            <p>SIGESEI <?php echo date('Y'); ?></p>
        </footer>
    </div>

    <script>
    function atualizarCampos() {
        const tipo = document.getElementById('tipo_movimentacao').value;
        const chamadoInput = document.getElementById('numero_chamado');
        const chamadoObrigatorio = document.getElementById('chamado_obrigatorio');
        const chamadoDescricao = document.getElementById('chamado_descricao');
        
        // Atualizar descrição do tipo
        let descricaoText = '';
        switch(tipo) {
            case 'entrada':
                descricaoText = 'Adicionar peças ao estoque';
                chamadoObrigatorio.style.display = 'none';
                chamadoDescricao.textContent = 'Opcional para entradas';
                chamadoInput.required = false;
                break;
            case 'saida':
            case 'uso':
                descricaoText = 'Retirar peças para uso em manutenção';
                chamadoObrigatorio.style.display = 'inline';
                chamadoDescricao.textContent = 'OBRIGATÓRIO para controle';
                chamadoInput.required = true;
                break;
            case 'descarte':
                descricaoText = 'Descartar peças danificadas/defeituosas';
                chamadoObrigatorio.style.display = 'inline';
                chamadoDescricao.textContent = 'OBRIGATÓRIO para registro';
                chamadoInput.required = true;
                break;
            case 'ajuste':
                descricaoText = 'Ajustar manualmente o estoque (correção)';
                chamadoObrigatorio.style.display = 'none';
                chamadoDescricao.textContent = 'Opcional para ajustes';
                chamadoInput.required = false;
                break;
            default:
                descricaoText = '';
                chamadoObrigatorio.style.display = 'none';
                chamadoDescricao.textContent = 'Obrigatório para retiradas e usos';
                chamadoInput.required = false;
        }
        
        document.getElementById('tipo_descricao').textContent = descricaoText;
        atualizarPreview();
    }
    
    function atualizarPreview() {
        const tipo = document.getElementById('tipo_movimentacao').value;
        const quantidade = parseInt(document.getElementById('quantidade').value) || 1;
        const estoqueAtual = <?php echo $peca['quantidade']; ?>;
        const preview = document.getElementById('preview-content');
        const numeroChamado = document.getElementById('numero_chamado').value;
        
        let previewHTML = '';
        
        switch(tipo) {
            case 'entrada':
                previewHTML = `
                    <div class="preview-item">
                        <span>Estoque Atual:</span> <strong>${estoqueAtual} unidades</strong>
                    </div>
                    <div class="preview-item">
                        <span>Quantidade a Adicionar:</span> <strong style="color:green">+${quantidade}</strong>
                    </div>
                    <div class="preview-item">
                        <span>Novo Estoque:</span> <strong style="color:blue">${estoqueAtual + quantidade} unidades</strong>
                    </div>
                `;
                break;
                
            case 'saida':
            case 'uso':
                const novoEstoqueSaida = estoqueAtual - quantidade;
                previewHTML = `
                    <div class="preview-item">
                        <span>Estoque Atual:</span> <strong>${estoqueAtual} unidades</strong>
                    </div>
                    <div class="preview-item">
                        <span>Quantidade a Retirar:</span> <strong style="color:red">-${quantidade}</strong>
                    </div>
                    <div class="preview-item">
                        <span>Novo Estoque:</span> <strong style="color:${novoEstoqueSaida < 0 ? 'red' : 'blue'}">${novoEstoqueSaida} unidades</strong>
                    </div>
                    ${numeroChamado ? `<div class="preview-item">
                        <span>Número do Chamado:</span> <strong>${numeroChamado}</strong>
                    </div>` : ''}
                    ${novoEstoqueSaida < 0 ? '<div class="alert error">⚠️ Estoque ficará negativo!</div>' : ''}
                `;
                break;
                
            case 'descarte':
                const novoEstoqueDescarte = estoqueAtual - quantidade;
                previewHTML = `
                    <div class="preview-item">
                        <span>Estoque Atual:</span> <strong>${estoqueAtual} unidades</strong>
                    </div>
                    <div class="preview-item">
                        <span>Quantidade a Descartar:</span> <strong style="color:orange">-${quantidade}</strong>
                    </div>
                    <div class="preview-item">
                        <span>Novo Estoque:</span> <strong style="color:blue">${estoqueAtual - quantidade} unidades</strong>
                    </div>
                    ${numeroChamado ? `<div class="preview-item">
                        <span>Número do Chamado:</span> <strong>${numeroChamado}</strong>
                    </div>` : ''}
                `;
                break;
                
            case 'ajuste':
                const diferenca = quantidade - estoqueAtual;
                previewHTML = `
                    <div class="preview-item">
                        <span>Estoque Atual:</span> <strong>${estoqueAtual} unidades</strong>
                    </div>
                    <div class="preview-item">
                        <span>Novo Valor do Estoque:</span> <input type="number" value="${quantidade}" onchange="document.getElementById('quantidade').value = this.value" min="0" style="width: 80px; padding: 5px;">
                    </div>
                    <div class="preview-item">
                        <span>Diferença:</span> <strong style="color:${diferenca > 0 ? 'green' : 'red'}">${diferenca > 0 ? '+' : ''}${diferenca}</strong>
                    </div>
                `;
                break;
                
            default:
                previewHTML = '<p>Selecione um tipo de movimentação para visualizar o resultado</p>';
        }
        
        preview.innerHTML = previewHTML;
    }
    
    // Atualizar quando quantidade mudar
    document.getElementById('quantidade').addEventListener('input', atualizarPreview);
    document.getElementById('numero_chamado').addEventListener('input', atualizarPreview);
    
    // Validação do formulário
    document.querySelector('form').addEventListener('submit', function(e) {
        const tipo = document.getElementById('tipo_movimentacao').value;
        const quantidade = parseInt(document.getElementById('quantidade').value) || 0;
        const responsavel = document.getElementById('responsavel').value.trim();
        const motivo = document.getElementById('motivo').value.trim();
        const numeroChamado = document.getElementById('numero_chamado').value.trim();
        
        // Validações básicas
        if (!tipo) {
            e.preventDefault();
            alert('Selecione o tipo de movimentação.');
            return false;
        }
        
        if (quantidade <= 0) {
            e.preventDefault();
            alert('Quantidade deve ser maior que zero.');
            return false;
        }
        
        if (!responsavel) {
            e.preventDefault();
            alert('Informe o responsável.');
            return false;
        }
        
        if (!motivo) {
            e.preventDefault();
            alert('Informe o motivo da movimentação.');
            return false;
        }
        
        // Validações específicas por tipo
        if (tipo === 'saida' || tipo === 'uso' || tipo === 'descarte') {
            if (quantidade > <?php echo $peca['quantidade']; ?>) {
                e.preventDefault();
                alert(`Quantidade insuficiente! Disponível: ${<?php echo $peca['quantidade']; ?>}`);
                return false;
            }
            
            if (!numeroChamado && confirm('Número do chamado não informado. Deseja continuar sem registro de chamado?')) {
             
                return true;
            } else if (!numeroChamado) {
                e.preventDefault();
                document.getElementById('numero_chamado').focus();
                return false;
            }
        }
        
        return true;
    });
    
    // Inicializar
    if (document.getElementById('tipo_movimentacao').value) {
        atualizarCampos();
    } else {
        atualizarPreview();
    }
    </script>
</body>
</html>

