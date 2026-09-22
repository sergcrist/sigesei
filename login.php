<?php

/* Página de login do sistema */

require_once __DIR__ . '/includes/auth.php';

// Se o usuário já estiver logado, redireciona diretamente para a Dashboard
if (Auth::estaLogado()) {
    header('Location: index.php');
    exit;
}

$mensagemErro = '';
$email = '';

// Processamento do formulário enviado via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tratamento das entradas
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    // Validação básica dos campos
    if ($email === '' || $senha === '') {
        $mensagemErro = 'Por favor, preencha todos os campos.';
    } elseif (Auth::fazerLogin($email, $senha)) {
        // Redireciona para a página solicitada anteriormente ou para a página inicial
        $destino = $_GET['redirect'] ?? 'index.php';
        header('Location: ' . $destino);
        exit;
    } else {
        $mensagemErro = 'E-mail ou senha incorretos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NOME; ?> - Login</title>
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Estilos específicos da tela de login -->
    <style>
        .login-wrapper {
            max-width: 400px;
            margin-top: 100px;
        }

        .login-cabecalho {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-titulo-sistema {
            font-size: 1rem;
            font-weight: 600;
            color: #2c3e50;
            line-height: 1.4;
        }

        .login-card {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .login-card h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .login-campo {
            margin-bottom: 20px;
        }

        .login-campo label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .login-campo input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .login-botao {
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .login-botao:hover {
            background: #2980b9;
        }

        .login-rodape {
            margin-top: 20px;
            text-align: center;
            color: #7f8c8d;
            font-size: 14px;
        }

        .login-rodape p + p {
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="container login-wrapper">
        <!--Cabeçalho da aplicação -->
        <div class="login-cabecalho">
            <p class="login-titulo-sistema">
                Sistema de Gestão Sustentável de Equipamentos de Informática &mdash; SIGESEI
            </p>
        </div>

        <!-- Exibição de erros de autenticação -->
        <?php if ($mensagemErro !== ''): ?>
            <div class="alert error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($mensagemErro); ?>
            </div>
        <?php endif; ?>

        <!-- Formuário principal de acesso -->
        <div class="login-card">
            <h2></i> Acesso ao Sistema</h2>

            <form method="POST" action="">
          
                <div class="login-campo">
                    <label for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        autofocus
                        value="<?php echo htmlspecialchars($email); ?>"
                        placeholder="seu@email.com">
                </div>

          
                <div class="login-campo" style="margin-bottom: 25px;">
                    <label for="senha">Senha</label>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        required
                        placeholder="Digite sua senha">
                </div>

           
                <button type="submit" class="login-botao">
                    <i class="fas fa-sign-in-alt"></i> Entrar
                </button>
            </form>


            <div class="login-rodape">
                <p>Use suas credenciais fornecidas</p>
                <p>Se não tem acesso, solicite ao administrador</p>
            </div>
        </div>
    </div>
</body>
</html>
