<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

$obj = array(
	"status" => isset($status) ? $status : null,
	"old_block_code_id" => isset($old_block_code_id) ? $old_block_code_id : null,
	"new_block_code_id" => isset($new_block_code_id) ? $new_block_code_id : null,
	"old_block_code_time" => isset($old_block_code_time) ? $old_block_code_time : null,
	"new_block_code_time" => isset($new_block_code_time) ? $new_block_code_time : null
);

$EVC->setTemplate("json");
?>
