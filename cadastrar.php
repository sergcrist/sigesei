<?php

/*Pagina de cadastro de peças, usuário tecnico, supervisor e adminstradores podem cadastrar */


require_once 'includes/auth.php';
Auth::exigirLogin('tecnico'); 

// liga os erros no navegador pra ajudar no debug se der algum problema
error_reporting(E_ALL);
ini_set('display_errors', 1);

// puxa o arquivo com as funções do sistema
require_once 'includes/funcoes.php';

// variáveis pra controlar se deu erro ou se cadastrou
$erro = '';
$sucesso = false;

// valores padrao pro formulario (ajuda a não zerar tudo se der erro de validação)
$dados_form = [
    'nome' => '',
    'descricao' => '',
    'categoria_id' => '',
    'quantidade' => '1',
    'estado' => 'usado',
    'localizacao' => '',
    'numero_serie' => '',
    'data_aquisicao' => date('Y-m-d'), 
    'observacoes' => '',
    'responsavel' => $_SESSION['usuario_nome'], // pega o nome de quem tá logado na sessao
    'numero_chamado' => '' 
];

// testa se o formulário foi enviado 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // limpa e pega os dados que vieram do POST
        $dados = [
            'nome' => trim($_POST['nome'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'categoria_id' => intval($_POST['categoria_id'] ?? 0),
            'quantidade' => intval($_POST['quantidade'] ?? 1),
            'estado' => trim($_POST['estado'] ?? 'usado'),
            'localizacao' => trim($_POST['localizacao'] ?? ''),
            'numero_serie' => trim($_POST['numero_serie'] ?? ''),
            'data_aquisicao' => trim($_POST['data_aquisicao'] ?? ''),
            'observacoes' => trim($_POST['observacoes'] ?? ''),
            'responsavel' => trim($_POST['responsavel'] ?? $_SESSION['usuario_nome']),
            'numero_chamado' => trim($_POST['numero_chamado'] ?? '') 
        ];
        
        // validacoes básicas antes de salvar
        if (empty($dados['nome'])) {
            throw new Exception("O nome da peça é obrigatório.");
        }
        
        if ($dados['categoria_id'] <= 0) {
            throw new Exception("Selecione uma categoria válida.");
        }
        
        if ($dados['quantidade'] < 0) {
            throw new Exception("A quantidade não pode ser negativa.");
        }
        
        // guarda os dados atualizados pra não perder o que foi digitado no form
        $dados_form = $dados;
        
        // chama a funçãoo que faz o INSERT no banco
        $resultado = Funcoes::cadastrarPeca($dados);
        
        if (!$resultado) {
            throw new Exception("Erro ao cadastrar peça no banco de dados.");
        }
        
        // pega o ID da peça que acabou de ser criada
        $db = Conexao::getConexao();
        $ultimoId = $db->lastInsertId();
        
        // se gravou a peça, registra tambem no histárico de movimentacoes
        if ($ultimoId) {
            Funcoes::registrarMovimentacao(
                $ultimoId, 
                'entrada', 
                $dados['quantidade'], 
                $dados['responsavel'],
                'Cadastro inicial da peça no sistema',
                'Cadastro no inventário',
                0,
                $dados['quantidade'],
                $dados['numero_chamado'] 
            );
        }
        
        $sucesso = true;
        
        // manda de volta pra home com mensagem de sucesso na URL
        header('Location: index.php?msg=sucesso_cadastro');
        exit;
        
    } catch (PDOException $e) {
        $erro = "Erro de banco de dados: " . $e->getMessage();
    } catch (Exception $e) {
        $erro = $e->getMessage(); // pega a mensagem do throw la de cima
    }
}

// pega todas as categorias pro select
$categorias = Funcoes::listarCategorias();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NOME; ?> - Cadastrar Peça</title>
  
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <h2><i class="fas fa-plus-circle"></i> Cadastrar Nova Peça</h2>
            <p class="subtitle">Preencha os dados da peça para adicionar ao inventário</p>
        </header>

        <!-- topo/menu -->
        <nav class="navbar">
            <a href="index.php"><i class="fas fa-arrow-left"></i>Dashboard</a>

            <!-- exibe quem ta logado -->
            <div class="user-menu">
                <span class="user-info">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>
                </span>
                <a href="logout.php" style="margin-left: auto; background: #e74c3c; color: white;">
                    <i class="fas fa-sign-out-alt"></i> Sair</a>        
            </div>
        </nav>

        <main>
            <!-- exibe mensagem de sucesso se deu certo -->
            <?php if ($sucesso): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> Peça cadastrada com sucesso! Redirecionando...
                </div>
            <?php endif; ?>

            <!-- exibe erro se deu problema na validacao ou no banco -->
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
                                   value="<?php echo htmlspecialchars($dados_form['nome']); ?>"
                                   placeholder="Ex: Fonte ATX 500W">
                        </div>

                        <div class="form-group">
                            <label for="categoria_id"><i class="fas fa-tags"></i> Categoria *</label>
                            <select id="categoria_id" name="categoria_id" required>
                                <option value="">Selecione uma categoria</option>
                                <!-- traz as categorias salvas no banco -->
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id']; ?>"
                                        <?php echo ($categoria['id'] == $dados_form['categoria_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="quantidade"><i class="fas fa-boxes"></i> Quantidade *</label>
                            <input type="number" id="quantidade" name="quantidade" required 
                                   min="0" value="<?php echo $dados_form['quantidade']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="estado"><i class="fas fa-clipboard-check"></i> Estado *</label>
                            <select id="estado" name="estado" required>
                                <option value="">Selecione o estado</option>
                                <option value="novo" <?php echo $dados_form['estado'] == 'novo' ? 'selected' : ''; ?>>Novo</option>
                                <option value="usado" <?php echo $dados_form['estado'] == 'usado' ? 'selected' : ''; ?>>Usado</option>
                                <option value="reparado" <?php echo $dados_form['estado'] == 'reparado' ? 'selected' : ''; ?>>Reparado</option>
                                <option value="danificado" <?php echo $dados_form['estado'] == 'danificado' ? 'selected' : ''; ?>>Danificado</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="numero_serie"><i class="fas fa-barcode"></i> Número de Série</label>
                            <input type="text" id="numero_serie" name="numero_serie" 
                                   value="<?php echo htmlspecialchars($dados_form['numero_serie']); ?>"
                                   placeholder="Opcional">
                        </div>

                        <div class="form-group">
                            <label for="localizacao"><i class="fas fa-map-marker-alt"></i> Localização</label>
                            <input type="text" id="localizacao" name="localizacao" 
                                   value="<?php echo htmlspecialchars($dados_form['localizacao']); ?>"
                                   placeholder="Ex: Armário A, Prateleira 3">
                        </div>

                        <div class="form-group">
                            <label for="data_aquisicao"><i class="fas fa-calendar-alt"></i> Data de Aquisição</label>
                            <input type="date" id="data_aquisicao" name="data_aquisicao"
                                   value="<?php echo $dados_form['data_aquisicao']; ?>">
                        </div>

                        <div class="form-group">
                            <label for="numero_chamado"><i class="fas fa-file-alt"></i> Número do Chamado (Entrada)</label>
                            <input type="text" id="numero_chamado" name="numero_chamado" 
                                   value="<?php echo htmlspecialchars($dados_form['numero_chamado']); ?>"
                                   placeholder="Número do chamado relacionado (opcional)">
                            <small>Para registrar em qual serviço esta peça foi adquirida</small>
                        </div>

                        <div class="form-group full-width">
                            <label for="descricao"><i class="fas fa-align-left"></i> Descrição</label>
                            <textarea id="descricao" name="descricao" rows="3" 
                                      placeholder="Descreva a peça, modelo, características..."><?php echo htmlspecialchars($dados_form['descricao']); ?></textarea>
                        </div>

                        <div class="form-group full-width">
                            <label for="observacoes"><i class="fas fa-sticky-note"></i> Observações</label>
                            <textarea id="observacoes" name="observacoes" rows="2" 
                                      placeholder="Observações adicionais..."><?php echo htmlspecialchars($dados_form['observacoes']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="responsavel"><i class="fas fa-user"></i> Responsável pelo Cadastro</label>
                            <!-- trava o campo com readonly pra não mudar o nome do usuario  -->
                            <input type="text" id="responsavel" name="responsavel" 
                                   value="<?php echo htmlspecialchars($dados_form['responsavel']); ?>"
                                   placeholder="Seu nome" readonly
                                   style="background-color: #f8f9fa;">
                            <small>Preenchido automaticamente</small>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn-secondary">
                            <i class="fas fa-redo"></i> Limpar
                        </button>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Cadastrar Peça
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <footer>
            <p>SIGESEI <?php echo date('Y'); ?></p>
            <p class="footer-info">Usuário: <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></p>
        </footer>
    </div>

    <script>
    // validaçãoo via javascript no lado do cliente antes de enviar o form
    document.querySelector('form').addEventListener('submit', function(e) {
        const nome = document.getElementById('nome').value.trim();
        const categoria = document.getElementById('categoria_id').value;
        const quantidade = document.getElementById('quantidade').value;
        
        // valida se o nome não esta vazio
        if (nome === '') {
            e.preventDefault();
            alert('Por favor, informe o nome da peça.');
            document.getElementById('nome').focus();
            return false;
        }
        
        // valida se escolheu uma categoria
        if (categoria === '') {
            e.preventDefault();
            alert('Por favor, selecione uma categoria.');
            document.getElementById('categoria_id').focus();
            return false;
        }
        
        // garante que não foi colocado número negativo
        if (parseInt(quantidade) < 0) {
            e.preventDefault();
            alert('A quantidade não pode ser negativa.');
            document.getElementById('quantidade').focus();
            return false;
        }
        
        return true;
    });

    // quando terminar de carregar a página, joga a data de hoje se o campo tiver vazio
    document.addEventListener('DOMContentLoaded', function() {
        const dataAquisicao = document.getElementById('data_aquisicao');
        if (!dataAquisicao.value) {
            const hoje = new Date().toISOString().split('T')[0];
            dataAquisicao.value = hoje;
        }
    });
    </script>
</body>
</html>
