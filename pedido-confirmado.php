
<?php
session_start();

require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['ultimo_pedido'])) {
    header('Location: index.php');
    exit;
}

$pedidoId = (int) $_SESSION['ultimo_pedido'];

$stmt = $pdo->prepare("
    SELECT id, nome, email, forma_pagamento, total, status, criado_em
    FROM pedidos
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$pedidoId]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    unset($_SESSION['ultimo_pedido']);
    header('Location: index.php');
    exit;
}

$stmtItens = $pdo->prepare("
    SELECT nome_produto, preco_unitario, quantidade, subtotal
    FROM pedido_itens
    WHERE pedido_id = ?
");

$stmtItens->execute([$pedidoId]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

unset($_SESSION['ultimo_pedido']);

$formaPagamento = $pedido['forma_pagamento'] === 'pix'
    ? 'Pix'
    : 'Cartão de crédito';

$statusTexto = [
    'pendente' => 'Aguardando pagamento',
    'pago' => 'Pagamento confirmado',
    'cancelado' => 'Pedido cancelado'
];

$status = $statusTexto[$pedido['status']] ?? 'Status desconhecido';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pedido confirmado | Essenza Perfumes</title>

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
            font-size: 24px;
            font-weight: bold;
            text-decoration: none;
        }

        main {
            width: 90%;
            max-width: 800px;
            margin: 45px auto;
        }

        .painel {
            background: #191919;
            border: 1px solid #333;
            border-radius: 14px;
            padding: 30px;
            margin-bottom: 25px;
        }

        .icone {
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 22px;
            border: 2px solid #d8b45a;
            border-radius: 50%;
            color: #d8b45a;
            font-size: 38px;
        }

        h1 {
            color: #d8b45a;
            margin-bottom: 14px;
        }

        h2 {
            color: #d8b45a;
            font-size: 20px;
            margin-bottom: 20px;
        }

        p {
            color: #ccc;
            line-height: 1.7;
        }

        .numero {
            display: inline-block;
            color: #f1d58a;
            font-size: 25px;
            font-weight: bold;
            margin: 12px 0;
        }

        .status {
            display: inline-block;
            padding: 9px 13px;
            margin-top: 15px;
            border: 1px solid #b89545;
            border-radius: 20px;
            color: #f1d58a;
            font-size: 14px;
        }

        .item {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #333;
        }

        .item small {
            display: block;
            color: #aaa;
            margin-top: 7px;
        }

        .valor {
            color: #e3c16f;
            white-space: nowrap;
        }

        .linha {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #333;
        }

        .linha span:first-child {
            color: #aaa;
        }

        .total {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 22px;
            font-size: 22px;
            font-weight: bold;
        }

        .aviso {
            background: #242116;
            border: 1px solid #65552c;
            border-radius: 8px;
            padding: 16px;
            margin-top: 20px;
        }

        .botoes {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 25px;
        }

        .botao {
            display: inline-block;
            padding: 14px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
        }

        .principal {
            background: #d8b45a;
            color: #111;
        }

        .secundario {
            border: 1px solid #d8b45a;
            color: #d8b45a;
        }

        @media (max-width: 550px) {
            .painel {
                padding: 20px;
            }

            header a {
                font-size: 20px;
            }

            .total {
                font-size: 19px;
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
        <div class="icone">✓</div>

        <h1>Pedido registrado!</h1>

        <p>
            Obrigado pela sua compra,
            <strong>
                <?= htmlspecialchars($pedido['nome'], ENT_QUOTES, 'UTF-8') ?>
            </strong>!
        </p>

        <p>Seu pedido foi registrado com sucesso em nossa loja.</p>

        <div class="numero">
            Pedido #<?= (int) $pedido['id'] ?>
        </div>

        <br>

        <span class="status">
            <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
        </span>

        <div class="aviso">
            <p>
                <strong>Importante:</strong>
                esta é uma versão de teste. Nenhum pagamento foi realizado
                automaticamente. Guarde o número do pedido para consulta.
            </p>
        </div>
    </section>

    <section class="painel">
        <h2>Detalhes do pedido</h2>

        <?php foreach ($itens as $item): ?>
            <div class="item">
                <div>
                    <strong>
                        <?= htmlspecialchars($item['nome_produto'], ENT_QUOTES, 'UTF-8') ?>
                    </strong>

                    <small>
                        Quantidade: <?= (int) $item['quantidade'] ?>
                    </small>
                </div>

                <strong class="valor">
                    R$ <?= number_format((float) $item['subtotal'], 2, ',', '.') ?>
                </strong>
            </div>
        <?php endforeach; ?>

        <div class="linha">
            <span>Forma de pagamento</span>
            <strong>
                <?= htmlspecialchars($formaPagamento, ENT_QUOTES, 'UTF-8') ?>
            </strong>
        </div>

        <div class="linha">
            <span>E-mail</span>
            <strong>
                <?= htmlspecialchars($pedido['email'], ENT_QUOTES, 'UTF-8') ?>
            </strong>
        </div>

        <div class="linha">
            <span>Data do pedido</span>
            <strong>
                <?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?>
            </strong>
        </div>

        <div class="total">
            <span>Total</span>

            <span class="valor">
                R$ <?= number_format((float) $pedido['total'], 2, ',', '.') ?>
            </span>
        </div>

        <div class="botoes">
            <a href="index.php" class="botao principal">
                Continuar comprando
            </a>

            <a href="carrinho.php" class="botao secundario">
                Ver carrinho
            </a>
        </div>
    </section>
</main>

</body>
</html>
