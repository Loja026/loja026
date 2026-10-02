<?php
// api/cron_pix_status.php
require_once 'pix_parser.php';

require_once __DIR__ . '/db.php';

if (!$conn) {
    exit("Erro de conexao com o banco");
}

// Credenciais (Preencher com as de produção)
$adyen_api_key = "SUA_API_KEY_ADYEN";
$dlocal_api_key = "SUA_API_KEY_DLOCAL";

// Buscar PIXs que ainda estão aguardando pagamento
$stmt = mysqli_query($conn, "SELECT id, codigo FROM pix_tabela_codigos WHERE status_pagamento = 'RESERVADO' AND reservado_em >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
$pedidos = [];
if ($stmt) {
    while ($row = mysqli_fetch_assoc($stmt)) {
        $pedidos[] = $row;
    }
}

foreach ($pedidos as $pedido) {
    // Passa pelo Parser para identificar a URL
    $dadosPix = analisarPayloadPix($pedido['codigo']);
    
    // Atualiza a tabela com os dados extraídos, se ainda não tiverem sido salvos
    if (!empty($dadosPix['gateway'])) {
        $pix_original = mysqli_real_escape_string($conn, $dadosPix['pix_original']);
        $qr_url = mysqli_real_escape_string($conn, $dadosPix['qr_url']);
        $qr_identificador = mysqli_real_escape_string($conn, $dadosPix['qr_identificador']);
        $gateway = mysqli_real_escape_string($conn, $dadosPix['gateway']);
        $pid = intval($pedido['id']);
        
        mysqli_query($conn, "UPDATE pix_tabela_codigos SET pix_original='$pix_original', qr_url='$qr_url', qr_identificador='$qr_identificador', gateway='$gateway' WHERE id=$pid");
    }

    $statusFinal = null;

    if ($dadosPix['gateway'] == 'ADYEN') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://checkout-test.adyen.com/v68/payments/" . $dadosPix['qr_identificador']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["x-API-key: $adyen_api_key"]);
        $resposta = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        if (isset($resposta['status']) && $resposta['status'] == 'Authorised') {
            $statusFinal = 'PAGO';
        }
    } 
    elseif ($dadosPix['gateway'] == 'DLOCAL') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.dlocal.com/payments/" . $dadosPix['qr_identificador']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $dlocal_api_key"]);
        $resposta = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        if (isset($resposta['status']) && $resposta['status'] == 'PAID') {
            $statusFinal = 'PAGO';
        }
    }

    // Se a API informou que foi pago, corrige o banco
    if ($statusFinal === 'PAGO') {
        $pid = intval($pedido['id']);
        mysqli_query($conn, "UPDATE pix_tabela_codigos SET status_pagamento = 'PAGO', pago_em = NOW() WHERE id = $pid");
        echo "Pedido ID {$pedido['id']} marcado como PAGO.<br>\n";
    }
}
echo "Cron executado com sucesso.";
?>
