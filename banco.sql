-- Criação do Banco de Dados para o Supermercado Ranchão
CREATE DATABASE IF NOT EXISTS `ranchao_db` 
DEFAULT CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `ranchao_db`;

-- 1. Tabela de Usuários (Administrador, Caixas, Gerentes)
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `senha` VARCHAR(255) NOT NULL,
    `nivel` ENUM('admin', 'caixa', 'gerente') DEFAULT 'caixa',
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabela de Categorias
CREATE TABLE IF NOT EXISTS `categorias` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `descricao` VARCHAR(255) NULL
) ENGINE=InnoDB;

-- 3. Tabela de Produtos
CREATE TABLE IF NOT EXISTS `produtos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_barras` VARCHAR(50) UNIQUE NULL,
    `nome` VARCHAR(150) NOT NULL,
    `categoria_id` INT NULL,
    `preco_custo` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `preco_venda` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `estoque_atual` DECIMAL(10,3) NOT NULL DEFAULT 0.000, -- Aceita peso/fracionado (ex: 1.500 kg)
    `unidade_medida` ENUM('UN', 'KG', 'L', 'PCT', 'CX') DEFAULT 'UN',
    `estoque_minimo` DECIMAL(10,3) DEFAULT 5.000,
    `imagem` VARCHAR(255) NULL,
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Tabela de Clientes
CREATE TABLE IF NOT EXISTS `clientes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `cpf_cnpj` VARCHAR(20) NULL,
    `telefone` VARCHAR(20) NULL,
    `endereco` VARCHAR(255) NULL,
    `limite_credito` DECIMAL(10,2) DEFAULT 0.00, -- Para vendas no "fiado" / conta do cliente
    `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5. Tabela de Vendas (Cabeçalho do Pedido/Caixa)
CREATE TABLE IF NOT EXISTS `vendas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo_venda` VARCHAR(20) NOT NULL UNIQUE,
    `usuario_id` INT NULL, -- Quem realizou a venda (Caixa)
    `cliente_id` INT NULL,
    `valor_subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `desconto` DECIMAL(10,2) DEFAULT 0.00,
    `valor_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `forma_pagamento` ENUM('Dinheiro', 'Pix', 'Cartão de Débito', 'Cartão de Crédito', 'Fiado') NOT NULL DEFAULT 'Dinheiro',
    `status` ENUM('concluida', 'cancelada') DEFAULT 'concluida',
    `data_venda` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 6. Tabela dos Itens da Venda
CREATE TABLE IF NOT EXISTS `itens_venda` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `venda_id` INT NOT NULL,
    `produto_id` INT NOT NULL,
    `quantidade` DECIMAL(10,3) NOT NULL,
    `preco_unitario` DECIMAL(10,2) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`venda_id`) REFERENCES `vendas`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`)
) ENGINE=InnoDB;

-- Inserindo Dados Iniciais de Teste
INSERT INTO `categorias` (`nome`, `descricao`) VALUES
('Hortifrúti', 'Frutas, legumes e verduras frescas'),
('Bebidas', 'Refrigerantes, sucos, águas e alcoólicos'),
('Padaria & Confeitaria', 'Pães, bolos e salgados'),
('Laticínios & Frios', 'Queijos, presuntos, leites e iogurtes'),
('Mercearia', 'Arroz, feijão, óleo, massas e enlatados'),
('Higiene & Limpeza', 'Produtos para limpeza da casa e higiene pessoal');

INSERT INTO `usuarios` (`nome`, `email`, `senha`, `nivel`) VALUES
('Administrador', 'admin@ranchao.com', '$2y$10$e0MYzXyjpJS7Pd0RVvHwHe1f8O2XNnUeGg1oXb4R3Y4qK8h4f1k2e', 'admin'); -- Senha inicial: admin123