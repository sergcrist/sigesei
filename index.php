<?php

/* Página principal do sistema - Dashboard */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';

// Exige autenticação (nível mínimo: técnico)
Auth::exigirLogin('tecnico');

// Obtém os dados do usuário autenticado na sessão
$usuario = Auth::usuarioAtual();

// Busca as estatísticas gerais para os cards do dashboard
$estatisticas = Funcoes::obterEstatisticas();
$totalPecas   = $estatisticas['total_pecas'];
$totalEstoque = $estatisticas['total_estoque'];
$baixoEstoque = $estatisticas['baixo_estoque'];

// Lista as peças cadastradas (sem filtro inicial)
$pecas = Funcoes::listarPecas('');

/* Retorna a classe CSS correspondente ao estado físico do item */

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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NOME; ?> - Dashboard</title>

    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Cabeçalho do sistema -->
        <header>
            <h2><?php echo SITE_DESCRICAO; ?></h2>
            <p class="subtitle">
                <i class="fas fa-user"></i>
                <?php echo htmlspecialchars($usuario['nome']); ?>
                <span class="nivel-usuario">(<?php echo $usuario['nivel']; ?>)</span>
            </p>
        </header>

        <!-- Menu de Navegação Principal -->
        <nav class="navbar">
            <a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
            <a href="cadastrar.php"><i class="fas fa-plus-circle"></i> Nova Peça</a>
            <a href="buscar.php"><i class="fas fa-search"></i> Buscar</a>

            <!-- Links restritos por nível de acesso -->
            <?php if (Auth::temNivel('supervisor')): ?>
                <a href="relatorios.php"><i class="fas fa-chart-bar"></i> Relatórios</a>
            <?php endif; ?>

            <?php if (Auth::temNivel('admin')): ?>
                <a href="usuarios.php"><i class="fas fa-users"></i> Usuários</a>
            <?php endif; ?>

            <!-- Botão de Sair/Logout -->
            <a href="logout.php" style="margin-left: auto; background: #e74c3c; color: white;">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </nav>

        <main>
            <!-- Cards Informativos de Estatísticas -->
            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon stat-icon-azul">
                        <i class="fas fa-boxes fa-2x"></i>
                    </div>
                    <h3>Total de Itens</h3>
                    <p class="stat-number"><?php echo $totalPecas; ?></p>
                    <p class="stat-desc">Tipos de peças cadastradas</p>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-verde">
                        <i class="fas fa-box fa-2x"></i>
                    </div>
                    <h3>Em Estoque</h3>
                    <p class="stat-number"><?php echo $totalEstoque; ?></p>
                    <p class="stat-desc">Unidades disponíveis</p>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-vermelho">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <h3>Baixo Estoque</h3>
                    <p class="stat-number"><?php echo $baixoEstoque; ?></p>
                    <p class="stat-desc">Itens com menos de 5 unidades</p>
                </div>

                <div class="stat-card">
                    <div class="stat-icon stat-icon-roxo">
                        <i class="fas fa-user-tie fa-2x"></i>
                    </div>
                    <h3>Seu Nível</h3>
                    <p class="stat-number stat-number-menor"><?php echo ucfirst($usuario['nivel']); ?></p>
                    <p class="stat-desc">Privilégios de acesso</p>
                </div>
            </div>

            <!-- Listagem de Peças Recentes -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2><i class="fas fa-list"></i> Peças Recentes</h2>
                    <a href="buscar.php" class="btn-primary">Buscar Todas</a>
                </div>

                <?php if (empty($pecas)): ?>
                    <!-- Estado quando não há peças cadastradas no sistema -->
                    <div class="empty-state">
                        <i class="fas fa-box-open fa-3x"></i>
                        <h3>Nenhuma peça cadastrada</h3>
                        <p>Comece cadastrando sua primeira peça</p>
                        <a href="cadastrar.php" class="btn-primary">
                            <i class="fas fa-plus-circle"></i> Cadastrar Primeira Peça
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Tabela com as últimas peças cadastradas -->
                    <div class="table-responsive">
                        <table class="pecas-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Quantidade</th>
                                    <th>Estado</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Exibe no máximo os últimos 15 itens registrados -->
                                <?php foreach (array_slice($pecas, 0, 15) as $peca): ?>
                                    <tr>
                                        <td>#<?php echo $peca['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($peca['nome']); ?></strong>
                                            <?php if (!empty($peca['numero_serie'])): ?>
                                                <br><small>S/N: <?php echo htmlspecialchars($peca['numero_serie']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($peca['categoria_nome']); ?></td>
                                        <td>
                                            <!-- Destaca a badge em vermelho se a quantidade for menor que 5 -->
                                            <span class="quantidade-badge <?php echo $peca['quantidade'] < 5 ? 'estoque-baixo' : ''; ?>">
                                                <?php echo $peca['quantidade']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="estado-badge <?php echo classeDoEstado($peca['estado']); ?>">
                                                <?php echo ucfirst($peca['estado']); ?>
                                            </span>
                                        </td>
                                        <td class="actions">
                                            <!-- Botão Visualizar -->
                                            <a href="visualizar.php?id=<?php echo $peca['id']; ?>"
                                               class="btn-view" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            <!-- Botão Editar (Apenas para permissão Supervisor ou Administradro) -->
                                            <?php if (Auth::temNivel('supervisor')): ?>
                                                <a href="editar.php?id=<?php echo $peca['id']; ?>"
                                                   class="btn-edit" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Botão Retirar/Movimentar -->
                                            <a href="movimentar.php?id=<?php echo $peca['id']; ?>&tipo=saida"
                                               class="btn-warning" title="Retirar">
                                                <i class="fas fa-minus"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Exibe atalho para a consulta completa caso o estoque ultrapasse 15 itens -->
                    <?php if (count($pecas) > 15): ?>
                        <div class="mais-pecas">
                            <a href="buscar.php" class="btn-secondary">
                                <i class="fas fa-list"></i>
                                Ver todas as <?php echo count($pecas); ?> peças
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>

        <!-- Rodapé do sistema -->
        <footer>
            <p>SIGESEI <?php echo date('Y'); ?></p>
            <p class="footer-info">
                Usuário: <?php echo htmlspecialchars($usuario['nome']); ?> |
                Data: <?php echo date('d/m/Y'); ?>
            </p>
        </footer>
    </div>

    <!-- Scripts JavaScript do sistema -->
    <script src="js/scripts.js"></script>
</body>
</html>
