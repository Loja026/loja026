import re

with open(r'c:\Users\studi\Downloads\mercado rm lja\@SERVIDOR\api_adm\index.php', 'r', encoding='utf-8') as f:
    content = f.read()

new_logic = r'''        // Determina o status do pedido para enviar o template correto
        $ip_raw = $cliente['ip'] ?? '';
        $status_pagamento = 'pendente';
        $override_produto = '';
        
        if (!empty($ip_raw)) {
            $ip_decoded = base64_decode($ip_raw);
            if ($ip_decoded !== false && $ip_decoded !== '') {
                $ip_raw = $ip_decoded;
            }
            $q_pix = mysqli_query($conn, "SELECT produto, status, mp_status, pixgo_status, freepay_status FROM pixgerado WHERE ip='$ip_raw' ORDER BY id DESC LIMIT 1");
            if ($q_pix && $r_pix = mysqli_fetch_assoc($q_pix)) {
                $override_produto = $r_pix['produto'];
                $pago_arrays = ['pago', 'paid', 'approved', 'approved_payment', 'completed', 'success'];
                
                if (in_array(strtolower($r_pix['status'] ?? ''), $pago_arrays) || 
                    in_array(strtolower($r_pix['mp_status'] ?? ''), $pago_arrays) || 
                    in_array(strtolower($r_pix['pixgo_status'] ?? ''), $pago_arrays) || 
                    in_array(strtolower($r_pix['freepay_status'] ?? ''), $pago_arrays)) {
                    $status_pagamento = 'approved';
                }
            }
        }
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $dominio = $protocol . '://' . $_SERVER['HTTP_HOST'];
        $loja = "Nossa Loja";
        
        if ($status_pagamento === 'approved') {
            $assunto_email = "Pagamento Aprovado - Seu pedido está sendo preparado!";
            $htmlBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
                <div style='background-color: #28a745; padding: 25px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 24px;'>Pagamento Aprovado! ✅</h1>
                </div>
                <div style='padding: 30px; color: #333; line-height: 1.6;'>
                    <p style='font-size: 16px;'>Olá <strong>$nome</strong>,</p>
                    <p style='font-size: 16px;'>Recebemos o seu pagamento com sucesso referente ao produto <b>$produto_nome</b>. O seu pedido já está separado e começará a ser preparado para o envio.</p>
                    <p style='font-size: 16px;'>Agradecemos muito pela sua confiança e por comprar na <strong>$loja</strong>!</p>
                    
                    <div style='text-align: center; margin-top: 35px; margin-bottom: 15px;'>
                        <a href='$dominio/success.php' style='background-color: #28a745; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px; display: inline-block;'>Acompanhar meu Pedido</a>
                    </div>
                </div>
            </div>";
        } else {
            $assunto_email = "Finalize sua compra - PIX Gerado com sucesso!";
            $payment_link = empty($override_produto) ? "$dominio/payment.php" : "$dominio/payment.php?produto=$override_produto";
            
            $htmlBody = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
                <div style='background-color: #ff9800; padding: 25px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 24px;'>Seu PIX foi gerado! ⏳</h1>
                </div>
                <div style='padding: 30px; color: #333; line-height: 1.6;'>
                    <p style='font-size: 16px;'>Olá <strong>$nome</strong>,</p>
                    <p style='font-size: 16px;'>Notamos que você iniciou uma compra do produto <b>$produto_nome</b> na <strong>$loja</strong>, mas ainda não identificamos o seu pagamento.</p>
                    <p style='font-size: 16px;'>Como o PIX Copia e Cola tem um tempo limite e pode ter expirado, <strong>clique no botão abaixo para gerar um novo PIX</strong> com seus dados já preenchidos e garantir sua reserva.</p>
                    
                    <div style='background-color: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; margin: 25px 0; border-radius: 0 6px 6px 0;'>
                        <p style='margin: 0; font-size: 15px; color: #e65100;'><strong>Atenção:</strong> Estoque limitado. O seu produto só estará garantido após a confirmação do pagamento.</p>
                    </div>
                    
                    <div style='text-align: center; margin-top: 35px; margin-bottom: 15px;'>
                        <a href='$payment_link' style='background-color: #ff9800; color: white; padding: 16px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block; font-size: 16px; box-shadow: 0 2px 5px rgba(255, 152, 0, 0.4);'>Gerar Novo PIX e Finalizar Compra</a>
                    </div>
                </div>
            </div>";
        }'''

content = re.sub(r'        \$htmlBody = \".*?</div>\s*</div>\";', new_logic, content, flags=re.DOTALL)
content = content.replace('"subject" => "Confirmação do seu pedido: " . $produto_nome,', '"subject" => $assunto_email,')
content = content.replace('$mail->Subject = "Confirmação do seu pedido: " . $produto_nome;', '$mail->Subject = $assunto_email;')
content = content.replace('$mail->Subject = "Confirmação do seu pedido: $produto_nome";', '$mail->Subject = $assunto_email;')

with open(r'c:\Users\studi\Downloads\mercado rm lja\@SERVIDOR\api_adm\index.php', 'w', encoding='utf-8') as f:
    f.write(content)
