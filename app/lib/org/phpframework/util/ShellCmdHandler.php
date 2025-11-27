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
 
class ShellCmdHandler {
	
	const FUNCTION_NAME = "shell_exec";
	
	public static function isAllowed() {
		return function_exists("shell_exec");
	}
	
	public static function exec($command, $prepare_command = true) {
		$prepare_command && self::prepareCommand($command);
		
		return $command ? shell_exec($command) : null;
	}
	
	public static function prepareCommand(&$command) {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') { //Windows
			/*$parts = self::splitCommand($command);
			$new_command = "";
			
			foreach ($parts as $part) {
				if (!preg_match("/cmd\s+\/c/i", $part))
					$part = "cmd /c " . $part;
				
				$new_command .= $part;
			}
			
			$command = $new_command;*/
			$command = "cmd /c $command";
		}
	}

	public static function escapeArg($arg) {
		return escapeshellarg($arg);
	}
	
	public static function getShellCommand($cmd_suffix) {
		$cmd = null;
		
		if (self::isAllowed()) {
			$cmds = array("/bin/$cmd_suffix", "/sbin/$cmd_suffix", "/usr/sbin/$cmd_suffix", "/usr/bin/$cmd_suffix");
			
			foreach ($cmds as $c) {
				$r = self::exec("ls $c");
				$r = $r ? trim($r) : "";
				
				if ($r == $c) {
					$cmd = $c;
					break;
				}
			}
		}
		
		return $cmd;
	}
	
	public static function splitCommand($command) {
		$parts = array();
		
		if ($command) {
			$odq = $osq = false;
			$start = 0;
			
			for ($i = 0, $t = strlen($command); $i < $t; $i++) {
				$char = $code[$i];
				
				if ($char == "'" && !$odq)
					$osq = !$osq;
				else if ($char == '"' && !$osq)
					$odq = !$odq;
				else if ($char == ";" && !$osq && !$odq) {
					$part = trim(substr($command, $start, $i));
					
					if ($part)
						$parts[] = $part;
					
					$start = $i + 1;
				}
			}
			
			$part = trim(substr($command, $start));
			
			if ($part)
				$parts[] = $part;
		}
		
		return $parts;
	}
}
?>
