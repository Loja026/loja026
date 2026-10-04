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

if (strtolower($MeuEmail) === 'sendpulse') {
    echo "<h3>Testando conexão com API do SendPulse via cURL...</h3>";
    
    $payload = json_encode([
        "email" => [
            "html" => "<p>Email de teste via SendPulse API.</p>",
            "text" => "Email de teste via SendPulse API.",
            "subject" => "Teste de Integração SendPulse",
            "from" => [
                "name" => "Teste",
                "email" => "teste@seu-dominio.com" // IMPORTANTE: precisa ser um e-mail validado no SendPulse
            ],
            "to" => [
                [
                    "name" => "Cliente",
                    "email" => "yagooook21@gmail.com" // Email de destino
                ]
            ]
        ]
    ]);

    $ch = curl_init('https://api.sendpulse.com/smtp/emails');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $MinhaSenha
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo "<br><b style='color:red;'>Erro de conexão cURL:</b> $curlError";
    } else {
        echo "<b>Código HTTP da Resposta:</b> " . $httpCode . "<br>";
        echo "<b>Resposta da API:</b> " . htmlspecialchars($response) . "<br>";
        
        $resObj = json_decode($response);
        if ($httpCode == 200 && isset($resObj->result) && $resObj->result === true) {
             echo "<br><b style='color:green;'>Conexão via API SendPulse estabelecida e e-mail enviado com sucesso!</b><br>";
        } else {
             echo "<br><b style='color:red;'>Falha na requisição API SendPulse. Verifique sua Chave de API.</b><br>";
        }
    }
} elseif (strpos(strtolower($MeuEmail), '@smtp-brevo.com') !== false || strpos(strtolower($MeuEmail), 'brevo') !== false) {
    echo "<h3>Testando conexão com API do Brevo (HTTPS porta 443)...</h3>";
    
    $payload = json_encode([
        "sender" => ["name" => "Teste", "email" => $MeuEmail],
        "to" => [["email" => "yagooook21@gmail.com", "name" => "Cliente"]],
        "subject" => "Teste de Integração Brevo",
        "htmlContent" => "<p>Email de teste via Brevo API.</p>"
    ]);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'api-key: ' . $MinhaSenha,
        'accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo "<br><b style='color:red;'>Erro de conexão cURL:</b> $curlError";
    } else {
        echo "<b>Código HTTP da Resposta:</b> " . $httpCode . "<br>";
        echo "<b>Resposta da API:</b> " . htmlspecialchars($response) . "<br>";
        
        if ($httpCode == 201 || $httpCode == 200) {
             echo "<br><b style='color:green;'>Conexão via API Brevo estabelecida e e-mail enviado com sucesso!</b><br>";
        } else {
             echo "<br><b style='color:red;'>Falha na requisição API Brevo. Verifique sua Chave de API (você colou a senha do SMTP em vez da API Key?).</b><br>";
        }
    }
} else {
    $mail = new PHPMailer(true);

    try {
        // Habilita o modo debug para vermos exatamente o que está acontecendo
        $mail->SMTPDebug = SMTP::DEBUG_SERVER;
        $mail->Debugoutput = 'html';
        $mail->Timeout = 10; // Adiciona timeout de 10 segundos

        $mail->isSMTP();
        
        if (strpos(strtolower($MeuEmail), '@gmail.com') !== false) {
            $mail->Host = 'smtp.gmail.com';
            $mail->Username = $MeuEmail;
            $mail->Password = $MinhaSenha;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            echo "<h3>Testando conexão com smtp.gmail.com (Porta 465)...</h3>";
        } else {
            // Default para Resend (Railway permite porta 2525)
            $mail->Host = 'smtp.resend.com';
            $mail->Username = 'resend';
            $mail->Password = $MinhaSenha;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 2525;
            echo "<h3>Testando conexão com smtp.resend.com (Porta 2525)...</h3>";
        }
        
        $mail->SMTPAuth = true;
        
        if ($mail->smtpConnect()) {
            echo "<br><b style='color:green;'>Conexão SMTP estabelecida e autenticada com sucesso!</b><br>";
            $mail->smtpClose();
        } else {
            echo "<br><b style='color:red;'>Falha ao conectar/autenticar no servidor SMTP.</b><br>";
        }

    } catch (Exception $e) {
        echo "<br><b style='color:red;'>Erro ao testar SMTP:</b> {$mail->ErrorInfo}";
    }
}
?>
