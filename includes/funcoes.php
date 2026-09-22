<?php

/* Acesso a dados e regras de negócio do sistema */

require_once __DIR__ . '/conexao.php';

class Funcoes
{
    /* Peças — CRUD */

    public static function listarPecas($filtro = '')
    {
        $db  = Conexao::getConexao();
        $sql = "SELECT p.*, c.nome AS categoria_nome
                FROM pecas p
                LEFT JOIN categorias c ON p.categoria_id = c.id";

        if ($filtro !== '') {
            $sql .= " WHERE p.nome LIKE :filtro
                         OR p.descricao LIKE :filtro
                         OR c.nome LIKE :filtro
                         OR p.numero_serie LIKE :filtro";
        }

        $sql .= " ORDER BY p.data_cadastro DESC";
        $stmt = $db->prepare($sql);

        if ($filtro !== '') {
            $termo = "%{$filtro}%";
            $stmt->bindParam(':filtro', $termo);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function buscarPeca($id)
    {
        $db  = Conexao::getConexao();
        $sql = "SELECT p.*, c.nome AS categoria_nome
                FROM pecas p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public static function cadastrarPeca($dados)
    {
        $db  = Conexao::getConexao();
        $sql = "INSERT INTO pecas
                    (nome, descricao, categoria_id, quantidade,
                     estado, localizacao, numero_serie, data_aquisicao, observacoes)
                VALUES
                    (:nome, :descricao, :categoria_id, :quantidade,
                     :estado, :localizacao, :numero_serie, :data_aquisicao, :observacoes)";

        $stmt = $db->prepare($sql);
        $dataAquisicao = !empty($dados['data_aquisicao']) ? $dados['data_aquisicao'] : null;
        return $stmt->execute([
            ':nome'           => $dados['nome'],
            ':descricao'      => $dados['descricao'],
            ':categoria_id'   => $dados['categoria_id'],
            ':quantidade'     => $dados['quantidade'],
            ':estado'         => $dados['estado'],
            ':localizacao'    => $dados['localizacao'],
            ':numero_serie'   => $dados['numero_serie'],
            ':data_aquisicao' => $dataAquisicao,
            ':observacoes'    => $dados['observacoes'],
        ]);
    }

    public static function atualizarPeca($id, $dados)
    {
        $db  = Conexao::getConexao();
        $sql = "UPDATE pecas SET
                    nome           = :nome,
                    descricao      = :descricao,
                    categoria_id   = :categoria_id,
                    quantidade     = :quantidade,
                    estado         = :estado,
                    localizacao    = :localizacao,
                    numero_serie   = :numero_serie,
                    data_aquisicao = :data_aquisicao,
                    observacoes    = :observacoes
                WHERE id = :id";

        $stmt = $db->prepare($sql);

        $dataAquisicao = !empty($dados[':data_aquisicao']) ? $dados[':data_aquisicao'] : null;
        $dados[':id']              = $id;
        $dados[':data_aquisicao']  = $dataAquisicao;
        return $stmt->execute($dados);
    }

    public static function excluirPeca($id)
    {
        $db = Conexao::getConexao();
        $stmtHistorico = $db->prepare("DELETE FROM historico WHERE peca_id = :id");
        $stmtHistorico->execute([':id' => $id]);
        $stmt = $db->prepare("DELETE FROM pecas WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /* Categorias */

    public static function listarCategorias()
    {
        $db   = Conexao::getConexao();
        $stmt = $db->prepare("SELECT * FROM categorias ORDER BY nome");
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /* Movimentações */

    public static function registrarMovimentacao(
        $peca_id,
        $tipo,
        $quantidade,
        $responsavel,
        $observacoes = '',
        $motivo = '',
        $quantidade_anterior = 0,
        $quantidade_nova = 0,
        $numero_chamado = ''
    ) {
        $db  = Conexao::getConexao();
        $sql = "INSERT INTO historico
                    (peca_id, tipo_movimentacao, quantidade, quantidade_anterior,
                     quantidade_nova, responsavel, motivo, observacoes, numero_chamado)
                VALUES
                    (:peca_id, :tipo, :quantidade, :quantidade_anterior,
                     :quantidade_nova, :responsavel, :motivo, :observacoes, :numero_chamado)";

        $stmt = $db->prepare($sql);

        return $stmt->execute([
            ':peca_id'            => $peca_id,
            ':tipo'               => $tipo,
            ':quantidade'         => $quantidade,
            ':quantidade_anterior'=> $quantidade_anterior,
            ':quantidade_nova'    => $quantidade_nova,
            ':responsavel'        => $responsavel,
            ':motivo'             => $motivo,
            ':observacoes'        => $observacoes,
            ':numero_chamado'     => $numero_chamado,
        ]);
    }

    public static function buscarHistoricoPeca($peca_id, $limit = 10)
    {
        $db  = Conexao::getConexao();
        $sql = "SELECT * FROM historico
                WHERE peca_id = :peca_id
                ORDER BY data_movimentacao DESC
                LIMIT :limit";

        $stmt = $db->prepare($sql);
        $stmt->bindParam(':peca_id', $peca_id, PDO::PARAM_INT);
        $stmt->bindParam(':limit',    $limit,   PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /* Operações de estoque */

    public static function usarPecas(
        $peca_id,
        $quantidade,
        $responsavel,
        $motivo = 'Uso em manutenção',
        $observacoes = '',
        $numero_chamado = ''
    ) {
        $peca = self::buscarPeca($peca_id);

        if (!$peca) {
            throw new Exception('Peça não encontrada.');
        }

        if ($peca['quantidade'] < $quantidade) {
            throw new Exception("Quantidade insuficiente. Disponível: {$peca['quantidade']}");
        }

        $quantidade_anterior = $peca['quantidade'];
        $quantidade_nova     = $quantidade_anterior - $quantidade;

        self::atualizarQuantidade($peca_id, $quantidade_nova);

        $tipo = 'saida';
        if (stripos($motivo, 'manutenção') !== false) {
            $tipo = 'uso';
        } elseif (stripos($motivo, 'descarte') !== false || stripos($motivo, 'danificado') !== false) {
            $tipo = 'descarte';
        }

        return self::registrarMovimentacao(
            $peca_id,
            $tipo,
            $quantidade,
            $responsavel,
            $observacoes,
            $motivo,
            $quantidade_anterior,
            $quantidade_nova,
            $numero_chamado
        );
    }

    public static function adicionarPecas(
        $peca_id,
        $quantidade,
        $responsavel,
        $motivo = 'Reposição de estoque',
        $observacoes = '',
        $numero_chamado = ''
    ) {
        $peca = self::buscarPeca($peca_id);

        if (!$peca) {
            throw new Exception('Peça não encontrada.');
        }

        if ($quantidade <= 0) {
            throw new Exception('Quantidade deve ser maior que zero.');
        }

        $quantidade_anterior = $peca['quantidade'];
        $quantidade_nova     = $quantidade_anterior + $quantidade;

        self::atualizarQuantidade($peca_id, $quantidade_nova);

        return self::registrarMovimentacao(
            $peca_id,
            'entrada',
            $quantidade,
            $responsavel,
            $observacoes,
            $motivo,
            $quantidade_anterior,
            $quantidade_nova,
            $numero_chamado
        );
    }

    public static function ajustarEstoque(
        $peca_id,
        $nova_quantidade,
        $responsavel,
        $motivo = 'Ajuste manual',
        $observacoes = '',
        $numero_chamado = ''
    ) {
        $peca = self::buscarPeca($peca_id);

        if (!$peca) {
            throw new Exception('Peça não encontrada.');
        }

        if ($nova_quantidade < 0) {
            throw new Exception('Quantidade não pode ser negativa.');
        }

        $quantidade_anterior = $peca['quantidade'];
        $diferenca = $nova_quantidade - $quantidade_anterior;

        if ($diferenca === 0) {
            throw new Exception('Nova quantidade é igual à atual. Nenhum ajuste necessário.');
        }

        self::atualizarQuantidade($peca_id, $nova_quantidade);

        return self::registrarMovimentacao(
            $peca_id,
            'ajuste',
            abs($diferenca),
            $responsavel,
            $observacoes,
            $motivo,
            $quantidade_anterior,
            $nova_quantidade,
            $numero_chamado
        );
    }

    /* Estatísticas */

    public static function obterEstatisticas()
    {
        $db = Conexao::getConexao();
        $totalPecas = $db->query("SELECT COUNT(*) AS total FROM pecas")->fetch()['total'];
        $totalEstoque = $db->query("SELECT SUM(quantidade) AS total FROM pecas")->fetch()['total'] ?? 0;
        $baixoEstoque = $db->query("SELECT COUNT(*) AS total FROM pecas WHERE quantidade < 5")->fetch()['total'];
        $sqlUltimaEntrada = "SELECT h.*, p.nome AS peca_nome
                             FROM historico h
                             LEFT JOIN pecas p ON h.peca_id = p.id
                             WHERE h.tipo_movimentacao = 'entrada'
                             ORDER BY h.data_movimentacao DESC
                             LIMIT 1";

        $sqlUltimaSaida = "SELECT h.*, p.nome AS peca_nome
                           FROM historico h
                           LEFT JOIN pecas p ON h.peca_id = p.id
                           WHERE h.tipo_movimentacao IN ('saida', 'uso', 'descarte')
                           ORDER BY h.data_movimentacao DESC
                           LIMIT 1";

        $sqlMaisMovimentadas = "SELECT p.nome, COUNT(h.id) AS total_movimentacoes
                                FROM historico h
                                LEFT JOIN pecas p ON h.peca_id = p.id
                                GROUP BY h.peca_id
                                ORDER BY total_movimentacoes DESC
                                LIMIT 5";

        return [
            'total_pecas'       => $totalPecas,
            'total_estoque'     => $totalEstoque,
            'baixo_estoque'     => $baixoEstoque,
            'ultima_entrada'    => $db->query($sqlUltimaEntrada)->fetch(),
            'ultima_saida'      => $db->query($sqlUltimaSaida)->fetch(),
            'mais_movimentadas' => $db->query($sqlMaisMovimentadas)->fetchAll(),
        ];
    }

    /* Métodos internos*/

    private static function atualizarQuantidade($peca_id, $quantidade)
    {
        $db   = Conexao::getConexao();
        $stmt = $db->prepare("UPDATE pecas SET quantidade = :quantidade WHERE id = :id");
        return $stmt->execute([
            ':quantidade' => $quantidade,
            ':id'         => $peca_id,
        ]);
    }
}
