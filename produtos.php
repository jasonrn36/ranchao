<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$mensagem = '';
$erro = '';

// Processar cadastro ou edição de produto
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo_barras = trim($_POST['codigo_barras'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $categoria_id = !empty($_POST['categoria_id']) ? $_POST['categoria_id'] : null;
    $preco_custo = str_replace(',', '.', $_POST['preco_custo'] ?? 0);
    $preco_venda = str_replace(',', '.', $_POST['preco_venda'] ?? 0);
    $estoque_atual = str_replace(',', '.', $_POST['estoque_atual'] ?? 0);
    $unidade_medida = $_POST['unidade_medida'] ?? 'UN';
    $estoque_minimo = str_replace(',', '.', $_POST['estoque_minimo'] ?? 5);

    if (empty($nome) || empty($preco_venda)) {
        $erro = "O nome do produto e o preço de venda são obrigatórios.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO produtos (codigo_barras, nome, categoria_id, preco_custo, preco_venda, estoque_atual, unidade_medida, estoque_minimo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$codigo_barras, $nome, $categoria_id, $preco_custo, $preco_venda, $estoque_atual, $unidade_medida, $estoque_minimo]);
            $mensagem = "Produto cadastrado com sucesso!";
        } catch (\PDOException $e) {
            $erro = "Erro ao cadastrar produto (Código de barras duplicado?).";
        }
    }
}

// Buscar categorias e produtos cadastrados
$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();
$produtos = $pdo->query("SELECT p.*, c.nome as categoria_nome FROM produtos p LEFT JOIN categorias c ON p.categoria_id = c.id ORDER BY p.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque e Produtos – Supermercado Ranchão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8f9fa; }
        .sidebar { background-color: #212529; min-height: 100vh; color: #fff; }
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 10px 15px; display: block; border-radius: 6px; margin-bottom: 5px; font-weight: 600; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #198754; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); background: #fff; }
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
                    <li class="nav-item"><a href="index.php">📊 Dashboard</a></li>
                    <li class="nav-item"><a href="produtos.php" class="active">📦 Estoque / Produtos</a></li>
                    <li class="nav-item"><a href="vendas.php">💰 Frente de Caixa (PDV)</a></li>
                    <li class="nav-item"><a href="clientes.php">👥 Clientes & Fiado</a></li>
                    <li class="nav-item"><a href="usuarios.php">⚙️ Usuários</a></li>
                    <li class="nav-item mt-4"><a href="logout.php" class="text-danger">🚪 Sair do Sistema</a></li>
                </ul>
            </div>

            <!-- Conteúdo Principal -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold text-dark">Gestão de Estoque e Produtos</h2>
                    <span class="badge bg-success p-2">Supermercado Ranchão</span>
                </div>

                <?php if ($mensagem): ?>
                    <div class="alert alert-success py-2 rounded-3"><?= htmlspecialchars($mensagem) ?></div>
                <?php endif; ?>
                <?php if ($erro): ?>
                    <div class="alert alert-danger py-2 rounded-3"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <!-- Formulário de Cadastro -->
                <div class="card card-custom p-4 mb-4">
                    <h5 class="fw-bold mb-3 text-success">➕ Cadastrar Novo Produto</h5>
                    <form method="POST" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Código de Barras</label>
                            <input type="text" name="codigo_barras" class="form-control rounded-3" placeholder="Opcional">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-bold">Nome do Produto *</label>
                            <input type="text" name="nome" class="form-control rounded-3" required placeholder="Ex: Arroz Tio João 5kg">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Categoria</label>
                            <select name="categoria_id" class="form-select rounded-3">
                                <option value="">Selecione...</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Preço Custo (R$)</label>
                            <input type="text" name="preco_custo" class="form-control rounded-3" value="0.00">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Preço Venda (R$) *</label>
                            <input type="text" name="preco_venda" class="form-control rounded-3" required value="0.00">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Estoque Atual</label>
                            <input type="text" name="estoque_atual" class="form-control rounded-3" value="0.000">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Unidade</label>
                            <select name="unidade_medida" class="form-select rounded-3">
                                <option value="UN">UN (Unidade)</option>
                                <option value="KG">KG (Quilo)</option>
                                <option value="L">L (Litro)</option>
                                <option value="PCT">PCT (Pacote)</option>
                                <option value="CX">CX (Caixa)</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Estoque Mínimo</label>
                            <input type="text" name="estoque_minimo" class="form-control rounded-3" value="5.000">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100 fw-bold rounded-3">Salvar</button>
                        </div>
                    </form>
                </div>

                <!-- Tabela de Produtos Cadastrados -->
                <div class="card card-custom p-4">
                    <h5 class="fw-bold mb-3">📦 Produtos Cadastrados no Sistema</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                ID
                                <th>Cód. Barras</th>
                                <th>Produto</th>
                                <th>Categoria</th>
                                <th>Preço Venda</th>
                                <th>Estoque</th>
                                <th>Unidade</th>
                            </thead>
                            <tbody>
                                <?php if (empty($produtos)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-3">Nenhum produto cadastrado ainda.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($produtos as $p): ?>
                                        <tr>
                                            <td><?= $p['id'] ?></td>
                                            <td><code><?= htmlspecialchars($p['codigo_barras'] ?? '---') ?></code></td>
                                            <td class="fw-bold"><?= htmlspecialchars($p['nome']) ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($p['categoria_nome'] ?? 'Sem Categoria') ?></span></td>
                                            <td class="text-success fw-bold">R$ <?= number_format($p['preco_venda'], 2, ',', '.') ?></td>
                                            <td class="<?= $p['estoque_atual'] <= $p['estoque_minimo'] ? 'text-danger fw-bold' : '' ?>">
                                                <?= number_format($p['estoque_atual'], 3, ',', '.') ?>
                                            </td>
                                            <td><?= $p['unidade_medida'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>