<?php
namespace WorkFlowTask\programming\geturlcontents;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	//restconnector task changes this
	protected $method_obj = "MyCurl";
	protected $method_name = "getUrlContents";
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = $props["method_name"];
			
			if ($method_name == $this->method_name && $props["method_static"] && $props["method_obj"] == $this->method_obj) {
				$args = $props["method_args"];
				
				$data = $args[0]["value"];
				$data_type = $args[0]["type"];
				
				$result_type = $args[1]["value"];
				$result_type_type = $args[1]["type"];
				
				if ($data_type == "array") {
					$data_stmts = $WorkFlowTaskCodeParser->getPHPParserEmulative()->parse("<?php\n" . $data . "\n?>");
					$data = $WorkFlowTaskCodeParser->getArrayItems($data_stmts[0]->items);
				}
				
				unset($props["method_obj"]);
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["data"] = $data;
				$props["data_type"] = $data_type;
				$props["result_type"] = $result_type;
				$props["result_type_type"] = self::getConfiguredParsedType($result_type_type);
				
				$props["label"] = "Get curl url";
				
				$props["exits"] = array(
					self::DEFAULT_EXIT_ID => array(
						"color" => "#426efa",
					),
				);
				
				return $props;
			}
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = $task["raw_data"];
		
		$data_type = $raw_data["childs"]["properties"][0]["childs"]["data_type"][0]["value"];
		if ($data_type == "array") {
			$data = $raw_data["childs"]["properties"][0]["childs"]["data"];
			$data = self::parseArrayItems($data);
		}
		else
			$data = $raw_data["childs"]["properties"][0]["childs"]["data"][0]["value"];
		
		$properties = array(
			"data" => $data,
			"data_type" => $data_type,
			"result_type" => $raw_data["childs"]["properties"][0]["childs"]["result_type"][0]["value"],
			"result_type_type" => $raw_data["childs"]["properties"][0]["childs"]["result_type_type"][0]["value"],
		);
		
		$properties = self::parseResultVariableProperties($raw_data, $properties);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$var_name = self::getPropertiesResultVariableCode($properties);
		
		$dt_type = $properties["data_type"];
		if ($dt_type == "array")
			$dt = self::getArrayString($properties["data"]);
		else
			$dt = self::getVariableValueCode($properties["data"], $dt_type);
		
		$code  = $prefix_tab . $var_name . $this->method_obj . "::" . $this->method_name . "(" . $dt . ($properties["result_type"] ? ", " . self::getVariableValueCode($properties["result_type"], $properties["result_type_type"]) : "" ) . ");\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
