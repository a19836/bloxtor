<?php
function listLastDirectoryFiles($dir) {
	$last_files = array();
	
	if ($dir && is_dir($dir)) {
		$files = array_diff(scandir($dir), array('.','..'));
		
		foreach ($files as $file) {
			$fp = "$dir/$file";
			
			if (is_dir($fp)) {
				$lf = listLastDirectoryFiles($fp . "/");
				$last_files = array_merge($last_files, $lf);
			}
			else 
				$last_files[] = $fp;
		}
	}
	
	return $last_files;
}

function printUnserializedLastDirectoryFilesContent($dir) {
	$files = listLastDirectoryFiles($dir);
	
	echo "<pre>";
	
	if ($files)
		foreach ($files as $file) 
			if (file_exists($file)) {
				$arr = unserialize( file_get_contents($file) );
				
				echo "<br>FILE: $file:\n<br> ";
				print_r($arr);
				echo "\n<br><br>";
			}
	
	echo "</pre>";
}
?>
