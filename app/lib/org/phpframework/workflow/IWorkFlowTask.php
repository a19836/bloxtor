<?php
interface IWorkFlowTask {
	public function parseProperties(&$task);
	public function printCode($tasks, $stop_task_id, $prefix_tab = "");
	
	public function createTaskPropertiesFromCodeStmt($stmt, $WorkFlowTaskCodeParser, &$exits = null, &$inner_tasks = null);//To be used by the WorkFlowTaskCodeTarser
}
?>
