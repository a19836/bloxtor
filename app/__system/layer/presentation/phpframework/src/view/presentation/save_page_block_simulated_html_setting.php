<?php
$obj = array(
	"status" => isset($status) ? $status : null,
	"old_block_code_id" => isset($old_block_code_id) ? $old_block_code_id : null,
	"new_block_code_id" => isset($new_block_code_id) ? $new_block_code_id : null,
	"old_block_code_time" => isset($old_block_code_time) ? $old_block_code_time : null,
	"new_block_code_time" => isset($new_block_code_time) ? $new_block_code_time : null
);

$EVC->setTemplate("json");
?>
