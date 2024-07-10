<?php
namespace WorkFlowTask\programming\createblock;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = $props["method_name"];
			
			if ($method_name == "createBlock" && empty($props["method_static"])) {
				$args = $props["method_args"];
				
				if (count($args) != 3)
					return null;
				
				$module_id = $args[0]["value"];
				$module_id_type = $args[0]["type"];
				$block_id = $args[1]["value"];
				$block_id_type = $args[1]["type"];
				$block_settings = $args[2]["value"];
				$block_settings_type = $args[2]["type"];
				
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["module_id"] = $module_id;
				$props["module_id_type"] = self::getConfiguredParsedType($module_id_type);
				$props["block_id"] = $block_id;
				$props["block_id_type"] = self::getConfiguredParsedType($block_id_type);
				$props["block_settings"] = $block_settings;
				$props["block_settings_type"] = self::getConfiguredParsedType($block_settings_type);
				
				$props["label"] = "create block: " . self::prepareTaskPropertyValueLabelFromCodeStmt($module_id) . " => " . self::prepareTaskPropertyValueLabelFromCodeStmt($block_id);
				
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
		
		$properties = array(
			"method_obj" => $raw_data["childs"]["properties"][0]["childs"]["method_obj"][0]["value"],
			"module_id" => $raw_data["childs"]["properties"][0]["childs"]["module_id"][0]["value"],
			"module_id_type" => $raw_data["childs"]["properties"][0]["childs"]["module_id_type"][0]["value"],
			"block_id" => $raw_data["childs"]["properties"][0]["childs"]["block_id"][0]["value"],
			"block_id_type" => $raw_data["childs"]["properties"][0]["childs"]["block_id_type"][0]["value"],
			"block_settings" => $raw_data["childs"]["properties"][0]["childs"]["block_settings"][0]["value"],
			"block_settings_type" => $raw_data["childs"]["properties"][0]["childs"]["block_settings_type"][0]["value"],
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$method_obj = $properties["method_obj"];
		if ($method_obj) {
			$static_pos = strpos($method_obj, "::");
			$non_static_pos = strpos($method_obj, "->");
			$method_obj = substr($method_obj, 0, 1) != '$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
			$method_obj .= "->";
		}
		
		$code  = $prefix_tab . $method_obj . "createBlock(" . self::getVariableValueCode($properties["module_id"], $properties["module_id_type"]) . ", " . self::getVariableValueCode($properties["block_id"], $properties["block_id_type"]) . ", " . self::getVariableValueCode($properties["block_settings"], $properties["block_settings_type"]) . ");\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
