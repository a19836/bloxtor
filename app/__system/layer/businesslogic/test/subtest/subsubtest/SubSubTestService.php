<?php 
namespace __system\businesslogic;
 
class SubSubTestService {
	
	public function __construct() {
		
	}
	
	public function executeBusinessLogicSubSubTest($value) {
		//usleep(10000);//1000000 microsec == 1 sec; 10000 microsec == 10 milliseconds == 1sec/100
		return "Time for '$value':" . date("Y-m-d H:i:s:u");
	}
}
?>
