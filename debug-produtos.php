
<?php
require __DIR__ . '/config/database.php';

echo '<h1>Teste dos produtos</h1>';

$stmt = $pdo->query(
    'SELECT id, nome, categoria, preco, ativo FROM produtos'
);

$produtos = $stmt->fetchAll();

echo '<p>Total encontrado: ' . count($produtos) . '</p>';

echo '<pre>';
print_r($produtos);
echo '</pre>';