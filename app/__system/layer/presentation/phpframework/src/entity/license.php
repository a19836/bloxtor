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

$raw_license = isset($_GET["raw_license"]) ? $_GET["raw_license"] : null;
$license_path_to_open = $license_path;

if ($raw_license && preg_match("/\.md$/", $raw_license) && file_exists(CMS_PATH . $raw_license))
	$license_path_to_open = CMS_PATH . $raw_license;

if (file_exists($license_path_to_open)) {
	include_once get_lib("lib.vendor.parsedown.Parsedown");
	
	$content = file_get_contents($license_path_to_open);
	$Parsedown = new Parsedown();
	$inner_html = $Parsedown->text($content);	
	
	$inner_html = str_replace('href="./', 'href="?raw_license=', $inner_html);
	
	if ($license_path_to_open != $license_path)
		$inner_html = '<div style="text-align:right; font-size:80%;"><a href="javascript:history.back();">Go Back</a></div>' . $inner_html;
	
	$html = '<html>
	<head>
		<style>
		blockquote {
			font: 14px/22px normal helvetica, sans-serif;
			margin-top: 10px;
			margin-bottom: 10px;
			margin-left: 0px;
			padding-left: 15px;
			border-left: 3px solid #FC3C44;
		}
		</style>
	</head>
	<body>' . $inner_html . '</body>
</html>';
	
	echo $html;
	die();
	
	//header('Content-Type: text/txt');
	//readfile($license_path_to_open);
}
else {
	$msg = "No License found in '<root path>/" . (substr($license_path_to_open, strlen(CMS_PATH))) . "'!<br/>In order to use this software, you must get your license first! <br/>Otherwise you are not allowed to use this software!";
	launch_exception(new Exception($msg));
}

die();
?>
