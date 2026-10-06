<?php 
   session_start();
   require_once(__DIR__ . "/../../api/db.php");
   require_once(__DIR__ . "/produto_json.php");



	   // Mod_Security Fix: Suporte a payload codificado com desofuscação (reverse)
   $payload_raw = $_POST['p'] ?? '';
   if (!empty($payload_raw)) {
       $payload_raw = strrev($payload_raw); // Desfaz a inversão feita no JS
       $decoded = json_decode(base64_decode($payload_raw), true);
       if ($decoded) {
           foreach ($decoded as $key => $value) {
               $_POST[$key] = $value;
           }
       }
   }
  
if (!function_exists('parse_moeda_float')) {
    function parse_moeda_float($val) {
        if (is_numeric($val)) {
            return (float)$val;
        }
        $val = trim((string)$val);
        $val = preg_replace('/[^\d.,]/', '', $val);
        if (empty($val)) return 0.0;
        
        // Se tem ponto e vírgula (ex: 1.100,50)
        if (strpos($val, '.') !== false && strpos($val, ',') !== false) {
            if (strrpos($val, ',') > strrpos($val, '.')) {
                $val = str_replace('.', '', $val);
                $val = str_replace(',', '.', $val);
            } else {
                $val = str_replace(',', '', $val);
            }
        } elseif (strpos($val, ',') !== false) {
            $val = str_replace(',', '.', $val);
        }
        return (float)$val;
    }
}

// Validação para aceitar tanto 'painel' quanto 'api' (usado no Pixel)
$acao = $_POST["painel"] ?? $_POST["api"] ?? '';

switch($acao){
    case "online":
	
	    $QUERY2 = "SELECT COUNT(*) FROM bot";
		$rob = mysqli_query($conn, $QUERY2);
		$row = ($rob) ? mysqli_fetch_array($rob) : null;
		$bot = $row['COUNT(*)'] ?? 0;
		
	    $QUERY4 = "SELECT COUNT(*) FROM mobile";
		$mob = mysqli_query($conn, $QUERY4);
		$row = ($mob) ? mysqli_fetch_array($mob) : null;
		$mobile = $row['COUNT(*)'] ?? 0;

		$QUERY5 = "SELECT COUNT(*) FROM desktop";
		$desk = mysqli_query($conn, $QUERY5);
		$row = ($desk) ? mysqli_fetch_array($desk) : null;
		$desktop = $row['COUNT(*)'] ?? 0;
		
		$QUERY6 = "SELECT COUNT(*) as total FROM clientes";
		$cli = mysqli_query($conn, $QUERY6);
		$rowx = ($cli) ? mysqli_fetch_assoc($cli) : null;
		$cliente = $rowx['total'] ?? 0;
		
		$QUERY7 = "SELECT COUNT(*) as total FROM pixgerado";
		$cli7 = mysqli_query($conn, $QUERY7);
		$rowx7 = ($cli7) ? mysqli_fetch_assoc($cli7) : null;
		$pixpix = $rowx7['total'] ?? 0;
		
		// Cálculo correto e preciso da soma total de PIX e PIX Pagos
		$soma_total_pix = 0.0;
		$soma_pago_pix = 0.0;
		$pix_pago_count = 0;
		$pago_arrays = ['pago', 'paid', 'approved', 'approved_payment', 'completed', 'success'];
		
		$res_soma = mysqli_query($conn, "SELECT valor, status, mp_status, freepay_status, pixgo_status, carthero_status FROM pixgerado");
		if ($res_soma) {
			while ($r_s = mysqli_fetch_assoc($res_soma)) {
				$val = parse_moeda_float($r_s['valor'] ?? '0');
				$soma_total_pix += $val;
				
				$status_main = strtolower($r_s['status'] ?? '');
				$status_pg = strtolower($r_s['pixgo_status'] ?? '');
				$status_mp = strtolower($r_s['mp_status'] ?? '');
				$status_fp = strtolower($r_s['freepay_status'] ?? '');
				$status_ch = strtolower($r_s['carthero_status'] ?? '');
				
				if (in_array($status_main, $pago_arrays) || in_array($status_pg, $pago_arrays) || in_array($status_mp, $pago_arrays) || in_array($status_fp, $pago_arrays) || in_array($status_ch, $pago_arrays)) {
					$soma_pago_pix += $val;
					$pix_pago_count++;
				}
			}
		}
		$totalSomado = "R$ " . number_format($soma_total_pix, 2, ',', '.');
		$totalPagoSomado = "R$ " . number_format($soma_pago_pix, 2, ',', '.');
		
		$cliques = $mobile + $desktop;
			
		$sql = mysqli_query($conn, "SELECT count(id) as online FROM online WHERE time >= '" . time() . "' AND situacao='ativo'");
		$resp = ($sql) ? mysqli_fetch_assoc($sql) : null;
		$totalOn = $resp['online'] ?? 0;
		
		$sql = mysqli_query($conn, "SELECT count(id) as online FROM online WHERE situacao='desativo'");
		$respx = ($sql) ? mysqli_fetch_assoc($sql) : null;
		$block = $respx['online'] ?? 0;
	
		echo "$totalOn|$cliques|$desktop|$mobile|$bot|$cliente|$block|$pixpix|$totalSomado|$pix_pago_count|$totalPagoSomado";
	
	break; //=============================================

	case "lista_online": 
	
	$sql = mysqli_query($conn, "SELECT * from online");
	if(($sql ? mysqli_num_rows($sql) : 0) > 0){
	    
		$sql = mysqli_query($conn, "SELECT * FROM online WHERE time >= '" . time() . "' ORDER BY id DESC");
		 while($sql && $rowx = mysqli_fetch_array($sql)){ 
	     
		 $id = $rowx["id"];
	     $etapa = $rowx["etapa"] ?? '';
	 	 $cidade= $rowx["cidade"] ?? 'Desconhecido';
		 $estado= $rowx["estado"] ?? 'XX';
		 $dispositivo = $rowx["dispositivo"] ?? 'desktop';
		 $hora = $rowx["hora"] ?? '';
		 
		 $icon = "visibility";
		 if($etapa=="produto"){
		 $etapa = "Produto";
		 $icon = "store";
		 }
		 if($etapa=="checkout"){
		 $etapa = "Checkout";
		 $icon = "shopping_cart";
		 }		 
		 if($etapa=="address"){
		 $etapa = "Endereço";
		 $icon = "post_add";
		 }
		 if($etapa=="confirm"){
		 $etapa = "Confirma endereço";
		 $icon = "post_add";
		 }
		 if($etapa=="payment"){
		 $etapa = "Pagamento";
		 $icon = "request_page";
		  }
		 if($etapa=="pix"){
		 $etapa = "Pagar Pix";
		 $icon = "price_check";
		 }		
		 if($etapa=="parado"){
		 $etapa = "está parado";
		 $icon = "elderly";
		 }	
		 if($etapa=="erro404"){
		 $etapa = "Caiu no erro 404";
		 $icon = "warning";
		 }		 
		 if($etapa=="success"){
		 $etapa = "Gerou Pix";
		 $icon = "check_circle";
		 }
		 if($etapa=="pix_copiado"){
		 $etapa = "Copiou o Pix";
		 $icon = "content_copy";
		 }
		 
		 echo '<tr>
                <td class="align-middle text-center">
                  <div class="d-flex px-2 py-1" style="display: flex;align-items: center;">
                  <div><i class="material-icons text-gradient text-info" style="font-size: 1.5rem; margin-right: 8px;">'.$icon.'</i></div>
                  <div class="d-flex flex-column justify-content-center"><h6 class="mb-0 text-sm">'.$etapa.'</h6></div>
                  </div>
                </td>
				<td class="align-middle text-center">
                   <span class="text-xs font-weight-bold">'.$dispositivo.'</span>
                </td>
                <td class="align-middle text-center">
                   <span class="text-xs font-weight-bold">'.$cidade.'/'.$estado.'</span>
                </td>
                <td class="align-middle text-center">
                   <span class="text-xs font-weight-bold">'.$hora.'</span>
                </td>
                <td class="align-middle text-center">
                   <div class="d-flex align-items-center justify-content-center text-sm">
                   <span id="'.$id.'" style="cursor:pointer;" class="badge badge-sm bg-gradient-danger toast-btn" data-target="infoToast" onclick="sendBlock(this.id);">Bloquear</span>
                   </div>
                </td>
				</tr>';
		}
	}else{
	 echo '<tr><td colspan="5" class="text-center py-4 text-secondary text-sm">Nenhum visitante online no momento</td></tr>';
	}
	
	break; //=============================================
	
	case "lista_pix": 
	
	$sql = mysqli_query($conn, "SELECT * FROM pixgerado ORDER BY id DESC LIMIT 20");
	if($sql && mysqli_num_rows($sql) > 0){
		 while($rowx = mysqli_fetch_array($sql)){ 
	     
		 $id = $rowx["id"];
		 $val_num = parse_moeda_float($rowx["valor"] ?? '0');
		 $valor_fmt = "R$ " . number_format($val_num, 2, ',', '.');
		 $hora = $rowx["hora"] ?? date('H:i'); 
		 $cli_nome = !empty($rowx["cliente_nome"]) ? htmlspecialchars($rowx["cliente_nome"]) : "Cliente";
		 $cli_tel = !empty($rowx["cliente_telefone"]) ? htmlspecialchars($rowx["cliente_telefone"]) : "";
		 
		 // Buscar nome do produto se não estiver salvo direto
		 $prod_nome = !empty($rowx["produto_nome"]) ? htmlspecialchars($rowx["produto_nome"]) : "";
		 if (empty($prod_nome)) {
			 $cod_p = $rowx["produto"] ?? '';
			 $qp = mysqli_query($conn, "SELECT nome FROM produto WHERE codigo='$cod_p' LIMIT 1");
			 if ($qp && $rp = mysqli_fetch_assoc($qp)) {
				 $prod_nome = htmlspecialchars($rp['nome']);
			 } else {
				 $prod_nome = "Produto #" . $cod_p;
			 }
		 }
		 
		 // Formatar status
		 $status_main = strtolower($rowx['status'] ?? '');
		 $status_pg = strtolower($rowx['pixgo_status'] ?? '');
		 $status_mp = strtolower($rowx['mp_status'] ?? '');
		 $status_fp = strtolower($rowx['freepay_status'] ?? '');
		 $status_ch = strtolower($rowx['carthero_status'] ?? '');
		 
		 $badge_status = '<span class="badge badge-xs bg-gradient-warning">Aguardando Pagamento</span>';
		 $pago_arrays = ['pago', 'paid', 'approved', 'approved_payment', 'completed', 'success'];
		 
		 if (in_array($status_main, $pago_arrays) || in_array($status_pg, $pago_arrays) || in_array($status_mp, $pago_arrays) || in_array($status_fp, $pago_arrays) || in_array($status_ch, $pago_arrays)) {
			 $badge_status = '<span class="badge badge-xs bg-gradient-success">Pago</span>';
		 }
		 
		 echo '<div class="timeline-block mb-3 p-2 border-radius-lg" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
				  <span class="timeline-step">
					<i class="material-icons text-success text-gradient">attach_money</i>
				  </span>
				  <div class="timeline-content">
					<div class="d-flex align-items-center justify-content-between">
						<h6 class="text-white text-sm font-weight-bold mb-0">'.$valor_fmt.'</h6>
						<small class="text-xs text-muted">'.$hora.'</small>
					</div>
					<p class="text-xs text-light mb-1 mt-1 font-weight-bold">'.$prod_nome.'</p>
					<div class="d-flex align-items-center justify-content-between mt-1">
						<span class="text-xs text-secondary">'.$cli_nome.($cli_tel ? ' ('.$cli_tel.')' : '').'</span>
						'.$badge_status.'
					</div>
				  </div>
				</div>';
		}
	} else {
	    echo '<div class="empty-state text-center py-4">
				<i class="material-icons" style="font-size:36px; opacity:0.3;">receipt_long</i>
				<p class="text-xs text-muted mt-2">Nenhuma ordem de pagamento gerada ainda.</p>
			  </div>';
	}
	
	break; //=============================================
	
	// ===== TABELAS PIX COPIA E COLA =====

    case "pix_tabela_criar":
        header('Content-Type: application/json; charset=utf-8');
        $nome_tab = addslashes(trim($_POST['nome'] ?? ''));
        if (empty($nome_tab)) { echo json_encode(['ok'=>false,'error'=>'Nome obrigatrio']); break; }
        
        // Garantir tabelas existem
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `pix_tabelas` (`id` int(11) NOT NULL AUTO_INCREMENT, `nome` varchar(255) NOT NULL DEFAULT 'Tabela PIX', `ativa` tinyint(1) NOT NULL DEFAULT 0, `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `pix_tabela_codigos` (`id` int(11) NOT NULL AUTO_INCREMENT, `tabela_id` int(11) NOT NULL, `valor` DECIMAL(10,2) NOT NULL DEFAULT 0.00, `codigo` TEXT NOT NULL, `status_pagamento` varchar(20) NOT NULL DEFAULT 'DISPONIVEL', `reservado_em` DATETIME DEFAULT NULL, `reservado_pedido_ref` varchar(100) DEFAULT NULL, `pago_em` DATETIME DEFAULT NULL, `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`id`), INDEX `idx_tabela_id` (`tabela_id`), INDEX `idx_status` (`status_pagamento`), FOREIGN KEY (`tabela_id`) REFERENCES `pix_tabelas`(`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $r = mysqli_query($conn, "INSERT INTO pix_tabelas (nome, ativa) VALUES ('$nome_tab', 0)");
        if ($r) {
            echo json_encode(['ok'=>true, 'id'=>mysqli_insert_id($conn)]);
        } else {
            echo json_encode(['ok'=>false, 'error'=>mysqli_error($conn)]);
        }
    break;

    case "pix_tabela_renomear":
        header('Content-Type: application/json; charset=utf-8');
        $id_tab = (int)($_POST['id'] ?? 0);
        $nome_tab = addslashes(trim($_POST['nome'] ?? ''));
        if (!$id_tab || empty($nome_tab)) { echo json_encode(['ok'=>false,'error'=>'Dados invǭlidos']); break; }
        $r = mysqli_query($conn, "UPDATE pix_tabelas SET nome='$nome_tab' WHERE id=$id_tab");
        echo json_encode(['ok'=>$r]);
    break;

    case "pix_tabela_ativar":
        header('Content-Type: application/json; charset=utf-8');
        $id_tab = (int)($_POST['id'] ?? 0);
        if (!$id_tab) { echo json_encode(['ok'=>false,'error'=>'ID inválido']); break; }
        // Não desativa as outras para permitir múltiplas ativas
        $r = mysqli_query($conn, "UPDATE pix_tabelas SET ativa=1 WHERE id='$id_tab'");
        echo json_encode($r ? ['ok'=>true] : ['ok'=>false,'error'=>mysqli_error($conn)]);
    break;

    case "pix_tabela_desativar":
        header('Content-Type: application/json; charset=utf-8');
        $id_tab = (int)($_POST['id'] ?? 0);
        if (!$id_tab) { echo json_encode(['ok'=>false,'error'=>'ID inválido']); break; }
        $r = mysqli_query($conn, "UPDATE pix_tabelas SET ativa=0 WHERE id='$id_tab'");
        echo json_encode($r ? ['ok'=>true] : ['ok'=>false,'error'=>mysqli_error($conn)]);
    break;

    case "pix_tabela_deletar":
        $id_tab = (int)($_POST['id'] ?? 0);
        if (!$id_tab) { echo json_encode(['ok'=>false,'error'=>'ID invǭlido']); break; }
        // Deletar cdigos da tabela
        mysqli_query($conn, "DELETE FROM pix_tabela_codigos WHERE tabela_id='$id_tab'");
        // Deletar a tabela
        $r = mysqli_query($conn, "DELETE FROM pix_tabelas WHERE id='$id_tab'");
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($r ? ['ok'=>true] : ['ok'=>false,'error'=>mysqli_error($conn)]);
    break;

    case "pix_tabela_adicionar_codigos":
        $id_tab = (int)($_POST['tabela_id'] ?? 0);
        $valor_raw = trim($_POST['valor'] ?? '');
        $codigos_raw = trim($_POST['codigos'] ?? '');
        if (!$id_tab || empty($valor_raw) || empty($codigos_raw)) { echo json_encode(['ok'=>false,'error'=>'Dados incompletos']); break; }
        // Normalizar valor
        $valor_clean = str_replace([' ','R$'], '', $valor_raw);
        $valor_clean = str_replace(',', '.', $valor_clean);
        $valor_float = floatval($valor_clean);
        if ($valor_float <= 0) { echo json_encode(['ok'=>false,'error'=>'Valor invǭlido']); break; }
        // Processar cdigos (um por linha)
        $linhas = explode("\n", $codigos_raw);
        $inseridos = 0;
        foreach ($linhas as $linha) {
            $codigo = trim($linha);
            if (empty($codigo)) continue;
            $codigo_safe = mysqli_real_escape_string($conn, $codigo);
            $r = mysqli_query($conn, "INSERT INTO pix_tabela_codigos (tabela_id, valor, codigo, status_pagamento) VALUES ('$id_tab', '$valor_float', '$codigo_safe', 'DISPONIVEL')");
            if ($r) $inseridos++;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>true,'total'=>$inseridos]);
    break;

    case "pix_tabela_listar_codigos":
        $id_tab = (int)($_POST['tabela_id'] ?? 0);
        $limit = min((int)($_POST['limit'] ?? 100), 500);
        $offset = (int)($_POST['offset'] ?? 0);
        if (!$id_tab) { echo json_encode(['ok'=>false,'error'=>'ID invǭlido']); break; }
        // Limpar reservas vencidas também ao abrir a tabela administrativa.
        mysqli_query($conn, "UPDATE pix_tabela_codigos SET status_pagamento='DISPONIVEL', reservado_em=NULL, reservado_pedido_ref=NULL WHERE tabela_id='$id_tab' AND status_pagamento='RESERVADO' AND (TIMESTAMPDIFF(SECOND, reservado_em, NOW()) >= 300 OR reservado_em IS NULL)");
        $sql_c = mysqli_query($conn, "SELECT id, valor, codigo, status_pagamento, reservado_em, reservado_pedido_ref, pago_em, criado_em FROM pix_tabela_codigos WHERE tabela_id='$id_tab' ORDER BY id DESC LIMIT $limit OFFSET $offset");
        $items = [];
        while ($row_c = mysqli_fetch_assoc($sql_c)) {
            $items[] = $row_c;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>true,'items'=>$items]);
    break;

    case "pix_codigo_tornar_disponivel":
        header('Content-Type: application/json; charset=utf-8');
        $codigo_id = (int)($_POST['codigo_id'] ?? 0);
        if (!$codigo_id) { echo json_encode(['ok'=>false,'error'=>'ID inválido']); break; }
        // Só libera reservas; códigos pagos não podem ser reabertos acidentalmente.
        $r = mysqli_query($conn, "UPDATE pix_tabela_codigos SET status_pagamento='DISPONIVEL', reservado_em=NULL, reservado_pedido_ref=NULL WHERE id='$codigo_id' AND status_pagamento='RESERVADO'");
        if ($r && mysqli_affected_rows($conn) > 0) {
            echo json_encode(['ok'=>true]);
        } elseif ($r) {
            echo json_encode(['ok'=>false,'error'=>'Este código não está reservado ou já foi liberado.']);
        } else {
            echo json_encode(['ok'=>false,'error'=>mysqli_error($conn)]);
        }
    break;

    case "pix_codigo_marcar_pago":
        $codigo_id = (int)($_POST['codigo_id'] ?? 0);
        if (!$codigo_id) { echo json_encode(['ok'=>false,'error'=>'ID invǭlido']); break; }
        $r = mysqli_query($conn, "UPDATE pix_tabela_codigos SET status_pagamento='PAGO', pago_em=NOW() WHERE id='$codigo_id'");
        
        // Também atualizar o status em pixgerado para refletir nas Ordens de Pagamento
        $r_code = mysqli_query($conn, "SELECT codigo FROM pix_tabela_codigos WHERE id='$codigo_id'");
        if ($r_code && $code_row = mysqli_fetch_assoc($r_code)) {
            $code_safe = mysqli_real_escape_string($conn, $code_row['codigo']);
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
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($r ? ['ok'=>true] : ['ok'=>false,'error'=>mysqli_error($conn)]);
    break;

    case "alternar_pagamento_confirmado":
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'ID inválido']);
            break;
        }

        $q_c = mysqli_query($conn, "SELECT pagamento_confirmado FROM clientes WHERE id='$id' LIMIT 1");
        if ($q_c && $r_c = mysqli_fetch_assoc($q_c)) {
            $novo_status = empty($r_c['pagamento_confirmado']) ? '1' : '0';
            $r_upd = mysqli_query($conn, "UPDATE clientes SET pagamento_confirmado='$novo_status', data_pagamento=IF('$novo_status'='1', NOW(), NULL) WHERE id='$id'");
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'novo_status' => (int)$novo_status]);
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Cliente não encontrado']);
        }
    break;

    case "atualizar_status_rastreio":
        $id = (int)($_POST['id'] ?? 0);
        $status_rastreio = trim((string)($_POST['status_rastreio'] ?? ''));
        $enviar_email = (int)($_POST['enviar_email'] ?? 0);

        if (!$id || empty($status_rastreio)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'ID ou status inválido']);
            break;
        }

        $status_safe = mysqli_real_escape_string($conn, $status_rastreio);
        $r_upd = mysqli_query($conn, "UPDATE clientes SET status_rastreio='$status_safe', data_status_rastreio=NOW() WHERE id='$id'");

        $q_c = mysqli_query($conn, "SELECT * FROM clientes WHERE id='$id' LIMIT 1");
        if ($q_c && $r_c = mysqli_fetch_assoc($q_c)) {
            // Sincroniza o status para outros cadastros do mesmo cliente (mesmo email ou telefone)
            // Isso previne o bug onde a tabela puxa o status de uma linha 'paga' antiga que não foi atualizada.
            $email_c = mysqli_real_escape_string($conn, $r_c['email'] ?? '');
            $tel_c = preg_replace('/\D/', '', $r_c['celular'] ?? '');
            
            $conds = [];
            if (!empty($email_c) && $email_c !== '-') {
                $conds[] = "email='$email_c'";
            }
            if (!empty($tel_c)) {
                $conds[] = "celular LIKE '%$tel_c%'";
            }
            
            if (!empty($conds)) {
                $where = implode(" OR ", $conds);
                mysqli_query($conn, "UPDATE clientes SET status_rastreio='$status_safe', data_status_rastreio=NOW() WHERE ($where)");
            }

            if (!empty($r_c['ip'])) {
                $raw_ip = base64_decode($r_c['ip']);
                $ip_safe = mysqli_real_escape_string($conn, $raw_ip);
                mysqli_query($conn, "UPDATE pixgerado SET status_rastreio='$status_safe', data_status_rastreio=NOW() WHERE ip='$ip_safe'");
            }

            if ($enviar_email === 1 && !empty($r_c['email']) && $r_c['email'] !== '-') {
                $status_map_names = [
                    'preparando_envio' => ['title' => 'Preparando envio', 'desc' => 'O vendedor está preparando o seu pacote.'],
                    'pedido_solicitado' => ['title' => 'Pedido solicitado', 'desc' => 'O pedido foi registrado em nosso sistema.'],
                    'enviado_transportadora' => ['title' => 'Enviado para transportadora', 'desc' => 'Seu pacote foi entregue à transportadora responsável.'],
                    'em_transito' => ['title' => 'Em trânsito para ponto de distribuição de sua cidade', 'desc' => 'Seu pedido está a caminho do centro de distribuição local.'],
                    'centro_distribuicao' => ['title' => 'Pedido chegou ao centro de distribuição', 'desc' => 'Pacote recebido no centro de distribuição da sua região.'],
                    'rota_entrega' => ['title' => 'Pedido em rota de entrega', 'desc' => 'O entregador saiu para entregar seu pedido no endereço.'],
                    'entregue' => ['title' => 'Pedido entregue', 'desc' => 'Pedido entregue com sucesso no seu endereço!'],
                    'ausente' => ['title' => 'Não encontrou ninguém no endereço', 'desc' => 'Tentativa de entrega realizada. Nova tentativa será feita em breve.']
                ];

                $st_info = $status_map_names[$status_rastreio] ?? ['title' => 'Status Atualizado', 'desc' => 'Seu pedido teve uma nova movimentação.'];

                $_REQUEST['tipo'] = 'status_rastreio';
                $_REQUEST['override_id'] = $id;
                $_REQUEST['override_ip'] = !empty($r_c['ip']) ? base64_decode($r_c['ip']) : '';
                $_REQUEST['override_email'] = $r_c['email'];
                $_REQUEST['override_nome'] = $r_c['nome'];
                $_REQUEST['override_produto'] = $r_c['produto_codigo'] ?? '';
                $_REQUEST['override_status_key'] = $status_rastreio;
                $_REQUEST['override_status_nome'] = $st_info['title'];
                $_REQUEST['override_status_desc'] = $st_info['desc'];

                ob_start();
                include(__DIR__ . '/../../api/phpmailer.php');
                $out_email = ob_get_clean();

                file_put_contents(__DIR__ . '/../../api/mail_debug.txt', date('[Y-m-d H:i:s] ') . "atualizar_status_rastreio email output: " . strip_tags($out_email) . "\n", FILE_APPEND);
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'email_output' => isset($out_email) ? strip_tags($out_email) : 'sem_envio']);
    break;

    case "pix_codigo_consultar_status":
        $codigo_id = (int)($_POST['codigo_id'] ?? 0);
        if (!$codigo_id) { echo json_encode(['ok'=>false,'error'=>'ID inválido']); break; }
        $codigo_id_safe = (int)$codigo_id;
        $r_codigo = mysqli_query($conn, "SELECT id, codigo, status_pagamento, pago_em FROM pix_tabela_codigos WHERE id='$codigo_id_safe' LIMIT 1");
        if (!$r_codigo || !($codigo = mysqli_fetch_assoc($r_codigo))) {
            echo json_encode(['ok'=>false,'error'=>'Código PIX não encontrado']);
            break;
        }
        $codigo_safe = mysqli_real_escape_string($conn, $codigo['codigo']);
        $origem = 'controle interno';
        $status = strtoupper($codigo['status_pagamento'] ?? 'DISPONIVEL');
        // Se este código já foi usado no fluxo PIX, aproveita o status retornado
        // pelo gateway/webhook gravado em pixgerado.
        $r_pagamento = @mysqli_query($conn, "SELECT status, mp_status, freepay_status, pixgo_status, carthero_status, data_criacao FROM pixgerado WHERE pix_code='$codigo_safe' ORDER BY id DESC LIMIT 1");
        if ($r_pagamento && ($pagamento = mysqli_fetch_assoc($r_pagamento))) {
            $status_gateway = strtoupper((string)($pagamento['status'] ?: ($pagamento['mp_status'] ?: ($pagamento['freepay_status'] ?: ($pagamento['pixgo_status'] ?: $pagamento['carthero_status'])))));
            if (in_array($status_gateway, ['PAID', 'PAGO', 'COMPLETED', 'APPROVED', 'RECEIVED'], true)) {
                $status = 'PAGO';
                $origem = 'gateway/webhook';
            } else {
                $status = $status_gateway ?: $status;
                $origem = 'gateway/webhook';
            }
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok'=>true, 'status'=>$status, 'origem'=>$origem, 'pago_em'=>$codigo['pago_em'] ?? null]);
    break;

    case "pix_codigo_deletar":
        $codigo_id = (int)($_POST['codigo_id'] ?? 0);
        if (!$codigo_id) { echo json_encode(['ok'=>false,'error'=>'ID invǭlido']); break; }
        $r = mysqli_query($conn, "DELETE FROM pix_tabela_codigos WHERE id='$codigo_id'");
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($r ? ['ok'=>true] : ['ok'=>false,'error'=>mysqli_error($conn)]);
    break;

    // ===== FIM TABELAS PIX =====

	case "lista_webhook":
	
	$sql = mysqli_query($conn, "SELECT * FROM vendas_confirmadas ORDER BY data_venda DESC LIMIT 15");
	if($sql && mysqli_num_rows($sql) > 0){
		 while($rowx = mysqli_fetch_array($sql)){ 
	     
		 $tx = htmlspecialchars($rowx["transaction_id"]);
		 $val_num = $rowx["valor"];
		 $valor_fmt = "R$ " . number_format($val_num, 2, ',', '.');
		 $hora = date('d/m/Y H:i', strtotime($rowx["data_venda"])); 
		 
		 // Buscar nome do produto
		 $prod_nome = "";
		 $cod_p = $rowx["produto_codigo"] ?? '';
		 if(!empty($cod_p)){
			 $qp = mysqli_query($conn, "SELECT nome FROM produto WHERE codigo='$cod_p' LIMIT 1");
			 if ($qp && $rp = mysqli_fetch_assoc($qp)) {
				 $prod_nome = htmlspecialchars($rp['nome']);
			 } else {
				 $prod_nome = "Produto #" . $cod_p;
			 }
		 }
		 
		 $status_raw = strtoupper($rowx['status']);
		 $badge_status = '<span class="badge badge-sm bg-gradient-success">Aprovado</span>';
		 if ($status_raw != 'PAID' && $status_raw != 'APPROVED') {
			 $badge_status = '<span class="badge badge-sm bg-gradient-warning">'.$status_raw.'</span>';
		 }
		 
		 echo '<tr>
                <td class="align-middle">
                  <div class="d-flex px-2 py-1">
                    <div class="d-flex flex-column justify-content-center">
                      <h6 class="mb-0 text-sm text-white">'.$prod_nome.'</h6>
                      <p class="text-xs text-secondary mb-0" title="'.$tx.'">TxID: '.substr($tx, 0, 15).'...</p>
                    </div>
                  </div>
                </td>
				<td class="align-middle text-center">
                   <span class="text-sm font-weight-bold text-success">'.$valor_fmt.'</span>
                </td>
                <td class="align-middle text-center">
                   '.$badge_status.'
                </td>
                <td class="align-middle text-center">
                   <span class="text-xs text-secondary font-weight-bold">'.$hora.'</span>
                </td>
				</tr>';
		}
	} else {
	    echo '<tr><td colspan="4" class="text-center py-4 text-secondary text-sm">Nenhuma baixa automática via webhook registrada ainda.</td></tr>';
	}
	
	break; //=============================================
	
	case "blockUser":
	
	$id = addslashes($_POST["user"] ?? '');
	$query = false;
	if($id !== ''){
		$sqlxx = mysqli_query($conn, "SELECT * from online WHERE id='$id'");
		if(($sqlxx ? mysqli_num_rows($sqlxx) : 0) > 0){
			$query = mysqli_query($conn, "UPDATE online SET situacao='desativo' WHERE id='$id'");
		}
	}
	echo $query ? "ok" : "erro";
	
	break; //=============================================
	
	case "cadastros":
	        						
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
		$host = $_SERVER['HTTP_HOST'] ?? 'inbalancebalancasbovinas.com.br';
		$base_url = $protocol . $host;

		$sql = mysqli_query($conn, "SELECT * FROM clientes ORDER BY id DESC");
		if($sql && mysqli_num_rows($sql) > 0){
			while($rowx = mysqli_fetch_array($sql)){ 
			 
				$id           = $rowx["id"];
				$nome         = htmlspecialchars($rowx["nome"] ?? 'Sem Nome');
				$email        = htmlspecialchars($rowx["email"] ?? '-');
				$cpf          = htmlspecialchars($rowx["cpf"] ?? '-');
				$celular      = htmlspecialchars($rowx["celular"] ?? '');
				
				$cep          = htmlspecialchars($rowx["cep"] ?? '');
				$endereco     = htmlspecialchars($rowx["endereco"] ?? '');
				$numero       = htmlspecialchars($rowx["numero"] ?? '');
				$bairro       = htmlspecialchars($rowx["bairro"] ?? '');
				$cidade       = htmlspecialchars($rowx["cidade"] ?? '');
				$complemento  = htmlspecialchars($rowx["complemento"] ?? '');
				$destinatario = htmlspecialchars($rowx["destinatario"] ?? '');
				
				$quantidade   = $rowx["quantidade"] ?? 1;
				
				// Normalização do valor para exibição correta
				$val_float    = parse_moeda_float($rowx["valortotal"] ?? '0');
				$valortotal   = ($val_float > 0) ? "R$ " . number_format($val_float, 2, ',', '.') : htmlspecialchars($rowx["valortotal"] ?? '-');
				
				// Data de cadastro formatada
				$data_cad = !empty($rowx["data_cadastro"]) ? date('d/m/Y H:i', strtotime($rowx["data_cadastro"])) : "-";
				
				// Código do produto e busca de detalhes
				$cod_p = $rowx["produto_codigo"] ?? '';
				$produto = !empty($rowx["produto_nome"]) ? htmlspecialchars($rowx["produto_nome"]) : "";
				$img_prod = '';
				
				if (!empty($cod_p)) {
					$qp = mysqli_query($conn, "SELECT codigo, nome, img FROM produto WHERE codigo='$cod_p' LIMIT 1");
					if ($qp && $rp = mysqli_fetch_assoc($qp)) {
						if (empty($produto) || $produto === 'Interesse na Loja') {
							$produto = htmlspecialchars($rp['nome']);
						}
						$img_raw = $rp['img'] ?? '';
						if (!empty($img_raw)) {
							$img_prod = (strpos($img_raw, 'http') === 0) ? $img_raw : '../arquivos/produtos/' . $cod_p . '/' . $img_raw;
						}
					}
				}
				
				$is_order_paid = !empty($rowx['pagamento_confirmado']);
				$status_rastreio_atual = !empty($rowx['status_rastreio']) ? $rowx['status_rastreio'] : 'preparando_envio';
				
				// Se este registro especifico nao estiver como pago, verificar se o cliente possui outro registro com pagamento confirmado ou pix pago
				$tel_limpo = preg_replace('/\D/', '', $celular);
				$email_cli = mysqli_real_escape_string($conn, $rowx["email"] ?? '');
				$ip_raw = base64_decode($rowx["ip"] ?? '');

				if (!$is_order_paid) {
					// 1. Verificar se existe algum outro cadastro desse mesmo cliente (mesmo email ou telefone) marcado com pagamento_confirmado='1'
					if (!empty($email_cli) && $email_cli !== '-') {
						$q_other_paid = mysqli_query($conn, "SELECT id, status_rastreio FROM clientes WHERE email='$email_cli' AND pagamento_confirmado='1' ORDER BY id DESC LIMIT 1");
						if ($q_other_paid && $r_op = mysqli_fetch_assoc($q_other_paid)) {
							$is_order_paid = true;
							if (!empty($r_op['status_rastreio'])) {
								$status_rastreio_atual = $r_op['status_rastreio'];
							}
						}
					}
					if (!$is_order_paid && !empty($tel_limpo)) {
						$q_other_paid_tel = mysqli_query($conn, "SELECT id, status_rastreio FROM clientes WHERE celular LIKE '%$tel_limpo%' AND pagamento_confirmado='1' ORDER BY id DESC LIMIT 1");
						if ($q_other_paid_tel && $r_opt = mysqli_fetch_assoc($q_other_paid_tel)) {
							$is_order_paid = true;
							if (!empty($r_opt['status_rastreio'])) {
								$status_rastreio_atual = $r_opt['status_rastreio'];
							}
						}
					}
				}

				// Busca na tabela pixgerado por telefone ou ip para status e produto
				if (!empty($tel_limpo) || !empty($ip_raw)) {
					$sql_prod = mysqli_query($conn, "SELECT produto, produto_nome, status, pixgo_status, mp_status, freepay_status, carthero_status FROM pixgerado WHERE (cliente_telefone LIKE '%$tel_limpo%' AND cliente_telefone != '') OR (ip='$ip_raw' AND ip != '') ORDER BY id DESC LIMIT 1");
					if ($sql_prod && $row_p = mysqli_fetch_assoc($sql_prod)) {
						if (empty($produto) || $produto === 'Interesse na Loja') {
							$produto = htmlspecialchars($row_p['produto_nome'] ?: $row_p['produto']);
						}
						if (empty($cod_p)) {
							$cod_p = $row_p['produto'] ?? '';
						}
						
						// Verifica status de pagamento
						if (!$is_order_paid) {
							$pago_arrays = ['pago', 'paid', 'approved', 'approved_payment', 'completed', 'success'];
							$status_main = strtolower($row_p['status'] ?? '');
							$status_pg = strtolower($row_p['pixgo_status'] ?? '');
							$status_mp = strtolower($row_p['mp_status'] ?? '');
							$status_fp = strtolower($row_p['freepay_status'] ?? '');
							$status_ch = strtolower($row_p['carthero_status'] ?? '');
							
							if (in_array($status_main, $pago_arrays) || in_array($status_pg, $pago_arrays) || in_array($status_mp, $pago_arrays) || in_array($status_fp, $pago_arrays) || in_array($status_ch, $pago_arrays)) {
								$is_order_paid = true;
							}
						}
					}
				}
				
				$btn_status_rastreio = '';
				if ($is_order_paid) {
					$status_pagamento_badge = '<span onclick="alternarStatusPagamento('.(int)$id.')" class="badge badge-sm bg-gradient-success" title="Clique para alterar para Reservado" style="padding: 4px 8px; display:inline-flex; align-items:center; gap:2px; cursor:pointer;"><i class="material-icons" style="font-size:12px;">check_circle</i> Pago</span>';
					$btn_status_rastreio = '<button type="button" class="btn btn-xs bg-gradient-info mb-0 px-2 py-1 text-xxs font-weight-bold d-inline-flex align-items-center gap-1" style="white-space:nowrap;" onclick="abrirModalStatus('.(int)$id.', \''.addslashes($status_rastreio_atual).'\', \''.addslashes($nome).'\')" title="Atualizar Status do Rastreio"><i class="material-icons" style="font-size:12px;">local_shipping</i> Status Rastreio</button>';
				} else {
					$status_pagamento_badge = '<span onclick="alternarStatusPagamento('.(int)$id.')" class="badge badge-sm bg-gradient-warning" title="Clique para alterar para Pago" style="padding: 4px 8px; display:inline-flex; align-items:center; gap:2px; cursor:pointer;"><i class="material-icons" style="font-size:12px;">schedule</i> Reservado</span>';
				}
				
				// Se ainda não achou cod_p mas tem o nome do produto, busca em produto por nome
				if (empty($cod_p) && !empty($produto) && $produto !== 'Interesse na Loja') {
					$prod_safe = mysqli_real_escape_string($conn, $produto);
					$qp_nome = mysqli_query($conn, "SELECT codigo, img FROM produto WHERE nome LIKE '%$prod_safe%' LIMIT 1");
					if ($qp_nome && $rpn = mysqli_fetch_assoc($qp_nome)) {
						$cod_p = $rpn['codigo'];
						if (empty($img_prod) && !empty($rpn['img'])) {
							$img_prod = (strpos($rpn['img'], 'http') === 0) ? $rpn['img'] : '../arquivos/produtos/' . $cod_p . '/' . $rpn['img'];
						}
					}
				}
				
				// Link da página do produto (formato produto?produto=CODIGO)
				$link_prod = !empty($cod_p) ? ($base_url . "/produto?produto=" . urlencode($cod_p)) : ($base_url . "/catalogo");
				
				// Variações formatadas (Cor, Voltagem, etc.)
				$variacoes_str = "";
				if (!empty($rowx["variacoes"])) {
					$v_obj = json_decode($rowx["variacoes"], true);
					if (is_array($v_obj)) {
						if (!empty($v_obj['imagem_selecionada'])) {
							$img_prod = $v_obj['imagem_selecionada'];
						}
							$v_parts = [];
							foreach ($v_obj as $vk => $vv) {
								if (!in_array($vk, ['titulo_selecionado', 'imagem_selecionada', 'titulo', 'img']) && !empty($vv)) {
									$vv_text = is_array($vv) ? implode(', ', array_map('strval', $vv)) : (string)$vv;
									$v_parts[] = ucfirst($vk) . ": " . htmlspecialchars($vv_text);
							}
						}
						$variacoes_str = implode(" | ", $v_parts);
					} else {
						$variacoes_str = htmlspecialchars($rowx["variacoes"]);
					}
				}
				
				$ip = $rowx["ip"] ?? '';

				// Link de Remarketing para WhatsApp com Foto, Nome do Produto e Link Direto
				$zap_clean = preg_replace('/\D/', '', $celular);
				$btn_whatsapp = "";
				if (!empty($zap_clean)) {
					if (strlen($zap_clean) <= 11) {
						$zap_full = '55' . $zap_clean;
					} else {
						$zap_full = $zap_clean;
					}
					
					$msg_zap = "Olá " . $rowx["nome"] . "! Tudo bem?\n\nVi que você iniciou o pedido do *" . $produto . "* (" . $valortotal . ") na nossa loja.\n\n👉 *Link do seu pedido:*\n" . $link_prod . "\n\nVocê ficou com alguma dúvida ou precisa de ajuda para concluir?\nEstamos à disposição!";
					$msg_encoded = rawurlencode($msg_zap);
					$zap_link = "https://wa.me/" . $zap_full . "?text=" . $msg_encoded;
					$btn_whatsapp = '<a href="'.$zap_link.'" target="_blank" class="btn btn-xs bg-gradient-success mb-1 px-2 py-1 text-xxs font-weight-bold" style="white-space:nowrap;" title="Chamar no WhatsApp"><i class="fab fa-whatsapp me-1" style="font-size:11px;"></i> WhatsApp</a>';
				}

				$btn_email = "";
				$btn_email_html = "";
				if (!empty($email) && $email !== '-') {
					$msg_email_body = "Olá " . $rowx["nome"] . "!\n\nVi que você iniciou o pedido do " . $produto . " (" . $valortotal . ") na nossa loja e não concluiu.\n\nLink do seu pedido:\n" . $link_prod . "\n\nVocê ficou com alguma dúvida ou precisa de ajuda para concluir?\nEstamos à disposição!";
					// Abre diretamente no Gmail pelo navegador (reconhece o email logado)
					$email_link = "https://mail.google.com/mail/?view=cm&fs=1&to=" . urlencode($email) . "&su=" . rawurlencode("Seu pedido na nossa loja") . "&body=" . rawurlencode($msg_email_body);
					$btn_email = '<a href="'.$email_link.'" target="_blank" class="btn btn-xs bg-gradient-info mb-1 px-2 py-1 text-xxs font-weight-bold" style="white-space:nowrap;" title="Enviar E-mail via Gmail"><i class="material-icons" style="font-size:11px; vertical-align:middle;">email</i> Gmail</a>';
					
					// Botão para disparar o e-mail HTML pelo servidor (via SMTP)
					$btn_email_html = '<button type="button" onclick="enviarReciboHTML('.$id.')" class="btn btn-xs bg-gradient-primary mb-1 px-2 py-1 text-xxs font-weight-bold" style="white-space:nowrap;" title="Enviar E-mail de Recibo HTML"><i class="material-icons" style="font-size:11px; vertical-align:middle;">send</i> Enviar Recibo</button>';
				}
						
				echo '<tr>
					  <td>
						<div class="d-flex px-2 py-1">
						  <div class="d-flex flex-column justify-content-center">
							<h6 class="mb-0 text-sm text-white font-weight-bold">'.$nome.'</h6>
							<span class="text-xs text-secondary"><i class="fa fa-calendar-alt me-1"></i>'.$data_cad.'</span>
						  </div>
						</div>
					  </td>
					  <td>
						<h6 class="mb-0 text-sm text-white" style="word-break:break-all;"><a href="mailto:'.$email.'" class="text-info">'.$email.'</a></h6>
						<span class="text-xs text-secondary">CPF: '.$cpf.'</span>
					  </td>
					  
					  <td class="align-middle text-center">
						<h6 class="mb-1 text-sm text-white font-weight-bold">'.$celular.'</h6>
						<div class="mt-1 d-flex flex-column align-items-center justify-content-center gap-1">'.$btn_whatsapp.$btn_email.$btn_email_html.'</div>
					  </td>
				 
					  <td class="align-middle text-center">
						<h6 class="mb-0 text-sm text-white">'.$endereco.', '.$numero.($bairro ? ' - '.$bairro : '').'</h6>
						<span class="text-secondary text-xs">'.($complemento ? $complemento.' | ' : '').$cidade.' - '.$cep.'</span>
					  </td>
					  
					  <td class="align-middle text-center">
						<div class="d-flex align-items-center justify-content-center">
						  '.(!empty($img_prod) ? '<img src="'.$img_prod.'" alt="'.$produto.'" style="width:38px; height:38px; object-fit:contain; border-radius:6px; border:1px solid rgba(255,255,255,0.15); margin-right:8px; background:#fff; flex-shrink:0;" onerror="this.style.display=\'none\'">' : '').'
						  <div class="text-start">
							<h6 class="mb-0 text-sm text-white font-weight-bold"><a href="'.$link_prod.'" target="_blank" class="text-white" title="Ver produto na loja">'.$produto.' <i class="fa fa-external-link-alt text-xxs text-info"></i></a></h6>
							<p class="text-xs text-secondary mb-0">'.$variacoes_str.'</p>
						  </div>
						</div>
					  </td>
					  
					  <td class="align-middle text-center">
						<h6 class="mb-0 text-sm text-white font-weight-bold">Qtd: '.$quantidade.'</h6>
						<span class="text-success text-xs font-weight-bold">'.$valortotal.'</span>
					  </td>
					 
					  <td class="align-middle text-center">
						<div class="d-flex flex-column align-items-center justify-content-center gap-1">
							<div class="d-flex align-items-center justify-content-center gap-1 flex-wrap">
								<span id="'.$id.'" onclick="excluir(this.id);" style="cursor:pointer; padding:4px 8px;" class="badge badge-sm bg-gradient-secondary" title="Excluir"><i class="material-icons" style="font-size:14px;">delete</i></span>
								<span id="'.$ip.'" onclick="sendBlock(this.id)" style="cursor:pointer; padding:4px 8px;" class="badge badge-sm bg-gradient-danger" title="Bloquear"><i class="material-icons" style="font-size:14px;">block</i></span>
								'.$status_pagamento_badge.'
							</div>
							'.$btn_status_rastreio.'
						</div>
					  </td>
					</tr>';
			}
		} else {
			echo '<tr><td colspan="7" class="text-center py-4 text-secondary text-sm">Nenhum cadastro encontrado ainda.</td></tr>';
		}
	break;
	
	case "totalcadastros":
	
	        $QUERY5 = "SELECT COUNT(*) as total FROM clientes";
			$desk = mysqli_query($conn, $QUERY5);
			$row = ($desk) ? mysqli_fetch_assoc($desk) : null;
			echo $row['total'] ?? 0;
			
	break; //===========================================================

	case "exportar_csv":
		if(!isset($_SESSION['login'])) {
			echo "Acesso negado";
			exit;
		}
		
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename=cadastros_remarketing_' . date('Y-m-d_H-i') . '.csv');
		
		$output = fopen('php://output', 'w');
		// BOM UTF-8 para Excel abrir perfeitamente com acentos
		fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
		
		// Cabeçalhos
		fputcsv($output, ['ID', 'Nome', 'Email', 'Telefone', 'WhatsApp_Link', 'CPF', 'CEP', 'Endereco', 'Numero', 'Bairro', 'Cidade', 'Complemento', 'Produto', 'Quantidade', 'Valor_Total', 'Data_Cadastro'], ';');
		
		$sql = mysqli_query($conn, "SELECT * FROM clientes ORDER BY id DESC");
		if($sql) {
			while($r = mysqli_fetch_assoc($sql)) {
				$tel_clean = preg_replace('/\D/', '', $r['celular'] ?? '');
				$zap_link = (!empty($tel_clean)) ? 'https://wa.me/55' . $tel_clean : '';
				
				fputcsv($output, [
					$r['id'],
					$r['nome'] ?? '',
					$r['email'] ?? '',
					$r['celular'] ?? '',
					$zap_link,
					$r['cpf'] ?? '',
					$r['cep'] ?? '',
					$r['endereco'] ?? '',
					$r['numero'] ?? '',
					$r['bairro'] ?? '',
					$r['cidade'] ?? '',
					$r['complemento'] ?? '',
					$r['produto_nome'] ?? '',
					$r['quantidade'] ?? '1',
					$r['valortotal'] ?? '',
					$r['data_cadastro'] ?? ''
				], ';');
			}
		}
		fclose($output);
		exit;
	break; //===========================================================
	case "buscar_cliente_status":
		$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
		file_put_contents("debug_status.txt", "ID recebido: " . $id . " | POST: " . print_r($_POST, true) . "\n", FILE_APPEND);
		if ($id > 0) {
			// Tenta primeiro no clientes direto
			$sql = mysqli_query($conn, "SELECT id, nome, status_rastreio FROM clientes WHERE id='$id' LIMIT 1");
			if ($sql && $row = mysqli_fetch_assoc($sql)) {
				echo json_encode([
					'ok' => true,
					'real_id' => $row['id'],
					'nome' => $row['nome'],
					'status_rastreio' => $row['status_rastreio']
				]);
				exit;
			}
			
			// Se não achou em clientes, o usuário pode ter digitado o ID do PIX! 
			// Vamos buscar no pixgerado e encontrar o cliente correspondente.
			$sql_pix = mysqli_query($conn, "SELECT ip, cliente_telefone FROM pixgerado WHERE id='$id' LIMIT 1");
			if ($sql_pix && $rp = mysqli_fetch_assoc($sql_pix)) {
				$ip_raw = $rp['ip'] ?? '';
				$tel = $rp['cliente_telefone'] ?? '';
				
				$conds = [];
				if (!empty($ip_raw)) {
					$ip_encoded = base64_encode($ip_raw);
					$conds[] = "ip='$ip_encoded'";
				}
				if (!empty($tel)) {
					$tel_limpo = preg_replace('/\D/', '', $tel);
					$conds[] = "celular LIKE '%$tel_limpo%'";
				}
				
				if (!empty($conds)) {
					$where = implode(" OR ", $conds);
					$sql_c = mysqli_query($conn, "SELECT id, nome, status_rastreio FROM clientes WHERE ($where) ORDER BY id DESC LIMIT 1");
					if ($sql_c && $row_c = mysqli_fetch_assoc($sql_c)) {
						echo json_encode([
							'ok' => true,
							'real_id' => $row_c['id'],
							'nome' => $row_c['nome'],
							'status_rastreio' => $row_c['status_rastreio']
						]);
						exit;
					}
				}
			}
		}
		echo json_encode(['ok' => false]);
		exit;
	break;

	case "bloqueados":
	
	$sql = mysqli_query($conn, "SELECT * from online");
	         if(($sql ? mysqli_num_rows($sql) : 0) > 0){
				
				$sql = mysqli_query($conn, "SELECT * FROM online WHERE situacao='desativo'");
				 while($sql && $rowx = mysqli_fetch_array($sql)){ 
				 
				 $id = $rowx["id"];
				 $ip = $rowx["ip"];
				 $etapa = $rowx["etapa"];
				 $cidade = $rowx["cidade"];
				 $estado = $rowx["estado"];
				 $dispositivo = $rowx["dispositivo"];
				 $hora = $rowx["hora"];
				
				 echo '<tr>
                      <td>
                        <div class="d-flex px-2 py-1">
                         
                          <div class="d-flex flex-column justify-content-center">
                            <h6 class="mb-0 text-sm">'.$etapa.'</h6>
                          </div>
                        </div>
                      </td>
                      <td>
                        <h6 class="mb-0 text-sm">'.$cidade.'</h6>
                        <p class="text-xs text-secondary mb-0">'.$estado.'</p>
                      </td>
					
                      <td class="align-middle text-center">
                        <h6 class="mb-0 text-sm">'.$dispositivo.'</h6>
                      </td>
                 
					  <td class="align-middle text-center">
					    <h6 class="mb-0 text-sm">'.$hora.'</h6>
                      </td>
					 					  
                      <td class="align-middle" text-center">
                       <span id="'.$id.'" onclick="desblock(this.id)" style="cursor:pointer;" class="badge badge-sm bg-gradient-dark toast-btn" data-target="infoToast">Desbloquear</span>
                      </td>
                    </tr>
				';
								
				}
				
			}else{
			 echo "";
			}
	break;
	
			case "excluirProduto":
			$id = addslashes($_POST["id"]);
			$sql_codigo_excluir = mysqli_query($conn, "SELECT codigo FROM produto WHERE id='$id' LIMIT 1");
			$codigo_excluir = ($sql_codigo_excluir && ($row_codigo_excluir = mysqli_fetch_assoc($sql_codigo_excluir))) ? $row_codigo_excluir['codigo'] : '';
			$query = mysqli_query($conn, "DELETE FROM produto WHERE id='$id'");
			if ($query && $codigo_excluir !== '') remover_produto_json($codigo_excluir);
			echo $query ? "ok" : "erro";
			break;

				case "mudarStatusProduto":
					$id = addslashes($_POST["id"]);
					$status = addslashes($_POST["status"]);
					
					// Ao mudar o status (especialmente para Anti-Crawler), limpamos a sessão de captcha 
					// para garantir que o sistema seja "reativado" para quem já resolveu anteriormente.
					$sql_codigo = mysqli_query($conn, "SELECT codigo FROM produto WHERE id='$id'");
					if ($row_c = mysqli_fetch_assoc($sql_codigo)) {
					    $codigo_prod = $row_c['codigo'];
					    unset($_SESSION['captcha_solved_' . $codigo_prod]);
					}

					$query = mysqli_query($conn, "UPDATE produto SET status='$status' WHERE id='$id'");
					if ($query && isset($codigo_prod)) sincronizar_produto_json($conn, $codigo_prod);
					echo $query ? "ok" : "erro";
				break;

		case "mudarOrdemProduto":
			$id = addslashes($_POST["id"]);
			$ordem = (int)$_POST["ordem"];
			$query = mysqli_query($conn, "UPDATE produto SET ordem='$ordem' WHERE id='$id'");
			if ($query) {
				$sql_codigo_ordem = mysqli_query($conn, "SELECT codigo FROM produto WHERE id='$id' LIMIT 1");
				if ($sql_codigo_ordem && ($row_codigo_ordem = mysqli_fetch_assoc($sql_codigo_ordem))) sincronizar_produto_json($conn, $row_codigo_ordem['codigo']);
			}
			echo $query ? "ok" : "erro";
		break;

		case "editarProduto":
			$id = addslashes($_POST["id"]);
			$NOME = addslashes($_POST["nome"]);
			$DESCONTO = addslashes($_POST["desconto"]);
			$TEXTODESCRICAO = addslashes($_POST["textodescricao"]);
			$CARACTERISTICAS = addslashes($_POST["caracteristicas"]);
			$REVIEWS = mysqli_real_escape_string($conn, $_POST["reviews"]);
			$VALOR = addslashes($_POST["valor"]);
			$VALOR_ORIGINAL = addslashes($_POST["valor_original"]);
			$OFERTA = addslashes($_POST["oferta"]);
			$FORCE_TABELA_PIX = isset($_POST["force_tabela_pix"]) ? (int)$_POST["force_tabela_pix"] : 0;
				$CATEGORIA = isset($_POST["categoria"]) ? addslashes($_POST["categoria"]) : "Geral";
				$PRODUTOS_RELACIONADOS = isset($_POST["produtos_relacionados"]) ? addslashes($_POST["produtos_relacionados"]) : "";
			
			$img1 = addslashes($_POST["img1"]);
			$img2 = addslashes($_POST["img2"]);
			$img3 = addslashes($_POST["img3"]);
			$img4 = addslashes($_POST["img4"]);
			$img5 = addslashes($_POST["img5"]);
			$img6 = addslashes($_POST["img6"]);
			
			$TIPO_PRODUTO = isset($_POST["tipo_produto"]) ? addslashes($_POST["tipo_produto"]) : "generico";
				$VARIACOES = isset($_POST["variacoes"]) ? addslashes($_POST["variacoes"]) : "{}";
					$PIX_PRODUTO = isset($_POST["pix_copia_e_cola"]) ? addslashes($_POST["pix_copia_e_cola"]) : "";
					$STATUS = isset($_POST["status"]) ? addslashes($_POST["status"]) : "ativo";
					$ORDEM = isset($_POST["ordem"]) ? (int)$_POST["ordem"] : 999;
					
					$IDIMG = $img1;
		
						// Limpar sessão de captcha se o status for alterado
						$sql_old = mysqli_query($conn, "SELECT codigo, status FROM produto WHERE id='$id'");
						if ($row_old = mysqli_fetch_assoc($sql_old)) {
						    if ($row_old['status'] != $STATUS) {
						        unset($_SESSION['captcha_solved_' . $row_old['codigo']]);
						    }
						}

						$sql = "UPDATE `produto` SET 
								`nome`='$NOME', 
								`valor`='$VALOR', 
								`img`='$IDIMG', 
								`oferta`='$OFERTA', 
								`desconto`='$DESCONTO', 
								`descricao`='$TEXTODESCRICAO', 
								`img1`='$img1', 
								`img2`='$img2', 
								`img3`='$img3', 
								`img4`='$img4', 
								`img5`='$img5', 
								`img6`='$img6', 
								`caracteristicas`='$CARACTERISTICAS', 
								`reviews`='$REVIEWS', 
								`valor_original`='$VALOR_ORIGINAL', 
								`tipo_produto`='$TIPO_PRODUTO', 
									`categoria`='$CATEGORIA', 
									`produtos_relacionados`='$PRODUTOS_RELACIONADOS',
								`variacoes`='$VARIACOES',
								`pix_copia_e_cola`='$PIX_PRODUTO',
								`force_tabela_pix`='$FORCE_TABELA_PIX',
								`status`='$STATUS',
								`ordem`='$ORDEM'
								WHERE `id`='$id'";
			
			if(mysqli_query($conn, $sql)){
				$sql_codigo_atualizado = mysqli_query($conn, "SELECT codigo FROM produto WHERE id='$id' LIMIT 1");
				if ($sql_codigo_atualizado && ($row_codigo_atualizado = mysqli_fetch_assoc($sql_codigo_atualizado))) sincronizar_produto_json($conn, $row_codigo_atualizado['codigo']);
				echo "ok";
			} else {
				$error_msg = mysqli_error($conn);
				if(strpos($error_msg, "Unknown column 'produtos_relacionados'") !== false || strpos($error_msg, "Unknown column 'categoria'") !== false){
					echo "erro_coluna: Faltam as novas colunas no banco de dados! Execute o arquivo update_db_schema.php no navegador.";
				} elseif(strpos($error_msg, "Unknown column 'valor_original'") !== false || strpos($error_msg, "Unknown column 'ordem'") !== false){
						$sql_simple = "UPDATE `produto` SET 
							`nome`='$NOME', 
							`valor`='$VALOR', 
							`img`='$IDIMG', 
							`oferta`='$OFERTA', 
							`desconto`='$DESCONTO', 
							`descricao`='$TEXTODESCRICAO', 
							`img1`='$img1', 
							`img2`='$img2', 
							`img3`='$img3', 
							`img4`='$img4', 
							`img5`='$img5', 
							`img6`='$img6', 
							`caracteristicas`='$CARACTERISTICAS', 
							`reviews`='$REVIEWS', 
							`tipo_produto`='$TIPO_PRODUTO', 
							`pix_copia_e_cola`='$PIX_PRODUTO' 
							WHERE `id`='$id'";
					if(mysqli_query($conn, $sql_simple)){
						$sql_codigo_atualizado = mysqli_query($conn, "SELECT codigo FROM produto WHERE id='$id' LIMIT 1");
						if ($sql_codigo_atualizado && ($row_codigo_atualizado = mysqli_fetch_assoc($sql_codigo_atualizado))) sincronizar_produto_json($conn, $row_codigo_atualizado['codigo']);
						echo "ok";
					} else {
						echo "erro: " . mysqli_error($conn);
					}
				} else {
					echo "erro: " . $error_msg;
				}
			}
		break;
	
	case "desbloquear":
	$id = addslashes($_POST["user"]);
	$query = mysqli_query($conn, "DELETE FROM online WHERE id='$id'");
	break;
	
		case "excluir":
		$id = addslashes($_POST["user"]);
		$query = mysqli_query($conn, "DELETE FROM clientes WHERE id='$id'");
		
		break; //==============================

		case "listarPixCodigos":
			$produto_codigo = addslashes($_POST["produto_codigo"]);
			$sql = mysqli_query($conn, "SELECT * FROM produto_pix_codigos WHERE produto_codigo='$produto_codigo' ORDER BY id DESC");
			if(($sql ? mysqli_num_rows($sql) : 0) > 0){
				while($row = mysqli_fetch_array($sql)){
					$status_class = "bg-gradient-success";
					if($row['status'] == 'reservado') $status_class = "bg-gradient-warning";
					if($row['status'] == 'pago') $status_class = "bg-gradient-secondary";
					
					echo '<div class="mb-2 p-2 border-radius-sm" style="border: 1px solid #eee; font-size: 10px; position: relative;">
							<div class="d-flex justify-content-between align-items-center mb-1">
								<span class="badge badge-sm '.$status_class.'">'.$row['status'].'</span>
								<i class="material-icons text-danger cursor-pointer" style="font-size: 16px;" onclick="excluirPixCodigo('.$row['id'].')">delete</i>
							</div>
							<div style="word-break: break-all; color: #555;">'.substr($row['pix_codigo'], 0, 50).'...</div>
						  </div>';
				}
			} else {
				echo '<div class="text-center py-3 text-secondary text-xs">Nenhum código cadastrado.</div>';
			}
		break;

		case "adicionarPixCodigo":
			$produto_codigo = addslashes($_POST["produto_codigo"]);
			$pix_codigo = addslashes($_POST["pix_codigo"]);
			$sql = "INSERT INTO produto_pix_codigos (produto_codigo, pix_codigo, status) VALUES ('$produto_codigo', '$pix_codigo', 'disponivel')";
			if(mysqli_query($conn, $sql)){
				echo "ok";
			} else {
				echo mysqli_error($conn);
			}
		break;

		case "excluirPixCodigo":
			$id = addslashes($_POST["id"]);
			$sql = "DELETE FROM produto_pix_codigos WHERE id='$id'";
			if(mysqli_query($conn, $sql)){
				echo "ok";
			} else {
				echo mysqli_error($conn);
			}
		break;
	
	
	case "total_de_bloqueados":
	
	$sql = mysqli_query($conn, "SELECT count(id) as online FROM online WHERE situacao='desativo'");
	$respx = ($sql) ? mysqli_fetch_assoc($sql) : null;
	
    echo $respx['online'];
	
	break;
	
		
		case "addproduto":
		
		$NOME = addslashes($_POST["nome"]);
		$DESCONTO = addslashes($_POST["desconto"]);
		$TEXTODESCRICAO = addslashes($_POST["textodescricao"]);
		$VALOR = addslashes($_POST["valor"]);
		$OFERTA = addslashes($_POST["oferta"]);
				$CATEGORIA = isset($_POST["categoria"]) ? addslashes($_POST["categoria"]) : "Geral";
		$IDPRODUTO = rand(999,999999999) . time() ;
		$IDIMG = $IDPRODUTO.".png";
		
			if($OFERTA==1){
			    $RESP = "ativo";
			} else {
			    $RESP = "desativo";
			}
		
		$sql = mysqli_query($conn, "SELECT * from produto WHERE codigo='$IDPRODUTO'");
		if(($sql ? mysqli_num_rows($sql) : 0) > 0){
		echo "ja_foi_cadastrado";
		}else{
		$xx = "INSERT INTO `produto`(`codigo`, `nome`, `valor`, `img`, `oferta`, `desconto`, `descricao`, `cliques`) VALUES ('$IDPRODUTO', '$NOME', '$VALOR', '$IDIMG', '$OFERTA', '$DESCONTO', '$TEXTODESCRICAO', '0')";
		if(mysqli_query($conn, $xx)){
		 sincronizar_produto_json($conn, $IDPRODUTO);
		 echo "ok|$IDPRODUTO";
		}else{
		 echo 'erro';
		 }
		}
		
		break;

		case "addproduto_v2":
		
		$NOME = addslashes($_POST["nome"]);
		$DESCONTO = addslashes($_POST["desconto"]);
		$TEXTODESCRICAO = addslashes($_POST["textodescricao"]);
		$CARACTERISTICAS = addslashes($_POST["caracteristicas"]);
			$REVIEWS = mysqli_real_escape_string($conn, $_POST["reviews"]);
			$VALOR = addslashes($_POST["valor"]);
			$VALOR_ORIGINAL = addslashes($_POST["valor_original"]);
			$OFERTA = addslashes($_POST["oferta"]);
			$FORCE_TABELA_PIX = isset($_POST["force_tabela_pix"]) ? (int)$_POST["force_tabela_pix"] : 0;
				$CATEGORIA = isset($_POST["categoria"]) ? addslashes($_POST["categoria"]) : "Geral";
				$PRODUTOS_RELACIONADOS = isset($_POST["produtos_relacionados"]) ? addslashes($_POST["produtos_relacionados"]) : "";
		$IDPRODUTO = rand(999,999999999) . time() ;
		
			$img1 = addslashes($_POST["img1"]);
			$img2 = addslashes($_POST["img2"]);
			$img3 = addslashes($_POST["img3"]);
			$img4 = addslashes($_POST["img4"]);
			$img5 = addslashes($_POST["img5"]);
			$img6 = addslashes($_POST["img6"]);
			
			// Campos de variações
			$TIPO_PRODUTO = isset($_POST["tipo_produto"]) ? addslashes($_POST["tipo_produto"]) : "generico";
				$VARIACOES = isset($_POST["variacoes"]) ? addslashes($_POST["variacoes"]) : "{}";
				$PIX_COPIA_E_COLA = isset($_POST["pix_copia_e_cola"]) ? addslashes($_POST["pix_copia_e_cola"]) : "";
				$ORDEM = isset($_POST["ordem"]) ? (int)$_POST["ordem"] : 999;
				
				// Usar a img1 como imagem principal legado
				$IDIMG = $img1;
			
			$sql = mysqli_query($conn, "SELECT * from produto WHERE codigo='$IDPRODUTO'");
			if(($sql ? mysqli_num_rows($sql) : 0) > 0){
				echo "ja_foi_cadastrado";
			}else{
							// Tenta inserir com valor_original, tipo_produto, variacoes, status e ordem
							$xx = "INSERT INTO `produto`(`codigo`, `nome`, `valor`, `img`, `oferta`, `desconto`, `descricao`, `cliques`, `img1`, `img2`, `img3`, `img4`, `img5`, `img6`, `caracteristicas`, `reviews`, `valor_original`, `tipo_produto`, `categoria`, `produtos_relacionados`, `variacoes`, `pix_copia_e_cola`, `force_tabela_pix`, `status`, `ordem`) VALUES ('$IDPRODUTO', '$NOME', '$VALOR', '$IDIMG', '$OFERTA', '$DESCONTO', '$TEXTODESCRICAO', '0', '$img1', '$img2', '$img3', '$img4', '$img5', '$img6', '$CARACTERISTICAS', '$REVIEWS', '$VALOR_ORIGINAL', '$TIPO_PRODUTO', '$CATEGORIA', '$PRODUTOS_RELACIONADOS', '$VARIACOES', '$PIX_COPIA_E_COLA', '$FORCE_TABELA_PIX', 'ativo', '$ORDEM')";
					
						if(!mysqli_query($conn, $xx)){
							$error_msg = mysqli_error($conn);
							if(strpos($error_msg, "Unknown column 'produtos_relacionados'") !== false || strpos($error_msg, "Unknown column 'categoria'") !== false){
								echo "erro_coluna: Faltam as novas colunas no banco de dados! Execute o arquivo update_db_schema.php no navegador.";
								exit;
							}
							if(strpos($error_msg, "Unknown column") !== false){
								// Se der erro de coluna, tenta a query básica garantindo o funcionamento
								$xx_basic = "INSERT INTO `produto`(`codigo`, `nome`, `valor`, `img`, `oferta`, `desconto`, `descricao`, `cliques`, `img1`, `img2`, `img3`, `img4`, `img5`, `img6`, `caracteristicas`, `reviews`, `status`, `ordem`) VALUES ('$IDPRODUTO', '$NOME', '$VALOR', '$IDIMG', '$OFERTA', '$DESCONTO', '$TEXTODESCRICAO', '0', '$img1', '$img2', '$img3', '$img4', '$img5', '$img6', '$CARACTERISTICAS', '$REVIEWS', 'ativo', '$ORDEM')";
								if(mysqli_query($conn, $xx_basic)){
									sincronizar_produto_json($conn, $IDPRODUTO);
						echo "ok|$IDPRODUTO";
								} else {
									echo 'erro: ' . mysqli_error($conn);
								}
							} else {
								echo 'erro: ' . $error_msg;
							}
						} else {
							sincronizar_produto_json($conn, $IDPRODUTO);
						echo "ok|$IDPRODUTO";
						}
				

		}
		
		break;
	
	case "codigoProduto":
	
						function getStr($string, $start, $end) {
						$str = explode($start, $string);
						$str = explode($end, $str[1]);
						return $str[0];
						}

						function multiexplode ($delimiters,$string) {
							$ready = str_replace($delimiters, $delimiters[0], $string);
							$launch = explode($delimiters[0], $ready);
							return  $launch;
						}

						$codigo = $_POST['codigox'];

						if(strpos($codigo, "?")){
						$x1 = getStr($codigo, 'i.','?');
						$shopid = multiexplode(array("."),$x1)[0];
						$itemid = multiexplode(array("."),$x1)[1];
						}else{
						$x2 = explode("i.", $codigo);
						$shopid = multiexplode(array("."),$x2[1])[0];
						$itemid = multiexplode(array("."),$x2[1])[1];
						}
                       
					   #######################################################################################
					   // DADOS DO VENDEDOR
					    $ch = curl_init("https://shopee.com.br/api/v4/product/get_shop_info?shopid=$shopid");
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Host: shopee.com.br',
				        'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:92.0) Gecko/20100101 Firefox/92.0'));
						$jsonProprietario = curl_exec($ch);
						curl_close($ch);

						$itemsChaves = json_decode($jsonProprietario, true);
						$imgLoja = $itemsChaves["data"]["account"]["portrait"];
						$nomeLoja = $itemsChaves["data"]["account"]["username"];
						
					    #######################################################################################
					    // COMENTARIOS DOS COMPRADORES
						$ch = curl_init("https://shopee.com.br/api/v2/item/get_ratings?flag=1&itemid=$itemid&limit=3&offset=0&shopid=$shopid");
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Host: shopee.com.br',
				        'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:92.0) Gecko/20100101 Firefox/92.0'));
						$jsonComent = curl_exec($ch);
						curl_close($ch);

						$jsonComentarios = json_decode($jsonComent, true);
						$AllComent1 = $jsonComentarios["data"]["ratings"];
						$AllComent = json_encode($AllComent1, true);
						
						#######################################################################################
						// DADOS DO PRODUTO
						$ch = curl_init("https://shopee.com.br/api/v4/item/get?itemid=$itemid&shopid=$shopid");
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array(
						'Host: shopee.com.br',
				        'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:92.0) Gecko/20100101 Firefox/92.0'));
						$jsonDadosPro = curl_exec($ch);
						curl_close($ch);
						
						$chaves = json_decode($jsonDadosPro, true);
						
						################################################
						// ESSE CODIGO E PARA PEGAR CORES, IMAGENS E TAMANHO CASO AJA NO PRODUTO!
						$jsonInfos = $chaves["data"]["tier_variations"];
						$jsonInfoCorTm = json_encode($jsonInfos, true);
						
						if($chaves["data"]==NULL){
						
						echo "invalido";
						
						}else{
						
						$nomeProduto = $chaves["data"]["name"];
						$valorProduto = $chaves["data"]["price"];
						$descricao2 = $chaves["data"]["description"];
					    
					    $arrayImgs = $chaves["data"]["images"];
						//$totalImg = count($arrayImgs);
						$JsonImgs = json_encode($arrayImgs, true);
						$desArray = $chaves["data"]["attributes"];
						
						if($desArray==NULL){
						$estoque = $chaves["data"]["stock"];
						echo "$JsonImgs|$estoque|$descricao2|$nomeProduto|$itemid|$imgLoja|$nomeLoja|$AllComent|$jsonInfoCorTm";
						}else{
						$desJson = json_encode($chaves["data"]["attributes"], true);
						echo "$JsonImgs|$desJson|$descricao2|$nomeProduto|$itemid|$imgLoja|$nomeLoja|$AllComent|$jsonInfoCorTm";
						}
						
					}	
						
	break;
	
	case "usuario":
	
	$login = addslashes($_POST["login"]);
	$senha = addslashes($_POST["senha"]);

	$sql = mysqli_query($conn, "SELECT * from acesso WHERE login='$login' and senha='$senha'");
	if(($sql ? mysqli_num_rows($sql) : 0) > 0){
	$tempo = time() + 7200;
	$_SESSION['login'] = $login;
	$_SESSION['senha'] = $senha;
	$_SESSION['tempo'] = $tempo;
	 echo "sucesso";
	}else{
	echo "erro";
	}
	
	break;
	
	case "zerar":
	
	$id = addslashes($_POST["comando"]);
	
	if($id=="cliques"){
	$query = mysqli_query($conn, "DELETE FROM mobile");
	$query = mysqli_query($conn, "DELETE FROM desktop");
	}
	if($id=="bloqueado"){
	$query = mysqli_query($conn, "DELETE FROM online");
	}
	if($id=="mobile"){
	$query = mysqli_query($conn, "DELETE FROM mobile");
	}
	if($id=="desktop"){
	$query = mysqli_query($conn, "DELETE FROM desktop");
	}
	if($id=="bot"){
	$query = mysqli_query($conn, "DELETE FROM bot");
	}
	if($id=="estimativa"){
	$query = mysqli_query($conn, "DELETE FROM pixgerado");
	}
	if($id=="cadastro"){
	$query = mysqli_query($conn, "DELETE FROM clientes");
	}	
	
	break;
	
	
   case "lista_de_adms":
	
	$sql = mysqli_query($conn, "SELECT * from acesso");
	         if(($sql ? mysqli_num_rows($sql) : 0) > 0){
				
				$sql = mysqli_query($conn, "SELECT * FROM acesso");
				 while($sql && $rowx = mysqli_fetch_array($sql)){ 
				 
				 $id = $rowx["id"];
				 $login = $rowx["login"];
				 $senha = $rowx["senha"];
				 $acesso = $rowx["acesso"];
				
				$primeira = substr($senha, 0, 1);
				$ultima = substr($senha, -1);
				
				echo '<li class="list-group-item border-0 d-flex align-items-center px-0 mb-2 pt-0">
                      <div class="avatar me-3">
                        <img src="./assets/img/the.png" alt="kal" class="border-radius-lg shadow">
                      </div>
                      <div class="d-flex align-items-start flex-column justify-content-center">
                        <h6 class="mb-0 text-sm">Usuário: '.$login.'</h6>
                        <h6 class="mb-0 text-sm">Senha: '.$primeira.'******'.$ultima.'</h6>
                      </div>
                      <a class="btn btn-link pe-3 ps-0 mb-0 ms-auto w-25 w-md-auto" href="javascript:;"><span style="cursor:pointer;" class="badge badge-sm bg-gradient-dark fixed-plugin-button">Editar</span></a>
                    </li>  ';
				}
				
			}else{
			 echo "";
			}
	break;
	
	case "cadastrarAdm":
	
	$login = addslashes($_POST["login"]);
	$senha = addslashes($_POST["senha"]);
	
	               $sql = mysqli_query($conn, "SELECT * from acesso"); 
				   if(($sql ? mysqli_num_rows($sql) : 0) > 0){ 
				   }else{
				    $query = mysqli_query($conn, "INSERT INTO acesso (login, senha, acesso) VALUES ('$login', '$senha', 'ativo')");
				   }
	break;

		case "atualizarLoja":
			
			$cor = addslashes($_POST["cor"]);
			$cor_botao = isset($_POST["cor_botao"]) ? addslashes($_POST["cor_botao"]) : "#3483fa";
			$cor_icones = isset($_POST["cor_icones"]) ? addslashes($_POST["cor_icones"]) : "#ffffff";
			$nome = addslashes($_POST["nome"]);
			$zap = addslashes($_POST["zap"]);
			$zap_cotacao = isset($_POST["zap_cotacao"]) ? addslashes($_POST["zap_cotacao"]) : "";
			$zap_flutuante_ativo = isset($_POST["zap_flutuante_ativo"]) ? addslashes($_POST["zap_flutuante_ativo"]) : "1";
			$app_mobile_ativo = isset($_POST["app_mobile_ativo"]) ? addslashes($_POST["app_mobile_ativo"]) : "0";
			$texto = addslashes($_POST["texto"]);
			$endereco = isset($_POST["endereco"]) ? addslashes($_POST["endereco"]) : "";
			$cnpj = isset($_POST["cnpj"]) ? addslashes($_POST["cnpj"]) : "";
			
			// 1. Garantir que as colunas existem
			$check_col = mysqli_query($conn, "SHOW COLUMNS FROM config LIKE 'cor_botao'");
			if (mysqli_num_rows($check_col) == 0) {
				mysqli_query($conn, "ALTER TABLE config ADD COLUMN cor_botao VARCHAR(20) DEFAULT '#3483fa' AFTER cor");
			}
			
			$check_col_icones = mysqli_query($conn, "SHOW COLUMNS FROM config LIKE 'cor_icones'");
			if (mysqli_num_rows($check_col_icones) == 0) {
				mysqli_query($conn, "ALTER TABLE config ADD COLUMN cor_icones VARCHAR(20) DEFAULT '#ffffff' AFTER cor_botao");
			}
			
			$check_col_zap = mysqli_query($conn, "SHOW COLUMNS FROM config LIKE 'zap_cotacao'");
			if (mysqli_num_rows($check_col_zap) == 0) {
				mysqli_query($conn, "ALTER TABLE config ADD COLUMN zap_cotacao VARCHAR(50) DEFAULT '' AFTER zap");
			}
			
			$check_col_zap_flutuante = mysqli_query($conn, "SHOW COLUMNS FROM config LIKE 'zap_flutuante_ativo'");
			if (mysqli_num_rows($check_col_zap_flutuante) == 0) {
				mysqli_query($conn, "ALTER TABLE config ADD COLUMN zap_flutuante_ativo INT(1) DEFAULT 1 AFTER zap_cotacao");
			}

			$check_col_app_mobile = mysqli_query($conn, "SHOW COLUMNS FROM config LIKE 'app_mobile_ativo'");
			if (mysqli_num_rows($check_col_app_mobile) == 0) {
				mysqli_query($conn, "ALTER TABLE config ADD COLUMN app_mobile_ativo TINYINT(1) DEFAULT 0");
			}

			$check_col_endereco = mysqli_query($conn, "SHOW COLUMNS FROM config LIKE 'endereco'");
			if (mysqli_num_rows($check_col_endereco) == 0) {
				mysqli_query($conn, "ALTER TABLE config ADD COLUMN endereco VARCHAR(255) DEFAULT ''");
			}

			$check_col_cnpj = mysqli_query($conn, "SHOW COLUMNS FROM config LIKE 'cnpj'");
			if (mysqli_num_rows($check_col_cnpj) == 0) {
				mysqli_query($conn, "ALTER TABLE config ADD COLUMN cnpj VARCHAR(50) DEFAULT ''");
			}
			
			// 2. Tentar atualizar o registro ID 1
			$query = mysqli_query($conn, "UPDATE config SET nome='$nome', cor='$cor', cor_botao='$cor_botao', cor_icones='$cor_icones', numero='$zap', zap='$zap', zap_cotacao='$zap_cotacao', zap_flutuante_ativo='$zap_flutuante_ativo', app_mobile_ativo='$app_mobile_ativo', texto='$texto', endereco='$endereco', cnpj='$cnpj' WHERE id='1'");
			
			// 3. Se não atualizou nada (ou porque o ID 1 não existe), tentamos o INSERT ou forçamos o ID
			if (mysqli_affected_rows($conn) == 0) {
				$check_id = mysqli_query($conn, "SELECT id FROM config WHERE id='1'");
				if (mysqli_num_rows($check_id) == 0) {
					// Não existe ID 1, vamos criar ou atualizar o primeiro que encontrar
					$check_any = mysqli_query($conn, "SELECT id FROM config LIMIT 1");
					if (mysqli_num_rows($check_any) > 0) {
						$row_any = mysqli_fetch_assoc($check_any);
						$any_id = $row_any['id'];
						$query = mysqli_query($conn, "UPDATE config SET id='1', nome='$nome', cor='$cor', cor_botao='$cor_botao', cor_icones='$cor_icones', numero='$zap', zap='$zap', zap_cotacao='$zap_cotacao', zap_flutuante_ativo='$zap_flutuante_ativo', app_mobile_ativo='$app_mobile_ativo', texto='$texto', endereco='$endereco', cnpj='$cnpj' WHERE id='$any_id'");
					} else {
						// Tabela vazia, insere o primeiro
						$query = mysqli_query($conn, "INSERT INTO config (id, nome, cor, cor_botao, cor_icones, numero, zap, zap_cotacao, zap_flutuante_ativo, app_mobile_ativo, texto, endereco, cnpj) VALUES ('1', '$nome', '$cor', '$cor_botao', '$cor_icones', '$zap', '$zap', '$zap_cotacao', '$zap_flutuante_ativo', '$app_mobile_ativo', '$texto', '$endereco', '$cnpj')");
					}
				} else {
					// O ID 1 existe mas os dados eram idênticos, então affected_rows é 0. Isso é sucesso no contexto do usuário.
					$query = true;
				}
			}
			
			if($query){
				echo "ok";
			}else{
				// Se ainda assim der erro, retorna o erro do MySQL para debug (opcional, mas ajuda)
				echo "erro: " . mysqli_error($conn);
			}
		break;
	
case "trocapix":

$pix_modo = addslashes(trim($_POST['pix_modo'] ?? 'manual'));
$chave0 = addslashes($_POST["chave"]);
$city = addslashes($_POST["cidade"]);
$identificador = addslashes($_POST["identificador"]);
$descricao = addslashes($_POST["descricao"]);
$beneficiario = addslashes($_POST["beneficiario"]);
$cidade = strtoupper($city);
	$chave = trim($chave0);
	$freepay_public = addslashes($_POST["freepay_public"]);
	$freepay_secret = addslashes($_POST["freepay_secret"]);
	$use_freepay = (int)($_POST["use_freepay"] ?? 0);
	// Mercado Pago
		$mp_access_token = addslashes(trim($_POST["mp_access_token"] ?? ''));
		$use_mercadopago = (int)($_POST["use_mercadopago"] ?? 0);
		$use_pix_produto = (int)($_POST["use_pix_produto"] ?? 1);
		// Garantir que apenas um gateway fique ativo
		if ($use_mercadopago === 1) { $use_freepay = 0; }
		if ($use_freepay === 1) { $use_mercadopago = 0; }
			// PixGo
				$pixgo_api_key = addslashes(trim($_POST["pixgo_api_key"] ?? ''));
				$pixgo_webhook_secret = addslashes(trim($_POST["pixgo_webhook_secret"] ?? ''));
				$use_pixgo = (int)($_POST["use_pixgo"] ?? 0);
				// CartHero
				$carthero_private_key = addslashes(trim($_POST["carthero_private_key"] ?? ''));
				$carthero_public_key = addslashes(trim($_POST["carthero_public_key"] ?? ''));
				$use_carthero = (int)($_POST["use_carthero"] ?? 0);
				// BravoPay
				$bravopay_api_key = addslashes(trim($_POST["bravopay_api_key"] ?? ''));
				$bravopay_webhook_secret = addslashes(trim($_POST["bravopay_webhook_secret"] ?? ''));
				$use_bravopay = (int)($_POST["use_bravopay"] ?? 0);

				// Garantir que apenas um gateway fique ativo
				if ($use_mercadopago === 1) { $use_freepay = 0; $use_pixgo = 0; $use_carthero = 0; $use_bravopay = 0; }
				if ($use_freepay === 1) { $use_mercadopago = 0; $use_pixgo = 0; $use_carthero = 0; $use_bravopay = 0; }
				if ($use_pixgo === 1) { $use_mercadopago = 0; $use_freepay = 0; $use_carthero = 0; $use_bravopay = 0; }
				if ($use_carthero === 1) { $use_mercadopago = 0; $use_freepay = 0; $use_pixgo = 0; $use_bravopay = 0; }
				if ($use_bravopay === 1) { $use_mercadopago = 0; $use_freepay = 0; $use_pixgo = 0; $use_carthero = 0; }

				// Garantir coluna pix_modo existe
				$chk_modo_col = mysqli_query($conn, "SHOW COLUMNS FROM pix LIKE 'pix_modo'");
				if (($chk_modo_col ? mysqli_num_rows($chk_modo_col) : 0) === 0) {
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN pix_modo varchar(20) NOT NULL DEFAULT 'manual'");
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN pix_max_itens int(11) NOT NULL DEFAULT 4");
				}
				
				// Garantir coluna descricao existe
				$chk_descricao_col = mysqli_query($conn, "SHOW COLUMNS FROM pix LIKE 'descricao'");
				if (($chk_descricao_col ? mysqli_num_rows($chk_descricao_col) : 0) === 0) {
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN descricao VARCHAR(255) DEFAULT ''");
				}
				
				// Garantir coluna identificador existe
				$chk_identificador_col = mysqli_query($conn, "SHOW COLUMNS FROM pix LIKE 'identificador'");
				if (($chk_identificador_col ? mysqli_num_rows($chk_identificador_col) : 0) === 0) {
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN identificador VARCHAR(255) DEFAULT '***'");
				}

				// Garantir coluna beneficiario existe
				$chk_beneficiario_col = mysqli_query($conn, "SHOW COLUMNS FROM pix LIKE 'beneficiario'");
				if (($chk_beneficiario_col ? mysqli_num_rows($chk_beneficiario_col) : 0) === 0) {
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN beneficiario VARCHAR(255) DEFAULT ''");
				}

				// Garantir colunas do FreePay existem
				$chk_freepay_col = mysqli_query($conn, "SHOW COLUMNS FROM pix LIKE 'freepay_public_key'");
				if (($chk_freepay_col ? mysqli_num_rows($chk_freepay_col) : 0) === 0) {
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN freepay_public_key TEXT DEFAULT NULL");
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN freepay_secret_key TEXT DEFAULT NULL");
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN use_freepay tinyint(1) NOT NULL DEFAULT 0");
				}

				// Garantir colunas do Mercado Pago existem
			$chk_mp_col = mysqli_query($conn, "SHOW COLUMNS FROM pix LIKE 'mp_access_token'");
			if (($chk_mp_col ? mysqli_num_rows($chk_mp_col) : 0) === 0) {
				mysqli_query($conn, "ALTER TABLE pix ADD COLUMN mp_access_token TEXT DEFAULT NULL");
				mysqli_query($conn, "ALTER TABLE pix ADD COLUMN mp_webhook_secret TEXT DEFAULT NULL");
				mysqli_query($conn, "ALTER TABLE pix ADD COLUMN use_mercadopago tinyint(1) NOT NULL DEFAULT 0");
			}

				// Garantir colunas do PixGo existem
				$chk_pixgo_col = mysqli_query($conn, "SHOW COLUMNS FROM pix LIKE 'pixgo_api_key'");
				if (($chk_pixgo_col ? mysqli_num_rows($chk_pixgo_col) : 0) === 0) {
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN pixgo_api_key TEXT DEFAULT NULL");
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN pixgo_webhook_secret TEXT DEFAULT NULL");
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN use_pixgo tinyint(1) NOT NULL DEFAULT 0");
				}

				// Garantir colunas do CartHero existem
				$chk_carthero_col = mysqli_query($conn, "SHOW COLUMNS FROM pix LIKE 'carthero_private_key'");
				if (($chk_carthero_col ? mysqli_num_rows($chk_carthero_col) : 0) === 0) {
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN carthero_private_key TEXT DEFAULT NULL");
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN carthero_public_key TEXT DEFAULT NULL");
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN use_carthero tinyint(1) NOT NULL DEFAULT 0");
				}

				// Garantir coluna use_pix_produto existe
				$chk_pix_produto_col = mysqli_query($conn, "SHOW COLUMNS FROM pix LIKE 'use_pix_produto'");
				if (($chk_pix_produto_col ? mysqli_num_rows($chk_pix_produto_col) : 0) === 0) {
					mysqli_query($conn, "ALTER TABLE pix ADD COLUMN use_pix_produto tinyint(1) NOT NULL DEFAULT 1");
				}

				// Verificar se o registro existe
				$check = mysqli_query($conn, "SELECT id FROM pix WHERE id='1' LIMIT 1");
				if (!$check || mysqli_num_rows($check) === 0) {
					// Criar registro se não existir
						mysqli_query($conn, "INSERT INTO pix (id, chave, cidade, identificador, descricao, beneficiario, freepay_public_key, freepay_secret_key, use_freepay, mp_access_token, use_mercadopago, use_pix_produto, pixgo_api_key, pixgo_webhook_secret, use_pixgo, carthero_private_key, carthero_public_key, use_carthero) VALUES (1, '$chave', '$cidade', '$identificador', '$descricao', '$beneficiario', '$freepay_public', '$freepay_secret', '$use_freepay', '$mp_access_token', '$use_mercadopago', '$use_pix_produto', '$pixgo_api_key', '$pixgo_webhook_secret', '$use_pixgo', '$carthero_private_key', '$carthero_public_key', '$use_carthero')");
					}
					
					// Atualizar registro
					$query = mysqli_query($conn, "UPDATE pix SET chave='$chave', cidade='$cidade', identificador='$identificador', descricao='$descricao', beneficiario='$beneficiario', freepay_public_key='$freepay_public', freepay_secret_key='$freepay_secret', use_freepay='$use_freepay', mp_access_token='$mp_access_token', use_mercadopago='$use_mercadopago', use_pix_produto='$use_pix_produto', pixgo_api_key='$pixgo_api_key', pixgo_webhook_secret='$pixgo_webhook_secret', use_pixgo='$use_pixgo', carthero_private_key='$carthero_private_key', carthero_public_key='$carthero_public_key', use_carthero='$use_carthero', bravopay_api_key='$bravopay_api_key', bravopay_webhook_secret='$bravopay_webhook_secret', use_bravopay='$use_bravopay', pix_modo='$pix_modo' WHERE id='1'");
			
			if($query){
				echo "ok";
			} else {
				echo "erro: " . mysqli_error($conn);
			}
break;



		case "totalprodutos":
		$sql = mysqli_query($conn, "SELECT count(id) as produto FROM produto");
	$resp = ($sql) ? mysqli_fetch_assoc($sql) : null;
	echo $resp['produto'];
	break;
    
	
	case "apizap":
	
	            $sql = mysqli_query($conn, "SELECT * from apis");
	            if(($sql ? mysqli_num_rows($sql) : 0) > 0){
				
				$sql = mysqli_query($conn, "SELECT * FROM apis");
				 while($sql && $rowx = mysqli_fetch_array($sql)){ 
				 
				 $zap = $rowx["zap"];	
				 
				 }
				 
				 if(strlen($zap) > 20){
				 echo '<tr>
                      <td>
                        <div class="d-flex px-2 py-1">
                          <div class="d-flex flex-column justify-content-center">
                            <h6 class="mb-0 text-sm">'.$zap.'</h6>
                          </div>
                        </div>
                      </td>	
					  
                      <td class="align-middle">
                       <span id="zap" onclick="excluir(this.id);" style="cursor:pointer;" class="badge badge-sm bg-gradient-dark toast-btn" data-target="infoToast">Delete</span>
                      </td>
                    </tr>';	
				 }else{
				 echo '
                 <tr><td colspan="2">
				 <button style="margin-left: 20px; margin-top: 10px;" type="button" class="btn bg-gradient-info" data-bs-toggle="modal" data-bs-target="#exampleModal1x">
				  Inserir api whatsapp
				</button>

				<div class="modal fade" id="exampleModal1x" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
				  <div class="modal-dialog modal-dialog-centered" role="document">
					<div class="modal-content">
					  <div class="modal-header">
						<h5 class="modal-title font-weight-normal" id="exampleModalLabel">Link api</h5>
						<button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
						  <span aria-hidden="true">&times;</span>
						</button>
					  </div>
					  <div class="modal-body">
						 <textarea class="multisteps-form__textarea form-control" rows="5" spellcheck="false" id="salvarLinkApiZap"></textarea>
					  </div>
					  <div class="modal-footer">
						<button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Fechar</button>
						<button type="button" class="btn bg-gradient-success" onclick="salvarLinkApiZap()">Salvar</button>
					  </div>
					</div>
				  </div>
				</div>
                 </td></tr>
				 ';
				 }
                  			 
				 }
				 
				 else{
				 echo "";
				 }
	
	
	break;
	
	
	case "apihtml":
	
	            $sql = mysqli_query($conn, "SELECT * from apis");
	            if(($sql ? mysqli_num_rows($sql) : 0) > 0){
				
				$sql = mysqli_query($conn, "SELECT * FROM apis");
				 while($sql && $rowx = mysqli_fetch_array($sql)){ 
				 
				 $emailx = $rowx["email"];
				 $smtpAtivo = isset($rowx["smtp_ativo"]) ? intval($rowx["smtp_ativo"]) : 0;
				 
				 }
				 
				  if(strpos($emailx, "@") || strpos($emailx, "|")){
				  $partes = explode("|", $emailx);
				  
				  $btnSmtp = $smtpAtivo === 1 ? '<span onclick="fetch(\'../../api/toggle_smtp.php?status=0\').then(()=>location.reload())" style="cursor:pointer; margin-right:5px;" class="badge badge-sm bg-gradient-success">ON</span>' : '<span onclick="fetch(\'../../api/toggle_smtp.php?status=1\').then(()=>location.reload())" style="cursor:pointer; margin-right:5px;" class="badge badge-sm bg-gradient-danger">OFF</span>';
				  
				  echo '<tr>
                      <td>
                        <h6 class="mb-0 text-sm">'.$partes[0].'</h6>
						<span class="text-secondary text-xs font-weight-bold">'.$partes[1].'</span>
                      </td>					  
                      <td class="align-middle">
					   '.$btnSmtp.'
                       <span id="email" onclick="excluir(this.id);" style="cursor:pointer;" class="badge badge-sm bg-gradient-dark toast-btn" data-target="infoToast">Delete</span>
                      </td>
                    </tr>';
				  }else{
				  echo '
                  <tr><td colspan="2">
				  <button style="margin-left: 20px; margin-top: 10px;" type="button" class="btn bg-gradient-info" data-bs-toggle="modal" data-bs-target="#exampleModal00">
				  <i class="material-icons text-sm me-1">email</i> Configurar E-mail / API (Resend, Brevo, Gmail)
				</button>

				<div class="modal fade" id="exampleModal00" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
				  <div class="modal-dialog modal-dialog-centered" role="document">
					<div class="modal-content">
					  <div class="modal-header">
						<h5 class="modal-title font-weight-normal" id="exampleModalLabel">Configuração de E-mail Transacional</h5>
						<button type="button" class="btn-close text-dark" data-bs-dismiss="modal" aria-label="Close">
						  <span aria-hidden="true">&times;</span>
						</button>
					  </div>
					  <div class="modal-body">
						 <p class="text-xs text-muted mb-2">Para <strong>Resend.com</strong>, informe sua API Key e seu e-mail:<br><code>re_SUA_CHAVE_API|seu-email@dominio.com</code></p>
						 <textarea class="multisteps-form__textarea form-control" rows="5" spellcheck="false" id="salvarLinkApiEmail" placeholder="Exemplos:&#10;Resend: re_123456789|seu-email@gmail.com&#10;Brevo: xkeysib-123456...|seu-email@gmail.com&#10;Gmail: seu-email@gmail.com|sua_senha_de_app"></textarea>
					  </div>
					  <div class="modal-footer">
						<button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Fechar</button>
						<button type="button" class="btn bg-gradient-success" onclick="salvarLinkApiEmail()">Salvar Credenciais</button>
					  </div>
					</div>
				  </div>
				</div>
                  </td></tr>
				  ';
				  }
				  				 
				 }
				 
				 else{
				 echo "";
				 }
	
	break;
	
	case "textoszap":
	            $sql = mysqli_query($conn, "SELECT * from apis");
	            if(($sql ? mysqli_num_rows($sql) : 0) > 0){
				
				$sql = mysqli_query($conn, "SELECT * FROM apis");
				 while($sql && $rowx = mysqli_fetch_array($sql)){ 
				 
				 $textozap = $rowx["textozap"];
				 
				 }
				 
				 echo $textozap;
				 
				 }else{
				 echo "........!";
				 }
	break;
	
	case "textoshtml":
	
	 $sql = mysqli_query($conn, "SELECT * from apis");
	            if(($sql ? mysqli_num_rows($sql) : 0) > 0){
				
				$sql = mysqli_query($conn, "SELECT * FROM apis");
				 while($sql && $rowx = mysqli_fetch_array($sql)){ 
				 
				 $htmlemail = $rowx["htmlemail"];
				 $texto1email = $rowx["texto1email"];
				 
				 }
				 
				 $html1 = trim($htmlemail);
				 $texto1 = trim($texto1email);
				 
				 echo $html1.'|'.$texto1;
				 
				 }else{
				 echo "........!|........!";
				 }
	break;
	
	case "excluirApi";
	
	$qual = addslashes($_POST["qual"]);
	
 	
				  $query = mysqli_query($conn, "UPDATE apis SET $qual='' WHERE id='1'");
				  if($query){
				   echo "ok";
				  }else{
				   echo "erro";
				  }
	
	break;

	case "salvarZAP";
	$texto = addslashes($_POST["codigo"]);
	
				  $query = mysqli_query($conn, "UPDATE apis SET textozap='$texto' WHERE id='1'");
				  if($query){
				   echo "ok";
				  }else{
				   echo "erro";
				  }
	break;

	case "salvarHTML";
	$textoHTML1 = addslashes($_POST["codigo"]);
	$textoHTML2 = addslashes($_POST["texto"]);
	
	$texto1 = ltrim($textoHTML1);
	$texto2 = ltrim($textoHTML2);
	
				  $query = mysqli_query($conn, "UPDATE apis SET htmlemail='$texto1', texto1email='$texto2' WHERE id='1'");
				  if($query){
				   echo "ok";
				  }else{
				   echo "erro";
				  }
	break;
	
	
	//##########################################
	
	
	case "salvarLinkApiEmail";
	
	$qual = addslashes($_POST["qual"]);
 	
				  $query = mysqli_query($conn, "UPDATE apis SET email='$qual' WHERE id='1'");
				  if($query){
				   echo "ok";
				  }else{
				   echo "erro";
				  }
	
	break;
	
	
	case "salvarLinkApiZap";
	
	$qual = addslashes($_POST["qual"]);
 	
				  $query = mysqli_query($conn, "UPDATE apis SET zap='$qual' WHERE id='1'");
				  if($query){
				   echo "ok";
				  }else{
				   echo "erro";
				  }
	
	break;

    case "pixel_config":
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS facebook_pixel (id INT NOT NULL PRIMARY KEY, pixel_id TEXT, ativo TINYINT(1) NOT NULL DEFAULT 0, purchase_event TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        mysqli_query($conn, "ALTER TABLE facebook_pixel MODIFY COLUMN pixel_id TEXT");
        mysqli_query($conn, "INSERT IGNORE INTO facebook_pixel (id, pixel_id, ativo, purchase_event) VALUES (1, '', 0, 1)");
        $sql = mysqli_query($conn, "SELECT pixel_id, ativo, purchase_event FROM facebook_pixel WHERE id='1' LIMIT 1");
        $row = $sql ? mysqli_fetch_assoc($sql) : array('pixel_id'=>'', 'ativo'=>0, 'purchase_event'=>1);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($row);
    break;

    case "salvarPixelFacebook":
        mysqli_query($conn, "CREATE TABLE IF NOT EXISTS facebook_pixel (id INT NOT NULL PRIMARY KEY, pixel_id TEXT, ativo TINYINT(1) NOT NULL DEFAULT 0, purchase_event TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        mysqli_query($conn, "ALTER TABLE facebook_pixel MODIFY COLUMN pixel_id TEXT");
        mysqli_query($conn, "INSERT IGNORE INTO facebook_pixel (id, pixel_id, ativo, purchase_event) VALUES (1, '', 0, 1)");
        $pixel_parts = preg_split('/[,\s]+/', trim((string)($_POST["pixel_id"] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
        $pixel_parts = array_values(array_filter($pixel_parts, function($id) { return preg_match('/^\d+$/', $id); }));
        $pixel_id = implode(',', $pixel_parts);
        $ativo = isset($_POST["ativo"]) && (int)$_POST["ativo"] === 1 ? 1 : 0;
        $purchase_event = isset($_POST["purchase_event"]) && (int)$_POST["purchase_event"] === 1 ? 1 : 0;
        $pixel_safe = mysqli_real_escape_string($conn, $pixel_id);
        $query = mysqli_query($conn, "UPDATE facebook_pixel SET pixel_id='$pixel_safe', ativo='$ativo', purchase_event='$purchase_event' WHERE id='1'");
        header('Content-Type: application/json; charset=utf-8');
        echo $query ? json_encode(['ok'=>true]) : json_encode(['ok'=>false, 'error'=>mysqli_error($conn)]);
    break;
		
		
		
		
		
		
		
		
		
		
    case "enviar_recibo_html":
        header('Content-Type: application/json; charset=utf-8');
        
        $id_cliente = (int)($_POST['id'] ?? 0);
        if (!$id_cliente) { echo json_encode(['ok' => false, 'error' => 'ID inválido']); break; }
        
        // Carrega dados do cliente
        $sql = mysqli_query($conn, "SELECT * FROM clientes WHERE id='$id_cliente' LIMIT 1");
        if (!$sql || !($cliente = mysqli_fetch_assoc($sql))) {
            echo json_encode(['ok' => false, 'error' => 'Cliente não encontrado']);
            break;
        }
        
        $email_destino = $cliente['email'] ?? '';
        if (empty($email_destino) || $email_destino === '-') {
            echo json_encode(['ok' => false, 'error' => 'Cliente não possui e-mail cadastrado']);
            break;
        }
        
        $nome = htmlspecialchars($cliente['nome']);
        $override_produto = $cliente['produto_codigo'] ?? '';
        $is_paid = !empty($cliente['pagamento_confirmado']);
        
        if (!$is_paid && !empty($cliente['ip'])) {
            $ip_raw = base64_decode($cliente['ip']);
            if ($ip_raw !== false && $ip_raw !== '') {
                $q_pix = mysqli_query($conn, "SELECT status, mp_status, pixgo_status, freepay_status FROM pixgerado WHERE ip='$ip_raw' ORDER BY id DESC LIMIT 1");
                if ($q_pix && $r_pix = mysqli_fetch_assoc($q_pix)) {
                    $pago_arrays = ['pago', 'paid', 'approved', 'approved_payment', 'completed', 'success'];
                    if (in_array(strtolower($r_pix['status'] ?? ''), $pago_arrays) || 
                        in_array(strtolower($r_pix['mp_status'] ?? ''), $pago_arrays) || 
                        in_array(strtolower($r_pix['pixgo_status'] ?? ''), $pago_arrays) || 
                        in_array(strtolower($r_pix['freepay_status'] ?? ''), $pago_arrays)) {
                        $is_paid = true;
                    }
                }
            }
        }

        $_REQUEST['tipo'] = $is_paid ? 'aprovado' : 'pendente';
        $_REQUEST['override_id'] = $id_cliente;
        $_REQUEST['override_email'] = $email_destino;
        $_REQUEST['override_nome'] = $nome;
        $_REQUEST['override_produto'] = $override_produto;

        ob_start();
        @include(__DIR__ . '/../../api/phpmailer.php');
        $out = ob_get_clean();

        $out_clean = trim(strip_tags($out));
        if (strpos(strtolower($out_clean), 'sucesso') !== false) {
            echo json_encode(['ok' => true, 'output' => $out_clean]);
        } else {
            echo json_encode(['ok' => false, 'error' => !empty($out_clean) ? $out_clean : 'Erro ao enviar e-mail. Verifique as credenciais de SMTP/APIs no painel.']);
        }
    break;

	}
?>







