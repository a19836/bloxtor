<?php
namespace __system\businesslogic;
 
class SubTestService {
	
	public function __construct() {
		
	}
	
	/**
	 * The Bar function
	 * @return varchar Whether or not something is true
	 * @param (name=value[0][name], tYPe=varchar, NotNull, default="This is only a test from Annotations"),
	 */
	public function executeBusinessLogicSubTest($value) {
		//usleep(10000);//1000000 microsec == 1 sec; 10000 microsec == 10 milliseconds == 1sec/100
		return "Time for " . json_encode($value) . ":" . date("Y-m-d H:i:s:u");
	}
}
?>
