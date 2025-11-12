<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */
 
class FilePermissionHandler {
	
	/**
	* getFilePermissionsInfo: outputs the permissions for a file in a visual way, this is, the same way than in a linux terminal.
	* 	This method was copied from: https://www.php.net/manual/en/function.fileperms.php
	*/
	public static function getFilePermissionsInfo($file_path) {
		$perms = fileperms($file_path);

		switch ($perms & 0xF000) {
			case 0xC000: // socket
				$info = 's';
				break;
			case 0xA000: // symbolic link
				$info = 'l';
				break;
			case 0x8000: // regular
				$info = 'r';
				break;
			case 0x6000: // block special
				$info = 'b';
				break;
			case 0x4000: // directory
				$info = 'd';
				break;
			case 0x2000: // character special
				$info = 'c';
				break;
			case 0x1000: // FIFO pipe
				$info = 'p';
				break;
			
			default: // unknown
				$info = 'u';
		}

		// Owner
		$info .= (($perms & 0x0100) ? 'r' : '-');
		$info .= (($perms & 0x0080) ? 'w' : '-');
		$info .= (($perms & 0x0040) ?
				(($perms & 0x0800) ? 's' : 'x' ) :
				(($perms & 0x0800) ? 'S' : '-'));

		// Group
		$info .= (($perms & 0x0020) ? 'r' : '-');
		$info .= (($perms & 0x0010) ? 'w' : '-');
		$info .= (($perms & 0x0008) ?
				(($perms & 0x0400) ? 's' : 'x' ) :
				(($perms & 0x0400) ? 'S' : '-'));

		// World
		$info .= (($perms & 0x0004) ? 'r' : '-');
		$info .= (($perms & 0x0002) ? 'w' : '-');
		$info .= (($perms & 0x0001) ?
				(($perms & 0x0200) ? 't' : 'x' ) :
				(($perms & 0x0200) ? 'T' : '-'));
		
		return $info;
	}
	
	public static function getFileUserOwnerInfo($file_path) {
		$uid = fileowner($file_path);
		
		return $uid && function_exists("posix_getpwuid") ? posix_getpwuid($uid) : null;
	}
	
	public static function getFileUserGroupInfo($file_path) {
		$gid = filegroup($file_path);
		
		return $gid && function_exists("posix_getgrgid") ? posix_getgrgid($gid) : null;
	}
}
?>
