
<?php
session_start();

require_once __DIR__ . '/config/database.php';

if (empty($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
    header('Location: carrinho.php');
    exit;
}

$ids = array_map('intval', array_keys($_SESSION['carrinho']));
$ids = array_values(array_filter($ids, fn($id) => $id > 0));

if (empty($ids)) {
    header('Location: carrinho.php');
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));

$sql = "SELECT id, nome, preco, imagem, estoque
        FROM produtos
        WHERE id IN ($placeholders) AND ativo = 1";

$stmt = $pdo->prepare($sql);
$stmt->execute($ids);
$produtosEncontrados = $stmt->fetchAll(PDO::FETCH_ASSOC);

$produtos = [];
$total = 0;

foreach ($produtosEncontrados as $produto) {
    $id = (int) $produto['id'];
    $quantidade = (int) ($_SESSION['carrinho'][$id] ?? 0);

    if ($quantidade < 1 || $quantidade > (int) $produto['estoque']) {
        continue;
    }

    $produto['quantidade'] = $quantidade;
    $produto['subtotal'] = round((float) $produto['preco'] * $quantidade, 2);

    $total += $produto['subtotal'];
    $produtos[] = $produto;
}

if (empty($produtos)) {
    header('Location: carrinho.php');
    exit;
}

$total = round($total, 2);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Finalizar compra | Essenza Perfumes</title>

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
            text-decoration: none;
            font-size: 24px;
            font-weight: bold;
        }

        main {
            width: 90%;
            max-width: 1050px;
            margin: 40px auto;
        }

        h1 {
            color: #d8b45a;
            margin-bottom: 10px;
        }

        .subtitulo {
            color: #bbb;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .painel {
            background: #191919;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }

        h2 {
            color: #d8b45a;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .produto {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #333;
        }

        .produto small {
            display: block;
            color: #aaa;
            margin-top: 7px;
        }

        .preco {
            color: #e3c16f;
            white-space: nowrap;
        }

        .total {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-top: 20px;
            font-size: 22px;
            font-weight: bold;
        }

        form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .campo {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .campo.completo {
            grid-column: 1 / -1;
        }

        label {
            color: #ddd;
        }

        input, textarea {
            width: 100%;
            padding: 13px;
            background: #101010;
            color: white;
            border: 1px solid #444;
            border-radius: 6px;
            font-size: 15px;
        }

        input:focus, textarea:focus {
            outline: 1px solid #d8b45a;
            border-color: #d8b45a;
        }

        .pagamento-opcao {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 17px;
            border: 1px solid #444;
            border-radius: 8px;
            cursor: pointer;
            transition: border-color .2s, background .2s;
        }

        .pagamento-opcao:hover {
            border-color: #d8b45a;
            background: #222;
        }

        .pagamento-opcao input[type="radio"] {
            width: 18px;
            height: 18px;
            accent-color: #d8b45a;
            flex-shrink: 0;
        }

        .pagamento-opcao strong {
            color: #f1d58a;
        }

        .pagamento-opcao small {
            display: block;
            color: #aaa;
            margin-top: 5px;
        }

        .aviso {
            color: #bbb;
            line-height: 1.6;
            font-size: 14px;
        }

        .botoes {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 25px;
        }

        .botao {
            display: inline-block;
            padding: 14px 22px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            text-align: center;
            cursor: pointer;
            font-size: 15px;
        }

        .principal {
            background: #d8b45a;
            color: #111;
            border: none;
        }

        .principal:hover {
            background: #edcd78;
        }

        .secundario {
            color: #d8b45a;
            border: 1px solid #d8b45a;
        }

        @media (max-width: 600px) {
            form {
                grid-template-columns: 1fr;
            }

            .campo.completo {
                grid-column: auto;
            }

            .painel {
                padding: 18px;
            }

            .total {
                font-size: 19px;
            }

            header a {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>

<header>
    <a href="index.php">ESSENZA PERFUMES</a>
</header>

<main>
    <h1>Finalizar compra</h1>

    <p class="subtitulo">
        Confira seu pedido, informe os dados de entrega e escolha a forma de pagamento.
    </p>

    <section class="painel">
        <h2>Resumo do pedido</h2>

        <?php foreach ($produtos as $produto): ?>
            <div class="produto">
                <div>
                    <strong>
                        <?= htmlspecialchars($produto['nome'], ENT_QUOTES, 'UTF-8') ?>
                    </strong>

                    <small>
                        Quantidade: <?= (int) $produto['quantidade'] ?>
                    </small>
                </div>

                <strong class="preco">
                    R$ <?= number_format($produto['subtotal'], 2, ',', '.') ?>
                </strong>
            </div>
        <?php endforeach; ?>

        <div class="total">
            <span>Total dos produtos</span>

            <span class="preco">
                R$ <?= number_format($total, 2, ',', '.') ?>
            </span>
        </div>
    </section>

    <section class="painel">
        <h2>Dados do cliente e entrega</h2>

        <form action="processar-pedido.php" method="post" id="form-checkout">

            <div class="campo completo">
                <label for="nome">Nome completo</label>
                <input type="text" id="nome" name="nome"
                       autocomplete="name" maxlength="150" required>
            </div>

            <div class="campo">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email"
                       autocomplete="email" maxlength="150" required>
            </div>

            <div class="campo">
                <label for="telefone">Telefone</label>
                <input type="tel" id="telefone" name="telefone"
                       autocomplete="tel" maxlength="30" required>
            </div>

            <div class="campo">
                <label for="cep">CEP</label>
                <input type="text" id="cep" name="cep"
                       autocomplete="postal-code" maxlength="15" required>
            </div>

            <div class="campo">
                <label for="cidade">Cidade</label>
                <input type="text" id="cidade" name="cidade"
                       autocomplete="address-level2" maxlength="100" required>
            </div>

            <div class="campo completo">
                <label for="endereco">Rua e endereço</label>
                <input type="text" id="endereco" name="endereco"
                       autocomplete="street-address" maxlength="255" required>
            </div>

            <div class="campo">
                <label for="numero">Número</label>
                <input type="text" id="numero" name="numero"
                       maxlength="20" required>
            </div>

            <div class="campo">
                <label for="complemento">Complemento (opcional)</label>
                <input type="text" id="complemento" name="complemento"
                       maxlength="150">
            </div>

            <div class="campo completo">
                <label for="observacoes">Observações (opcional)</label>
                <textarea id="observacoes" name="observacoes"
                          rows="3"></textarea>
            </div>

            <div class="campo completo">
                <label>Escolha a forma de pagamento</label>

                <label class="pagamento-opcao">
                    <input type="radio" name="pagamento"
                           value="pix" required>

                    <span>
                        <strong>Pix</strong>
                        <small>Pagamento via Pix</small>
                    </span>
                </label>

                <label class="pagamento-opcao">
                    <input type="radio" name="pagamento"
                           value="cartao">

                    <span>
                        <strong>Cartão de crédito</strong>
                        <small>Pagamento por ambiente seguro</small>
                    </span>
                </label>
            </div>

        </form>

        <p class="aviso" style="margin-top:20px;">
            Ambiente de teste: o pedido será registrado como pendente.
            Nenhuma cobrança será realizada nesta etapa.
        </p>

        <div class="botoes">
            <a href="carrinho.php" class="botao secundario">
                Voltar ao carrinho
            </a>

            <button type="submit" form="form-checkout"
                    class="botao principal">
                Registrar pedido
            </button>
        </div>
    </section>
</main>

</body>
</html>
