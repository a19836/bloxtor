<?php
class ErrorHandler {
	private $error;
	
	public function __construct() {
		$this->start();
	}
	
	public function stop() {
		$this->error = true;
	}
	
	public function start() {
		$this->error = false;
	}
	
	public function ok() {
		return !$this->error;
	}
}
?>
