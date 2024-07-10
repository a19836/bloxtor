<?php
namespace __system\businesslogic;
 
include_once $vars["business_logic_modules_service_common_file_path"];

class TestService extends CommonService {
	
	public function getQuery($data) {
		$module = isset($data["module"]) ? $data["module"] : null;
		$type = isset($data["type"]) ? $data["type"] : null;
		$service = isset($data["service"]) ? $data["service"] : null;
		$parameters = isset($data["parameters"]) ? $data["parameters"] : null;
		$options = isset($data["options"]) ? $data["options"] : null;
	
		$result = $this->getBroker($options)->callQuery($module, $type, $service, $parameters, $options);
		
		if(strtolower($type) == "insert" && $result)
			return $this->getBusinessLogicLayer()->getBroker()->getInsertedId($options);
		
		/*
		* You can create cache or delete cache, through the obj: $this->getUserCacheHandler();
		  Here is an example:
			$UserCacheHandler = $this->getUserCacheHandler();
			$UserCacheHandler->config(123, false);
			$cont = $UserCacheHandler->read("test/test1");
			if(!$cont) {
				$cont = "hello world";
				$UserCacheHandler->write("test/test1", $cont);
				echo "create new content: $cont";
			}
			else {
				echo "get old content: $cont";
			}
			die();
		*/
		return $result;
	}
	
	public function getQuerySQL($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		$type = isset($data["type"]) ? $data["type"] : null;
		$module = isset($data["module"]) ? $data["module"] : null;
		$service = isset($data["service"]) ? $data["service"] : null;
		$parameters = isset($data["parameters"]) ? $data["parameters"] : null;
		
		return $this->getBroker(isset($options["dal_broker"]) ? $options["dal_broker"] : null)->callQuerySQL($module, $type, $service, $parameters);
	}
	
	public function getObj($data) {
		$module = isset($data["module"]) ? $data["module"] : null;
		$service = isset($data["service"]) ? $data["service"] : null;
		$options = isset($data["options"]) ? $data["options"] : null;
		
		$obj = $this->getBusinessLogicLayer()->getBroker(2)->callObject($module, $service, $options);
		
		return $obj;
	}
	
	public function executeBusinessLogicTest($value) {
		//usleep(10000);//1000000 microsec == 1 sec; 10000 microsec == 10 milliseconds == 1sec/100
		return "Time for '$value':" . date("Y-m-d H:i:s:u");
	}
	
	public function deleteBusinessLogicTestCache() {
		return true;
	}
}
?>
