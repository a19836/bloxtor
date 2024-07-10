<?php
namespace __system\businesslogic;
 
/**
 * The Bar function
 * @return varchar Whether or not something is true
 * @params (
 * 	@param mixed function_vars,
 * 	@param (name=value, tYPe=varchar, NotNull, @Min(1), default="This is only a test from Annotations"),
 * )
 */
function foo($function_vars, $value) {
	//usleep(10000);//1000000 microsec == 1 sec; 10000 microsec == 10 milliseconds == 1sec/100
	return "Time for '$value':" . date("Y-m-d H:i:s:u");
}

function bar($function_vars, $value) {
	$content = print_r(array_keys($function_vars), true);
	$content .= isset($function_vars["vars"]) ? print_r($function_vars["vars"], true) : "";
	
	//file_put_contents("/tmp/test.log", $content);
	
	return "<pre>$content</pre>!VALUE:$value!";
}
?>
