<?php
class ExceptionLogHandler {
	private $LogHandler;
	private $die_when_throw_exception;
	
	public function __construct($LogHandler, $die_when_throw_exception = false) {
		$this->LogHandler = $LogHandler;
		$this->die_when_throw_exception = $die_when_throw_exception;
	}
	
	public function log(Exception $exception) {
		if($this->LogHandler) {
			$message = $exception->getMessage();
			$problem = $exception->problem;
			$msg = $message != $problem ? "$message\n$problem" : $problem;
			
			$this->LogHandler->setExceptionLog($msg, $exception->getTrace());
			//$this->LogHandler->setExceptionLog($msg, $exception->getTraceAsString());
		}
		
		if($this->die_when_throw_exception) {
			echo "<p style=\"margin:10px; font-weight:bold; color:#2C2D34;\">DIE: Program execution ends on the ExceptionHandler class (" . date("Y-m-d H:i:s", time()) . ")</p>";
			die(1); //1: terminate with error, as in shell script
		}
	}
	
	public function setLogHandler($LogHandler) {$this->LogHandler = $LogHandler;}
	public function getLogHandler() {return $this->LogHandler;}
	
	public function setDieWhenThrowException($die_when_throw_exception) {$this->die_when_throw_exception = $die_when_throw_exception;}
	public function getDieWhenThrowException() {return $this->die_when_throw_exception;}
}
?>
