<?php

// Página de busca e listagem de peças.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/funcoes.php';

// so deixa passar se for pelo menos tecnico logado
Auth::exigirLogin('tecnico');

// pega os dados que vem da busca 
$termo           = trim($_GET['q'] ?? '');
$filtroCategoria = $_GET['categoria'] ?? '';
$filtroEstado    = $_GET['estado'] ?? '';

// arrays pra montar o SQL depois
$condicoes  = [];
$parametros = [];

// se digitou algo no campo de busca
if ($termo !== '') {
    $condicoes[]         = "(p.nome LIKE :termo OR p.descricao LIKE :termo OR p.numero_serie LIKE :termo)";
    $parametros[':termo'] = "%{$termo}%"; // busca com % pra achar em qualquer parte do texto
}

// se escolheu alguma categoria no filtro
if ($filtroCategoria !== '') {
    $condicoes[]              = "p.categoria_id = :categoria";
    $parametros[':categoria'] = $filtroCategoria;
}

// se escolheu algum estado da peça no filtro
if ($filtroEstado !== '') {
    $condicoes[]          = "p.estado = :estado";
    $parametros[':estado'] = $filtroEstado;
}

// conecta no banco e faz a query principal com JOIN pra pegar o nome da categoria
$db  = Conexao::getConexao();
$sql = "SELECT p.*, c.nome AS categoria_nome
        FROM pecas p
        LEFT JOIN categorias c ON p.categoria_id = c.id";

// se tiver algum filtro ativo, junta tudo com AND no WHERE
if ($condicoes) {
    $sql .= " WHERE " . implode(" AND ", $condicoes);
}

// ordena por nome A-Z
$sql .= " ORDER BY p.nome ASC";

$stmt = $db->prepare($sql);

// passa os parametros pra evitar SQL Injection
foreach ($parametros as $chave => $valor) {
    $stmt->bindValue($chave, $valor);
}

// roda a consulta e pega os resultados
$stmt->execute();
$resultados = $stmt->fetchAll();

// carrega as categorias pra preencher o select do filtro
$categorias = Funcoes::listarCategorias();
$estados    = ['novo', 'usado', 'reparado', 'danificado'];

// checa se tem algum filtro ligado pra mostrar o botão de limpar
$temFiltroAtivo = $termo !== '' || $filtroCategoria !== '' || $filtroEstado !== '';

// funcao simples pra retornar a classe CSS da badge de estado
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
    <title><?php echo SITE_NOME; ?> - Buscar Peças</title>

    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h2><i class="fas fa-search"></i> Buscar Peças</h2>
            <p class="subtitle">Encontre peças no inventário usando filtros</p>
        </header>

        <!-- Menu do topo -->
        <nav class="navbar">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Dashboard</a>
            <a href="logout.php" style="margin-left: auto; background: #e74c3c; color: white;">
            <i class="fas fa-sign-out-alt"></i> Sair</a>
        </nav>

        <main>
            <div class="search-section">
                <!-- Formulário de busca -->
                <form method="GET" action="buscar.php" class="search-form">
                    <div class="search-bar">
                        <!-- Campo de texto mantendo o valor digitado -->
                        <input type="text" name="q"
                               placeholder="Digite o nome, descrição ou número de série..."
                               value="<?php echo htmlspecialchars($termo); ?>">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search"></i> Buscar
                        </button>
                    </div>

                    <div class="filters">
                        <!-- Select de Categoria  -->
                        <div class="filter-group">
                            <label for="categoria"><i class="fas fa-tags"></i> Categoria:</label>
                            <select id="categoria" name="categoria" onchange="this.form.submit()">
                                <option value="">Todas as categorias</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>"
                                        <?php echo $filtroCategoria == $categoria['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Select de Estado da Peça -->
                        <div class="filter-group">
                            <label for="estado"><i class="fas fa-clipboard-check"></i> Estado:</label>
                            <select id="estado" name="estado" onchange="this.form.submit()">
                                <option value="">Todos os estados</option>
                                <?php foreach ($estados as $estado): ?>
                                    <option value="<?php echo $estado; ?>"
                                        <?php echo $filtroEstado === $estado ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($estado); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Botao de limpar filtros se tiver algo filtrado -->
                        <?php if ($temFiltroAtivo): ?>
                            <a href="buscar.php" class="btn-secondary">
                                <i class="fas fa-times"></i> Limpar Filtros
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="search-results-header">
                    <h3>
                        <i class="fas fa-list"></i> Resultados da Busca
                        <!-- Mostra a quantidade de itens achados -->
                        <span class="results-count">
                            (<?php echo count($resultados); ?> encontrados)
                        </span>
                    </h3>

                    <!-- Avisa qual termo esta buscando -->
                    <?php if ($termo !== ''): ?>
                        <p class="search-term">
                            Buscando por: "<strong><?php echo htmlspecialchars($termo); ?></strong>"
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Se não achar nada mostra aviso -->
                <?php if (empty($resultados)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search fa-3x"></i>
                        <h3>Nenhuma peça encontrada</h3>
                        <p>Tente ajustar os termos da busca ou os filtros</p>
                        <a href="buscar.php" class="btn-secondary">Limpar Busca</a>
                        <a href="cadastrar.php" class="btn-primary">Cadastrar Nova Peça</a>
                    </div>
                <!-- Se achar mostra a tabela -->
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="pecas-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Categoria</th>
                                    <th>Quantidade</th>
                                    <th>Estado</th>
                                    <th>Localização</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Loop para listar cada peça retornada do banco -->
                                <?php foreach ($resultados as $peca): ?>
                                    <tr>
                                        <td>#<?php echo $peca['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($peca['nome']); ?></strong>
                                            <!-- Só mostra número de serie se tiver preenchido -->
                                            <?php if (!empty($peca['numero_serie'])): ?>
                                                <br><small>S/N: <?php echo htmlspecialchars($peca['numero_serie']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($peca['categoria_nome']); ?></td>
                                        <td>
                                            <span class="quantidade-badge"><?php echo $peca['quantidade']; ?></span>
                                        </td>
                                        <td>
                                            <span class="estado-badge <?php echo classeDoEstado($peca['estado']); ?>">
                                                <?php echo ucfirst($peca['estado']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <!-- Se não tiver localização preenchida mostra texto padrão -->
                                            <?php echo !empty($peca['localizacao'])
                                                    ? htmlspecialchars($peca['localizacao'])
                                                    : 'Não informada'; ?>
                                        </td>
                                        <td class="actions">
                                            <!-- Botoes de ação (ver, editar, excluir) -->
                                            <a href="visualizar.php?id=<?php echo $peca['id']; ?>"
                                               class="btn-view" title="Visualizar">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="editar.php?id=<?php echo $peca['id']; ?>"
                                               class="btn-edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <!-- Alerta em JavaScript pra não apagar sem querer -->
                                            <a href="excluir.php?id=<?php echo $peca['id']; ?>"
                                               class="btn-delete" title="Excluir"
                                               onclick="return confirm('Tem certeza que deseja excluir esta peça?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Rodapé da tabela com botão de imprimir -->
                    <div class="export-options">
                        <p>Total de peças encontradas: <strong><?php echo count($resultados); ?></strong></p>
                        <div class="export-buttons">
                            <button type="button" onclick="window.print()" class="btn-secondary">
                                <i class="fas fa-print"></i> Imprimir Lista
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <footer>
            <p>SIGESEI <?php echo date('Y'); ?></p>
        </footer>
    </div>

    <script src="js/scripts.js"></script>
</body>
</html>
