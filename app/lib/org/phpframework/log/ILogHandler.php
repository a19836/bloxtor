<?php
interface ILogHandler {
	public function setLogLevel($debug_level);
	public function getLogLevel();
	
	public function setEchoActive($echo_active);
	public function getEchoActive();
	
	public function setFilePath($file_path);
	public function getFilePath();
	
	public function setCSS($css);
	public function getCSS();
	
	public function setExceptionLog($log_message, $back_trace_message_or_back_trace_on = null);
	public function setErrorLog($log_message, $back_trace_message_or_back_trace_on = null);
	public function setInfoLog($log_message, $back_trace_message_or_back_trace_on = null);
	public function setDebugLog($log_message, $back_trace_message_or_back_trace_on = null);
}
?>
