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
 
class PathHandler {
	
	/*
	 * Because realpath() does not work on files that do not exist, here is a function that does.
	 * It replaces (consecutive) occurences of / and \\ with whatever is in DIRECTORY_SEPARATOR, and processes /. and /.. fine.
	 * Paths returned by getAbsolutePath() contain no (back)slash at position 0 (beginning of the string) or position -1 (ending)
	 */
	public static function getAbsolutePath($path) {
		$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
		$parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
		$absolutes = array();
		
		foreach ($parts as $part) {
			if ('.' == $part)
				continue;
			
			if ('..' == $part)
				array_pop($absolutes);
			else
				$absolutes[] = $part;
		}
		
		return (substr($path, 0, 1) == DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : "") . implode(DIRECTORY_SEPARATOR, $absolutes) . (substr($path, -1) == DIRECTORY_SEPARATOR ? DIRECTORY_SEPARATOR : "");
	}
}
?>
