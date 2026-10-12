
<?php
session_start();

require_once __DIR__ . '/config/database.php';

function voltarComErro(string $mensagem): void {
    $_SESSION['erro_cadastro'] = $mensagem;
    header('Location: cadastro.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastro.php');
    exit;
}

$token = $_POST['token'] ?? '';
$tokenSessao = $_SESSION['token_cadastro'] ?? '';

if (
    !is_string($token) ||
    !is_string($tokenSessao) ||
    $tokenSessao === '' ||
    !hash_equals($tokenSessao, $token)
) {
    voltarComErro('Sessão inválida. Atualize a página e tente novamente.');
}

$nome = trim($_POST['nome'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';

if ($nome === '' || mb_strlen($nome) > 150) {
    voltarComErro('Informe um nome válido de até 150 caracteres.');
}

if (
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    strlen($email) > 150
) {
    voltarComErro('Informe um endereço de e-mail válido.');
}

if (!is_string($senha) || strlen($senha) < 8 || strlen($senha) > 72) {
    voltarComErro('A senha deve ter entre 8 e 72 bytes.');
}

if (!is_string($confirmarSenha) || $senha !== $confirmarSenha) {
    voltarComErro('As senhas não coincidem.');
}

try {
    $verificar = $pdo->prepare(
        'SELECT id FROM usuarios WHERE email = ? LIMIT 1'
    );
    $verificar->execute([$email]);

    if ($verificar->fetch()) {
        voltarComErro('Este e-mail já está cadastrado.');
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    $inserir = $pdo->prepare("
        INSERT INTO usuarios (nome, email, senha, tipo)
        VALUES (?, ?, ?, 'cliente')
    ");

    $inserir->execute([$nome, $email, $senhaHash]);

    unset($_SESSION['token_cadastro']);
    unset($_SESSION['erro_cadastro']);

    header('Location: cadastro.php?sucesso=1');
    exit;

} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        voltarComErro('Este e-mail já está cadastrado.');
    }

    error_log('Erro no cadastro da Essenza Perfumes: ' . $e->getMessage());

    voltarComErro('Não foi possível concluir o cadastro. Tente novamente.');
}
