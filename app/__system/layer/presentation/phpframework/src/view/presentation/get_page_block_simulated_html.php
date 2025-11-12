<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$obj = array(
	"html" => isset($html) ? $html : null,
	"editable_settings" => isset($editable_settings) ? $editable_settings : null,
	"block_code_id" => isset($block_code_id) ? $block_code_id : null,
	"block_code_time" => isset($block_code_time) ? $block_code_time : null
);

$EVC->setTemplate("json");
?>
