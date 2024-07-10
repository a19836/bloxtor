<?php
namespace __system\businesslogic;

include_once $vars["business_logic_modules_service_common_file_path"];
include_once get_lib("org.phpframework.localdb.LocalDBTableHandler");

class LocalDBAuthService extends CommonService {
	protected $LocalDBTableHandler;
	
	protected function initLocalDBTableHandler(&$data) {
		if (!$this->LocalDBTableHandler) {
			$root_path = isset($data["root_path"]) ? $data["root_path"] : null;
			$encryption_key = isset($data["encryption_key"]) ? $data["encryption_key"] : null;
			
			$this->LocalDBTableHandler = new \LocalDBTableHandler($root_path, $encryption_key);
		}
		
		unset($data["root_path"]);
		unset($data["encryption_key"]);
	}
}
?>
