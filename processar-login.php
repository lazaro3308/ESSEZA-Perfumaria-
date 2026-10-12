```php
<?php
session_start();

require_once __DIR__ . '/config/database.php';

function voltarLogin(string $mensagem): void
{
    $_SESSION['erro_login'] = $mensagem;
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = $_POST['email'] ?? '';
$senha = $_POST['senha'] ?? '';
$redirect = $_POST['redirect'] ?? 'finalizar-compra.php';

$destinosPermitidos = [
    'finalizar-compra.php',
    'index.php'
];

if (!is_string($email) || !is_string($senha)) {
    voltarLogin('Informe seu e-mail e sua senha.');
}

$email = strtolower(trim($email));

if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 150 ||
    $senha === ''
) {
    voltarLogin('E-mail ou senha inválidos.');
}

if (
    !is_string($redirect) ||
    !in_array($redirect, $destinosPermitidos, true)
) {
    $redirect = 'finalizar-compra.php';
}

try {
    $consulta = $pdo->prepare(
        'SELECT id, nome, email, senha, tipo
         FROM usuarios
         WHERE email = ?
         LIMIT 1'
    );

    $consulta->execute([$email]);
    $usuario = $consulta->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        voltarLogin('E-mail ou senha incorretos.');
    }

    // Impede a reutilização do identificador da sessão.
    session_regenerate_id(true);

    $_SESSION['usuario_id'] = (int) $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['usuario_email'] = $usuario['email'];
    $_SESSION['usuario_tipo'] = $usuario['tipo'];

    unset($_SESSION['erro_login']);

    header('Location: ' . $redirect);
    exit;

} catch (PDOException $e) {
    error_log('Erro no login da Essenza: ' . $e->getMessage());
    voltarLogin('Não foi possível entrar agora. Tente novamente.');
}
