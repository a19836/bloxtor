<?php
namespace WorkFlowTask\layer\dataaccess;

include_once get_lib("org.phpframework.workflow.WorkFlowTask");

class WorkFlowTaskImpl extends \WorkFlowTask {
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null) {
		return null;
	}
	
	public function parseProperties(&$task) {
		return null;
	}
	
	public function printCode($tasks, $stop_task_id, $prefix_tab = "", $options = null) {
		return null;
	}
}
?>
