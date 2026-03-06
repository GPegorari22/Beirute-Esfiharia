CREATE DATABASE IF NOT EXISTS beirute;
USE beirute;

CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('comum', 'admin', 'funcionario') NOT NULL DEFAULT 'comum',
    telefone VARCHAR(15),
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB;

CREATE TABLE enderecos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    rua VARCHAR(255) NOT NULL,
    numero VARCHAR(10) NOT NULL,
    bairro VARCHAR(50) NOT NULL,
    cidade VARCHAR(50) NOT NULL,
    cep VARCHAR(10) NOT NULL,
    complemento VARCHAR(100),
    principal BOOLEAN NOT NULL DEFAULT FALSE,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    categoria ENUM('tradicionais', 'especiais', 'vegetarianas', 'doces', 'combos', 'bebidas') NOT NULL,
    imagem VARCHAR(255) NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE carrinhos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    ativo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE itens_carrinho (
    id_item INT AUTO_INCREMENT PRIMARY KEY,
    id_carrinho INT NOT NULL,
    id_produto INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) AS (quantidade * preco_unitario) STORED,
    FOREIGN KEY (id_carrinho) REFERENCES carrinhos(id),
    FOREIGN KEY (id_produto) REFERENCES produtos(id)
) ENGINE=InnoDB;

CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT,
    id_endereco INT,
    id_carrinho INT,
    valor_total DECIMAL(10,2) NOT NULL,
    metodo_pagamento ENUM('pix', 'debito', 'credito', 'dinheiro') NOT NULL,
    status_pedido ENUM('pendente', 'em preparo', 'saiu para entrega', 'entregue', 'cancelado'),
    data_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    observacoes TEXT,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    FOREIGN KEY (id_endereco) REFERENCES enderecos(id),
    FOREIGN KEY (id_carrinho) REFERENCES carrinhos(id)
) ENGINE=InnoDB;

CREATE TABLE historico_pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    status_anterior ENUM('pendente', 'em preparo', 'saiu para entrega', 'entregue', 'cancelado') NOT NULL,
    status_novo ENUM('pendente', 'em preparo', 'saiu para entrega', 'entregue', 'cancelado') NOT NULL,
    id_usuario_responsavel INT NOT NULL,
    data_alteracao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pedido) REFERENCES pedidos(id),
    FOREIGN KEY (id_usuario_responsavel) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_pedido INT,
    mensagem VARCHAR(255) NOT NULL,
    lida BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id),
    FOREIGN KEY (id_pedido) REFERENCES pedidos(id)
) ENGINE=InnoDB;

ALTER TABLE pedidos
MODIFY COLUMN status_pedido ENUM('pendente', 'em preparo', 'saiu para entrega', 'entregue', 'cancelado') 
DEFAULT 'pendente';

ALTER TABLE carrinhos
ADD COLUMN status ENUM('ativo', 'finalizado', 'abandonado') DEFAULT 'ativo';

INSERT INTO usuarios (nome, email, senha, perfil, telefone)
VALUES
('Cliente Teste', 'cliente@teste.com', 'cliente123', 'comum', '11999999999'),
('Funcionario Teste', 'funcionario@teste.com','funcionario123', 'funcionario', '11988888888'),
('Admin Teste', 'admin@teste.com', 'admin123', 'admin', '11977777777');

SELECT * FROM usuarios;

ALTER TABLE produtos
ADD COLUMN quantidade_vendida INT DEFAULT 0;

CREATE TABLE ingredientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    estoque INT NOT NULL DEFAULT 0,
    ativo BOOLEAN DEFAULT TRUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE produto_ingredientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_produto INT NOT NULL,
    id_ingrediente INT NOT NULL,
    quantidade_por_produto INT NOT NULL DEFAULT 1,
    FOREIGN KEY (id_produto) REFERENCES produtos(id),
    FOREIGN KEY (id_ingrediente) REFERENCES ingredientes(id)
) ENGINE=InnoDB;

CREATE TABLE historico_itens_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pedido INT NOT NULL,
    id_produto INT NOT NULL,
    quantidade INT NOT NULL,
    preco_unitario DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_pedido) REFERENCES pedidos(id),
    FOREIGN KEY (id_produto) REFERENCES produtos(id)
) ENGINE=InnoDB;

CREATE TABLE banners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descricao TEXT,
    imagem VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    data_inicio DATE,
    data_fim DATE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO usuarios (nome, email, senha, perfil, telefone)
VALUES (
  'Guilherme Pegorari',
  'gpegorari9@gmail.com',
  SHA2(CONCAT('S@ltFix0_2025!', 'gui12345'), 256),
  'admin',
  '11999999999'
);

ALTER TABLE banners ADD COLUMN ordem INT DEFAULT 0 AFTER ativo;

ALTER TABLE usuarios 
ADD COLUMN imagem_perfil VARCHAR(255) DEFAULT 'avatar-default.png' AFTER telefone;

ALTER TABLE notificacoes 
ADD COLUMN IF NOT EXISTS tipo ENUM('pedido_confirmado', 'pedido_preparando', 'pedido_pronto', 'pedido_entregue', 'pedido_cancelado') DEFAULT 'pedido_confirmado';