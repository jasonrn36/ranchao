<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $erro = "Por favor, preencha o e-mail e a senha.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_nivel'] = $usuario['nivel'];
            header("Location: index.php");
            exit;
        } else {
            $erro = "E-mail ou senha incorretos.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Supermercado Ranchão</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f4f6f9; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { border: none; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); width: 100%; max-width: 400px; background: #ffffff; }
        .btn-primary-custom { background-color: #198754; border-color: #198754; font-weight: 600; }
        .btn-primary-custom:hover { background-color: #157347; border-color: #146c43; }
    </style>
</head>
<body>
    <div class="login-card p-4 p-md-5">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-success">🛒 Ranchão</h3>
            <p class="text-muted small">Sistema de Gestão para Supermercado</p>
        </div>

        <?php if ($erro): ?>
            <div class="alert alert-danger py-2 small rounded-3"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label small fw-bold">E-mail de Acesso</label>
                <input type="email" name="email" class="form-control rounded-3" required placeholder="admin@ranchao.com">
            </div>
            <div class="mb-4">
                <label class="form-label small fw-bold">Senha</label>
                <input type="password" name="senha" class="form-control rounded-3" required placeholder="********">
            </div>
            <button type="submit" class="btn btn-primary-custom w-100 py-2 rounded-3 shadow-sm">Entrar no Sistema</button>
        </form>
        <div class="text-center mt-3 text-muted small">
            Dica padrão: admin@ranchao.com / admin123
        </div>
    </div>
</body>
</html>