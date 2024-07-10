<?php
namespace WorkFlowTask\programming\restconnector;

include_once dirname(__DIR__) . "/geturlcontents/WorkFlowTaskImpl.php"; //Do not change this with get_lib bc we will cache this file and we wish to include the cached geturlcontents/WorkFlowTaskImpl.php file, otherwise if we use the get_lib we may include twice the geturlcontents/WorkFlowTaskImpl.php, this is, from lib and from cache.

class WorkFlowTaskImpl extends \WorkFlowTask\programming\geturlcontents\WorkFlowTaskImpl {
	
	public function __construct() {
		$this->method_obj = "RestConnector";
		$this->method_name = "connect";
	}
}
?>
