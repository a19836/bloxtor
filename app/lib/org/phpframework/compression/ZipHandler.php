<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class ZipHandler {
	
	public static function zip($source, $destination, $options = null) {
	    if (!extension_loaded('zip') || !file_exists($source))
			return false;

	    $ZipArchive = new ZipArchive();
	    
	    if (!$ZipArchive->open($destination, ZIPARCHIVE::CREATE))
			return false;

	    $source = str_replace('\\', '/', realpath($source));
	    
	    $include_source_folder = $options && array_key_exists("include_source_folder", $options) ? $options["include_source_folder"] : true;
	    $exclude_files = $options && isset($options["exclude_files"]) ? $options["exclude_files"] : null;
	    
	    if ($exclude_files) {
	    		$exclude_files = is_array($exclude_files) ? $exclude_files : array($exclude_files);
	    		
	    		foreach ($exclude_files as $idx => $file) {
	    			if (file_exists($file))
	    				$exclude_files[$idx] = realpath($file);
	    			else
	    				unset($exclude_files[$idx]);
			}
		}
		
	    if (is_dir($source) === true) {
			$prefix = "";
			
			if ($include_source_folder) {
				$prefix = basename($source) . '/';
				$ZipArchive->addEmptyDir($prefix);
			}
			
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
			
			foreach ($files as $file) {
				$file = str_replace('\\', '/', $file);

				// Ignore "." and ".." folders
				if ( in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')) )
					continue 1;

				$file = realpath($file);
				$file_name = str_replace($source . '/', '', $file);
				
				$exclude = false;
				
				if ($exclude_files)
					foreach ($exclude_files as $exclude_file)
						if ((strpos($file, $exclude_file) === 0) || (is_dir($file) && $file . "/" == $exclude_file)) {
							$exclude = true;
							break;
						}
				
				if ($exclude)
					continue 1;
				else if (is_dir($file) === true)
					$ZipArchive->addEmptyDir($prefix . $file_name . '/');
				else if (is_file($file) === true)
					$ZipArchive->addFromString($prefix . $file_name, file_get_contents($file));
			}
	    }
	    else if (is_file($source) === true)
			$ZipArchive->addFromString(basename($source), file_get_contents($source));

	    return $ZipArchive->close();
	}
	
	//unzip $source to $destination. $source is a .zip file and $destination is a folder
	public static function unzip($source, $destination) {
		$ZipArchive = new ZipArchive();
		$status = file_exists($source) && $ZipArchive->open($source) === true;

		if ($status) {
			$status = $ZipArchive->extractTo($destination);
			$ZipArchive->close();
		}
		
		return $status;
	}
	
	//$add_to: is the relative path where the $file_path_to_add should be added inside of the $zip_file_path
	public static function addFileToZip($zip_file_path, $file_path_to_add, $add_to = "") {
		$ZipArchive = new ZipArchive();
		$status = false;
		
		if ($file_path_to_add && file_exists($zip_file_path) && file_exists($file_path_to_add) && $ZipArchive->open($zip_file_path) === true) {
			$status = true;
			$file_path_to_add = str_replace('\\', '/', realpath($file_path_to_add));

		    	if (is_dir($file_path_to_add) === true) {
				$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($file_path_to_add), RecursiveIteratorIterator::SELF_FIRST);

				foreach ($files as $file) {
				    $file = str_replace('\\', '/', $file);

				    // Ignore "." and ".." folders
				    if ( in_array(substr($file, strrpos($file, '/') + 1), array('.', '..')) )
					   continue 1;

				    $file = realpath($file);
				    
				    if (is_dir($file) === true)
					   $ZipArchive->addEmptyDir($add_to . str_replace($file_path_to_add . '/', '', $file . '/'));
				    else if (is_file($file) === true)
					   $ZipArchive->addFromString($add_to . str_replace($file_path_to_add . '/', '', $file), file_get_contents($file));
				}
		    	}
		    	else if (is_file($file_path_to_add) === true)
				$ZipArchive->addFromString($add_to . basename($file_path_to_add), file_get_contents($file_path_to_add));
			
			$ZipArchive->close();
		}
		
		return $status;
	}
	
	public static function renameFileInZip($zip_file_path, $to_replace, $replacement) {
		$ZipArchive = new ZipArchive();
		$to_replace = trim($to_replace);
		$replacement = trim($replacement);
		$status = false;
		
		if ($to_replace && $replacement && $zip_file_path && file_exists($zip_file_path) && $ZipArchive->open($zip_file_path) === true) {
			$to_replace_folder = $to_replace . (substr($to_replace, -1) != '/' ? '/' : '');
			$replacement_folder = $replacement . (substr($replacement, -1) != '/' ? '/' : '');
			
			for ($i = 0; $i < $ZipArchive->numFiles; $i++) {
				$file = $ZipArchive->getNameIndex($i); //return the absolute path inside of the zip. If a folder return the folder name with a slash at the end.
		          $is_dir = substr($file, -1) == "/"; //it means $file is a file and not a folder
				
				if (!$is_dir && $file == $to_replace) {
					$ZipArchive->renameIndex($i, $replacement);
					$status = true;
					break;
				}
				else if (strpos($file, $to_replace_folder) === 0) {
					$new_path = str_replace(substr($file, 0, strlen($to_replace_folder)), $replacement_folder, $file);
					$ZipArchive->renameIndex($i, $new_path);
					$status = true;
					//Do not break files here, bc we must continue rename all the sub-files, so we must continue the loop
				}
			}

			$ZipArchive->close();
		}
		
		return $status;
	}
}
