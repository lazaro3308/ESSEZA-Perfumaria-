<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require __DIR__ . '/config/database.php';

$loja = 'Essenza Perfumes';
$erroCatalogo = false;
$perfumes = [];

try {
    $sql = 'SELECT id, nome, descricao, categoria, preco, imagem
            FROM produtos
            ORDER BY id DESC';

    $stmt = $pdo->query($sql);
    $perfumes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erroCatalogo = true;
}

function escapar($valor): string
{
    return htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

$usuarioLogado = !empty($_SESSION['usuario_id']);
$nomeUsuario = $usuarioLogado
    ? ($_SESSION['usuario_nome'] ?? 'Minha conta')
    : 'Entrar';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="description"
          content="Descubra fragrâncias selecionadas na Essenza Perfumes.">

    <title><?= escapar($loja) ?> | Perfumaria</title>

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

        html {
            scroll-behavior: smooth;
        }

        body {
            background: var(--fundo);
            color: var(--texto);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
        }

        a {
            color: inherit;
        }

        /* CABEÇALHO ORIGINAL + CARRINHO E PERFIL */

        header {
            min-height: 78px;
            padding: 20px 8%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            border-bottom: 1px solid var(--borda);
        }

        .logo {
            color: var(--dourado);
            font-family: Georgia, serif;
            font-size: 26px;
            letter-spacing: 3px;
            text-decoration: none;
        }

        nav {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
        }

        nav a {
            font-size: 14px;
            text-decoration: none;
            transition: color 0.2s;
        }

        nav a:hover {
            color: var(--dourado);
        }

        .acoes-cabecalho {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .acao-cabecalho {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            color: var(--dourado);
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
            transition: color 0.2s;
        }

        .acao-cabecalho:hover {
            color: var(--texto);
        }

        .acao-cabecalho svg {
            width: 21px;
            height: 21px;
            stroke: currentColor;
            fill: none;
            stroke-width: 1.7;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }

        /* BANNER PRINCIPAL */

        .hero {
            min-height: 430px;
            padding: 85px 8%;
            display: flex;
            align-items: center;
            background:
                radial-gradient(
                    ellipse at 85% 50%,
                    #49371c 0%,
                    transparent 42%
                ),
                linear-gradient(110deg, #10100f 45%, #211a10);
        }

        .hero-content {
            max-width: 650px;
        }

        .eyebrow {
            color: var(--dourado);
            text-transform: uppercase;
            letter-spacing: 4px;
            font-size: 12px;
        }

       h1 {
    margin: 14px 0;
    font-family: Georgia, serif;
    font-size: clamp(38px, 6vw, 66px);
    font-weight: normal;
    line-height: 1.08;
}
        

        .hero p {
            max-width: 470px;
            color: var(--suave);
        }

        .botao {
            display: inline-block;
            margin-top: 28px;
            padding: 14px 25px;
            background: var(--dourado);
            color: #17130b;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.2s;
        }

        .botao:hover {
            background: #ebcb8b;
        }

        /* CATÁLOGO */

        .catalogo {
            padding: 65px 8% 80px;
        }

        .titulo-secao {
            font-family: Georgia, serif;
            font-size: 36px;
            font-weight: normal;
        }

        .subtitulo {
            margin: 8px 0 35px;
            color: var(--suave);
        }

        .resultado {
            margin-bottom: 20px;
            color: var(--suave);
            font-size: 14px;
        }

        .produtos {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 24px;
        }

        .produto {
            min-width: 0;
            padding: 20px;
            background: var(--card);
            border: 1px solid var(--borda);
            transition: border-color 0.2s, transform 0.2s;
        }

        .produto:hover {
            border-color: var(--dourado);
            transform: translateY(-3px);
        }

        .imagem-produto {
            width: 100%;
            height: 220px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: linear-gradient(145deg, #28251e, #121210);
        }

        .imagem-produto img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .frasco-placeholder {
            color: var(--dourado);
            font-family: Georgia, serif;
            font-size: 55px;
        }

        .categoria {
            color: var(--dourado);
            font-size: 11px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .produto h3 {
            margin: 10px 0;
            font-family: Georgia, serif;
            font-size: 23px;
            font-weight: normal;
            overflow-wrap: anywhere;
        }

        .descricao {
            min-height: 48px;
            color: var(--suave);
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .preco {
            margin-top: 18px;
            color: var(--dourado);
            font-size: 22px;
        }

        .link-detalhes {
            display: inline-block;
            margin-top: 16px;
            padding: 10px 15px;
            border: 1px solid var(--dourado);
            color: var(--dourado);
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s, color 0.2s;
        }

        .link-detalhes:hover {
            background: var(--dourado);
            color: #17130b;
        }

        .mensagem {
            padding: 22px;
            background: var(--card);
            border: 1px solid var(--borda);
            color: var(--suave);
        }

        /* RODAPÉ */

        footer {
            padding: 28px 8%;
            border-top: 1px solid var(--borda);
            text-align: center;
            color: var(--suave);
            font-size: 13px;
        }

        /* RESPONSIVIDADE */

        @media (max-width: 1000px) {
            header {
                padding-left: 5%;
                padding-right: 5%;
                flex-wrap: wrap;
            }

            .produtos {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            header {
                align-items: center;
                flex-wrap: wrap;
                gap: 15px;
            }

            nav {
                gap: 16px;
            }

            .acoes-cabecalho {
                gap: 16px;
            }

            .hero {
                min-height: 370px;
                padding-top: 65px;
                padding-bottom: 65px;
            }

            .catalogo {
                padding-top: 45px;
            }
        }

        @media (max-width: 450px) {
            header {
                padding: 18px 5%;
            }

            .logo {
                font-size: 23px;
            }

            nav {
                width: 100%;
                order: 3;
            }

            .acoes-cabecalho {
                margin-left: auto;
            }

            .acao-cabecalho {
                gap: 5px;
                font-size: 12px;
            }

            .acao-cabecalho svg {
                width: 19px;
                height: 19px;
            }

            .produtos {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

<header>
    <a class="logo" href="index.php#inicio">ESSENZA.</a>

    <nav aria-label="Navegação principal">
        <a href="index.php#inicio">Início</a>
        <a href="index.php#colecao">Coleção</a>
        <a href="index.php#contato">Contato</a>
    </nav>

    <div class="acoes-cabecalho">

        <a class="acao-cabecalho"
           href="carrinho.php"
           aria-label="Meu carrinho"
           title="Meu carrinho">

            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"></path>
            </svg>

            <span>Carrinho</span>
        </a>

        <a class="acao-cabecalho"
           href="<?= $usuarioLogado ? 'perfil.php' : 'login.php' ?>"
           aria-label="Minha conta"
           title="Minha conta">

            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="8" r="4"></circle>
                <path d="M4 21v-2a8 8 0 0 1 16 0v2"></path>
            </svg>

            <span><?= escapar($nomeUsuario) ?></span>
        </a>

    </div>
</header>

<main>
    <section class="hero" id="inicio">
        <div class="hero-content">
            <span class="eyebrow">A arte da perfumaria</span>

            <h1>Uma fragrância.<br>Uma identidade.</h1>

            <p>
                Descubra fragrâncias selecionadas para transformar
                momentos em memórias e revelar sua personalidade.
            </p>

            <a class="botao" href="#colecao">
                Explorar coleção
            </a>
        </div>
    </section>

    <section class="catalogo" id="colecao">
        <h2 class="titulo-secao">Nossa coleção</h2>

        <p class="subtitulo">
            Fragrâncias escolhidas para momentos especiais.
        </p>

        <?php if ($erroCatalogo): ?>

            <div class="mensagem">
                Não foi possível carregar os perfumes.
                Verifique a tabela produtos no banco de dados.
            </div>

        <?php elseif (empty($perfumes)): ?>

            <div class="mensagem">
                Ainda não há perfumes cadastrados.
            </div>

        <?php else: ?>

            <p class="resultado">
                <?= count($perfumes) ?> perfume(s) encontrado(s).
            </p>

            <div class="produtos">

                <?php foreach ($perfumes as $perfume): ?>

                    <article class="produto">

                        <div class="imagem-produto">

                            <?php if (!empty($perfume['imagem'])): ?>

                                <img
                                    src="<?= escapar($perfume['imagem']) ?>"
                                    alt="<?= escapar($perfume['nome']) ?>"
                                    loading="lazy"
                                >

                            <?php else: ?>

                                <span class="frasco-placeholder"
                                      aria-hidden="true">E.</span>

                            <?php endif; ?>

                        </div>

                        <span class="categoria">
                            <?= escapar($perfume['categoria']) ?>
                        </span>

                        <h3>
                            <?= escapar($perfume['nome']) ?>
                        </h3>

                        <p class="descricao">
                            <?= escapar($perfume['descricao']) ?>
                        </p>

                        <p class="preco">
                            R$ <?= number_format(
                                (float) $perfume['preco'],
                                2,
                                ',',
                                '.'
                            ) ?>
                        </p>

                        <a
                            class="link-detalhes"
                            href="produto.php?id=<?= (int) $perfume['id'] ?>"
                        >
                            Ver detalhes
                        </a>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>
    </section>
</main>

<footer id="contato">
    <p>
        &copy; <?= date('Y') ?> Essenza Perfumes.
        Todos os direitos reservados.
    </p>
</footer>

</body>
</html>