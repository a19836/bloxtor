<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

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
