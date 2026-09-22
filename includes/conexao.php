<?php

/*Gerencia a conexão com o banco de dados MySQL. */

require_once __DIR__ . '/config.php';

class Conexao
{
    private static $instancia;

    public static function getConexao()
    {
        if (self::$instancia === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8';

            try {
                self::$instancia = new PDO($dsn, DB_USER, DB_PASS);
                self::$instancia->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$instancia->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die('Erro na conexão: ' . $e->getMessage());
            }
        }

        return self::$instancia;
    }
}
