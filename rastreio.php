<?php
session_start();
require_once __DIR__ . '/api/db.php';

$produto_codigo = trim((string)($_GET['produto'] ?? $_GET['id'] ?? ''));
$cid = trim((string)($_GET['cid'] ?? ''));

if ($produto_codigo === '' && $cid !== '') {
    $cid_safe = mysqli_real_escape_string($conn, $cid);
    $c_res = mysqli_query($conn, "SELECT produto_codigo FROM clientes WHERE id='$cid_safe' LIMIT 1");
    if ($c_res && $c_row = mysqli_fetch_assoc($c_res)) {
        $produto_codigo = trim((string)($c_row['produto_codigo'] ?? ''));
    }
}

if ($produto_codigo === '') {
    $p_res = mysqli_query($conn, "SELECT codigo FROM produto ORDER BY id DESC LIMIT 1");
    if ($p_res && $p_row = mysqli_fetch_assoc($p_res)) {
        $produto_codigo = trim((string)$p_row['codigo']);
    }
}

if ($produto_codigo === '') {
    header('Location: ./index');
    exit;
}

$produto_safe = mysqli_real_escape_string($conn, $produto_codigo);
$produto_result = mysqli_query($conn, "SELECT * FROM produto WHERE codigo='$produto_safe' LIMIT 1");
$produto = $produto_result ? mysqli_fetch_assoc($produto_result) : null;
if (!$produto) {
    header('Location: ./index');
    exit;
}

$cliente = [];
if ($cid !== '') {
    $cid_safe = mysqli_real_escape_string($conn, $cid);
    $cliente_result = mysqli_query($conn, "SELECT * FROM clientes WHERE id='$cid_safe' LIMIT 1");
    $cliente = ($cliente_result && mysqli_num_rows($cliente_result) > 0) ? mysqli_fetch_assoc($cliente_result) : [];
}
if (empty($cliente)) {
    $cliente_ip = mysqli_real_escape_string($conn, base64_encode($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($produto_codigo !== '') {
        $cliente_result = mysqli_query($conn, "SELECT * FROM clientes WHERE ip='$cliente_ip' AND produto_codigo='$produto_safe' ORDER BY id DESC LIMIT 1");
        $cliente = ($cliente_result && mysqli_num_rows($cliente_result) > 0) ? mysqli_fetch_assoc($cliente_result) : [];
    }
    if (empty($cliente)) {
        $cliente_result = mysqli_query($conn, "SELECT * FROM clientes WHERE ip='$cliente_ip' ORDER BY id DESC LIMIT 1");
        $cliente = ($cliente_result && mysqli_num_rows($cliente_result) > 0) ? mysqli_fetch_assoc($cliente_result) : [];
    }
}

$pix = null;
if (!empty($cliente['ip'])) {
    $raw_ip = base64_decode($cliente['ip']);
    $ip_safe = mysqli_real_escape_string($conn, $raw_ip);
    $pix_result = mysqli_query($conn, "SELECT * FROM pixgerado WHERE ip='$ip_safe' AND produto='$produto_safe' ORDER BY id DESC LIMIT 1");
    $pix = $pix_result ? mysqli_fetch_assoc($pix_result) : null;
}
if (!$pix) {
    $ip = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR'] ?? '');
    $pix_result = mysqli_query($conn, "SELECT * FROM pixgerado WHERE ip='$ip' AND produto='$produto_safe' ORDER BY id DESC LIMIT 1");
    $pix = $pix_result ? mysqli_fetch_assoc($pix_result) : null;
}

$config_result = mysqli_query($conn, "SELECT * FROM config LIMIT 1");
$config = $config_result ? mysqli_fetch_assoc($config_result) : [];
$nome_loja = $config['nome'] ?? 'Minha Loja';
$cor = $config['cor'] ?? '#ffe600';
$logo_files = glob(__DIR__ . '/arquivos/logo/*.png');
$logo = !empty($logo_files) ? 'arquivos/logo/' . basename($logo_files[0]) : '';

$gateway_status = strtoupper(trim((string)($pix['mp_status'] ?? $pix['freepay_status'] ?? $pix['pixgo_status'] ?? $pix['carthero_status'] ?? '')));
$paid_statuses = ['APPROVED', 'PAID', 'PAGO', 'COMPLETED', 'RECEIVED', 'CONFIRMED', 'SUCCEEDED', 'SETTLED'];
$is_paid = in_array($gateway_status, $paid_statuses, true) || strtoupper((string)($pix['status'] ?? '')) === 'PAGO';
$created_at = !empty($pix['data_criacao']) ? strtotime($pix['data_criacao']) : time();

$data_entrega = new DateTime();
$data_entrega->setTimestamp($created_at);
$dias_uteis = 0;
while ($dias_uteis < 5) {
    $data_entrega->modify('+1 day');
    if ((int)$data_entrega->format('N') <= 5) $dias_uteis++;
}
$dias_semana_pt = ['', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado', 'domingo'];
$meses_pt = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
$delivery_date = $dias_semana_pt[(int)$data_entrega->format('N')] . ', ' . $data_entrega->format('j') . ' de ' . $meses_pt[(int)$data_entrega->format('n')];
$nome_produto = $produto['nome'] ?? 'Produto';
$valor_texto = trim((string)($pix['valor'] ?? $produto['valor'] ?? 0));
if (strpos($valor_texto, ',') !== false) {
    $valor_texto = str_replace('.', '', $valor_texto);
    $valor_texto = str_replace(',', '.', $valor_texto);
}
$valor = (float)$valor_texto;
$valor_formatado = number_format($valor, 2, ',', '.');
$destino = trim((string)($cliente['cidade'] ?? ''));
if (!empty($cliente['estado'])) $destino .= ($destino ? ', ' : '') . $cliente['estado'];
if ($destino === '') $destino = 'Seu endereço de entrega';

$status_key = '';
if (!empty($cliente['status_rastreio'])) {
    $status_key = trim((string)$cliente['status_rastreio']);
} elseif (!empty($pix['status_rastreio'])) {
    $status_key = trim((string)$pix['status_rastreio']);
}

if (empty($status_key)) {
    if ((string)($_GET['confirmado'] ?? '') === '1' || $is_paid) {
        $status_key = 'preparando_envio';
    } else {
        $status_key = 'pedido_solicitado';
    }
}

$status_map = [
    'pedido_solicitado' => [
        'title' => 'Pedido solicitado',
        'desc' => 'O pedido foi registrado em nosso sistema.',
        'head' => 'Aguardando pagamento',
        'label' => 'Pedido registrado',
        'progress' => 25
    ],
    'preparando_envio' => [
        'title' => 'Preparando envio',
        'desc' => 'O vendedor está preparando o seu pacote.',
        'head' => 'Chega dia ' . $delivery_date,
        'label' => 'Pagamento Confirmado',
        'progress' => 45
    ],
    'enviado_transportadora' => [
        'title' => 'Enviado para transportadora',
        'desc' => 'Seu pacote foi entregue à transportadora responsável.',
        'head' => 'Pacote enviado',
        'label' => 'Enviado para transportadora',
        'progress' => 60
    ],
    'em_transito' => [
        'title' => 'Em trânsito para ponto de distribuição de sua cidade',
        'desc' => 'Seu pedido está a caminho do centro de distribuição local.',
        'head' => 'Em trânsito',
        'label' => 'A caminho da sua cidade',
        'progress' => 75
    ],
    'centro_distribuicao' => [
        'title' => 'Pedido chegou ao centro de distribuição',
        'desc' => 'Pacote recebido no centro de distribuição da sua região.',
        'head' => 'No centro de distribuição',
        'label' => 'Chegou na sua região',
        'progress' => 85
    ],
    'rota_entrega' => [
        'title' => 'Pedido em rota de entrega',
        'desc' => 'O entregador saiu para entregar seu pedido no endereço.',
        'head' => 'Saiu para entrega hoje',
        'label' => 'Em rota de entrega',
        'progress' => 92
    ],
    'entregue' => [
        'title' => 'Pedido entregue',
        'desc' => 'Pedido entregue com sucesso no seu endereço!',
        'head' => 'Entregue com sucesso!',
        'label' => 'Pedido Entregue',
        'progress' => 100
    ],
    'ausente' => [
        'title' => 'Não encontrou ninguém no endereço',
        'desc' => 'Tentativa de entrega realizada. Nova tentativa será feita em breve.',
        'head' => 'Tentativa de entrega realizada',
        'label' => 'Destinatário Ausente',
        'progress' => 88
    ]
];

$seq_keys = ['pedido_solicitado', 'preparando_envio', 'enviado_transportadora', 'em_transito', 'centro_distribuicao', 'rota_entrega', 'entregue'];
if ($status_key === 'ausente') {
    $seq_keys = ['pedido_solicitado', 'preparando_envio', 'enviado_transportadora', 'em_transito', 'centro_distribuicao', 'rota_entrega', 'ausente'];
}

$cur_info = $status_map[$status_key] ?? $status_map['preparando_envio'];
$progress_pct = $cur_info['progress'];
$status_title = $cur_info['head'];
$status_label = $cur_info['label'];

$idx = array_search($status_key, $seq_keys);
if ($idx === false) $idx = 1;

$date_upd = !empty($cliente['data_status_rastreio']) ? strtotime($cliente['data_status_rastreio']) : $created_at;

$events = [];
for ($i = $idx; $i >= 0; $i--) {
    $k = $seq_keys[$i];
    if (isset($status_map[$k])) {
        $time_offset = $date_upd - (($idx - $i) * 3600 * 6);
        $events[] = [
            'key' => $k,
            'title' => $status_map[$k]['title'],
            'desc' => $status_map[$k]['desc'],
            'is_current' => ($i === $idx),
            'time' => date('d/m/Y H:i', $time_offset)
        ];
    }
}

$step0_color = ($idx >= 0) ? '#00a650' : '#8c8c8c';
$step1_color = ($idx >= 1) ? '#00a650' : '#8c8c8c';
$step2_color = ($idx >= 2) ? '#00a650' : '#8c8c8c';
$step3_color = ($idx >= 6) ? '#00a650' : '#8c8c8c';

$img_src = '';
$img_file = trim((string)($produto['img'] ?? ''));
if (!empty($img_file)) {
    if (strpos($img_file, 'http') === 0) {
        $img_src = $img_file;
    } else {
        $img_src = "./arquivos/produtos/{$produto['codigo']}/$img_file";
    }
}
if (empty($img_src) && !empty($produto['codigo'])) {
    $glob_imgs = glob(__DIR__ . "/arquivos/produtos/{$produto['codigo']}/*.{png,jpg,jpeg,webp,gif}", GLOB_BRACE);
    if (!empty($glob_imgs)) {
        $img_src = "./arquivos/produtos/{$produto['codigo']}/" . basename($glob_imgs[0]);
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Acompanhar pedido - <?php echo htmlspecialchars($nome_loja); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{box-sizing:border-box}body{margin:0;background:#ebebeb;color:#333;font-family:Arial,Helvetica,sans-serif}.header{background:<?php echo htmlspecialchars($cor); ?>;border-bottom:1px solid rgba(0,0,0,.1)}.header-inner{max-width:1180px;margin:auto;min-height:64px;padding:12px 24px;display:flex;justify-content:space-between;align-items:center}.logo{max-width:180px;max-height:40px;object-fit:contain}.brand{font-weight:600;font-size:20px}.secure{font-size:13px;color:#555}.wrap{max-width:1180px;margin:0 auto;padding:32px 16px 60px;display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:24px}.card{background:#fff;border:1px solid #ddd;border-radius:8px;box-shadow:0 1px 2px rgba(0,0,0,.06);overflow:hidden}.status-head{padding:30px 32px 16px;display:flex;gap:18px}.status-icon{width:58px;height:58px;border-radius:50%;background:#e8f5e9;color:#00a650;display:flex;align-items:center;justify-content:center;font-size:28px;flex:none}.status-title{margin:0;color:#00a650;font-size:28px;line-height:1.2}.status-label{font-weight:600;margin:8px 0;color:#333}.guarantee{color:#666;font-size:14px;margin-top:18px}.progress-area{padding:12px 32px 30px}.bar{height:8px;border-radius:20px;background:#f1f1f1;overflow:hidden}.bar span{display:block;height:100%;width:<?php echo $progress_pct; ?>%;background:#00a650;border-radius:20px;transition:width 0.4s ease}.steps{display:flex;justify-content:space-between;gap:10px;margin-top:12px;font-size:11px;font-weight:bold;text-transform:uppercase}.history{padding:28px 32px}.history h2,.side h2{font-size:20px;margin:0 0 26px}.event{position:relative;padding:0 0 28px 38px;border-left:2px solid #eee;margin-left:8px}.event:last-child{padding-bottom:0;border-left-color:transparent}.dot{position:absolute;left:-9px;top:2px;width:16px;height:16px;border-radius:50%;background:#ddd;border:3px solid #fff;box-shadow:0 0 0 1px #ddd}.event.completed .dot{background:#00a650;box-shadow:0 0 0 1px #00a650}.event.pending{opacity:0.45}.event h3{font-size:17px;margin:0 0 6px}.event p{font-size:14px;color:#666;margin:0;line-height:1.4}.event time{display:inline-block;margin-top:9px;color:#666;background:#f8f8f8;padding:5px 8px;border-radius:4px;font-size:11px;font-weight:bold}.side{padding:24px}.product{display:flex;gap:14px;padding-bottom:20px;border-bottom:1px solid #eee}.product-icon{width:56px;height:56px;border-radius:7px;background:#f5f5f5;display:flex;align-items:center;justify-content:center;color:#aaa;font-size:24px}.product-name{font-size:14px;font-weight:600}.product-meta{font-size:12px;color:#666;margin-top:5px}.total{display:flex;justify-content:space-between;align-items:center;margin-top:20px}.total strong{font-size:23px}.delivery{margin-top:24px;padding-top:20px;border-top:1px solid #eee;font-size:14px;line-height:1.5}.delivery b{display:block;margin-bottom:5px}.footer{padding:24px 16px;background:#fff;border-top:1px solid #ddd;text-align:center;color:#777;font-size:12px}.footer a{color:#666;margin:0 8px;text-decoration:none}@media(max-width:800px){.wrap{grid-template-columns:1fr;padding:20px 10px 40px}.status-head{padding:24px 20px 12px}.progress-area,.history{padding-left:20px;padding-right:20px}.status-title{font-size:23px}.header-inner{padding:10px 16px}.secure{font-size:11px}.steps{font-size:9px}}
</style>
</head>
<body>
<header class="header"><div class="header-inner"><div><?php if ($logo): ?><img class="logo" src="<?php echo htmlspecialchars($logo); ?>" alt="<?php echo htmlspecialchars($nome_loja); ?>"><?php else: ?><span class="brand"><?php echo htmlspecialchars($nome_loja); ?></span><?php endif; ?></div><div class="secure"><i class="fa-solid fa-shield-halved"></i> Compra segura</div></div></header>
<main class="wrap">
<section class="card">
<div class="status-head"><div class="status-icon"><i class="fa-solid <?php echo ($status_key === 'entregue') ? 'fa-circle-check' : (($status_key === 'ausente') ? 'fa-triangle-exclamation' : 'fa-truck'); ?>"></i></div><div><h1 class="status-title"><?php echo htmlspecialchars($status_title); ?></h1><p class="status-label">Status: <?php echo htmlspecialchars($status_label); ?></p><p class="guarantee"><i class="fa-solid fa-shield-halved" style="color:#00a650"></i> Compra Garantida</p></div></div>
<div class="progress-area"><div class="bar"><span></span></div><div class="steps"><span style="color:<?php echo $step0_color; ?>">Pedido realizado</span><span style="color:<?php echo $step1_color; ?>">Pagamento</span><span style="color:<?php echo $step2_color; ?>">Enviado</span><span style="color:<?php echo $step3_color; ?>">Entregue</span></div></div>
<div class="history"><h2><i class="fa-solid fa-clock-rotate-left" style="color:#3483fa"></i> Histórico de movimentação</h2>
<?php foreach ($events as $idx_ev => $ev): ?>
<div class="event <?php echo ($idx_ev === 0) ? 'completed' : ''; ?>"><span class="dot"></span><h3><?php echo htmlspecialchars($ev['title']); ?></h3><p><?php echo htmlspecialchars($ev['desc']); ?></p><?php if (!empty($ev['time'])): ?><time><?php echo htmlspecialchars($ev['time']); ?></time><?php endif; ?></div>
<?php endforeach; ?>
</div></section>

<aside class="card side"><h2>Produtos</h2><div class="product"><?php if (!empty($img_src)): ?><img src="<?php echo htmlspecialchars($img_src); ?>" alt="<?php echo htmlspecialchars($nome_produto); ?>" style="width:56px;height:56px;object-fit:contain;border-radius:7px;border:1px solid #eee;background:#fff;padding:2px;"><?php else: ?><div class="product-icon"><i class="fa-solid fa-box"></i></div><?php endif; ?><div><div class="product-name"><?php echo htmlspecialchars($nome_produto); ?></div><div class="product-meta">1 unidade</div></div></div><div class="total"><span style="font-size:13px;font-weight:bold;color:#777;text-transform:uppercase">Total</span><strong>R$ <?php echo $valor_formatado; ?></strong></div><div class="delivery"><b>Informações de entrega</b><span><?php echo htmlspecialchars($destino); ?></span><br><span>Previsão: <?php echo htmlspecialchars($delivery_date); ?></span></div></aside>
</main>
<footer class="footer"><a href="politica-de-privacidade">Política de Privacidade</a><a href="termos-de-uso">Termos de Uso</a><a href="trocas-e-devolucoes">Trocas e Devoluções</a><p>Copyright © <?php echo date('Y'); ?> <?php echo htmlspecialchars($nome_loja); ?>. Todos os direitos reservados.</p></footer>
</body></html>
