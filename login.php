<?php
session_start();

if (!empty($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$erro = $_SESSION['erro_login'] ?? '';
unset($_SESSION['erro_login']);

$sucesso = isset($_GET['cadastro']);
$destino = $_GET['redirect'] ?? 'finalizar-compra.php';

// Aceita somente destinos internos conhecidos.
if (!in_array($destino, ['finalizar-compra.php', 'index.php'], true)) {
    $destino = 'finalizar-compra.php';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entrar | Essenza Perfumes</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #101010;
            color: #f5f0e8;
            font-family: Arial, sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 430px;
            padding: 38px 30px;
            background: #191919;
            border: 1px solid #4b3a20;
            border-radius: 14px;
            box-shadow: 0 10px 35px #0008;
        }

        .logo {
            text-align: center;
            color: #d8b15f;
            font-size: 30px;
            letter-spacing: 3px;
            margin-bottom: 10px;
        }

        .subtitulo {
            text-align: center;
            color: #bdb8af;
            margin-bottom: 30px;
            font-size: 14px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #e8d6b0;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 13px;
            margin-bottom: 20px;
            background: #101010;
            border: 1px solid #49433a;
            border-radius: 7px;
            color: white;
            outline: none;
        }

        input:focus {
            border-color: #d8b15f;
        }

        button {
            width: 100%;
            padding: 14px;
            background: #d8b15f;
            color: #17130b;
            border: none;
            border-radius: 7px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
        }

        button:hover {
            background: #f0ce82;
        }

        .mensagem {
            padding: 12px;
            margin-bottom: 18px;
            border-radius: 7px;
            font-size: 14px;
            line-height: 1.5;
        }

        .erro {
            background: #481e1e;
            color: #ffd4d4;
        }

        .sucesso {
            background: #193b27;
            color: #d4ffdf;
        }

        .links {
            margin-top: 24px;
            text-align: center;
            font-size: 14px;
            line-height: 2;
            color: #bdb8af;
        }

        a {
            color: #d8b15f;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .voltar {
            display: block;
            margin-top: 20px;
            text-align: center;
            color: #bdb8af;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <main class="login-container">
        <h1 class="logo">ESSENZA</h1>
        <p class="subtitulo">Entre na sua conta</p>

        <?php if ($sucesso): ?>
            <div class="mensagem sucesso">
                Cadastro realizado! Agora entre com seu e-mail e senha.
            </div>
        <?php endif; ?>

        <?php if ($erro !== ''): ?>
            <div class="mensagem erro">
                <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="processar-login.php" method="POST">
            <input
                type="hidden"
                name="redirect"
                value="<?= htmlspecialchars($destino, ENT_QUOTES, 'UTF-8') ?>"
            >

            <label for="email">E-mail</label>
            <input
                type="email"
                id="email"
                name="email"
                autocomplete="email"
                maxlength="150"
                required
            >

            <label for="senha">Senha</label>
            <input
                type="password"
                id="senha"
                name="senha"
                autocomplete="current-password"
                required
            >

            <button type="submit">ENTRAR NA MINHA CONTA</button>
        </form>

        <div class="links">
            Ainda não tem conta?
            <a href="cadastro.php">Criar minha conta</a>
        </div>

        <a class="voltar" href="index.php">
            ← Voltar para a loja
        </a>
    </main>
</body>
</html>
