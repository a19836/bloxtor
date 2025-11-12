<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */
 
if (!isset($file_path))
	$file_path = false;

//die($file_path);
	
if (!empty($file_path) && file_exists($file_path)) {
	$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
	$mime_type = finfo_file($finfo, $file_path) . "\n";
	finfo_close($finfo);

	header("Content-Type: " . $mime_type);
    	header("Content-Length: " . filesize($file_path));
    
    	@readfile($file_path) or die("File not found.");
}
else {
	header("HTTP/1.0 404 Not Found");
	echo "File not found.";
}
?>
