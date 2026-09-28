<?php
// test_smtp.php
session_start();
require_once("api/db.php");
require_once('api/src/PHPMailer.php');
require_once('api/src/SMTP.php');
require_once('api/src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-type: text/html; charset=utf-8');

echo "<h2>Teste de Integração SMTP</h2>";

if (!$conn) {
    die("Erro na conexão com o banco de dados.");
}

$sql = mysqli_query($conn, "SELECT * from apis LIMIT 1");
if ($sql && $row = mysqli_fetch_array($sql)) {
    $emailPHPMAILER = $row["email"];
    
    if (empty($emailPHPMAILER)) {
        die("Erro: O campo de email na tabela 'apis' está vazio.");
    }
    
    $recorte = explode("|", $emailPHPMAILER);
    if (count($recorte) < 2) {
        die("Erro: O formato do email na tabela 'apis' está incorreto. Esperado: email|senha");
    }
    
    $MeuEmail = $recorte[0];
    $MinhaSenha = $recorte[1];
    echo "Credenciais encontradas para o email: <b>$MeuEmail</b><br><br>";
} else {
    die("Erro: Nenhuma configuração de API encontrada na tabela 'apis'.");
}

$mail = new PHPMailer(true);

try {
    // Habilita o modo debug para vermos exatamente o que está acontecendo
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;
    $mail->Debugoutput = 'html';
    $mail->Timeout = 10; // Adiciona timeout de 10 segundos

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $MeuEmail;
    $mail->Password = $MinhaSenha;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Tenta TLS
    $mail->Port = 587; // Tenta porta 587

    // Apenas testando a conexão com o servidor SMTP
    echo "<h3>Testando conexão com smtp.gmail.com...</h3>";
    
    if ($mail->smtpConnect()) {
        echo "<br><b style='color:green;'>Conexão SMTP estabelecida e autenticada com sucesso!</b><br>";
        $mail->smtpClose();
    } else {
        echo "<br><b style='color:red;'>Falha ao conectar/autenticar no servidor SMTP.</b><br>";
    }

} catch (Exception $e) {
    echo "<br><b style='color:red;'>Erro ao testar SMTP:</b> {$mail->ErrorInfo}";
}
?>
