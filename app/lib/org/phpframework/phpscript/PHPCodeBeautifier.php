<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("lib.vendor.phpcf.phpcf-src.src.init"); // Connecting constants and autoloader

class PHPCodeBeautifier {
	private $was_formatted;
	private $issues;
	private $error;
	private $status;
	
	public function __construct() {
		$this->was_formatted = false;
		$this->issues = null;
		$this->error = null;
		$this->status = false;
	}
	
	public function wasFormatted() { return $this->was_formatted; }
	public function getIssues() { return $this->issues; }
	public function getError() { return $this->error; } /* return a string: a printstacktrace string */
	public function getStatus() { return $this->status; }
	
	public function beautifyCode($code) {
		// Create formatting options
		$Options = new \Phpcf\Options();

		// Optional settings (there are default values ​​for all)
		$Options->setTabSequence("\t"); // Your 3-4 spaces or Tab
		//$Options->setMaxLineLength(130); // 120 by default
		//$Options->setCustomStyle(__DIR__ . '/styles/default/'); // path to the directory with your styles
		//$Options->toggleCyrillicFilter(false); // toggle the filter of Cyrillic characters
		//$Options->usePure(true); // force use of the version without extension

		$Formatter = new \Phpcf\Formatter($Options);

		// Format the file
		#$Result = $Formatter->formatFile('file.php'); // whole file
		#$Result = $Formatter->formatFile('file.php:1-40,65'); // range of lines
		
		// Format string
		$Result = $Formatter->format($code); // whole line with code
		#$Result = $Formatter->format($code, [1, 2, 10]); // line numbers for formatting

		// All of the above formatting functions return a \Phpcf\FormattingResult object

		$new_code = $Result->getContent(); // string with formatted code
		
		$this->was_formatted = $Result->wasFormatted(); // bool, whether the code was changed
		$this->issues = $Result->getIssues(); // array, textual description of code formatting problems
		$this->error = $Result->getError(); // \ Exception | null error while formatting the code
		$this->status = $this->was_formatted && empty($this->issues) && empty($this->error);
		
		if ($this->status) {
			//Formatter removes the last php end tag, so we must add it
			if (substr(trim($code), -2) == "?>" && substr(trim($new_code), -2) != "?>")
				$new_code = trim($new_code) . "\n?>";
			
			return $new_code;
		}
		
		return $code;
	}
}
?>
