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

//php /home/jplpinto/Desktop/phpframework/trunk/app/lib/org/phpframework/phpscript/test_php_code_obfuscator.php; cat /tmp/sample_php_code_obfuscator.php

include __DIR__ . "/PHPCodeObfuscator.php";

$files_settings = array(
	/*"/var/www/html/livingroop/default/app/lib/org/phpframework/bean/BeanFactory.php" => array(
		1 => array(
			"save_path" => "/tmp/sample_php_code_obfuscator.php",
			"all_properties" => array("obfuscate_name_private" => 1),
			"all_methods" => array("obfuscate_code" => 1, "obfuscate_name_private" => 1),
		),
		"BeanFactory" => array(
			"methods" => array(
				"initObject" => array("obfuscate_encapsed_string" => 1, "ignore_local_variables" => array('$objs', '$vars')),
				"initFunction" => array("obfuscate_encapsed_string" => 1),
				"getArgumentStr" => array("obfuscate_encapsed_string" => 1),
			),
		),
	),*/
	__DIR__ . "/sample_php_code_obfuscator.php" => array(
		0 => array( //Global functions and variables
			"foo" => array(
				"obfuscate_name" => 0,
				"obfuscate_code" => 1,
			),
			"d" => array(
				"obfuscate_name" => 0,
				"obfuscate_code" => 1,
				"strip_encapsed_string_eol" => 0,
			),
			"\$p" => 0,
			"\$o" => 1,
			"\$func" => 1,
			"\$w" => 0,
			"\$q" => array("obfuscate_encapsed_string" => 1, "strip_encapsed_string_eol" => 1),
		),
		1 => array( //Generic settings
			"save_path" => "/tmp/sample_php_code_obfuscator.php",
			"obfuscate_encapsed_string" => 1,
			//"all_functions" => array("obfuscate_name" => 1),
			//"all_variables" => array("obfuscate_name" => 1),
			//"all_properties" => array("obfuscate_name" => 1),
			//"all_methods" => array("obfuscate_name" => 1),
			//"all_classes" => array("obfuscate_name" => 1),
		),
		"Foo" => array( //class Foo
			"obfuscate_name" => 0,
			"properties" => array(
				"\$x" => 0,
				"\$bar1" => array("obfuscate_name" => 1, "obfuscate_encapsed_string" => 1),
				"\$bar2" => 0,
				"\$bar3" => 1,
				"bar4" => 0,
				"\$a" => 0,
				"\$p" => 0,
			),
			"methods" => array(
				"__construct" => array("obfuscate_code" => 1, "obfuscate_encapsed_string" => 1/*, "ignore_local_variables" => array('$d', '$as')*/, "objects_methods_or_vars" => array("getName")),
				"bar1" => array("obfuscate_name" => 1, "obfuscate_code" => 1),
				"bar2" => array("obfuscate_name" => 0, "obfuscate_code" => 1),
				"getClass" => array("obfuscate_name" => 1, "obfuscate_code" => 1),
			),
		),
		"I" => array( //class Foo
			"obfuscate_name" => 1,
			"all_methods" => array("obfuscate_name" => 1),
		),
		"X" => array( //class X
			"obfuscate_name" => 0,
			//"all_properties" => array("obfuscate_name_private" => 1),
			"properties" => array(
				"\$y" => 1,
			),
			//"all_methods" => array("obfuscate_code" => 1, "obfuscate_name_private" => 1),
			"methods" => array(
				"y" => array("obfuscate_name" => 1, "obfuscate_code" => 1),
				"t" => array("obfuscate_name" => 0, "obfuscate_code" => 1),
				"funcWithEval" => array("obfuscate_name" => 1, "obfuscate_code" => 1, "obfuscate_encapsed_string" => 1, "ignore_local_variables" => array('$other'), "objects_methods_or_vars" => array("getName")),
				"getName" => array("obfuscate_name" => 1),
				"cloneTask" => array("obfuscate_code" => 1, "obfuscate_encapsed_string" => 1),
			),
		),
		"W" => array( //class W
			"obfuscate_name" => 1,
		),
	),
);
//$files_settings = array(__DIR__ . "/sample_php_code_obfuscator.php" => array(1 => array("save_path" => "/tmp/sample_php_code_obfuscator.php"))); //only minify file.

$options = array("plain_encode" => 0, "strip_comments" => 1, "strip_doc_comments" => 1, "strip_eol" => 0);
//$options["obfuscate_name"] = 1;
//$options["obfuscate_code"] = 1;
//$options["obfuscate_encapsed_string"] = 1;
//$options["strip_encapsed_string_eol"] = 0;

$PHPCodeObfuscator = new PHPCodeObfuscator($files_settings);
$status = $PHPCodeObfuscator->obfuscateFiles($options);

echo "STATUS:$status\n";
echo $PHPCodeObfuscator->getIncludesWarningMessage();
?>
