<?php
require_once("db.php");

if (!isset($_GET['status'])) {
    die("Uso: ?status=1 (Para Ligar) ou ?status=0 (Para Desligar)");
}

$status = intval($_GET['status']) === 1 ? 1 : 0;

// Garantir que a coluna existe
mysqli_query($conn, "ALTER TABLE apis ADD COLUMN IF NOT EXISTS smtp_ativo TINYINT(1) DEFAULT 0");

// Atualizar o status
$update = mysqli_query($conn, "UPDATE apis SET smtp_ativo = $status");

if ($update) {
    if ($status === 1) {
        echo "<h2 style='color:green;'>SMTP ATIVADO COM SUCESSO!</h2>";
        echo "<p>O site agora VAI enviar os e-mails.</p>";
    } else {
        echo "<h2 style='color:red;'>SMTP DESATIVADO COM SUCESSO!</h2>";
        echo "<p>O site NÃO vai mais enviar os e-mails, mas continuará processando pedidos normalmente.</p>";
    }
} else {
    echo "Erro ao atualizar: " . mysqli_error($conn);
}
?>
