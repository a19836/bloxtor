<?php 
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
