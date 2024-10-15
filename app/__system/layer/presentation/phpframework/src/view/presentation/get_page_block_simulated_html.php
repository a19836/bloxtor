<?php
$obj = array(
	"html" => isset($html) ? $html : null,
	"editable_settings" => isset($editable_settings) ? $editable_settings : null,
	"block_code_id" => isset($block_code_id) ? $block_code_id : null,
	"block_code_time" => isset($block_code_time) ? $block_code_time : null
);

$EVC->setTemplate("json");
?>
