<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

// Indicadores rápidos para o Dashboard
$totalProdutos = $pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
$totalClientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
$estoqueBaixo = $pdo->query("SELECT COUNT(*) FROM produtos WHERE estoque_atual <= estoque_minimo")->fetchColumn();

$vendasHoje = $pdo->query("SELECT COALESCE(SUM(valor_total), 0) FROM vendas WHERE DATE(data_venda) = CURDATE() AND status = 'concluida'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Supermercado Ranchão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8f9fa; }
        .sidebar { background-color: #212529; min-height: 100vh; color: #fff; }
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 10px 15px; display: block; border-radius: 6px; margin-bottom: 5px; font-weight: 600; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #198754; }
        .card-metric { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); background: #fff; }
    </style>
</head>
<body>

    <div class="container-fluid">
        <div class="row">
            <!-- Menu Lateral -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse p-3">
                <h4 class="fw-bold text-success mb-4 text-center">🛒 RANCHÃO</h4>
                <div class="small text-uppercase text-muted fw-bold mb-2">Painel Operacional</div>
                <ul class="nav flex-column">
                    <li class="nav-item"><a href="index.php" class="active">📊 Dashboard</a></li>
                    <li class="nav-item"><a href="produtos.php">📦 Estoque / Produtos</a></li>
                    <li class="nav-item"><a href="vendas.php">💰 Frente de Caixa (PDV)</a></li>
                    <li class="nav-item"><a href="clientes.php">👥 Clientes & Fiado</a></li>
                    <li class="nav-item"><a href="usuarios.php">⚙️ Usuários</a></li>
                    <li class="nav-item mt-4"><a href="logout.php" class="text-danger">🚪 Sair do Sistema</a></li>
                </ul>
            </div>

            <!-- Conteúdo Principal -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold text-dark">Visão Geral do Supermercado</h2>
                    <span class="badge bg-success p-2">Olá, <?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
                </div>

                <!-- Cards de Indicadores -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card card-metric p-3">
                            <small class="text-muted fw-bold">Vendas de Hoje</small>
                            <h3 class="fw-bold text-success mt-2 mb-0">R$ <?= number_format($vendasHoje, 2, ',', '.') ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-metric p-3">
                            <small class="text-muted fw-bold">Total de Produtos</small>
                            <h3 class="fw-bold text-primary mt-2 mb-0"><?= $totalProdutos ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-metric p-3">
                            <small class="text-muted fw-bold">Produtos em Estoque Baixo</small>
                            <h3 class="fw-bold text-danger mt-2 mb-0"><?= $estoqueBaixo ?></h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-metric p-3">
                            <small class="text-muted fw-bold">Clientes Cadastrados</small>
                            <h3 class="fw-bold text-dark mt-2 mb-0"><?= $totalClientes ?></h3>
                        </div>
                    </div>
                </div>

                <div class="card card-metric p-4">
                    <h5 class="fw-bold mb-3">⚡ Atalhos Rápidos</h5>
                    <div class="d-flex gap-2">
                        <a href="produtos.php" class="btn btn-outline-success fw-bold">➕ Cadastrar Novo Produto</a>
                        <a href="vendas.php" class="btn btn-success fw-bold">💻 Abrir Frente de Caixa (PDV)</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>