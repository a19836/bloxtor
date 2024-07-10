<?php
namespace __system\businesslogic;
 
include_once $vars["business_logic_modules_service_common_file_path"];

class TestExtendCommonService extends CommonService {
	
	public function getQuerySQL($data) {
		$options = isset($data["options"]) ? $data["options"] : null;
		$type = isset($data["type"]) ? $data["type"] : null;
		$module = isset($data["module"]) ? $data["module"] : null;
		$service = isset($data["service"]) ? $data["service"] : null;
		$parameters = isset($data["parameters"]) ? $data["parameters"] : null;
		
		return $this->getBroker(isset($options["dal_broker"]) ? $options["dal_broker"] : null)->callQuerySQL($module, $type, $service, $parameters);
	}
}
?>
