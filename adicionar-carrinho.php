
<?php

session_start();

require_once __DIR__ . '/config/database.php';

// Aceitar somente envios POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Validar o ID do perfume.
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header('Location: index.php');
    exit;
}

// Validar a quantidade solicitada.
$quantidadeSolicitada = filter_input(
    INPUT_POST,
    'quantidade',
    FILTER_VALIDATE_INT
);

if (!$quantidadeSolicitada || $quantidadeSolicitada < 1) {
    header('Location: produto.php?id=' . $id);
    exit;
}

try {
    // Buscar o produto ativo.
    $sql = 'SELECT id, nome, estoque
            FROM produtos
            WHERE id = :id AND ativo = 1
            LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);

    $produto = $stmt->fetch();

    if (!$produto) {
        header('Location: index.php');
        exit;
    }

    $estoque = (int) $produto['estoque'];

    if ($estoque < 1) {
        header('Location: produto.php?id=' . $id . '&erro=estoque');
        exit;
    }

    // Garantir que o carrinho seja um array.
    if (
        !isset($_SESSION['carrinho']) ||
        !is_array($_SESSION['carrinho'])
    ) {
        $_SESSION['carrinho'] = [];
    }

    // Quantidade que já existe no carrinho.
    $quantidadeAtual = (int) (
        $_SESSION['carrinho'][$id] ?? 0
    );

    $novaQuantidade = $quantidadeAtual + $quantidadeSolicitada;

    // Não permitir quantidade acima do estoque.
    if ($novaQuantidade > $estoque) {
        header('Location: produto.php?id=' . $id . '&erro=limite');
        exit;
    }

    // Salvar no carrinho.
    $_SESSION['carrinho'][$id] = $novaQuantidade;

    // Voltar para o produto com mensagem de sucesso.
    header('Location: produto.php?id=' . $id . '&adicionado=1');
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);
    exit('Não foi possível adicionar o perfume. Tente novamente.');
}
