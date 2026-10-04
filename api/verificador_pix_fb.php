<?php
// api/verificador_pix_fb.php
header('Content-Type: application/json');

require_once __DIR__ . '/db.php';

if (!$conn) {
    exit(json_encode(["error" => "Erro DB", "msg" => "Nao conectou ao banco"]));
}

// 1. Busca todos os PIXs que estão aguardando pagamento
$sql = "SELECT id, codigo FROM pix_tabela_codigos WHERE status_pagamento IN ('RESERVADO', 'DISPONIVEL') AND reservado_em >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)";

// Se foi passado um ID específico via AJAX, otimiza para buscar só ele
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql .= " AND id = $id";
}

$result = mysqli_query($conn, $sql);
$pedidos = [];
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $pedidos[] = $row;
    }
}

$resultados = [];

foreach ($pedidos as $pedido) {
    $codigoPix = $pedido['codigo'];
    $urlExtraida = null;

    // 2. Extrair a URL do meio do código PIX Copia e Cola (padrão Banco Central br.gov.bcb.pix)
    if (preg_match('/br\.gov\.bcb\.pix25[0-9]{2}(.+?)(5204|5303)/i', $codigoPix, $matches)) {
        $urlExtraida = 'https://' . $matches[1];
    } elseif (preg_match('/(qrcode\.dlocal\.com.*?|pix\.adyen\.com.*?)(5204|5303)/i', $codigoPix, $matches)) {
        // Fallback para o regex antigo caso falhe
        $urlExtraida = 'https://' . $matches[1];
    }

    if ($urlExtraida) {
        // 3. Checa a URL secreta usando cURL
        $ch = curl_init($urlExtraida);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux; Android 13) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Reduzido para 3s para evitar 502 Gateway Timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $foiPago = false;

        // Se deu erro (ex: 404, 410, 400) ou a resposta estiver vazia (Padrão Adyen/Dlocal)
        if ($httpCode >= 400 || $httpCode == 0 || empty($response)) {
            $foiPago = true;
        } 
        elseif (strpos(strtolower($response), 'not found') !== false || strpos(strtolower($response), 'error') !== false) {
            $foiPago = true;
        }
        else {
            // Tenta verificar se a resposta é um JWS (Padrão BACEN moderno)
            // O formato JWS vem como: Base64Header.Base64Payload.Signature
            $parts = explode('.', $response);
            if (count($parts) >= 2) {
                // Adiciona padding se necessário para o base64
                $payloadB64 = str_replace(['-', '_'], ['+', '/'], $parts[1]);
                $padLength = 4 - (strlen($payloadB64) % 4);
                if ($padLength < 4) {
                    $payloadB64 .= str_repeat('=', $padLength);
                }
                
                $json = json_decode(base64_decode($payloadB64), true);
                if (isset($json['status']) && strtoupper($json['status']) === 'CONCLUIDA') {
                    $foiPago = true;
                }
            }
        }

        if ($foiPago) {
            // 4. Marca como pago no Banco de Dados (Tabela de Códigos)
            $pedidoId = $pedido['id'];
            $stmtUpdate = "UPDATE pix_tabela_codigos SET status_pagamento = 'PAGO', pago_em = NOW() WHERE id = $pedidoId";
            mysqli_query($conn, $stmtUpdate);
            
            // 5. Marca como pago também nas Ordens de Pagamento (pixgerado) para aparecer no Painel
            $codigoSafe = mysqli_real_escape_string($conn, $codigoPix);
            $stmtOrdem = "UPDATE pixgerado SET status = 'pago' WHERE pix_code = '$codigoSafe' AND status NOT IN ('pago','paid','approved','completed','success') AND data_criacao >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)";
            mysqli_query($conn, $stmtOrdem);
            
            // 6. Dispara o e-mail de pagamento aprovado
            $q_cli = mysqli_query($conn, "SELECT ip, cliente_email, cliente_nome FROM pixgerado WHERE pix_code = '$codigoSafe' LIMIT 1");
            if ($q_cli && $r_cli = mysqli_fetch_assoc($q_cli)) {
                $_REQUEST['tipo'] = 'aprovado';
                $_REQUEST['override_ip'] = $r_cli['ip'];
                $_REQUEST['override_email'] = $r_cli['cliente_email'];
                $_REQUEST['override_nome'] = $r_cli['cliente_nome'];
                ob_start();
                @include(__DIR__ . '/phpmailer.php');
                ob_end_clean();
            }
            
            $resultados[] = ["id" => $pedido['id'], "status" => "PAGO", "url_testada" => $urlExtraida];
        } else {
            $resultados[] = ["id" => $pedido['id'], "status" => "AGUARDANDO", "http_code" => $httpCode];
        }
    } else {
        $resultados[] = ["id" => $pedido['id'], "status" => "URL_NAO_ENCONTRADA"];
    }
}

echo json_encode([
    "sucesso" => true,
    "checados" => count($pedidos),
    "resultados" => $resultados
]);
?>
