<?php
/* Gerenciamento de autenticação e controle de acesso */

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth
{
    /* Hierarquia de permissões: cada nível herda as permissões do anterior */
    private const NIVEIS = [
        'tecnico'    => 1,
        'supervisor' => 2,
        'admin'      => 3,
    ];

    public static function estaLogado()
    {
        return isset($_SESSION['usuario_id']) && $_SESSION['usuario_logado'] === true;
    }

    public static function temNivel($nivelRequerido)
    {
        if (!self::estaLogado()) {
            return false;
        }

        $nivelUsuario = $_SESSION['usuario_nivel'] ?? 'tecnico';

        return (self::NIVEIS[$nivelUsuario] ?? 0) >= (self::NIVEIS[$nivelRequerido] ?? 99);
    }

    public static function fazerLogin($email, $senha)
    {
        require_once __DIR__ . '/conexao.php';
        $db = Conexao::getConexao();

        $sql = "SELECT * FROM usuarios WHERE email = :email AND ativo = 1 LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            return false;
        }

        $_SESSION['usuario_id']     = $usuario['id'];
        $_SESSION['usuario_nome']   = $usuario['nome'];
        $_SESSION['usuario_email']  = $usuario['email'];
        $_SESSION['usuario_nivel']  = $usuario['nivel'];
        $_SESSION['usuario_logado'] = true;

        return true;
    }

    public static function fazerLogout()
    {
        $_SESSION = [];
        session_destroy();

        return true;
    }

    public static function exigirLogin($nivelMinimo = 'tecnico')
    {
        if (!self::estaLogado()) {
            $urlLogin = 'login.php';
            $uriAtual = $_SERVER['REQUEST_URI'] ?? '';

            if ($uriAtual !== '' && $uriAtual !== '/') {
                $urlLogin .= '?redirect=' . urlencode($uriAtual);
            }

            header('Location: ' . $urlLogin);
            exit;
        }

        if (!self::temNivel($nivelMinimo)) {
            header('Location: index.php?msg=acesso_negado');
            exit;
        }
    }

    public static function usuarioAtual()
    {
        if (!self::estaLogado()) {
            return null;
        }

        return [
            'id'    => $_SESSION['usuario_id'],
            'nome'  => $_SESSION['usuario_nome'],
            'email' => $_SESSION['usuario_email'],
            'nivel' => $_SESSION['usuario_nivel'],
        ];
    }
}
