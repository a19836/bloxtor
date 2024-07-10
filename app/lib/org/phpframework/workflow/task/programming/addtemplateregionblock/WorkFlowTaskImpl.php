<?php
namespace WorkFlowTask\programming\addtemplateregionblock;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$props = $WorkFlowTaskCodeParser->getObjectMethodProps($stmt);
		
		if ($props) {
			$method_name = $props["method_name"];
			
			if ($method_name == "addRegionBlock" && empty($props["method_static"])) {
				$args = $props["method_args"];
				
				$region = $args[0]["value"];
				$region_type = $args[0]["type"];
				$block = $args[1]["value"];
				$block_type = $args[1]["type"];
				
				unset($props["method_name"]);
				unset($props["method_args"]);
				unset($props["method_static"]);
				
				$props["region"] = $region;
				$props["region_type"] = self::getConfiguredParsedType($region_type);
				$props["block"] = $block;
				$props["block_type"] = self::getConfiguredParsedType($block_type);
				
				$props["label"] = "Add block: " . self::prepareTaskPropertyValueLabelFromCodeStmt($block) . " for region: " . self::prepareTaskPropertyValueLabelFromCodeStmt($region);
				
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
			"region" => $raw_data["childs"]["properties"][0]["childs"]["region"][0]["value"],
			"region_type" => $raw_data["childs"]["properties"][0]["childs"]["region_type"][0]["value"],
			"block" => $raw_data["childs"]["properties"][0]["childs"]["block"][0]["value"],
			"block_type" => $raw_data["childs"]["properties"][0]["childs"]["block_type"][0]["value"],
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
		
		$code  = $prefix_tab . $method_obj . "addRegionBlock(" . self::getVariableValueCode($properties["region"], $properties["region_type"]) . ", " . self::getVariableValueCode($properties["block"], $properties["block_type"]) . ");\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
