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
 
$ip = !empty($_REQUEST['override_ip']) ? $_REQUEST['override_ip'] : base64_encode(get_real_ip());
$override_id = !empty($_REQUEST['override_id']) ? (int)$_REQUEST['override_id'] : 0;

$emailCliente = !empty($_REQUEST['override_email']) ? $_REQUEST['override_email'] : '';
$nome = !empty($_REQUEST['override_nome']) ? $_REQUEST['override_nome'] : '';
$cell = '';
$endereco = '';
$numero = '';
$bairro = '';
$cidadeXestado = '';
$cep = '';
$idCliente = $override_id;

if ($override_id > 0) {
    $sql = mysqli_query($conn, "SELECT * from clientes WHERE id='$override_id' LIMIT 1");
} else {
    $sql = mysqli_query($conn, "SELECT * from clientes WHERE ip='$ip' ORDER BY id DESC LIMIT 1");
}
while($sql && $row = mysqli_fetch_array($sql)){   	   
    if (empty($emailCliente)) $emailCliente = $row["email"]; 
    if (empty($nome)) $nome = $row["nome"];
    $cell = $row["celular"];	   
    $endereco = $row["endereco"];
    $numero = $row["numero"];
    $bairro = $row["bairro"];
    $cidadeXestado = $row["cidade"];
    $cep = $row["cep"];
    $idCliente = $row["id"];
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
			return;
		}
		
		$recorte = explode("|", $emailPHPMAILER ?? '');
		$MeuEmail = trim($recorte[0] ?? '');
		$MinhaSenha = trim($recorte[1] ?? '');
		$config_email_remetente = trim($recorte[2] ?? '');

		// Se o primeiro valor for uma API Key da Brevo (xkeysib-...) e não houver segundo valor
		if (strpos($MeuEmail, 'xkeysib-') === 0 && empty($MinhaSenha)) {
			$MinhaSenha = $MeuEmail;
		} elseif (strpos($MeuEmail, 'xkeysib-') === 0 && !empty($MinhaSenha) && empty($config_email_remetente)) {
			// Formato: xkeysib-...|remetente@email.com
			$config_email_remetente = $MinhaSenha;
			$MinhaSenha = $MeuEmail;
		}

		if (empty($MeuEmail)) {
		    error_log('[Email Debug] Credenciais SMTP ausentes.');
		    echo 'Erro: Credenciais SMTP não configuradas.';
		    return;
		}
		
		$sql = mysqli_query($conn, "SELECT * from config");
		while($sql && $row = mysqli_fetch_array($sql)){   	   
			$loja = $row["nome"];
			if (empty($config_email_remetente) && !empty($row["email"])) {
				$config_email_remetente = trim($row["email"]);
			}
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
		} else if ($tipoEmail == 'status_rastreio') {
			// ==========================================
			// TEMPLATE: ATUALIZAÇÃO DE STATUS DE RASTREIO
			// ==========================================
			$statusNome = !empty($_REQUEST['override_status_nome']) ? $_REQUEST['override_status_nome'] : 'Status Atualizado';
			$statusDesc = !empty($_REQUEST['override_status_desc']) ? $_REQUEST['override_status_desc'] : 'Seu pedido teve uma nova movimentação.';
			
			$statusKey = !empty($_REQUEST['override_status_key']) ? $_REQUEST['override_status_key'] : '';
			$texto1email = "Atualização do seu pedido: $statusNome";
			$tracking_link = "$dominio/tracking.php?produto=$codigoProduto&cid=$idCliente" . ($statusKey !== '' ? "&st=$statusKey" : '');

			$texto = "
			<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05); background-color: #fff;'>
				<div style='background-color: #3483fa; padding: 25px; text-align: center;'>
					<h1 style='color: white; margin: 0; font-size: 22px;'>Atualização no seu pedido 📦</h1>
				</div>
				<div style='padding: 30px; color: #333; line-height: 1.6;'>
					<p style='font-size: 16px;'>Olá <strong>$nome</strong>,</p>
					<p style='font-size: 16px;'>O seu pedido teve uma nova atualização de rastreamento:</p>
					
					<div style='background-color: #f8f9fa; border-left: 4px solid #3483fa; padding: 15px 20px; margin: 20px 0; border-radius: 4px;'>
						<h3 style='margin: 0 0 5px 0; color: #3483fa; font-size: 18px;'>$statusNome</h3>
						<p style='margin: 0; color: #555; font-size: 14px;'>$statusDesc</p>
					</div>

					<p style='font-size: 15px; color: #666;'>Você pode acompanhar o histórico completo do seu pedido a qualquer momento clicando no botão abaixo:</p>
					
					<div style='text-align: center; margin-top: 30px; margin-bottom: 20px;'>
						<a href='$tracking_link' style='background-color: #00a650; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px; display: inline-block;'>Acompanhar meu Pedido</a>
					</div>
				</div>
				<div style='background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #6c757d; border-top: 1px solid #e0e0e0;'>
					<p style='margin: 0 0 5px 0;'>Atenciosamente, equipe <strong>$loja</strong></p>
					<p style='margin: 0;'>&copy; " . date('Y') . " $loja. Todos os direitos reservados.</p>
				</div>
			</div>";
		} else {
			// ==========================================
			// TEMPLATE: PEDIDO PENDENTE / PIX
			// ==========================================
			$texto1email = "Seu pedido foi reservado";
			$payment_link = "$dominio/payment.php?produto=$codigoProduto&cid=$idCliente";

			// Buscar nome e valor do produto reais (Priorizar tabela clientes que tem o valor final com frete/desconto)
			$nomeProduto = '';
			$valorProduto = '';
			
			// 1. Tentar buscar da tabela clientes PRIMEIRO
			if (!empty($idCliente)) {
				$sql_c = mysqli_query($conn, "SELECT produto_nome, valortotal, produto_codigo FROM clientes WHERE id='$idCliente' LIMIT 1");
				if ($sql_c && $row_c = mysqli_fetch_assoc($sql_c)) {
					if (!empty($row_c['produto_nome'])) $nomeProduto = $row_c['produto_nome'];
					if (!empty($row_c['valortotal'])) $valorProduto = $row_c['valortotal'];
					if (empty($codigoProduto) && !empty($row_c['produto_codigo'])) {
						$codigoProduto = $row_c['produto_codigo'];
						$payment_link = "$dominio/payment.php?produto=$codigoProduto&cid=$idCliente";
					}
				}
			}

			// 2. Tentar buscar o valor de pixgerado caso ainda não tenha valor (pode ter sido inserido lá e não no cliente)
			if (empty($valorProduto) && (!empty($idCliente) || !empty($codigoProduto))) {
				// Busca pelo ID do cliente ou IP, limitando por produto
				$cond = [];
				if (!empty($idCliente)) $cond[] = "cliente_id='$idCliente'";
				if (!empty($codigoProduto)) $cond[] = "produto='$codigoProduto'";
				$where = implode(" OR ", $cond);
				
				$sql_pix_val = mysqli_query($conn, "SELECT valor FROM pixgerado WHERE $where ORDER BY id DESC LIMIT 1");
				if ($sql_pix_val && $row_pv = mysqli_fetch_assoc($sql_pix_val)) {
					if (!empty($row_pv['valor'])) $valorProduto = $row_pv['valor'];
				}
			}
			
			// 3. Fallback: buscar na tabela produto se ainda faltar nome ou valor
			if ((empty($nomeProduto) || empty($valorProduto)) && !empty($codigoProduto)) {
				$prod_safe = mysqli_real_escape_string($conn, $codigoProduto);
				$sql_p = mysqli_query($conn, "SELECT nome, valor FROM produto WHERE codigo='$prod_safe' LIMIT 1");
				if ($sql_p && $row_p = mysqli_fetch_assoc($sql_p)) {
					if (empty($nomeProduto) && !empty($row_p['nome'])) $nomeProduto = $row_p['nome'];
					if (empty($valorProduto) && !empty($row_p['valor'])) $valorProduto = $row_p['valor'];
				}
			}

			if (empty($nomeProduto)) $nomeProduto = 'seu produto';
			
			// Formatar valor se existir
			$valorFormatadoHtml = '';
			if (!empty($valorProduto)) {
				$v_clean = str_replace(['R$', ' '], '', (string)$valorProduto);
				if (strpos($v_clean, ',') !== false) {
					$v_clean = str_replace('.', '', $v_clean);
					$v_clean = str_replace(',', '.', $v_clean);
				}
				$v_num = (float)$v_clean;
				if ($v_num > 0) {
					$valorFormatadoHtml = ' no valor de <strong>R$ ' . number_format($v_num, 2, ',', '.') . '</strong>';
				}
			}

			$texto = "
<div style='max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; background-color: #fff;'>
    <div style='background-color: #00a650; padding: 20px; text-align: center;'>
        <h1 style='color: white; margin: 0; font-size: 22px;'>Seu pedido foi reservado</h1>
    </div>
    <div style='padding: 30px; line-height: 1.6; color: #333;'>
        <p style='font-size: 16px; margin-top: 0;'>Olá <strong>$nome</strong>,</p>
        <p style='font-size: 16px;'>Notamos que você iniciou uma compra do produto <strong>$nomeProduto</strong>$valorFormatadoHtml no Mercado Livre, mas ainda não identificamos o seu pagamento.</p>
        

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
	curl_setopt($ch, CURLOPT_TIMEOUT, 6);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
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
} elseif (strpos($MinhaSenha, 'xkeysib-') === 0 || strpos(strtolower($MeuEmail), 'smtp-brevo.com') !== false || strpos(strtolower($MeuEmail), 'brevo') !== false || strpos(strtolower($emailPHPMAILER), 'xkeysib-') !== false) {
    // Usar API HTTP do Brevo (Porta 443 HTTPS) para evitar bloqueio de portas SMTP (110/587) na hospedagem
    $apiKey = (strpos($MinhaSenha, 'xkeysib-') === 0) ? $MinhaSenha : ((strpos($MeuEmail, 'xkeysib-') === 0) ? $MeuEmail : trim($emailPHPMAILER));
    
    // Determinar o e-mail do remetente
    $senderEmail = '';
    if (!empty($config_email_remetente) && filter_var($config_email_remetente, FILTER_VALIDATE_EMAIL) && strpos(strtolower($config_email_remetente), '@smtp-brevo.com') === false) {
        $senderEmail = $config_email_remetente;
    } elseif (filter_var($MeuEmail, FILTER_VALIDATE_EMAIL) && strpos(strtolower($MeuEmail), '@smtp-brevo.com') === false) {
        $senderEmail = $MeuEmail;
    }
    if (empty($senderEmail)) {
        $q_em = mysqli_query($conn, "SELECT email FROM config WHERE email != '' AND email NOT LIKE '%smtp-brevo%' LIMIT 1");
        if ($q_em && $r_em = mysqli_fetch_assoc($q_em)) {
            $senderEmail = trim($r_em['email']);
        }
    }
    if (empty($senderEmail)) {
        $senderEmail = 'nao-responda@' . ($_SERVER['HTTP_HOST'] ?? 'loja.com');
    }
    
	$payload = json_encode([
		"sender" => ["name" => $loja, "email" => $senderEmail],
		"to" => [["email" => $emailCliente, "name" => $nome]],
		"subject" => "$texto1email id:$idCliente",
		"htmlContent" => $texto
	]);

	$ch = curl_init('https://api.brevo.com/v3/smtp/email');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 8);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'api-key: ' . $apiKey,
		'accept: application/json'
	]);

	$response = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	file_put_contents(__DIR__ . '/mail_debug.txt', date('[Y-m-d H:i:s] ') . "Brevo HTTP $httpCode - $response\n", FILE_APPEND);

	if ($httpCode == 201 || $httpCode == 200) {
		echo 'Email enviado com sucesso';
	} else {
		echo "Erro ao enviar mensagem via Brevo API. Código: $httpCode Resposta: $response";
	}
} else {
	try {
		//$mail->SMTPDebug = SMTP::DEBUG_SERVER;
		$mail->isSMTP();
		$mail->Timeout = 5;
		
		if (strpos(strtolower($MeuEmail), '@gmail.com') !== false) {
			$mail->Host = 'smtp.gmail.com';
			$mail->Username = $MeuEmail;
			$mail->Password = $MinhaSenha;
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port = 465;
			$mail->setFrom($MeuEmail, "$loja");
		} elseif (strpos($MinhaSenha, 're_') === 0 || strpos($MeuEmail, 're_') === 0) {
			// ==========================================
			// PROVEDOR 1: RESEND (Melhor Grátis - API HTTP)
			// ==========================================
			$apiKey = (strpos($MinhaSenha, 're_') === 0) ? $MinhaSenha : $MeuEmail;
			// Após verificar o domínio casaecozinha.casa no Resend, usar email do domínio verificado (pedidos para evitar filtros de spam)
			$senderEmail = filter_var($config_email_remetente, FILTER_VALIDATE_EMAIL) ? $config_email_remetente : 'pedidos@casaecozinha.casa';
			
			$payload = json_encode([
				"from" => "$loja <$senderEmail>",
				"to" => [$emailCliente],
				"subject" => "$texto1email id:$idCliente",
				"html" => $texto
			]);

			$ch = curl_init('https://api.resend.com/emails');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 8);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Content-Type: application/json',
				'Authorization: Bearer ' . $apiKey
			]);

			$response = curl_exec($ch);
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($httpCode == 200 || $httpCode == 201) {
				echo 'Email enviado com sucesso';
			} else {
				echo "Erro ao enviar via Resend API. Código: $httpCode Resposta: $response";
			}
			return;
		} elseif (strpos(strtolower($MeuEmail), 'mailtrap') !== false || strpos(strtolower($MeuEmail), 'live.smtp.mailtrap.io') !== false) {
			// ==========================================
			// PROVEDOR 2: MAILTRAP (SMTP Grátis)
			// ==========================================
			$mail->Host = 'live.smtp.mailtrap.io';
			$mail->Username = $MeuEmail;
			$mail->Password = $MinhaSenha;
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			$mail->Port = 587;
			$fromAddr = (!empty($config_email_remetente) && filter_var($config_email_remetente, FILTER_VALIDATE_EMAIL)) ? $config_email_remetente : $MeuEmail;
			$mail->setFrom($fromAddr, "$loja");
		} elseif (strpos(strtolower($MeuEmail), 'smtp-brevo.com') !== false || strpos(strtolower($MeuEmail), 'brevo') !== false || strpos(strtolower($MinhaSenha), 'xsmtpsib-') === 0) {
			$mail->Host = 'smtp-relay.brevo.com';
			$mail->Username = $MeuEmail;
			$mail->Password = $MinhaSenha;
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
			$mail->Port = 587;

			$fromAddr = '';
			if (!empty($config_email_remetente) && filter_var($config_email_remetente, FILTER_VALIDATE_EMAIL) && strpos(strtolower($config_email_remetente), '@smtp-brevo.com') === false) {
				$fromAddr = $config_email_remetente;
			} elseif (filter_var($MeuEmail, FILTER_VALIDATE_EMAIL) && strpos(strtolower($MeuEmail), '@smtp-brevo.com') === false) {
				$fromAddr = $MeuEmail;
			}
			if (empty($fromAddr)) {
				$q_em = mysqli_query($conn, "SELECT email FROM config WHERE email != '' AND email NOT LIKE '%smtp-brevo%' LIMIT 1");
				if ($q_em && $r_em = mysqli_fetch_assoc($q_em)) {
					$fromAddr = trim($r_em['email']);
				}
			}
			if (empty($fromAddr)) {
				$fromAddr = $MeuEmail;
			}
			$mail->setFrom($fromAddr, "$loja");
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








