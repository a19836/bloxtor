<?php
include_once get_lib("org.phpframework.layer.presentation.cms.CMSExternalTemplateLayer");

$defined_vars = get_defined_vars(); //get all defined vars including the default system vars
$ignore = array('GLOBALS' => null, '_FILES' => null, '_COOKIE' => null, '_POST' => null, '_GET' => null, '_SERVER' => null, '_ENV' => null, 'ignore' => null, 'argc' => null, 'argv' => null); //list of keys to ignore
$external_vars = array_diff_key($defined_vars, $ignore); //diff the ignore list as keys with the defined list

echo CMSExternalTemplateLayer::getParsedTemplateCode($EVC, $template_params, $external_vars); 
?>
