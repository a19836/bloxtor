<?php
namespace WorkFlowTask\programming\settemplateregionblockparam;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");
include_once get_lib("org.phpframework.layer.presentation.cms.CMSFileHandler");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	const MAIN_VARIABLE_NAME = "region_block_local_variables";
	
	public function __construct() {
		$this->priority = 2;
	}
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		$stmt_type = strtolower($stmt->getType());
		
		if ($stmt_type == "expr_assign") {
			$props = $WorkFlowTaskCodeParser->getVariableNameProps($stmt);
			$props = $props ? $props : array();
			
			$result_var_name = isset($props["result_var_name"]) ? $props["result_var_name"] : null;
			preg_match_all('/^' . self::MAIN_VARIABLE_NAME . '([ ]*)\[([^\]]*)\]([ ]*)\[([^\]]*)\]([ ]*)\[([^\]]*)\]([ ]*)$/iu', trim($result_var_name), $matches, PREG_PATTERN_ORDER);  //'/u' means with accents and ç too.
			
			if (empty($matches[0][0])) {
				return null;
			}
			
			$region = $matches[2][0];
			$region_type = \CMSFileHandler::getArgumentType($region);
			$region = \CMSFileHandler::prepareArgument($region, $region_type);
			
			$block = $matches[4][0];
			$block_type = \CMSFileHandler::getArgumentType($block);
			$block = \CMSFileHandler::prepareArgument($block, $block_type);
			
			$param_name = $matches[6][0];
			$param_name_type = \CMSFileHandler::getArgumentType($param_name);
			$param_name = \CMSFileHandler::prepareArgument($param_name, $param_name_type);
			
			$expr = isset($stmt->expr) ? $stmt->expr : null;
			$expr_type = $expr ? strtolower($expr->getType()) : "";
			
			$code = $WorkFlowTaskCodeParser->printCodeExpr($expr);
			$code = $WorkFlowTaskCodeParser->getStmtValueAccordingWithType($code, $expr_type);
			
			$props = array();
			$props["main_variable_name"] = self::MAIN_VARIABLE_NAME;
			$props["region"] = $region;
			$props["region_type"] = self::getConfiguredParsedType($region_type);
			$props["block"] = $block;
			$props["block_type"] = self::getConfiguredParsedType($block_type);
			$props["param_name"] = $param_name;
			$props["param_name_type"] = self::getConfiguredParsedType($param_name_type);
			$props["param_value"] = $code;
			$props["param_value_type"] = self::getConfiguredParsedType( $WorkFlowTaskCodeParser->getStmtType($expr) );
			
			$props["label"] = "Add param: " . self::prepareTaskPropertyValueLabelFromCodeStmt($param_name) . " for block " . self::prepareTaskPropertyValueLabelFromCodeStmt($block) . " in region " . self::prepareTaskPropertyValueLabelFromCodeStmt($region);
			
			$props["exits"] = array(
				self::DEFAULT_EXIT_ID => array(
					"color" => "#426efa",
				),
			);
	
			return $props;
		}
	}
	
	public function parseProperties(&$task) {
		$raw_data = isset($task["raw_data"]) ? $task["raw_data"] : null;
		
		$properties = array(
			"main_variable_name" => isset($raw_data["childs"]["properties"][0]["childs"]["main_variable_name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["main_variable_name"][0]["value"] : null,
			"region" => isset($raw_data["childs"]["properties"][0]["childs"]["region"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["region"][0]["value"] : null,
			"region_type" => isset($raw_data["childs"]["properties"][0]["childs"]["region_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["region_type"][0]["value"] : null,
			"block" => isset($raw_data["childs"]["properties"][0]["childs"]["block"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["block"][0]["value"] : null,
			"block_type" => isset($raw_data["childs"]["properties"][0]["childs"]["block_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["block_type"][0]["value"] : null,
			"param_name" => isset($raw_data["childs"]["properties"][0]["childs"]["param_name"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["param_name"][0]["value"] : null,
			"param_name_type" => isset($raw_data["childs"]["properties"][0]["childs"]["param_name_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["param_name_type"][0]["value"] : null,
			"param_value" => isset($raw_data["childs"]["properties"][0]["childs"]["param_value"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["param_value"][0]["value"] : null,
			"param_value_type" => isset($raw_data["childs"]["properties"][0]["childs"]["param_value_type"][0]["value"]) ? $raw_data["childs"]["properties"][0]["childs"]["param_value_type"][0]["value"] : null,
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = isset($this->data) ? $this->data : null;
		
		$properties = isset($data["properties"]) ? $data["properties"] : null;
		
		$main_var_name = '$' . (!empty($properties["main_variable_name"]) ? $properties["main_variable_name"] : self::MAIN_VARIABLE_NAME);
		
		$region = isset($properties["region"]) ? $properties["region"] : null;
		$region_type = isset($properties["region_type"]) ? $properties["region_type"] : null;
		$block = isset($properties["block"]) ? $properties["block"] : null;
		$block_type = isset($properties["block_type"]) ? $properties["block_type"] : null;
		$param_name = isset($properties["param_name"]) ? $properties["param_name"] : null;
		$param_name_type = isset($properties["param_name_type"]) ? $properties["param_name_type"] : null;
		$param_value = isset($properties["param_value"]) ? $properties["param_value"] : null;
		$param_value_type = isset($properties["param_value_type"]) ? $properties["param_value_type"] : null;
		
		$code  = $prefix_tab . $main_var_name . "[" . self::getVariableValueCode($region, $region_type) . "][" . self::getVariableValueCode($block, $block_type) . "][" . self::getVariableValueCode($param_name, $param_name_type) . "] = " . self::getVariableValueCode($param_value, $param_value_type) . ";\n";
		
		$exit_task_id = isset($data["exits"][self::DEFAULT_EXIT_ID]) ? $data["exits"][self::DEFAULT_EXIT_ID] : null;
		return $code . self::printTask($tasks, $exit_task_id, $stop_task_id, $prefix_tab, $options);
	}
}
?>
