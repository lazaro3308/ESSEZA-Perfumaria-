
<?php
session_start();

require_once __DIR__ . '/config/database.php';

// O pedido precisa ter sido criado pelo checkout.
if (empty($_SESSION['ultimo_pedido'])) {
    header('Location: index.php');
    exit;
}

$pedidoId = (int) $_SESSION['ultimo_pedido'];

$stmt = $pdo->prepare("
    SELECT id, nome, email, forma_pagamento, total, status
    FROM pedidos
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$pedidoId]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    header('Location: index.php');
    exit;
}

// Não permite iniciar o fluxo de pagamento de pedidos já encerrados.
if ($pedido['status'] !== 'pendente') {
    header('Location: pedido-confirmado.php');
    exit;
}

$mensagem = '';
$metodo = $pedido['forma_pagamento'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metodoEscolhido = $_POST['pagamento'] ?? '';

    if (!in_array($metodoEscolhido, ['pix', 'cartao'], true)) {
        $mensagem = 'Selecione uma forma de pagamento válida.';
    } else {
        // Somente demonstração: não cria cobrança nem altera o status.
        $metodo = $metodoEscolhido;
        $mensagem = 'Modo de teste: nenhuma cobrança foi criada. '
            . 'Seu pedido continua pendente.';
    }
}

function e($valor): string {
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento | Essenza Perfumes</title>

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: #101010;
            color: #f5f5f5;
            font-family: Arial, sans-serif;
        }

        header {
            background: #080808;
            border-bottom: 1px solid #b89545;
            padding: 22px 7%;
        }

        header a {
            color: #d8b45a;
            font-size: 23px;
            font-weight: bold;
            text-decoration: none;
        }

        main {
            width: 90%;
            max-width: 720px;
            margin: 40px auto;
        }

        .painel {
            background: #191919;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 26px;
            margin-bottom: 22px;
        }

        h1, h2 {
            color: #d8b45a;
            margin-bottom: 18px;
        }

        p {
            color: #ccc;
            line-height: 1.7;
        }

        .pedido {
            font-size: 14px;
            color: #aaa;
            margin-bottom: 10px;
        }

        .total {
            font-size: 28px;
            color: #e3c16f;
            font-weight: bold;
            margin-top: 12px;
        }

        .opcao {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 17px;
            margin: 12px 0;
            border: 1px solid #444;
            border-radius: 8px;
            cursor: pointer;
        }

        .opcao:hover {
            border-color: #d8b45a;
        }

        .opcao input {
            accent-color: #d8b45a;
            width: 18px;
            height: 18px;
        }

        .opcao strong {
            color: #f1d58a;
        }

        .opcao small {
            display: block;
            color: #aaa;
            margin-top: 6px;
        }

        .aviso {
            border: 1px solid #66552b;
            background: #242116;
            border-radius: 8px;
            padding: 15px;
            margin-top: 18px;
        }

        .mensagem {
            border: 1px solid #66552b;
            background: #242116;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        button, .voltar {
            display: inline-block;
            border: none;
            border-radius: 6px;
            padding: 14px 20px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            font-size: 15px;
        }

        button {
            background: #d8b45a;
            color: #111;
            width: 100%;
            margin-top: 15px;
        }

        .voltar {
            border: 1px solid #d8b45a;
            color: #d8b45a;
            margin-top: 15px;
        }

        @media (max-width: 500px) {
            .painel {
                padding: 19px;
            }
        }
    </style>
</head>

<body>
<header>
    <a href="index.php">ESSENZA PERFUMES</a>
</header>

<main>
    <section class="painel">
        <h1>Pagamento do pedido</h1>

        <p class="pedido">
            Pedido #<?= (int) $pedido['id'] ?>
        </p>

        <p>
            Cliente:
            <strong><?= e($pedido['nome']) ?></strong>
        </p>

        <div class="total">
            R$ <?= number_format((float) $pedido['total'], 2, ',', '.') ?>
        </div>

        <div class="aviso">
            <p>
                <strong>Ambiente de teste.</strong>
                Esta página ainda não está conectada à API do Nubank.
                Não há cobrança real nem QR Code Pix válido.
            </p>
        </div>
    </section>

    <section class="painel">
        <h2>Escolha como deseja pagar</h2>

        <?php if ($mensagem !== ''): ?>
            <div class="mensagem" role="status">
                <?= e($mensagem) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="pagamento.php">
            <label class="opcao">
                <input
                    type="radio"
                    name="pagamento"
                    value="pix"
                    <?= $metodo === 'pix' ? 'checked' : '' ?>
                    required
                >
                <span>
                    <strong>Pix</strong>
                    <small>
                        QR Code e código Pix serão disponibilizados
                        quando a integração estiver configurada.
                    </small>
                </span>
            </label>

            <label class="opcao">
                <input
                    type="radio"
                    name="pagamento"
                    value="cartao"
                    <?= $metodo === 'cartao' ? 'checked' : '' ?>
                    required
                >
                <span>
                    <strong>Cartão de crédito</strong>
                    <small>
                        O checkout seguro será integrado posteriormente.
                    </small>
                </span>
            </label>

            <button type="submit">Testar seleção de pagamento</button>
        </form>

        <a class="voltar" href="pedido-confirmado.php">
            Voltar ao pedido
        </a>
    </section>
</main>
</body>
</html>
