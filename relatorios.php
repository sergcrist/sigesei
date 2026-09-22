<?php

/*Página de relatório*/

require_once 'includes/auth.php';
Auth::exigirLogin('supervisor');

require_once 'includes/funcoes.php';

// Obter dados do usuário
$usuario = Auth::usuarioAtual();

$db = Conexao::getConexao();

// Relatório de movimentações por período
$data_inicio = $_GET['data_inicio'] ?? date('Y-m-01');
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

// Exportar para CSV
if (isset($_GET['exportar']) && $_GET['exportar'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=relatorio_movimentacoes_' . date('Ymd_His') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // Cabeçalho do CSV
    fputcsv($output, [
        'Data/Hora', 'Peça', 'Tipo', 'Quantidade', 'Responsável', 
        'Número do Chamado', 'Motivo', 'Observações', 'Estoque Anterior', 'Novo Estoque'
    ]);
    
    // Dados
    $sql = "SELECT h.*, p.nome as peca_nome 
            FROM historico h 
            LEFT JOIN pecas p ON h.peca_id = p.id 
            WHERE DATE(h.data_movimentacao) BETWEEN :data_inicio AND :data_fim 
            ORDER BY h.data_movimentacao DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([':data_inicio' => $data_inicio, ':data_fim' => $data_fim]);
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            date('d/m/Y H:i', strtotime($row['data_movimentacao'])),
            $row['peca_nome'],
            ucfirst($row['tipo_movimentacao']),
            ($row['tipo_movimentacao'] == 'entrada' ? '+' : '-') . $row['quantidade'],
            $row['responsavel'],
            $row['numero_chamado'] ?? '',
            $row['motivo'],
            $row['observacoes'] ?? '',
            $row['quantidade_anterior'],
            $row['quantidade_nova']
        ]);
    }
    
    fclose($output);
    exit;
}

// Estatísticas gerais
$sqlTotal = "SELECT COUNT(*) as total FROM pecas";
$stmt = $db->query($sqlTotal);
$totalPecas = $stmt->fetch()['total'];

$sqlEstoque = "SELECT SUM(quantidade) as total FROM pecas";
$stmt = $db->query($sqlEstoque);
$totalEstoque = $stmt->fetch()['total'] ?? 0;

// Movimentações do período
$sql = "SELECT h.*, p.nome as peca_nome 
        FROM historico h 
        LEFT JOIN pecas p ON h.peca_id = p.id 
        WHERE DATE(h.data_movimentacao) BETWEEN :data_inicio AND :data_fim 
        ORDER BY h.data_movimentacao DESC";
$stmt = $db->prepare($sql);
$stmt->execute([':data_inicio' => $data_inicio, ':data_fim' => $data_fim]);
$movimentacoes = $stmt->fetchAll();

// Estatísticas por tipo
$sqlTipos = "SELECT tipo_movimentacao, COUNT(*) as quantidade, SUM(h.quantidade) as total
             FROM historico h
             WHERE DATE(h.data_movimentacao) BETWEEN :data_inicio AND :data_fim 
             GROUP BY tipo_movimentacao";
$stmt = $db->prepare($sqlTipos);
$stmt->execute([':data_inicio' => $data_inicio, ':data_fim' => $data_fim]);
$estatisticasTipo = $stmt->fetchAll();

// Top 5 peças mais movimentadas
$sqlTop5 = "SELECT p.nome, COUNT(h.id) as total_mov, SUM(h.quantidade) as total_quantidade
            FROM historico h 
            LEFT JOIN pecas p ON h.peca_id = p.id 
            WHERE DATE(h.data_movimentacao) BETWEEN :data_inicio AND :data_fim 
            GROUP BY h.peca_id 
            ORDER BY total_mov DESC 
            LIMIT 5";
$stmt = $db->prepare($sqlTop5);
$stmt->execute([':data_inicio' => $data_inicio, ':data_fim' => $data_fim]);
$topPecas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIGESEI - Relatórios Analíticos</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        
        .relatorios-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .relatorio-card {
            background: white;
            padding: 1.2rem;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }
        
        .relatorio-card h3 {
            color: #2c3e50;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 0.8rem;
            font-weight: 600;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
            font-size: 0.9rem;
        }
        
        .stat-label {
            color: #6c757d;
            font-weight: 500;
        }
        
        .stat-value {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .stat-value.entrada { color: #28a745; }
        .stat-value.saida { color: #dc3545; }
        .stat-value.uso { color: #ffc107; }
        .stat-value.descarte { color: #6c757d; }
        
        .filtros-form {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }
        
        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .periodo-info {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            color: #6c757d;
            border-left: 3px solid #3498db;
            margin-top: 15px;
        }
        
        .export-button-container {
            text-align: center;
            margin: 30px 0 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }
        
        .export-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 25px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .export-button:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);
        }
        
        .export-button i {
            font-size: 1.1rem;
        }
        
        .badge-chamado {
            background: #e3f2fd;
            color: #1976d2;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .top-pecas {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
        }
        
        .top-item {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #dee2e6;
            font-size: 0.85rem;
        }
        
        .resumo-periodo {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .info-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e3f2fd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1976d2;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .info-item-complexo {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .relatorios-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h2><i class="fas fa-chart-bar"></i> Relatórios Analíticos</h2>
            <p class="subtitle">Análise detalhada das movimentações do sistema</p>
        </header>

        <nav class="navbar">
	    <a href="index.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
	    <a href="logout.php" style="margin-left: auto; background: #e74c3c; color: white;">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </nav>

        <main>
            <!-- Filtros -->
            <div class="filtros-form">
                <h3><i class="fas fa-filter"></i> Filtros do Relatório</h3>
                
                <form method="GET" action="relatorios.php">
                    <div class="filtros-grid">
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #495057; font-weight: 500;">Data Início</label>
                            <input type="date" name="data_inicio" value="<?php echo $data_inicio; ?>" 
                                   style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-size: 0.9rem;">
                        </div>
                        <div>
                            <label style="display: block; margin-bottom: 5px; color: #495057; font-weight: 500;">Data Fim</label>
                            <input type="date" name="data_fim" value="<?php echo $data_fim; ?>" 
                                   style="width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-size: 0.9rem;">
                        </div>
                        <div>
                            <button type="submit" class="btn-primary" style="padding: 11px 25px; width: 100%;">
                                <i class="fas fa-search"></i> Aplicar Filtros
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="periodo-info">
                    <i class="fas fa-info-circle"></i> 
                    Relatório gerado em <?php echo date('d/m/Y H:i'); ?> | 
                    Total de registros: <strong><?php echo count($movimentacoes); ?></strong>
                </div>
            </div>

            <!-- Cards de Estatísticas -->
            <div class="relatorios-container">
                <!-- Visão Geral -->
                <div class="relatorio-card">
                    <h3><i class="fas fa-chart-pie"></i> Visão Geral</h3>
                    <div class="stat-item">
                        <span class="stat-label">Total de Peças:</span>
                        <span class="stat-value"><?php echo $totalPecas; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Unidades em Estoque:</span>
                        <span class="stat-value"><?php echo $totalEstoque; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Movimentações:</span>
                        <span class="stat-value"><?php echo count($movimentacoes); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Período:</span>
                        <span class="stat-value">
                            <?php 
                            $inicio = new DateTime($data_inicio);
                            $fim = new DateTime($data_fim);
                            echo $inicio->diff($fim)->days + 1;
                            ?> dias
                        </span>
                    </div>
                </div>

                <!-- Por Tipo -->
                <div class="relatorio-card">
                    <h3><i class="fas fa-exchange-alt"></i> Movimentações por Tipo</h3>
                    <?php 
                    $totalEntradas = 0;
                    $totalSaidas = 0;
                    
                    foreach ($estatisticasTipo as $estat):
                        if ($estat['tipo_movimentacao'] == 'entrada') {
                            $totalEntradas = $estat['total'];
                        } else {
                            $totalSaidas += $estat['total'];
                        }
                    ?>
                    <div class="stat-item">
                        <span class="stat-label">
                            <?php echo ucfirst($estat['tipo_movimentacao']); ?>
                        </span>
                        <span class="stat-value <?php echo $estat['tipo_movimentacao']; ?>">
                            <?php echo $estat['quantidade']; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (!empty($estatisticasTipo)): ?>
                    <div class="top-pecas">
                        <div class="top-item">
                            <span>Entradas:</span>
                            <span style="color: #28a745; font-weight: bold;">+<?php echo $totalEntradas; ?></span>
                        </div>
                        <div class="top-item">
                            <span>Saídas:</span>
                            <span style="color: #dc3545; font-weight: bold;">-<?php echo $totalSaidas; ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Informações -->
                <div class="relatorio-card">
                    <h3><i class="fas fa-info-circle"></i> Informações</h3>
                    <div class="info-item-complexo">
                        <div class="info-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <div style="color: #6c757d; font-size: 0.85rem;">Gerado por</div>
                            <div style="color: #2c3e50; font-weight: 600;"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item-complexo">
                        <div class="info-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <div style="color: #6c757d; font-size: 0.85rem;">Nível</div>
                            <div style="color: #2c3e50; font-weight: 600;"><?php echo ucfirst($usuario['nivel']); ?></div>
                        </div>
                    </div>
                    
                    <div class="info-item-complexo">
                        <div class="info-icon">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div>
                            <div style="color: #6c757d; font-size: 0.85rem;">Data</div>
                            <div style="color: #2c3e50; font-weight: 600;"><?php echo date('d/m/Y H:i'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabela de Movimentações -->
            <div class="dashboard-section">
                <h2><i class="fas fa-history"></i> Movimentações do Período</h2>
                
                <?php if (empty($movimentacoes)): ?>
                    <div class="empty-state">
                        <i class="fas fa-chart-line fa-3x"></i>
                        <h3>Nenhuma movimentação registrada</h3>
                        <p>Não há movimentações no período selecionado.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="pecas-table">
                            <thead>
                                <tr>
                                    <th>Data/Hora</th>
                                    <th>Peça</th>
                                    <th>Tipo</th>
                                    <th>Quantidade</th>
                                    <th>Responsável</th>
                                    <th>Chamado</th>
                                    <th>Motivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($movimentacoes as $mov): 
                                    $corTipo = '';
                                    switch($mov['tipo_movimentacao']) {
                                        case 'entrada': $corTipo = 'estado-novo'; break;
                                        case 'saida': $corTipo = 'estado-usado'; break;
                                        case 'uso': $corTipo = 'estado-reparado'; break;
                                        case 'descarte': $corTipo = 'estado-danificado'; break;
                                    }
                                ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i', strtotime($mov['data_movimentacao'])); ?></td>
                                    <td><?php echo htmlspecialchars($mov['peca_nome']); ?></td>
                                    <td>
                                        <span class="estado-badge <?php echo $corTipo; ?>">
                                            <?php echo ucfirst($mov['tipo_movimentacao']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: <?php echo $mov['tipo_movimentacao'] == 'entrada' ? '#28a745' : '#dc3545'; ?>; font-weight: bold;">
                                            <?php echo $mov['tipo_movimentacao'] == 'entrada' ? '+' : '-'; ?>
                                            <?php echo $mov['quantidade']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($mov['responsavel']); ?></td>
                                    <td>
                                        <?php if (!empty($mov['numero_chamado'])): ?>
                                            <span class="badge-chamado">
                                                <?php echo htmlspecialchars($mov['numero_chamado']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: #6c757d;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($mov['motivo']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Botão único do exportação -->
            <?php if (!empty($movimentacoes)): ?>
            <div class="export-button-container">
                <p style="margin-bottom: 15px; color: #6c757d;">
                    <i class="fas fa-download"></i> 
                    Exporte o relatório completo em formato CSV
                </p>
                <a href="?exportar=csv&data_inicio=<?php echo $data_inicio; ?>&data_fim=<?php echo $data_fim; ?>" 
                   class="export-button">
                    <i class="fas fa-file-csv"></i> 
                    Exportar Relatório CSV
                </a>
                <p style="margin-top: 10px; font-size: 0.85rem; color: #6c757d;">
                    <?php echo count($movimentacoes); ?> registros | 
                    Período: <?php echo date('d/m/Y', strtotime($data_inicio)); ?> a <?php echo date('d/m/Y', strtotime($data_fim)); ?>
                </p>
            </div>
            <?php endif; ?>

        </main>

        <footer>
            <p>SIGESEI <?php echo date('Y'); ?></p>
            <p class="footer-info">
                Relatório: <?php echo date('d/m/Y H:i'); ?> | 
                Usuário: <?php echo htmlspecialchars($usuario['nome']); ?>
            </p>
        </footer>
    </div>
</body>
</html>
