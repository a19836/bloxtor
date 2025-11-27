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

include_once get_lib("org.phpframework.log.ILogHandler");

class LogHandler implements ILogHandler {
	private $debug_level;
	private $echo_active;
	private $file_path;
	private $css;
	private $root_path;
	
	protected $back_traces_limit = 15;
	protected $files_to_exclude = array("LogHandler.php", "ExceptionLogHandler.php");
	protected $max_file_path_prefix_length = 20;
	protected $max_file_path_suffix_length = 50;
	
	public function __construct() {
		$this->debug_level = 0;
		$this->echo_active = false;
		$this->file_path = false;
		$this->css = "";
	}
	
	public function setLogLevel($debug_level) {$this->debug_level = $debug_level;}
	public function getLogLevel() {return $this->debug_level;}
	
	public function setEchoActive($echo_active) {$this->echo_active = $echo_active;}
	public function getEchoActive() {return $this->echo_active;}
	
	public function setFilePath($file_path) {$this->file_path = $file_path;}
	public function getFilePath() {return $this->file_path;}
	
	public function setCSS($css) {$this->css = $css;}
	public function getCSS() {return $this->css;}
	
	public function setRootPath($root_path) {$this->root_path = $root_path;}
	public function getRootPath() {return $this->root_path;}
	
	//debug_level = 1
	public function setExceptionLog($log_message, $back_trace_message_or_back_trace_on = null, $echo_active = null) {
		if ($this->debug_level >= 1) {
			$back_trace_message_or_back_trace_on = empty($back_trace_message_or_back_trace_on) ? true : $back_trace_message_or_back_trace_on;
			
			return $this->logMessage("EXCEPTION", $log_message, $back_trace_message_or_back_trace_on, $echo_active);
		}
	}
	
	//debug_level = 2
	public function setErrorLog($log_message, $back_trace_message_or_back_trace_on = null, $echo_active = null) {
		if ($this->debug_level >= 2)
			return $this->logMessage("ERROR", $log_message, $back_trace_message_or_back_trace_on, $echo_active);
	}
	
	//debug_level = 3
	public function setInfoLog($log_message, $back_trace_message_or_back_trace_on = null, $echo_active = null) {
		if ($this->debug_level >= 3)
			return $this->logMessage("INFO", $log_message, $back_trace_message_or_back_trace_on, $echo_active);
	}
	
	//debug_level = 4
	public function setDebugLog($log_message, $back_trace_message_or_back_trace_on = null, $echo_active = null) {
		if ($this->debug_level >= 4)
			return $this->logMessage("DEBUG", $log_message, $back_trace_message_or_back_trace_on, $echo_active);
	}
	
	private function logMessage($log_type, $log_message, $back_trace_message_or_back_trace_on = null, $echo_active = null) {
		$log_message = $this->prepareMessage($log_type, "MESSAGE", $log_message);
		
		$trace_message = "";
		
		if (!empty($back_trace_message_or_back_trace_on)) {
			if (is_string($back_trace_message_or_back_trace_on) && !is_numeric($back_trace_message_or_back_trace_on)) {
				$trace_message = $back_trace_message_or_back_trace_on;
				$trace_message = $this->prepareMessage($log_type, "TRACE", "\n$trace_message");
				
				$trace_message .= "<br/><hr/><br/>";
			}
			else if (is_array($back_trace_message_or_back_trace_on)) {
				$traces = $back_trace_message_or_back_trace_on;
				$trace_message = $this->getDebugBackTraceAsString($traces);
				$trace_message = $this->prepareMessage($log_type, "TRACE", "\n$trace_message");
				
				$trace_message .= "<br/><hr/><br/>";
			}
			
			$traces = debug_backtrace();
			$trace_message_aux = $this->getDebugBackTraceAsString($traces);
			$trace_message_aux = $this->prepareMessage($log_type, "TRACE", "\n$trace_message_aux");
		
			$trace_message .= $trace_message_aux;
		}
				
		$this->setLog($log_message, $trace_message, $echo_active);
	}
	
	private function setLog($log_message, $trace_message = null, $echo_active = null) {
		if ($this->debug_level > 0) {
			$this->logToFile($log_message, $trace_message);
			
			if ((isset($echo_active) && $echo_active) || (!isset($echo_active) && $this->echo_active)) {
				$output = '<style type="text/css">' . $this->css . '</style>'
						. '<div class="log_handler">'
						. '<div class="message">'
							. '<div class="toggle_trace" onClick="this.parentNode.closest(\'.log_handler\').querySelector(\'.trace\').classList.toggle(\'hidden\')">&Xi;</div>'
							. str_replace("\n", "<br/>", $log_message)
						. '</div>'
						. '<div class="trace hidden">' . str_replace("\n", "<br/>", $trace_message) . '</div>'
					. '</div>';;
					
				if (headers_sent())
					echo $output;
				else
					echo "<html><body>$output</body></html>";
			}
		}
	}
	
	private function logToFile($log_message, $trace_message = null) {
		$log_message = $this->convertMessageToString($log_message);
		$message = "\n" . $log_message . $trace_message; //2020-01-21: Do not use strip_tags here bc the $log_message can be a php code with php open and close tags. if we add the strip_tags here, it will remove all the php code. This is used in the PHPScriptHandler::isValidPHPContents method.
		
		if (!empty($this->file_path))
			error_log($message, 3, $this->file_path);
		else
			error_log($message, 0);
	}
	
	private function prepareMessage($log_type, $message_type, $message) {
		$message = $this->convertMessageToString($message);
		$message = str_replace($this->root_path, "", $message);
		
		return "<span class=\"" . strtolower($log_type) . "\">[$log_type] [" . date("Y-m-d H:i:s") . "] [$message_type]:" . ($message_type == "TRACE" ? "\n" : "") . "$message\n</span>";
	}
	
	private function convertMessageToString($message) {
		return is_array($message) || is_object($message) ? print_r($message, 1) : trim($message);
	}
	
	private function getDebugBackTraceAsString($traces, $nl = "\n") {
		$ret = array();
		
		$first_back_traces_to_ignore = 0;
		$back_traces_limit = $this->back_traces_limit;
		
		$total = $traces ? count($traces) : 0;
		for ($i = 0; $i < $total; $i++) {
			$call = $traces[$i];
			
			$file = isset($call["file"]) ? $call["file"] : null;
			$file_base_name = basename($file);
			if (in_array($file_base_name, $this->files_to_exclude)) {
				++$first_back_traces_to_ignore;
				continue 1;
			}
			
			$file = str_replace($this->root_path, "", $file);
			
			if (strlen($file) > ($this->max_file_path_prefix_length + $this->max_file_path_suffix_length)) {
				$file = substr($file, 0, $this->max_file_path_prefix_length) . "..." . substr($file, strlen($file) - $this->max_file_path_suffix_length);
			}
			else {
				$file = $file;
			}
			
			$line = isset($call["line"]) ? $call["line"] : null;
			$message = "<strong>#" . ($i - $first_back_traces_to_ignore) . " " . $file . "(" . $line . "):</strong> ";
			
			$function = isset($call["function"]) ? $call["function"] : null;
			$fl = strtolower($function);
			
			if ($fl == "include" || $fl == "include_once" || $fl == "require_once" || $fl == "require")
				$message .= $function . "('" . (!empty($call["args"][0]) ? $call["args"][0] : "") . "')";
			else {
				$object = "";
				$args_str = "";
			
				if (isset($call["class"])) {
					$object = $call["class"] . (isset($call["type"]) ? $call["type"] : null);
					$args_str = isset($call["args"]) ? self::getArgsInString($call["args"]) : null;
				}

				$message .= $object . $function . "(" . $args_str . ")";
			}
			
			$ret[] = $message;
			
			--$back_traces_limit;
			if ($back_traces_limit <= 0) {
				break;
			}
		}

		return implode($nl, $ret);
	}
	
	public static function getArgsInString($args) {
		if (is_array($args)) {
			$new_args = array();
		
			$total = count($args);
			for ($i = 0; $i < $total; $i++)
				$new_args[] = self::getArgInString( $args[$i] );
		
			return implode(", ", $new_args);
		}
		return "";
	}
	
	public static function getArgInString($arg) {
		if (is_array($arg)) 
			return stripslashes(json_encode($arg));//"Array(" . self::getArgsInString($arg) . ")";
		else if (is_object($arg)) 
			return "Object(" . get_class($arg) . ")";
		else if ($arg === true)
			return "true";
		else if ($arg === false) 
			return "false";
		else if ($arg == null)
			return "null";
		else if (is_numeric($arg)) 
			return (int)$arg;
		else 
			return "'" . $arg . "'";
	}
	
	/**
	 * Modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/ and of https://gist.github.com/lorenzos/1711e81a9162320fde20 and JPLPINTO
	 * @author Kinga the Witch (Trans-dating.com), Torleif Berger, Lorenzo Stanco, JPLPINTO
	 * @link http://stackoverflow.com/a/15025877/995958
	 * @license http://creativecommons.org/licenses/by/3.0/
	 */    
	public function tail($lines = 0, $minimum_seek = 0, $skip = 0, $adaptive = true, $lock = false) {
		$output = '';
		$check_lines = $lines > 0;
		$check_minimum_seek = $minimum_seek > 0;
		
		// Open file
		$fp = @fopen($this->file_path, "rb");
		
		if ($lock && @flock($fp, LOCK_SH) === false) 
			return false;
		
		if ($fp === false) 
			return false;
		
		$meta = stream_get_meta_data($fp);
		
		if (!empty($meta['seekable'])) {
			if (!$adaptive) 
				$buffer = 4096;
			else {
				// Sets buffer size, according to the number of lines to retrieve.
				// This gives a performance boost when reading a few lines from the file.
				$max = $check_lines ? max($lines, $skip) : $skip;
				
				$buffer = ($max < 2 ? 64 : ($max < 10 ? 512 : 4096));
			}
			
			if ($check_minimum_seek) {
				$file_size = filesize($this->file_path);
				$min = $file_size - $minimum_seek;
				
				//if minimum_seek is negative, it means that the offset is bigger than the file size, which means we will return an empty string, bc there is nothing to return.
				if ($min < 0)
					return '';
				
				$buffer = min($minimum_seek, $buffer);
			}
			
			// Jump to last character
			fseek($fp, -1, SEEK_END);

			// Read it and adjust line number if necessary
			// (Otherwise the result would be wrong if file doesn't end with a blank line)
			if (fread($fp, 1) == "\n") {
				if ($skip > 0) { 
					$skip++;
					$lines--; 
				}
			} 
			else
				$lines--;

			if (!$check_lines) {
				$seek = $check_minimum_seek ? $minimum_seek : 0;
				fseek($fp, $seek, SEEK_SET);
				
				while (!feof($fp))
					$output .= fread($fp, $buffer);
				
				while ($skip > 0) {
					$output = substr($output, 0, strrpos($output, "\n") + 1);
					--$skip;
				}
			}
			else {
				// Jump to last character
				fseek($fp, -1, SEEK_END);
				
				// Start reading
				$chunk = '';
				
				// While we would like more
				while (($current_position = ftell($fp)) > 0 && (!$check_lines || $lines >= 0)) {
					// Figure out how far back we should jump
					$seek = min($current_position, $buffer);
					
					if ($check_minimum_seek) {
						//if current position is equal or minor than the minimum ofsset position 
						if ($current_position <= $minimum_seek)
							break;
						
						//prepare next seek according with minimum_seek
						$next_position = $current_position - $seek;
						
						if ($next_position < $minimum_seek)
							$seek = $current_position - $minimum_seek;
					}
					
					// Do the jump (backwards, relative to where we are)
					if ($current_position - $seek < 0)
						$seek -= abs($current_position - $seek);
					
					if (fseek($fp, -$seek, SEEK_CUR) == -1)
						break; //avoids infinit loops when the cursor is less than 0.
					else
						$current_position -= $seek;
					
					if ($seek == 0)
						break;
					
					// Read a chunk
					$chunk = fread($fp, $seek);
					
					// Calculate chunk parameters
					$count = substr_count($chunk, "\n");
					$strlen = mb_strlen($chunk, '8bit');
					
					// Jump back to where we started reading
					if (fseek($fp, -$strlen, SEEK_CUR) == -1)
						break; //avoids infinit loops when the cursor is less than 0.
					
					if ($skip > 0) { // There are some lines to skip
						// Chunk contains less new line symbols than
						if ($skip > $count) { 
							$skip -= $count; 
							$chunk = ''; 
						} 
						else {
							$pos = 0;
							
							while ($skip > 0) {
								// Calculate the offset - NEGATIVE position of last new line symbol
								if ($pos > 0) 
									$offset = $pos - $strlen - 1; 
								else // First search (without offset)
									$offset = 0; 
								
								if ($offset < 0) //Protection against infinite loop (just in case)
									break;
								
								// Search for last (including offset) new line symbol
								$pos = strrpos($chunk, "\n", $offset); 
								
								// Found new line symbol - skip the line
								if ($pos !== false) 
									$skip--;
								else // "else break;" - Protection against infinite loop (just in case)
									break; 
							}
							
							$chunk = substr($chunk, 0, $pos); // Truncated chunk
							$count = substr_count($chunk, "\n"); // Count new line symbols in truncated chunk
						}
					}

					if (strlen($chunk) > 0) {
						// Add chunk to the output
						$output = $chunk . $output;
						// Decrease our line counter
						$lines -= $count;
					}
					
					//error_log("current_position:$current_position\n", 3, "/var/www/html/livingroop/default/tmp/test.log");
					if ($current_position <= 0)
						break; //avoids infinit loops when the cursor is less than 0.
				}
				
				// While we have too many lines
				// (Because of buffer size we might have read too many)
				if ($check_lines)
					while ($lines++ < 0) {
						// Find first newline and remove all text before that
						$output = substr($output, strpos($output, "\n") + 1);
					}
			}
		}
		
		// Unlock file
		if ($lock)
			@flock($fp, LOCK_UN);
		
		// Close file and return
		fclose($fp);
		return trim($output);
	}
}
?>
