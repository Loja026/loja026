import os

def update_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Colors
    content = content.replace("background-color: #ff9800;", "background-color: #00a650;")
    content = content.replace("border-left: 4px solid #ff9800;", "border-left: 4px solid #00a650;")
    content = content.replace("rgba(255, 152, 0, 0.4)", "rgba(52, 131, 250, 0.4)")
    content = content.replace("background-color: #ff9800; color: white; padding: 16px 30px;", "background-color: #3483fa; color: white; padding: 16px 30px;")

    # Text
    content = content.replace(
        "Como o PIX Copia e Cola tem um tempo limite e pode ter expirado, <strong>clique no botão abaixo para gerar um novo PIX com seus dados já preenchidos e garantir sua reserva.</strong>",
        "Como o PIX Copia e Cola tem um tempo limite e pode ter expirado, <strong>clique no botão abaixo para gerar um novo PIX e finalize seu pagamento e garantir sua reserva.</strong>"
    )

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

update_file(r'c:\Users\studi\Downloads\mercado rm lja\api\phpmailer.php')
update_file(r'c:\Users\studi\Downloads\mercado rm lja\@SERVIDOR\api_adm\index.php')
