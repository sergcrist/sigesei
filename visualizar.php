<?php

/* Página de detalhes de uma peça */

require_once __DIR__ . '/includes/funcoes.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id   = (int) $_GET['id'];
$peca = Funcoes::buscarPeca($id);

if (!$peca) {
    header('Location: index.php?msg=nao_encontrado');
    exit;
}
// Monta a consulta SQL para buscar os 10 últimos registros
$db  = Conexao::getConexao();
$sql = "SELECT * FROM historico
        WHERE peca_id = :id
        ORDER BY data_movimentacao DESC
        LIMIT 10";

$stmt = $db->prepare($sql);
$stmt->execute([':id' => $id]);
$historico = $stmt->fetchAll();

/* Função auxiliar que associa o estado físico da peça a uma classe CSS */

function classeDoEstado(string $estado): string
{
    return match ($estado) {
        'novo'       => 'estado-novo',
        'usado'      => 'estado-usado',
        'reparado'   => 'estado-reparado',
        'danificado' => 'estado-danificado',
        default      => '',
    };
}

/* Função auxiliar que retorna a classe CSS do tipo de movimentação */

function estiloDaMovimentacao(string $tipo): array
{
    return match ($tipo) {
        'entrada'  => ['tipo-entrada',  'fa-plus'],
        'saida'    => ['tipo-saida',    'fa-minus'],
        'uso'      => ['tipo-uso',      'fa-tools'],
        'descarte' => ['tipo-descarte', 'fa-trash'],
        'ajuste'   => ['tipo-ajuste',   'fa-adjust'],
        default    => ['',              ''],
    };
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NOME; ?> - Visualizar Peça</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
      <!-- Cabeçalho principal do topo da página -->
        <header>
            <h2><i class="fas fa-eye"></i> Visualizar Peça</h2>
            <p class="subtitle">Detalhes completos do item no inventário</p>
        </header>
	 <!-- Menu de navegação superior -->
        <nav class="navbar">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <a href="logout.php" style="margin-left: auto; background: #e74c3c; color: white;">
            <i class="fas fa-sign-out-alt"></i> Sair</a>
            </a>
        </nav>
        <!-- Conteúdo principal -->
        <main>
            <div class="detail-card">
                <div class="detail-header">
                    <h2><?php echo htmlspecialchars($peca['nome']); ?></h2>
                    <div class="detail-actions">
                        <span class="badge id-badge">ID: #<?php echo $peca['id']; ?></span>
                        <span class="estado-badge <?php echo classeDoEstado($peca['estado']); ?>">
                            <?php echo ucfirst($peca['estado']); ?>
                        </span>
                    </div>
                </div>
		 <!-- Grade com as informações detalhadas da peça -->
                <div class="detail-grid">
                    <div class="detail-item">
                        <h3><i class="fas fa-tags"></i> Categoria</h3>
                        <p><?php echo htmlspecialchars($peca['categoria_nome']); ?></p>
                    </div>

                    <div class="detail-item">
                        <h3><i class="fas fa-boxes"></i> Quantidade</h3>
                        <p class="quantidade-destaque"><?php echo $peca['quantidade']; ?> unidades</p>
                    </div>

                    <div class="detail-item">
                        <h3><i class="fas fa-barcode"></i> Número de Série</h3>
                        <p><?php echo $peca['numero_serie'] !== '' && $peca['numero_serie'] !== null
                                ? htmlspecialchars($peca['numero_serie'])
                                : 'Não informado'; ?></p>
                    </div>

                    <div class="detail-item">
                        <h3><i class="fas fa-map-marker-alt"></i> Localização</h3>
                        <p><?php echo $peca['localizacao'] !== '' && $peca['localizacao'] !== null
                                ? htmlspecialchars($peca['localizacao'])
                                : 'Não informada'; ?></p>
                    </div>

                    <div class="detail-item">
                        <h3><i class="fas fa-calendar-alt"></i> Data de Aquisição</h3>
                        <p><?php echo !empty($peca['data_aquisicao'])
                                ? date('d/m/Y', strtotime($peca['data_aquisicao']))
                                : 'Não informada'; ?></p>
                    </div>

                    <div class="detail-item">
                        <h3><i class="fas fa-calendar-plus"></i> Data de Cadastro</h3>
                        <p><?php echo date('d/m/Y H:i', strtotime($peca['data_cadastro'])); ?></p>
                    </div>

                    <?php if (!empty($peca['descricao'])): ?>
                        <div class="detail-item full-width">
                            <h3><i class="fas fa-align-left"></i> Descrição</h3>
                            <p><?php echo nl2br(htmlspecialchars($peca['descricao'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($peca['observacoes'])): ?>
                        <div class="detail-item full-width">
                            <h3><i class="fas fa-sticky-note"></i> Observações</h3>
                            <p><?php echo nl2br(htmlspecialchars($peca['observacoes'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                </div>
		 <!-- Seção de controle e histórico de movimentações da peça -->
                <div class="controle-estoque">
                    <h3><i class="fas fa-warehouse"></i> Controle de Estoque</h3>

                    <div class="controle-actions">
                        <a href="movimentar.php?id=<?php echo $id; ?>&tipo=entrada" class="btn-success">
                            <i class="fas fa-plus"></i> Entrada
                        </a>
                        <a href="movimentar.php?id=<?php echo $id; ?>&tipo=saida" class="btn-warning">
                            <i class="fas fa-minus"></i> Saída
                        </a>
                        <a href="excluir.php?id=<?php echo $id; ?>" class="btn-delete">
                            <i class="fas fa-trash"></i> Excluir
                        </a>
                    </div>

                    <h4><i class="fas fa-history"></i> Últimas Movimentações</h4>

                    <?php if (empty($historico)): ?>
                        <p class="empty-history">Nenhuma movimentação registrada ainda.</p>
                    <?php else: ?>
                        <div class="historico-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Tipo</th>
                                        <th>Quantidade</th>
                                        <th>Responsável</th>
                                        <th>Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                 <!-- Itera sobre o array de movimentações retornado do banco -->
                                    <?php foreach ($historico as $mov): ?>
                                        <?php [$corTipo, $iconeTipo] = estiloDaMovimentacao($mov['tipo_movimentacao']); ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($mov['data_movimentacao'])); ?></td>
                                            <td>
                                                <span class="tipo-movimentacao <?php echo $corTipo; ?>">
                                                    <i class="fas <?php echo $iconeTipo; ?>"></i>
                                                    <?php echo ucfirst($mov['tipo_movimentacao']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="quantidade-mov <?php echo $mov['tipo_movimentacao'] === 'entrada' ? 'entrada' : 'saida'; ?>">
                                                    <?php echo $mov['tipo_movimentacao'] === 'entrada' ? '+' : '-'; ?>
                                                    <?php echo $mov['quantidade']; ?>
                                                </span>
                                            </td>
                                            <!-- Nome do responsável e o motivo da ação -->
                                            <td><?php echo htmlspecialchars($mov['responsavel']); ?></td>
                                            <td><?php echo htmlspecialchars($mov['motivo']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Link para abrir a lista completa de movimentações -->
                        <a href="historico.php?id=<?php echo $id; ?>" class="btn-link">
                            <i class="fas fa-list"></i> Ver histórico completo
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <footer>
            <p>SIGESEI <?php echo date('Y'); ?></p>
        </footer>
    </div>
</body>
</html>

