
<?php

session_start();

require_once __DIR__ . '/config/database.php';

function escapar($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$produto = null;

if ($id && $id > 0) {
    $sql = 'SELECT id, nome, descricao, categoria, preco, imagem, estoque
            FROM produtos
            WHERE id = :id AND ativo = 1
            LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $produto = $stmt->fetch();
}

if (!$produto) {
    http_response_code(404);
}

$loja = 'Essenza Perfumes';

$erroCompra = '';

if (isset($_GET['erro'])) {
    if ($_GET['erro'] === 'estoque') {
        $erroCompra = 'Este perfume está sem estoque no momento.';
    } elseif ($_GET['erro'] === 'limite') {
        $erroCompra = 'Você já adicionou todo o estoque disponível desse perfume.';
    }
}

$adicionado = isset($_GET['adicionado']) && $_GET['adicionado'] === '1';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Conheça os detalhes das fragrâncias da Essenza Perfumes.">

    <title>
        <?= $produto ? escapar($produto['nome']) . ' | ' : '' ?>
        <?= escapar($loja) ?>
    </title>

    <style>
        :root {
            --fundo: #10100f;
            --card: #191917;
            --dourado: #d4af67;
            --texto: #f5f0e7;
            --suave: #aaa69d;
            --borda: #343128;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--fundo);
            color: var(--texto);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
        }

        header {
            min-height: 78px;
            padding: 20px 8%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            border-bottom: 1px solid var(--borda);
        }

        .logo {
            color: var(--dourado);
            font-family: Georgia, serif;
            font-size: 26px;
            letter-spacing: 3px;
            text-decoration: none;
        }

        .navegacao {
            display: flex;
            align-items: center;
            gap: 22px;
            flex-wrap: wrap;
        }

        .voltar,
        .link-carrinho {
            color: var(--texto);
            text-decoration: none;
        }

        .voltar:hover,
        .link-carrinho:hover {
            color: var(--dourado);
        }

        main {
            width: min(1100px, 84%);
            margin: 60px auto 80px;
        }

        .produto {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 55px;
            align-items: center;
        }

        .imagem-produto {
            min-height: 420px;
            padding: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: linear-gradient(145deg, #28251e, #121210);
            border: 1px solid var(--borda);
        }

        .imagem-produto img {
            display: block;
            max-width: 100%;
            max-height: 480px;
            object-fit: contain;
        }

        .frasco-placeholder {
            color: var(--dourado);
            font-family: Georgia, serif;
            font-size: 85px;
        }

        .categoria {
            color: var(--dourado);
            font-size: 12px;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        h1 {
            margin: 15px 0 20px;
            font-family: Georgia, serif;
            font-size: clamp(32px, 5vw, 48px);
            font-weight: normal;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .descricao {
            color: var(--suave);
            white-space: pre-line;
            overflow-wrap: anywhere;
        }

        .preco {
            margin: 28px 0 12px;
            color: var(--dourado);
            font-size: 32px;
            font-weight: bold;
        }

        .estoque {
            color: var(--suave);
            margin-bottom: 22px;
        }

        .form-compra {
            display: grid;
            gap: 16px;
            max-width: 360px;
        }

        .campo-quantidade {
            display: grid;
            gap: 7px;
            color: var(--suave);
            font-size: 14px;
        }

        .campo-quantidade input {
            width: 100%;
            padding: 12px;
            background: var(--card);
            color: var(--texto);
            border: 1px solid var(--borda);
            border-radius: 4px;
            font-size: 16px;
        }

        .botao {
            display: inline-block;
            padding: 14px 22px;
            background: var(--dourado);
            color: #17130b;
            border: none;
            border-radius: 3px;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        .botao:hover {
            background: #ebcb8b;
        }

        .botao:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .botao-secundario {
            display: inline-block;
            margin-top: 12px;
            color: var(--dourado);
            text-decoration: none;
        }

        .aviso {
            padding: 13px 16px;
            margin-bottom: 22px;
            border-radius: 5px;
            background: #203c2b;
            color: #d8f5df;
        }

        .aviso-erro {
            background: #492323;
            color: #ffdada;
        }

        .nao-encontrado {
            padding: 35px;
            border: 1px solid var(--borda);
            background: var(--card);
        }

        .nao-encontrado h1 {
            font-size: 32px;
        }

        footer {
            padding: 28px 8%;
            border-top: 1px solid var(--borda);
            text-align: center;
            color: var(--suave);
            font-size: 13px;
        }

        @media (max-width: 750px) {
            header {
                padding: 20px 5%;
            }

            main {
                width: 90%;
                margin-top: 35px;
            }

            .produto {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .imagem-produto {
                min-height: 280px;
            }
        }
    </style>
</head>

<body>

<header>
    <a class="logo" href="index.php">ESSENZA.</a>

    <nav class="navegacao">
        <a class="voltar" href="index.php#colecao">
            ← Voltar à coleção
        </a>

        <a class="link-carrinho" href="carrinho.php">
            🛒 Meu carrinho
        </a>
    </nav>
</header>

<main>

    <?php if (!$produto): ?>

        <section class="nao-encontrado">
            <h1>Perfume não encontrado</h1>

            <p>
                Esse produto não existe ou o endereço está incorreto.
            </p>

            <a class="botao" href="index.php#colecao">
                Voltar à coleção
            </a>
        </section>

    <?php else: ?>

        <?php if ($adicionado): ?>
            <div class="aviso">
                Perfume adicionado ao carrinho com sucesso!
                <a class="botao-secundario" href="carrinho.php">
                    Ver carrinho →
                </a>
            </div>
        <?php endif; ?>

        <?php if ($erroCompra !== ''): ?>
            <div class="aviso aviso-erro">
                <?= escapar($erroCompra) ?>
            </div>
        <?php endif; ?>

        <section class="produto">

            <div class="imagem-produto">

                <?php if (!empty($produto['imagem'])): ?>

                    <img
                        src="<?= escapar($produto['imagem']) ?>"
                        alt="<?= escapar($produto['nome']) ?>"
                    >

                <?php else: ?>

                    <span class="frasco-placeholder" aria-hidden="true">
                        E.
                    </span>

                <?php endif; ?>

            </div>

            <div class="informacoes">

                <span class="categoria">
                    <?= escapar($produto['categoria']) ?>
                </span>

                <h1>
                    <?= escapar($produto['nome']) ?>
                </h1>

                <p class="descricao">
                    <?= escapar(
                        $produto['descricao'] ?: 'Descrição não cadastrada.'
                    ) ?>
                </p>

                <p class="preco">
                    R$ <?= number_format(
                        (float) $produto['preco'],
                        2,
                        ',',
                        '.'
                    ) ?>
                </p>

                <p class="estoque">
                    <?php if ((int) $produto['estoque'] > 0): ?>
                        <?= (int) $produto['estoque'] ?> unidade(s) disponível(is)
                    <?php else: ?>
                        Produto indisponível no momento.
                    <?php endif; ?>
                </p>

                <?php if ((int) $produto['estoque'] > 0): ?>

                    <form
                        class="form-compra"
                        action="adicionar-carrinho.php"
                        method="POST"
                    >
                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) $produto['id'] ?>"
                        >

                        <label class="campo-quantidade">
                            Quantidade
                            <input
                                type="number"
                                name="quantidade"
                                value="1"
                                min="1"
                                max="<?= (int) $produto['estoque'] ?>"
                                required
                            >
                        </label>

                        <button class="botao" type="submit">
                            🛒 Adicionar ao carrinho
                        </button>
                    </form>

                <?php else: ?>

                    <button class="botao" type="button" disabled>
                        Produto esgotado
                    </button>

                <?php endif; ?>

                <a class="botao-secundario" href="carrinho.php">
                    Ir para o carrinho →
                </a>

            </div>

        </section>

    <?php endif; ?>

</main>

<footer>
    <p>
        &copy; <?= date('Y') ?> Essenza Perfumes.
        Todos os direitos reservados.
    </p>
</footer>

</body>
</html>
