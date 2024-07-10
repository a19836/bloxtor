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
			
			preg_match_all('/^' . self::MAIN_VARIABLE_NAME . '([ ]*)\[([^\]]*)\]([ ]*)\[([^\]]*)\]([ ]*)\[([^\]]*)\]([ ]*)$/iu', trim($props["result_var_name"]), $matches, PREG_PATTERN_ORDER);  //'/u' means with accents and ç too.
			
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
			
			$expr = $stmt->expr;
			$expr_type = strtolower($expr->getType());
			
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
		$raw_data = $task["raw_data"];
		
		$properties = array(
			"main_variable_name" => $raw_data["childs"]["properties"][0]["childs"]["main_variable_name"][0]["value"],
			"region" => $raw_data["childs"]["properties"][0]["childs"]["region"][0]["value"],
			"region_type" => $raw_data["childs"]["properties"][0]["childs"]["region_type"][0]["value"],
			"block" => $raw_data["childs"]["properties"][0]["childs"]["block"][0]["value"],
			"block_type" => $raw_data["childs"]["properties"][0]["childs"]["block_type"][0]["value"],
			"param_name" => $raw_data["childs"]["properties"][0]["childs"]["param_name"][0]["value"],
			"param_name_type" => $raw_data["childs"]["properties"][0]["childs"]["param_name_type"][0]["value"],
			"param_value" => $raw_data["childs"]["properties"][0]["childs"]["param_value"][0]["value"],
			"param_value_type" => $raw_data["childs"]["properties"][0]["childs"]["param_value_type"][0]["value"],
		);
		
		return $properties;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		$data = $this->data;
		
		$properties = $data["properties"];
		
		$main_var_name = '$' . ($properties["main_variable_name"] ? $properties["main_variable_name"] : self::MAIN_VARIABLE_NAME);
		
		$code  = $prefix_tab . $main_var_name . "[" . self::getVariableValueCode($properties["region"], $properties["region_type"]) . "][" . self::getVariableValueCode($properties["block"], $properties["block_type"]) . "][" . self::getVariableValueCode($properties["param_name"], $properties["param_name_type"]) . "] = " . self::getVariableValueCode($properties["param_value"], $properties["param_value_type"]) . ";\n";
		
		return $code . self::printTask($tasks, $data["exits"][self::DEFAULT_EXIT_ID], $stop_task_id, $prefix_tab, $options);
	}
}
?>
