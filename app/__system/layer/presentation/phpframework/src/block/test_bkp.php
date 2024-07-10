<?php
$block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);//"test";//must be the same than this file name.

$block_settings[$block_id] = array(
	"form_class" => "yyy",
	"form_id" => isset($block_local_variables["Enter here The form id"]) ? $block_local_variables["Enter here The form id"] : null,
	
	//...
);

$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("module_xxx", $block_id, $block_settings[$block_id]);
?>
