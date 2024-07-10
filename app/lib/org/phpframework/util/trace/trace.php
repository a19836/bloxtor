<?php
function print_code_trace() {
	$trace = debug_backtrace();
	$trace_text = array();
	
	if (isset($trace[0]["function"]) && $trace[0]["function"] == "include" && !isset($trace[0]["args"][0]))
		$trace[0]["args"][0] = __FILE__;
	
	foreach($trace as $i => $call) {
		$file = isset($call['file']) ? $call['file'] : null;
		$line = isset($call['line']) ? $call['line'] : null;
		$object = isset($call['object']) ? $call['object'] : null;
		$type = isset($call['type']) ? $call['type'] : null;
		$function = isset($call['function']) ? $call['function'] : null;
		$args = isset($call['args']) ? $call['args'] : null;
		
		/**
		 * THIS IS NEEDED! If all your objects have a __toString function it's not needed!
		 *
		 * Catchable fatal error: Object of class B could not be converted to string
		 * Catchable fatal error: Object of class A could not be converted to string
		 * Catchable fatal error: Object of class B could not be converted to string
		 */
		if (is_object($object)) 
			$object = 'CONVERTED OBJECT OF CLASS '.get_class($object);
		
		if (is_array($args)) {
		    foreach ($args as &$arg)
			if (is_object($arg)) 
				$arg = 'CONVERTED OBJECT OF CLASS '.get_class($arg);
		
		$trace_text[$i] = "#".$i." ".$file.'('.$line.') ';
		$trace_text[$i] .= $object ? $object.$type : '';
		$trace_text[$i] .= $function.'('.implode(', ',$args).')';
	}
	
	for ($i = count($trace_text) - 1; $i >= 0; --$i)
		echo $trace_text[$i]."<br>";
}
?>
