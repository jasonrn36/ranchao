<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$erro = '';
$sucesso = '';

// Inicializar Carrinho de Vendas na Sessão se não existir
if (!isset($_SESSION['carrinho_pdv'])) {
    $_SESSION['carrinho_pdv'] = [];
}

// Ações do PDV (Adicionar item, Remover item, Cancelar venda ou Finalizar)
$acao = $_GET['acao'] ?? '';

if ($acao === 'adicionar') {
    $produto_id = intval($_POST['produto_id'] ?? 0);
    $quantidade = floatval(str_replace(',', '.', $_POST['quantidade'] ?? 1));

    if ($produto_id > 0 && $quantidade > 0) {
        $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
        $stmt->execute([$produto_id]);
        $prod = $stmt->fetch();

        if ($prod) {
            // Verificar se tem estoque suficiente
            if ($prod['estoque_atual'] < $quantidade) {
                $erro = "Estoque insuficiente para o produto: " . $prod['nome'] . " (Disponível: " . $prod['estoque_atual'] . ")";
            } else {
                // Adicionar ou atualizar no carrinho da sessão
                if (isset($_SESSION['carrinho_pdv'][$produto_id])) {
                    $_SESSION['carrinho_pdv'][$produto_id]['quantidade'] += $quantidade;
                } else {
                    $_SESSION['carrinho_pdv'][$produto_id] = [
                        'id' => $prod['id'],
                        'nome' => $prod['nome'],
                        'preco_venda' => $prod['preco_venda'],
                        'quantidade' => $quantidade,
                        'unidade' => $prod['unidade_medida']
                    ];
                }
            }
        }
    }
} elseif ($acao === 'remover') {
    $id_remover = intval($_GET['id'] ?? 0);
    unset($_SESSION['carrinho_pdv'][$id_remover]);
} elseif ($acao === 'cancelar') {
    $_SESSION['carrinho_pdv'] = [];
    header("Location: vendas.php");
    exit;
} elseif ($acao === 'finalizar') {
    $cliente_id = !empty($_POST['cliente_id']) ? intval($_POST['cliente_id']) : null;
    $forma_pagamento = $_POST['forma_pagamento'] ?? 'Dinheiro';

    if (empty($_SESSION['carrinho_pdv'])) {
        $erro = "O carrinho de vendas está vazio.";
    } else {
        try {
            $pdo->beginTransaction();

            // Calcular valor total
            $valor_total = 0;
            foreach ($_SESSION['carrinho_pdv'] as $item) {
                $valor_total += $item['preco_venda'] * $item['quantidade'];
            }

            // Gerar código único da venda
            $codigo_venda = 'VD-' . date('Ymd') . '-' . rand(1000, 9999);

            // Inserir cabeçalho da venda
            $stmtVenda = $pdo->prepare("INSERT INTO vendas (codigo_venda, usuario_id, cliente_id, valor_subtotal, valor_total, forma_pagamento, status) VALUES (?, ?, ?, ?, ?, ?, 'concluida')");
            $stmtVenda->execute([$codigo_venda, $_SESSION['usuario_id'], $cliente_id, $valor_total, $valor_total, $forma_pagamento]);
            $venda_id = $pdo->lastInsertId();

            // Inserir itens e baixar estoque
            foreach ($_SESSION['carrinho_pdv'] as $item) {
                $subtotal_item = $item['preco_venda'] * $item['quantidade'];

                $stmtItem = $pdo->prepare("INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
                $stmtItem->execute([$venda_id, $item['id'], $item['quantidade'], $item['preco_venda'], $subtotal_item]);

                // Baixar do estoque
                $stmtEstoque = $pdo->prepare("UPDATE produtos SET estoque_atual = estoque_atual - ? WHERE id = ?");
                $stmtEstoque->execute([$item['quantidade'], $item['id']]);
            }

            $pdo->commit();
            $_SESSION['carrinho_pdv'] = []; // Limpar carrinho
            $sucesso = "Venda finalizada com sucesso! Código: " . $codigo_venda;
        } catch (\Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao finalizar a venda: " . $e->getMessage();
        }
    }
}

// Buscar produtos para autocomplete/seleção e clientes para fiado/cadastro
$listaProdutos = $pdo->query("SELECT id, nome, preco_venda, estoque_atual, unidade_medida FROM produtos WHERE estoque_atual > 0 ORDER BY nome ASC")->fetchAll();
$listaClientes = $pdo->query("SELECT id, nome FROM clientes ORDER BY nome ASC")->fetchAll();

// Calcular total atual do carrinho
$totalCarrinho = 0;
foreach ($_SESSION['carrinho_pdv'] as $i) {
    $totalCarrinho += $i['preco_venda'] * $i['quantidade'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Frente de Caixa (PDV) – Supermercado Ranchão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8f9fa; }
        .sidebar { background-color: #212529; min-height: 100vh; color: #fff; }
        .sidebar a { color: #adb5bd; text-decoration: none; padding: 10px 15px; display: block; border-radius: 6px; margin-bottom: 5px; font-weight: 600; }
        .sidebar a:hover, .sidebar a.active { color: #fff; background-color: #198754; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.04); background: #fff; }
        .total-box { background-color: #198754; color: white; border-radius: 12px; }
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
                    <li class="nav-item"><a href="produtos.php">📦 Estoque / Produtos</a></li>
                    <li class="nav-item"><a href="vendas.php" class="active">💰 Frente de Caixa (PDV)</a></li>
                    <li class="nav-item"><a href="clientes.php">👥 Clientes & Fiado</a></li>
                    <li class="nav-item"><a href="usuarios.php">⚙️ Usuários</a></li>
                    <li class="nav-item mt-4"><a href="logout.php" class="text-danger">🚪 Sair do Sistema</a></li>
                </ul>
            </div>

            <!-- Conteúdo Principal / PDV -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold text-dark">💻 Frente de Caixa (PDV)</h2>
                    <span class="badge bg-success p-2">Operador: <?= htmlspecialchars($_SESSION['usuario_nome']) ?></span>
                </div>

                <?php if ($sucesso): ?>
                    <div class="alert alert-success py-2 rounded-3"><?= htmlspecialchars($sucesso) ?></div>
                <?php endif; ?>
                <?php if ($erro): ?>
                    <div class="alert alert-danger py-2 rounded-3"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Lado Esquerdo: Adicionar Produtos e Tabela do Carrinho -->
                    <div class="col-lg-8">
                        <div class="card card-custom p-4 mb-4">
                            <h5 class="fw-bold mb-3 text-success">➕ Inserir Item no Carrinho</h5>
                            <form action="vendas.php?acao=adicionar" method="POST" class="row g-3">
                                <div class="col-md-7">
                                    <label class="form-label small fw-bold">Selecionar Produto</label>
                                    <select name="produto_id" class="form-select rounded-3" required>
                                        <option value="">Escolha o produto...</option>
                                        <?php foreach ($listaProdutos as $p): ?>
                                            <option value="<?= $p['id'] ?>">
                                                <?= htmlspecialchars($p['nome']) ?> — R$ <?= number_format($p['preco_venda'], 2, ',', '.') ?> (Estoque: <?= $p['estoque_atual'] . ' ' . $p['unidade_medida'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Quantidade</label>
                                    <input type="text" name="quantidade" class="form-control rounded-3" value="1" required>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-success w-100 fw-bold rounded-3">Adicionar</button>
                                </div>
                            </form>
                        </div>

                        <!-- Tabela de Itens da Venda Atual -->
                        <div class="card card-custom p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="fw-bold mb-0">🛒 Itens da Compra Atual</h5>
                                <?php if (!empty($_SESSION['carrinho_pdv'])): ?>
                                    <a href="vendas.php?acao=cancelar" class="btn btn-outline-danger btn-sm fw-bold">Cancelar Venda</a>
                                <?php endif; ?>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produto</th>
                                            <th>Preço Unit.</th>
                                            <th>Qtd</th>
                                            <th>Subtotal</th>
                                            <th>Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($_SESSION['carrinho_pdv'])): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">Nenhum item adicionado à venda atual.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($_SESSION['carrinho_pdv'] as $item): ?>
                                                <tr>
                                                    <td class="fw-bold"><?= htmlspecialchars($item['nome']) ?></td>
                                                    <td>R$ <?= number_format($item['preco_venda'], 2, ',', '.') ?></td>
                                                    <td><?= number_format($item['quantidade'], 3, ',', '.') . ' ' . $item['unidade'] ?></td>
                                                    <td class="text-success fw-bold">R$ <?= number_format($item['preco_venda'] * $item['quantidade'], 2, ',', '.') ?></td>
                                                    <td>
                                                        <a href="vendas.php?acao=remover&id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger">Remover</a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Lado Direito: Resumo e Pagamento -->
                    <div class="col-lg-4">
                        <div class="card total-box p-4 mb-3 shadow-sm">
                            <small class="text-uppercase fw-bold opacity-75">Valor Total a Pagar</small>
                            <h1 class="fw-bold display-5 mt-2 mb-0">R$ <?= number_format($totalCarrinho, 2, ',', '.') ?></h1>
                        </div>

                        <div class="card card-custom p-4">
                            <h5 class="fw-bold mb-3">💳 Fechamento de Caixa</h5>
                            <form action="vendas.php?acao=finalizar" method="POST">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Cliente (Opcional)</label>
                                    <select name="cliente_id" class="form-select rounded-3">
                                        <option value="">Cliente Consumidor / Balcão</option>
                                        <?php foreach ($listaClientes as $c): ?>
                                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nome']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label small fw-bold">Forma de Pagamento</label>
                                    <select name="forma_pagamento" class="form-select rounded-3" required>
                                        <option value="Dinheiro">Dinheiro</option>
                                        <option value="Pix">Pix</option>
                                        <option value="Cartão de Débito">Cartão de Débito</option>
                                        <option value="Cartão de Crédito">Cartão de Crédito</option>
                                        <option value="Fiado">Fiado (Conta do Cliente)</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-success w-100 py-3 fw-bold rounded-3 shadow-sm" <?= empty($_SESSION['carrinho_pdv']) ? 'disabled' : '' ?>>
                                    Finalizar Venda & Imprimir
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>