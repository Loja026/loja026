<?php
require_once('api/db.php');
$flag_file = sys_get_temp_dir() . '/loja_schema_' . md5(realpath(__DIR__ . '/api/schema_check.php')) . '.flag';
if (file_exists($flag_file)) {
    unlink($flag_file);
    echo "Flag removida. ";
}
require_once('api/schema_check.php');
echo "Schema verificado e atualizado.";
?>
