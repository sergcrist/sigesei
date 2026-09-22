<?php

/* Página de histórico das peças */


require_once 'includes/auth.php';
// exige que o usuario esteja logado para visualizar
Auth::exigirLogin();

require_once 'includes/funcoes.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$peca = Funcoes::buscarPeca($id);

if (!$peca) {
    header('Location: index.php?msg=nao_encontrada');
    exit;
}

$db = Conexao::getConexao();
$sql = "SELECT * FROM historico WHERE peca_id = :id ORDER BY data_movimentacao DESC";
$stmt = $db->prepare($sql);
$stmt->execute([':id' => $id]);
$historico = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NOME; ?> - Histórico Completo</title>

    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h2><i class="fas fa-history"></i> Histórico de Movimentações</h2>
            <p class="subtitle">Registro completo de todas as movimentações desta peça</p>
        </header>

        <!-- topo/menu -->
        <nav class="navbar">
            <a href="visualizar.php?id=<?php echo $id; ?>"><i class="fas fa-arrow-left"></i> Voltar para Peça</a>
            <a href="movimentar.php?id=<?php echo $id; ?>"><i class="fas fa-exchange-alt"></i> Nova Movimentação</a>
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
            <div class="peca-header">
                <h2><?php echo htmlspecialchars($peca['nome']); ?></h2>
                <p>Estoque atual: <strong><?php echo $peca['quantidade']; ?> unidade(s)</strong></p>
            </div>

            <?php if (empty($historico)): ?>
                <div class="empty-state">
                    <i class="fas fa-history fa-3x"></i>
                    <h3>Nenhuma movimentação registrada</h3>
                    <p>Esta peça ainda não teve nenhuma movimentação de estoque.</p>
                    <a href="movimentar.php?id=<?php echo $id; ?>" class="btn-primary">
                        <i class="fas fa-exchange-alt"></i> Realizar Primeira Movimentação
                    </a>
                </div>
            <?php else: ?>
                <div class="historico-completo">
                    <div class="historico-summary">
                        <p><strong><?php echo count($historico); ?></strong> movimentação(ões) registrada(s)</p>
                        <div class="summary-stats">
                            <?php
                            $entradas = 0;
                            $saidas = 0;
                            foreach ($historico as $mov) {
                                if ($mov['tipo_movimentacao'] == 'entrada') {
                                    $entradas += $mov['quantidade'];
                                } else {
                                    $saidas += $mov['quantidade'];
                                }
                            }
                            ?>
                            <div class="stat">
                                <span class="stat-label">Total Entradas:</span>
                                <span class="stat-value positivo">+<?php echo $entradas; ?></span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Total Saídas:</span>
                                <span class="stat-value negativo">-<?php echo $saidas; ?></span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Saldo Líquido:</span>
                                <span class="stat-value <?php echo ($entradas - $saidas) >= 0 ? 'positivo' : 'negativo'; ?>">
                                    <?php echo ($entradas - $saidas) >= 0 ? '+' : ''; ?><?php echo $entradas - $saidas; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="historico-table">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Chamado</th>
                                    <th>Tipo</th>
                                    <th>Quantidade</th>
                                    <th>Estoque Anterior</th>
                                    <th>Novo Estoque</th>
                                    <th>Responsável</th>
                                    <th>Motivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico as $mov): 
                                    $corTipo = '';
                                    $iconeTipo = '';
                                    switch($mov['tipo_movimentacao']) {
                                        case 'entrada': $corTipo = 'tipo-entrada'; $iconeTipo = 'fa-plus'; break;
                                        case 'saida': $corTipo = 'tipo-saida'; $iconeTipo = 'fa-minus'; break;
                                        case 'uso': $corTipo = 'tipo-uso'; $iconeTipo = 'fa-tools'; break;
                                        case 'descarte': $corTipo = 'tipo-descarte'; $iconeTipo = 'fa-trash'; break;
                                        case 'ajuste': $corTipo = 'tipo-ajuste'; $iconeTipo = 'fa-adjust'; break;
                                        default: $corTipo = 'tipo-outro'; $iconeTipo = 'fa-exchange-alt'; break;
                                    }
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($mov['data_movimentacao'])); ?></td>
                                    <td>
                                        <?php if (!empty($mov['numero_chamado'])): ?>
                                            <span class="badge-chamado" style="background: #e9ecef; padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 0.85rem;">
                                                <i class="fas fa-file-alt"></i> <?php echo htmlspecialchars($mov['numero_chamado']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #999;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="tipo-movimentacao <?php echo $corTipo; ?>">
                                            <i class="fas <?php echo $iconeTipo; ?>"></i>
                                            <?php echo ucfirst($mov['tipo_movimentacao']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="quantidade-mov <?php echo $mov['tipo_movimentacao'] == 'entrada' ? 'entrada' : 'saida'; ?>">
                                            <?php echo $mov['tipo_movimentacao'] == 'entrada' ? '+' : '-'; ?>
                                            <?php echo $mov['quantidade']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $mov['quantidade_anterior']; ?></td>
                                    <td><?php echo $mov['quantidade_nova']; ?></td>
                                    <td><?php echo htmlspecialchars($mov['responsavel']); ?></td>
                                    <td><?php echo htmlspecialchars($mov['motivo']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </main>

        <footer>
            <p>SIGESEI <?php echo date('Y'); ?></p>
            <p class="footer-info">
                Usuário: <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?> | 
                Nível: <?php echo htmlspecialchars($_SESSION['usuario_nivel'] ?? ''); ?>
            </p>
        </footer>
    </div>
</body>
</html>
