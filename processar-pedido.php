
<?php
session_start();

require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: finalizar-compra.php');
    exit;
}

if (empty($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
    header('Location: carrinho.php');
    exit;
}

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$cep = trim($_POST['cep'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$numero = trim($_POST['numero'] ?? '');
$complemento = trim($_POST['complemento'] ?? '');
$observacoes = trim($_POST['observacoes'] ?? '');
$pagamento = $_POST['pagamento'] ?? '';

if (
    $nome === '' ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    $telefone === '' ||
    $cep === '' ||
    $cidade === '' ||
    $endereco === '' ||
    $numero === '' ||
    !in_array($pagamento, ['pix', 'cartao'], true)
) {
    exit('Dados inválidos. Volte ao checkout e confira os campos.');
}

$ids = [];
$quantidades = [];

foreach ($_SESSION['carrinho'] as $id => $quantidade) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    $quantidade = filter_var($quantidade, FILTER_VALIDATE_INT);

    if (!$id || !$quantidade || $quantidade < 1) {
        exit('Há um item inválido no carrinho.');
    }

    $ids[] = $id;
    $quantidades[$id] = $quantidade;
}

if (!$ids) {
    header('Location: carrinho.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $sql = "SELECT id, nome, preco, estoque
            FROM produtos
            WHERE id IN ($placeholders) AND ativo = 1
            FOR UPDATE";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($produtos) !== count($ids)) {
        throw new RuntimeException(
            'Um ou mais produtos não estão disponíveis.'
        );
    }

    $total = 0;
    $itensPedido = [];

    foreach ($produtos as $produto) {
        $id = (int) $produto['id'];
        $quantidade = $quantidades[$id];
        $estoque = (int) $produto['estoque'];
        $preco = (float) $produto['preco'];

        if ($quantidade > $estoque) {
            throw new RuntimeException(
                'Estoque insuficiente para: ' . $produto['nome']
            );
        }

        $subtotal = round($preco * $quantidade, 2);
        $total += $subtotal;

        $itensPedido[] = [
            'id' => $id,
            'nome' => $produto['nome'],
            'preco' => $preco,
            'quantidade' => $quantidade,
            'subtotal' => $subtotal
        ];
    }

    $total = round($total, 2);

    $sqlPedido = "INSERT INTO pedidos (
        nome, email, telefone, cep, cidade, endereco,
        numero, complemento, observacoes,
        forma_pagamento, total, status
    ) VALUES (
        :nome, :email, :telefone, :cep, :cidade, :endereco,
        :numero, :complemento, :observacoes,
        :pagamento, :total, 'pendente'
    )";

    $stmtPedido = $pdo->prepare($sqlPedido);

    $stmtPedido->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':telefone' => $telefone,
        ':cep' => $cep,
        ':cidade' => $cidade,
        ':endereco' => $endereco,
        ':numero' => $numero,
        ':complemento' => $complemento !== '' ? $complemento : null,
        ':observacoes' => $observacoes !== '' ? $observacoes : null,
        ':pagamento' => $pagamento,
        ':total' => $total
    ]);

    $pedidoId = (int) $pdo->lastInsertId();

    $sqlItem = "INSERT INTO pedido_itens (
        pedido_id, produto_id, nome_produto,
        preco_unitario, quantidade, subtotal
    ) VALUES (
        :pedido_id, :produto_id, :nome_produto,
        :preco, :quantidade, :subtotal
    )";

    $stmtItem = $pdo->prepare($sqlItem);

    foreach ($itensPedido as $item) {
        $stmtItem->execute([
            ':pedido_id' => $pedidoId,
            ':produto_id' => $item['id'],
            ':nome_produto' => $item['nome'],
            ':preco' => $item['preco'],
            ':quantidade' => $item['quantidade'],
            ':subtotal' => $item['subtotal']
        ]);
    }

    $pdo->commit();

    // Guarda o pedido criado para mostrar a confirmação.
    $_SESSION['ultimo_pedido'] = $pedidoId;

    // Evita que o cliente envie o mesmo formulário novamente.
    $_SESSION['carrinho'] = [];

    header('Location: pedido-confirmado.php');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(400);
    echo 'Não foi possível registrar o pedido: '
        . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
