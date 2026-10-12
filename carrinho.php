<?php
session_start();

require_once __DIR__ . '/config/database.php';

function escapar($valor)
{
    return htmlspecialchars(
        (string) $valor,
        ENT_QUOTES,
        'UTF-8'
    );
}

// Garantir que o carrinho exista.
if (!isset($_SESSION['carrinho']) ||
    !is_array($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$mensagem = '';
$erro = '';

// Processar alterações no carrinho.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'remover') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if ($id && isset($_SESSION['carrinho'][$id])) {
            unset($_SESSION['carrinho'][$id]);
            $mensagem = 'Perfume removido do carrinho.';
        }
    } elseif ($acao === 'atualizar') {
        $quantidades = $_POST['quantidade'] ?? [];

        if (is_array($quantidades)) {
            foreach ($quantidades as $id => $quantidade) {
                $id = filter_var($id, FILTER_VALIDATE_INT);
                $quantidade = filter_var(
                    $quantidade,
                    FILTER_VALIDATE_INT
                );

                if (!$id || !isset($_SESSION['carrinho'][$id])) {
                    continue;
                }

                if ($quantidade === false || $quantidade < 1) {
                    $erro = 'Informe quantidades válidas.';
                    continue;
                }

                $_SESSION['carrinho'][$id] = min($quantidade, 99);
            }

            if ($erro === '') {
                $mensagem = 'Carrinho atualizado.';
            }
        }
    }

    // Evitar reenviar o formulário ao atualizar a página.
    if ($mensagem !== '' || $erro !== '') {
        $_SESSION['mensagem_carrinho'] = $mensagem;
        $_SESSION['erro_carrinho'] = $erro;

        header('Location: carrinho.php');
        exit;
    }
}

$mensagem = $_SESSION['mensagem_carrinho'] ?? '';
$erro = $_SESSION['erro_carrinho'] ?? '';

unset(
    $_SESSION['mensagem_carrinho'],
    $_SESSION['erro_carrinho']
);

$itens = [];
$total = 0;
$quantidadeTotal = 0;

try {
    $carrinho = $_SESSION['carrinho'];

    if (!empty($carrinho)) {
        $ids = [];

        foreach ($carrinho as $id => $quantidade) {
            $id = filter_var($id, FILTER_VALIDATE_INT);
            $quantidade = filter_var(
                $quantidade,
                FILTER_VALIDATE_INT
            );

            if ($id && $quantidade && $quantidade > 0) {
                $ids[$id] = min($quantidade, 99);
            }
        }

        $_SESSION['carrinho'] = $ids;

        if (!empty($ids)) {
            $marcadores = implode(
                ',',
                array_fill(0, count($ids), '?')
            );

            $sql = "SELECT id, nome, categoria, preco, imagem, estoque
                    FROM produtos
                    WHERE id IN ($marcadores) AND ativo = 1";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_keys($ids));

            $produtos = $stmt->fetchAll();
            $produtosEncontrados = [];

            foreach ($produtos as $produto) {
                $produtosEncontrados[(int) $produto['id']] = $produto;
            }

            foreach ($ids as $id => $quantidade) {
                if (!isset($produtosEncontrados[$id])) {
                    unset($_SESSION['carrinho'][$id]);
                    continue;
                }

                $produto = $produtosEncontrados[$id];
                $estoque = (int) $produto['estoque'];

                if ($estoque < 1) {
                    unset($_SESSION['carrinho'][$id]);
                    continue;
                }

                $quantidade = min($quantidade, $estoque);
                $_SESSION['carrinho'][$id] = $quantidade;

                $preco = (float) $produto['preco'];
                $subtotal = $preco * $quantidade;

                $produto['quantidade'] = $quantidade;
                $produto['subtotal'] = $subtotal;

                $itens[] = $produto;
                $total += $subtotal;
                $quantidadeTotal += $quantidade;
            }
        }
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Não foi possível carregar o carrinho.');
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meu carrinho | Essenza Perfumes</title>

    <style>
        :root {
            --fundo: #101010;
            --painel: #1a1a1a;
            --dourado: #d4af67;
            --texto: #f5f1e8;
            --suave: #b5b0a6;
            --borda: #35312a;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--fundo);
            color: var(--texto);
            font-family: Arial, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        header {
            padding: 22px 7%;
            border-bottom: 1px solid var(--borda);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .logo {
            color: var(--dourado);
            font-size: 23px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        main {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        h1 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .subtitulo {
            color: var(--suave);
            margin-bottom: 28px;
        }

        .mensagem {
            background: #203c2b;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .erro {
            background: #492323;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 24px;
            align-items: start;
        }

        .lista {
            display: grid;
            gap: 15px;
        }

        .item {
            display: grid;
            grid-template-columns: 110px minmax(0, 1fr);
            gap: 18px;
            padding: 18px;
            background: var(--painel);
            border: 1px solid var(--borda);
            border-radius: 12px;
        }

        .item img {
            width: 100%;
            height: 125px;
            object-fit: contain;
            border-radius: 8px;
            background: #fff;
        }

        .categoria {
            color: var(--dourado);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .item h2 {
            font-size: 18px;
            margin: 7px 0;
        }

        .preco {
            color: var(--dourado);
            font-weight: bold;
        }

        .controles {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        input[type="number"] {
            width: 75px;
            padding: 9px;
            background: #101010;
            border: 1px solid var(--borda);
            color: white;
            border-radius: 6px;
        }

        button, .botao {
            display: inline-block;
            border: 0;
            border-radius: 6px;
            padding: 11px 15px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }

        .botao-dourado {
            background: var(--dourado);
            color: #17130b;
        }

        .botao-secundario {
            background: #302c25;
            color: var(--texto);
        }

        .remover {
            background: transparent;
            color: #ff9a9a;
            padding-left: 0;
        }

        .resumo {
            background: var(--painel);
            border: 1px solid var(--borda);
            border-radius: 12px;
            padding: 22px;
            position: sticky;
            top: 20px;
        }

        .linha {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin: 18px 0;
            color: var(--suave);
        }

        .total {
            border-top: 1px solid var(--borda);
            padding-top: 18px;
            color: var(--texto);
            font-size: 19px;
            font-weight: bold;
        }

        .resumo .botao {
            width: 100%;
            text-align: center;
            margin-top: 10px;
        }

        .vazio {
            padding: 40px 20px;
            text-align: center;
            border: 1px solid var(--borda);
            border-radius: 12px;
            background: var(--painel);
        }

        .vazio p {
            color: var(--suave);
            line-height: 1.6;
        }

        footer {
            text-align: center;
            color: var(--suave);
            padding: 35px 15px;
            border-top: 1px solid var(--borda);
            margin-top: 50px;
        }

        @media (max-width: 760px) {
            .layout {
                grid-template-columns: 1fr;
            }

            .resumo {
                position: static;
            }
        }

        @media (max-width: 430px) {
            .item {
                grid-template-columns: 1fr;
            }

            .item img {
                height: 170px;
            }
        }
    </style>
</head>
<body>

<header>
    <a class="logo" href="index.php">ESSENZA</a>
    <a href="index.php">Continuar comprando</a>
</header>

<main>
    <h1>Meu carrinho</h1>
    <p class="subtitulo">
        Seus perfumes favoritos, reunidos em um só lugar.
    </p>

    <?php if ($mensagem !== ''): ?>
        <div class="mensagem">
            <?= escapar($mensagem) ?>
        </div>
    <?php endif; ?>

    <?php if ($erro !== ''): ?>
        <div class="erro">
            <?= escapar($erro) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($itens)): ?>

        <section class="vazio">
            <h2>Seu carrinho está vazio</h2>
            <p>
                Explore nossa coleção e encontre seu próximo perfume favorito.
            </p>
            <a class="botao botao-dourado" href="index.php">
                Explorar perfumes
            </a>
        </section>

    <?php else: ?>

        <div class="layout">
            <section>
                <form method="POST" action="carrinho.php">
                    <input type="hidden" name="acao" value="atualizar">

                    <div class="lista">
                        <?php foreach ($itens as $item): ?>
                            <article class="item">
                                <?php if (!empty($item['imagem'])): ?>
                                    <img
                                        src="<?= escapar($item['imagem']) ?>"
                                        alt="<?= escapar($item['nome']) ?>"
                                    >
                                <?php else: ?>
                                    <div></div>
                                <?php endif; ?>

                                <div>
                                    <span class="categoria">
                                        <?= escapar($item['categoria'] ?? 'Perfume') ?>
                                    </span>

                                    <h2><?= escapar($item['nome']) ?></h2>

                                    <p class="preco">
                                        R$ <?= number_format(
                                            (float) $item['preco'],
                                            2,
                                            ',',
                                            '.'
                                        ) ?>
                                    </p>

                                    <p>
                                        Subtotal:
                                        <strong>
                                            R$ <?= number_format(
                                                (float) $item['subtotal'],
                                                2,
                                                ',',
                                                '.'
                                            ) ?>
                                        </strong>
                                    </p>

                                    <div class="controles">
                                        <label>
                                            Quantidade
                                            <input
                                                type="number"
                                                name="quantidade[<?= (int) $item['id'] ?>]"
                                                min="1"
                                                max="<?= max(1, (int) $item['estoque']) ?>"
                                                value="<?= (int) $item['quantidade'] ?>"
                                                required
                                            >
                                        </label>

                                        <a
                                            class="botao botao-secundario"
                                            href="produto.php?id=<?= (int) $item['id'] ?>"
                                        >
                                            Ver perfume
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <div class="controles">
                        <button class="botao-dourado" type="submit">
                            Atualizar quantidades
                        </button>
                    </div>
                </form>

                <?php foreach ($itens as $item): ?>
                    <form method="POST" action="carrinho.php"
                          class="controles">
                        <input type="hidden" name="acao" value="remover">
                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) $item['id'] ?>"
                        >
                        <button class="remover" type="submit">
                            Remover <?= escapar($item['nome']) ?>
                        </button>
                    </form>
                <?php endforeach; ?>
            </section>

            <aside class="resumo">
                <h2>Resumo do pedido</h2>

                <div class="linha">
                    <span>Itens</span>
                    <span><?= (int) $quantidadeTotal ?></span>
                </div>

                <div class="linha">
                    <span>Subtotal</span>
                    <span>
                        R$ <?= number_format($total, 2, ',', '.') ?>
                    </span>
                </div>

                <div class="linha">
                    <span>Frete</span>
                    <span>A calcular</span>
                </div>

                <div class="linha total">
                    <span>Total parcial</span>
                    <span>
                        R$ <?= number_format($total, 2, ',', '.') ?>
                    </span>
                </div>

                <p style="color: #b5b0a6; font-size: 12px;">
                    O frete será calculado na etapa de finalização.
                </p>

                <a class="botao botao-secundario" href="index.php">
                    Continuar comprando
                </a>
                <a href="finalizar-compra.php"
   style="display:inline-block;padding:14px 22px;background:#d8b45a;color:#111;text-decoration:none;border-radius:6px;font-weight:bold;">
    Finalizar compra
</a>
            </aside>
        </div>

    <?php endif; ?>
</main>

<footer>
    Essenza Perfumes — Sua essência, sua marca.
</footer>

</body>
</html>