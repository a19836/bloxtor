<?php
class SQLMap {
	
	public function __construct() {
		
	}
	
	public function getErrorHandler() {
		global $GlobalErrorHandler;

		return $GlobalErrorHandler;
	}
}
?>
