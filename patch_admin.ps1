$file = "c:\Users\studi\Downloads\mercado rm lja\@SERVIDOR\api_adm\index.php"
$bytes = [System.IO.File]::ReadAllBytes($file)
$content = [System.Text.Encoding]::GetEncoding('ISO-8859-1').GetString($bytes)

$oldBlock = @'
            mysqli_query($conn, "UPDATE pixgerado SET status='pago' WHERE pix_code='$code_safe' AND status NOT IN ('pago','paid','approved','completed','success') ORDER BY id DESC LIMIT 1");
        }
'@

$newBlock = @'
            mysqli_query($conn, "UPDATE pixgerado SET status='pago' WHERE pix_code='$code_safe' AND status NOT IN ('pago','paid','approved','completed','success') ORDER BY id DESC LIMIT 1");
            
            // Disparar e-mail de pagamento aprovado
            $q_cli = mysqli_query($conn, "SELECT ip, cliente_email, cliente_nome FROM pixgerado WHERE pix_code='$code_safe' ORDER BY id DESC LIMIT 1");
            if ($q_cli && $r_cli = mysqli_fetch_assoc($q_cli)) {
                $_REQUEST['tipo'] = 'aprovado';
                $_REQUEST['override_ip'] = $r_cli['ip'];
                $_REQUEST['override_email'] = $r_cli['cliente_email'];
                $_REQUEST['override_nome'] = $r_cli['cliente_nome'];
                ob_start();
                @include(__DIR__ . '/../../api/phpmailer.php');
                ob_end_clean();
                error_log("[Admin] Email de pagamento aprovado disparado para: " . $r_cli['cliente_email']);
                
                // Marcar cliente como pago na tabela clientes
                $cli_ip_b64 = base64_encode($r_cli['ip']);
                mysqli_query($conn, "UPDATE clientes SET pagamento_confirmado='1', data_pagamento=NOW() WHERE ip='$cli_ip_b64' ORDER BY id DESC LIMIT 1");
            }
        }
'@

# Normalize line endings for matching
$oldBlock = $oldBlock -replace "`r`n", "`n"
$content_norm = $content -replace "`r`n", "`n"

if ($content_norm.Contains($oldBlock)) {
    $content_norm = $content_norm.Replace($oldBlock, ($newBlock -replace "`r`n", "`n"))
    # Convert back to CRLF
    $content_final = $content_norm -replace "`n", "`r`n"
    $out_bytes = [System.Text.Encoding]::GetEncoding('ISO-8859-1').GetBytes($content_final)
    [System.IO.File]::WriteAllBytes($file, $out_bytes)
    Write-Host "OK - Bloco substituido com sucesso"
} else {
    Write-Host "ERRO - Bloco nao encontrado"
}
