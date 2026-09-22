-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS inventario_pecas;
USE inventario_pecas;

-- Tabela de categorias de peças
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT
);

-- Tabela principal de peças/equipamentos
CREATE TABLE pecas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    descricao TEXT,
    categoria_id INT,
    quantidade INT NOT NULL DEFAULT 0,
    estado ENUM('novo', 'usado', 'reparado', 'danificado') NOT NULL,
    localizacao VARCHAR(100),
    numero_serie VARCHAR(100),
    data_aquisicao DATE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observacoes TEXT,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Tabela de histórico de movimentações
CREATE TABLE historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peca_id INT,
    tipo_movimentacao ENUM('entrada', 'saida', 'transferencia', 'ajuste') NOT NULL,
    quantidade INT NOT NULL,
    data_movimentacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responsavel VARCHAR(100),
    observacao TEXT,
    FOREIGN KEY (peca_id) REFERENCES pecas(id)
);

-- Inserir categorias iniciais
INSERT INTO categorias (nome, descricao) VALUES
('Fonte de Alimentação', 'Fontes ATX para computadores'),
('Placa Mãe', 'Placas mãe para diversos sockets'),
('Memória RAM', 'Memórias DDR3, DDR4, DDR5'),
('Processador', 'CPUs Intel e AMD'),
('Armazenamento', 'HDDs, SSDs, NVMe'),
('Placa de Vídeo', 'GPUs para games e trabalho'),
('Gabinete', 'Gabinetes ATX, Micro-ATX, Mini-ITX'),
('Cooler', 'Coolers e sistemas de refrigeração'),
('Outros', 'Outros componentes e periféricos');
