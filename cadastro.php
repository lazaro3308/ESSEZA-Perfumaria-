
<?php
session_start();

if (empty($_SESSION['token_cadastro'])) {
    $_SESSION['token_cadastro'] = bin2hex(random_bytes(32));
}

$erro = $_SESSION['erro_cadastro'] ?? '';
unset($_SESSION['erro_cadastro']);

$sucesso = isset($_GET['sucesso']) && $_GET['sucesso'] === '1';

function e($valor): string {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Criar conta | Essenza Perfumes</title>

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
            padding: 25px;
            background: #101010;
            color: #f5f5f5;
            font-family: Arial, sans-serif;
        }

        .cadastro {
            width: 100%;
            max-width: 460px;
            padding: 35px;
            background: #191919;
            border: 1px solid #3c3320;
            border-radius: 14px;
        }

        .logo {
            display: block;
            margin-bottom: 30px;
            color: #d8b45a;
            font-size: 23px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
        }

        h1 {
            margin-bottom: 10px;
            color: #d8b45a;
            text-align: center;
        }

        .descricao {
            margin-bottom: 25px;
            color: #aaa;
            line-height: 1.6;
            text-align: center;
        }

        .campo {
            margin-bottom: 18px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #ddd;
        }

        input {
            width: 100%;
            padding: 13px;
            background: #101010;
            border: 1px solid #444;
            border-radius: 6px;
            color: white;
            font-size: 15px;
        }

        input:focus {
            outline: 1px solid #d8b45a;
            border-color: #d8b45a;
        }

        button {
            width: 100%;
            padding: 14px;
            margin-top: 5px;
            background: #d8b45a;
            border: none;
            border-radius: 6px;
            color: #111;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #edcd78;
        }

        .mensagem {
            margin-bottom: 20px;
            padding: 13px;
            border-radius: 6px;
            line-height: 1.5;
        }

        .erro {
            background: #351c1c;
            border: 1px solid #813b3b;
            color: #ffbaba;
        }

        .sucesso {
            background: #193322;
            border: 1px solid #356b48;
            color: #b8f0c8;
        }

        .rodape {
            margin-top: 24px;
            color: #aaa;
            text-align: center;
            line-height: 1.8;
        }

        a.link {
            color: #e3c16f;
            text-decoration: none;
        }

        @media (max-width: 480px) {
            .cadastro {
                padding: 25px 20px;
            }
        }
    </style>
</head>

<body>

<div class="cadastro">

    <a href="index.php" class="logo">ESSENZA PERFUMES</a>

    <h1>Criar sua conta</h1>

    <p class="descricao">
        Cadastre-se para aproveitar sua experiência na Essenza.
    </p>

    <?php if ($erro !== ''): ?>
        <div class="mensagem erro">
            <?= e($erro) ?>
        </div>
    <?php endif; ?>

    <?php if ($sucesso): ?>
        <div class="mensagem sucesso">
            Cadastro realizado com sucesso! Sua conta já está criada.
            Agora você pode acessar a página de login quando ela estiver pronta.
        </div>
    <?php endif; ?>

    <form action="processar-cadastro.php" method="post">

        <input
            type="hidden"
            name="token"
            value="<?= e($_SESSION['token_cadastro']) ?>"
        >

        <div class="campo">
            <label for="nome">Nome completo</label>
            <input
                type="text"
                id="nome"
                name="nome"
                maxlength="150"
                autocomplete="name"
                required
            >
        </div>

        <div class="campo">
            <label for="email">E-mail</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                autocomplete="email"
                required
            >
        </div>

        <div class="campo">
            <label for="senha">Senha</label>
            <input
                type="password"
                id="senha"
                name="senha"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                required
            >
        </div>

        <div class="campo">
            <label for="confirmar_senha">Confirmar senha</label>
            <input
                type="password"
                id="confirmar_senha"
                name="confirmar_senha"
                minlength="8"
                maxlength="72"
                autocomplete="new-password"
                required
            >
        </div>

        <button type="submit">Criar minha conta</button>

    </form>

    <p class="rodape">
        <a href="index.php" class="link">Voltar para a loja</a>
    </p>

</div>

</body>
</html>
