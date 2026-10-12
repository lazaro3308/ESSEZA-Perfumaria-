
<?php
session_start();

// Remove os dados da sessão do usuário.
unset(
    $_SESSION['usuario_id'],
    $_SESSION['usuario_nome'],
    $_SESSION['usuario_email'],
    $_SESSION['usuario_tipo']
);

// Mantém o carrinho e outros dados da sessão.
header('Location: index.php');
exit;
?>