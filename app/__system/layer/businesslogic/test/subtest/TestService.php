<?php
namespace Test;

include_once $vars["business_logic_modules_service_common_file_path"];

class TestService extends \__system\businesslogic\CommonService {
	
	public function getQuerySQL($data) {
		return "getQuerySQL: TEST $data";
	}
}

namespace Test\a;

class TestService extends \__system\businesslogic\CommonService {
	
	public function getSQL($data) {
		return array("getSQL: TEST $data");
	}
}
?>
