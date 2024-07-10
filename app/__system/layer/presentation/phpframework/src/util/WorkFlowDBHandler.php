<?php
include_once $EVC->getUtilPath("WorkFlowTasksFileHandler");
include_once $EVC->getUtilPath("WorkFlowBeansConverter");
include_once $EVC->getUtilPath("PHPVariablesFileHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once get_lib("org.phpframework.db.DB");

class WorkFlowDBHandler {
	private $user_global_variables_file_path;
	private $user_beans_folder_path;
	private $error;
	
	const TASK_TABLE_TYPE = "02466a6d";
	const TASK_TABLE_TAG = "table";
	
	public function __construct($user_beans_folder_path, $user_global_variables_file_path) {
		$this->user_beans_folder_path = $user_beans_folder_path;
		$this->user_global_variables_file_path = $user_global_variables_file_path;
	}
	
	public function getError() {
		return $this->error;
	}
	
	public static function getTablesConnectionTypes() {
		return array(
			"One To One" => "1-1",
			"One To Many" => "1-*",
			"Many To One" => "*-1",
			"Many To Many" => "*-*",
		);
	}
	
	public function areTasksDBDriverSettingsValid($tasks_file_path, $create_db_if_not_exists, $only_active_dbs = true, &$invalid_task_label = null) {
		$status = true;
		
		$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($tasks_file_path);
		$WorkFlowTasksFileHandler->init();
		$tasks = $WorkFlowTasksFileHandler->getTasksByLayerTag("dbdriver");
		
		foreach ($tasks as $task)
			if (!$only_active_dbs || $task["properties"]["active"]) {
				if (!$this->isTaskDBDriverSettingsValid($task, $create_db_if_not_exists, $only_active_dbs)) {
					$status = false;
					$invalid_task_label = $task["label"];
					break;
				}
			}
		
		return $status;
	}
	
	public function isTaskDBDriverSettingsValid($task, $create_db_if_not_exists, $only_active_dbs = true) {
		$status = false;
		
		$task_layer_tags = WorkFlowTasksFileHandler::getTaskLayerTags();
		
		if ($task && $task["tag"] == $task_layer_tags["dbdriver"] && (!$only_active_dbs || $task["properties"]["active"]))
			return $this->isDBDriverSettingsValid($task["properties"], $create_db_if_not_exists);
		
		return $status;
	}
	
	public function isDBDriverSettingsValid($settings, $create_db_if_not_exists) {
		$status = false;
		
		if ($settings && $settings["type"]) {
			$is_ok = $GLOBALS["GlobalErrorHandler"]->ok();
			$exception_lanched = false;
			
			try {
				$PHPVariablesFileHandler = new PHPVariablesFileHandler($this->user_global_variables_file_path);
				$PHPVariablesFileHandler->startUserGlobalVariables();
				
				$DBDriver = DB::createDriverByType($settings["type"]);
				
				if ($DBDriver) {
					//replace variables in settings by the correspodnent GLOBAL var
					foreach($settings as $setting_key => $setting_value) {
						if (substr(trim($setting_value), 0, 1) == '$') {
							$global_var_name = substr(trim($setting_value), 1);
							
							if ($global_var_name)
								$settings[$setting_key] = $GLOBALS[$global_var_name];
						}
					}
					
					$DBDriver->setOptions($settings);
					
					try {
						$status = @$DBDriver->connect(); //if $create_db_if_not_exists is true and DB doesn't exists yet, the $DBDriver->connect() will give a php error, so we add the @to avoid the error. Then bellow the DB will be created and reconnected to the DB again without @. From the other hand, if the connect options are invalid it will give a PHP warning, so all the connect methods must have @.
					}
					catch (Exception $e) {
						$exception_lanched = true;
					}
					
					//Attempts to create a new database if doesnt exist yet
					if (!$status && $create_db_if_not_exists) {
						try {
							$options = $DBDriver->getOptions();
							$status = @$DBDriver->createDB($options["db_name"]) && @$DBDriver->connect(); //If the connect options are invalid it will give a PHP warning, so all the connect methods must have @.
						}
						catch (Exception $e) {
							$exception_lanched = true;
						}
					}
				}
				
				$PHPVariablesFileHandler->endUserGlobalVariables();
			}
			catch (Exception $e) {
				$exception_lanched = true;
			}
			
			if ($exception_lanched && $e) {
				debug_log($e, "exception");
				$this->error = $e->getMessage();
			}
			
			//when we call the DBDriver->connect method, if no DB created yet or any other connection error, will set the GlobalErrorHandler->stop() when it launches the correspondent exception. In this case, we need to set GlobalErrorHandler->start(), otherwise if this function gets called by another one that uses the GlobalErrorHandler (like calling BusinessLogicLayer->callBusinessLogic), the code will NOT continue bc the GlobalErrorHandler->ok() is false.
			if ($exception_lanched && $is_ok && !$GLOBALS["GlobalErrorHandler"]->ok())
				$GLOBALS["GlobalErrorHandler"]->start();
		}
		
		return $status;
	}
	
	public function areTasksDBDriverBeanValid($tasks_file_path, $create_db_if_not_exists, $only_active_dbs = true, &$invalid_task_label = null) {
		$status = true;
		
		$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($tasks_file_path);
		$WorkFlowTasksFileHandler->init();
		$tasks = $WorkFlowTasksFileHandler->getTasksByLayerTag("dbdriver");
		
		foreach ($tasks as $task)
			if (!$only_active_dbs || $task["properties"]["active"]) {
				if (!$this->isTaskDBDriverBeanValid($task, $create_db_if_not_exists, $only_active_dbs)) {
					$status = false;
					$invalid_task_label = $task["label"];
					break;
				}
			}
		
		return $status;
	}
	
	public function isTaskDBDriverBeanValid($task, $create_db_if_not_exists, $only_active_dbs = true) {
		$status = false;
		
		$task_layer_tags = WorkFlowTasksFileHandler::getTaskLayerTags();
		
		if ($task && $task["tag"] == $task_layer_tags["dbdriver"] && (!$only_active_dbs || $task["properties"]["active"])) {
			$beans_file_name = WorkFlowBeansConverter::getFileNameFromRawLabel($task["label"]) . "_dbdriver.xml";
			$object = $this->getBeanObject($beans_file_name, $task["label"]);
			
			if ($object) {
				$is_ok = $GLOBALS["GlobalErrorHandler"]->ok();
				$exception_lanched = false;
				
				try {
					$status = @$object->connect(); //if $create_db_if_not_exists is true and DB doesn't exists yet, the $object->connect() will give a php error, so we add the @to avoid the error. Then bellow the DB will be created and reconnected to the DB again without @. From the other hand, if the connect options are invalid it will give a PHP warning, so all the connect methods must have @.
				}
				catch (Exception $e) {
					$exception_lanched = true;
				}
				
				//Attempts to create a new database if doesnt exist yet
				if (!$status && $create_db_if_not_exists) {
					try {
						$options = $object->getOptions();
						$status = @$object->createDB($options["db_name"]) && @$object->connect(); //If the connect options are invalid it will give a PHP warning, so all the connect methods must have @.
					}
					catch (Exception $e) {
						$exception_lanched = true;
					}
				}
				
				if ($exception_lanched && $e) {
					debug_log($e, "exception");
					$this->error = $e->getMessage();
				}
				
				//when we call the DBDriver->connect method, if no DB created yet or any other connection error, will set the GlobalErrorHandler->stop() when it launches the correspondent exception. In this case, we need to set GlobalErrorHandler->start(), otherwise if this function gets called by another one that uses the GlobalErrorHandler (like calling BusinessLogicLayer->callBusinessLogic), the code will NOT continue bc the GlobalErrorHandler->ok() is false.
				if ($exception_lanched && $is_ok && !$GLOBALS["GlobalErrorHandler"]->ok())
					$GLOBALS["GlobalErrorHandler"]->start(); 
			}
		}
		
		return $status;
	}
	
	public function getFirstTaskDBDriverCredentials($tasks_file_path, $attr_prefix = "") {
		$properties = array();
		
		$task = $this->getFirstTaskDBDriver($tasks_file_path);
		
		if ($task)
			if (is_array($task["properties"]))
				foreach ($task["properties"] as $k => $v) 
					if ($k != "exits")
						$properties[$attr_prefix . $k] = $v;
		
		return $properties;
	}
	
	public function getFirstTaskDBDriver($tasks_file_path) {
		$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($tasks_file_path);
		$WorkFlowTasksFileHandler->init();
		$tasks = $WorkFlowTasksFileHandler->getTasksByLayerTag("dbdriver", 1);
		return $tasks[0];
	}
	
	public function getBeanObject($beans_file_name, $bean_name) {
		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($this->user_beans_folder_path . $beans_file_name, $this->user_global_variables_file_path);
		
		$bean_name = WorkFlowBeansConverter::getObjectNameFromRawLabel($bean_name);
		$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
		$this->error = $WorkFlowBeansFileHandler->getError();
		
		return $obj;
	}
	
	public function getDBTables($bean_file_name, $bean_name) {
		$DBDriver = $this->getBeanObject($bean_file_name, $bean_name);
	
		if ($DBDriver) {
			return $DBDriver->listTables();
		}
		
		return false;
	}
	
	public function getDBTableAttributes($bean_file_name, $bean_name, $table) {
		$DBDriver = $this->getBeanObject($bean_file_name, $bean_name);
		
		if ($DBDriver) {
			return $DBDriver->listTableFields($table);
		}
		
		return false;
	}
	
	public function getTaskDBDiagramSql($bean_file_name, $bean_name, $tasks_file_path) {
		$sql = "";
		
		$DBDriver = $this->getBeanObject($bean_file_name, $bean_name);
		
		if ($DBDriver) {
			$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($tasks_file_path);
			$WorkFlowTasksFileHandler->init();
			$tasks = $WorkFlowTasksFileHandler->getWorkflowData();
			$tasks = $tasks["tasks"];
			//print_r($tasks);
		
			if (is_array($tasks)) {
				foreach ($tasks as $task_id => $task) {
					$properties = $task["properties"];
				
					$table_data = array(
						"table_name" => $task["label"],
						"charset" => $properties["table_charset"],
						"collation" => $properties["table_collation"],
						"table_storage_engine" => $properties["table_storage_engine"],
						"attributes" =>array(),
					);
				
					$attrs = $properties["table_attr_names"];
					if ($attrs) {
						if (is_array($attrs)) {
							$t = count($attrs);
							for ($i = 0; $i < $t; $i++) {
								$table_data["attributes"][] = array(
									"name" => $attrs[$i],
									"primary_key" => strtolower($properties["table_attr_primary_keys"][$i]) == "true" || $properties["table_attr_primary_keys"][$i] == "1",
									"type" => $properties["table_attr_types"][$i],
									"length" => $properties["table_attr_lengths"][$i],
									"null" => strtolower($properties["table_attr_nulls"][$i]) == "true" || $properties["table_attr_nulls"][$i] == "1",
									"unsigned" => strtolower($properties["table_attr_unsigneds"][$i]) == "true" || $properties["table_attr_unsigneds"][$i] == "1",
									"unique" => strtolower($properties["table_attr_uniques"][$i]) == "true" || $properties["table_attr_uniques"][$i] == "1",
									"auto_increment" => strtolower($properties["table_attr_auto_increments"][$i]) == "true" || $properties["table_attr_auto_increments"][$i] == "1",
									"default" => strtolower($properties["table_attr_has_defaults"][$i]) == "true" || $properties["table_attr_has_defaults"][$i] == "1" ? $properties["table_attr_defaults"][$i] : null,
									"extra" => $properties["table_attr_extras"][$i],
									"charset" => $properties["table_attr_charsets"][$i],
									"collation" => $properties["table_attr_collations"][$i],
									"comment" => $properties["table_attr_comments"][$i],
								);
							}
						}
						else {
							$table_data["attributes"][] = array(
								"name" => $attrs,
								"primary_key" => strtolower($properties["table_attr_primary_keys"]) == "true" || $properties["table_attr_primary_keys"] == "1",
								"type" => $properties["table_attr_types"],
								"length" => $properties["table_attr_lengths"],
								"null" => strtolower($properties["table_attr_nulls"]) == "true" || $properties["table_attr_nulls"] == "1",
								"unsigned" => strtolower($properties["table_attr_unsigneds"]) == "true" || $properties["table_attr_unsigneds"] == "1",
								"unique" => strtolower($properties["table_attr_uniques"]) == "true" || $properties["table_attr_uniques"] == "1",
								"auto_increment" => strtolower($properties["table_attr_auto_increments"]) == "true" || $properties["table_attr_auto_increments"] == "1",
								"default" => strtolower($properties["table_attr_has_defaults"]) == "true" || $properties["table_attr_has_defaults"] == "1" ? $properties["table_attr_defaults"] : null,
								"extra" => $properties["table_attr_extras"],
								"charset" => $properties["table_attr_charsets"],
								"collation" => $properties["table_attr_collations"],
								"comment" => $properties["table_attr_comments"],
							);
						}
					}
					
					$sql .= ($sql ? "\n\n" : "") . 
							$DBDriver->getDropTableStatement($table_data["table_name"], $DBDriver->getOptions()) . ";\n" . 
							$DBDriver->getCreateTableStatement($table_data, $DBDriver->getOptions()) . ";";
				}
			}
		}
		
		return $sql;
	}
	
	public function getTaskDBDiagramSettings($tasks_file_path, $filter_by_setting = null) {
		$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($tasks_file_path);
		$WorkFlowTasksFileHandler->init();
		$data = $WorkFlowTasksFileHandler->getWorkflowData();
		
		if ($data["settings"] && $filter_by_setting)
			return $data["settings"][$filter_by_setting];
		
		return $data["settings"];
	}
	
	public function getUpdateTaskDBDiagram($bean_file_name, $bean_name, $tasks_file_path = false) {
		$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($tasks_file_path);
		$WorkFlowTasksFileHandler->init();
		$tasks = $WorkFlowTasksFileHandler->getWorkflowData();
		
		$DBDriver = $this->getBeanObject($bean_file_name, $bean_name);
		
		$tables = $DBDriver ? $DBDriver->listTables() : array();
		
		$tables_data = array();
		
		$total = count($tables);
		for ($i = 0; $i < $total; $i++) {
			$table = $tables[$i];
			
			if (!empty($table)) {
				$attrs = $DBDriver ? $DBDriver->listTableFields($table["name"]) : array();
				$fks = $DBDriver ? $DBDriver->listForeignKeys($table["name"]) : array();
				
				$tables_data[ $table["name"] ] = array($attrs, $fks, $table);
			}
		}
		
		return self::getUpdateTaskDBDiagramFromTablesData($tables_data, $tasks);
	}
	
	public function updateFileTasksDBDiagramTablesFromServer($bean_file_name, $bean_name, $tasks_file_path, $filter_by_tables = null) {
		$workflow_data = $this->getUpdateTaskDBDiagram($bean_file_name, $bean_name, $tasks_file_path);
		$tasks_details = $workflow_data["tasks"];
		
		//filter tasks_details by $filter_by_tables
		if ($tasks_details && $filter_by_tables) {
			//prepare tasks_by_table_name
			$tasks_by_table_name = array();
			
			foreach ($tasks_details as $task_id => $task)
				if ($task["label"])
					$tasks_by_table_name[ $task["label"] ] = $task_id;
			
			//prepare filter_by_tasks_ids
			$filter_by_tasks_ids = array();
			$filter_by_tables = is_array($filter_by_tables) ? $filter_by_tables : array($filter_by_tables); //$filter_by_tables can be a simple table name
			
			foreach ($filter_by_tables as $table_name) {
				$task_table_name = self::getTableTaskRealNameFromTasks($tasks_by_table_name, $table_name);
				$task_id = $tasks_by_table_name[$task_table_name];
				
				if ($task_id)
					$filter_by_tasks_ids[] = $task_id;
			}
			
			//prepare new tasks_details with only the tasks in $filter_by_tables
			$new_tasks_details = array();
			
			foreach ($filter_by_tasks_ids as $task_id)
				$new_tasks_details[$task_id] = $tasks_details[$task_id];
			
			$tasks_details = $new_tasks_details;
			$workflow_data["tasks"] = $tasks_details;
		}
		
		if (!file_exists($tasks_file_path))
			return WorkFlowTasksFileHandler::createTasksFile($tasks_file_path, $workflow_data);
		else if ($tasks_details) {
			//load old tasks from existent file
			$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($tasks_file_path);
			$WorkFlowTasksFileHandler->init();
			$tasks = $WorkFlowTasksFileHandler->getWorkflowData();
			$updated = false;
			
			foreach ($tasks_details as $task) {
				$task_id = $task["id"];
				$exists = false;
				
				foreach ($tasks["tasks"] as $old_task)
					if ($old_task["id"] == $task_id) {
						$updated = true;
						$exists = true;
						
						//update exits from old_task
						if (empty($task["exits"]))
							$task["exits"] = $old_task["exits"];
						
						$tasks["tasks"][$task_id] = $task;
						
						//update settings[old_tables_names] and settings[old_tables_attributes_names]
						if ($tasks["settings"]) {
							if (is_array($tasks["settings"]["old_tables_names"]) && $tasks["settings"]["old_tables_names"][$task_id])
								$tasks["settings"]["old_tables_names"][$task_id] = $task["label"];
							
							if (is_array($tasks["settings"]["old_tables_attributes_names"]) && $tasks["settings"]["old_tables_attributes_names"][$task_id])
								$tasks["settings"]["old_tables_attributes_names"][$task_id] = $task["properties"]["table_attr_names"];
						}
					}
				
				if (!$exists) {
					$tasks["tasks"][] = $task;
					$updated = true;
				}
			}
			
			return $updated ? WorkFlowTasksFileHandler::createTasksFile($tasks_file_path, $tasks) : true;
		}
		
		return false;
	}
	
	public function renameFileTasksDBDiagramTables($tasks_file_path, $renamed_tables) {
		if ($renamed_tables && file_exists($tasks_file_path)) {
			$renamed_tables = is_array($renamed_tables) ? $renamed_tables : array($renamed_tables); //$renamed_tables can be a simple table name
			
			//load tasks from existent file
			$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($tasks_file_path);
			$WorkFlowTasksFileHandler->init();
			$tasks = $WorkFlowTasksFileHandler->getWorkflowData();
			
			//prepare tasks_by_table_name
			$tasks_by_table_name = array();
			
			foreach ($tasks["tasks"] as $idx => $task)
				if ($task["label"])
					$tasks_by_table_name[ $task["label"] ] = $idx;
			
			//rename tasks
			$renamed = false;
			
			foreach ($renamed_tables as $old_table_name => $new_table_name) {
				$task_table_name = self::getTableTaskRealNameFromTasks($tasks_by_table_name, $old_table_name);
				$idx = $tasks_by_table_name[$task_table_name];
				
				if (array_key_exists($idx, $tasks["tasks"])) {
					$renamed = true;
					$tasks["tasks"][$idx]["label"] = $new_table_name;
					
					//update settings[old_tables_names]
					if ($tasks["settings"]) {
						$task_id = $tasks["tasks"][$idx]["id"];
						
						if (is_array($tasks["settings"]["old_tables_names"]) && $tasks["settings"]["old_tables_names"][$task_id])
							$tasks["settings"]["old_tables_names"][$task_id] = $new_table_name;
					}
				}
			}
			
			return $renamed ? WorkFlowTasksFileHandler::createTasksFile($tasks_file_path, $tasks) : true;
		}
		
		return true;
	}
	
	public function removeFileTasksDBDiagramTables($tasks_file_path, $removed_tables) {
		if ($removed_tables && file_exists($tasks_file_path)) {
			$removed_tables = is_array($removed_tables) ? $removed_tables : array($removed_tables); //$removed_tables can be a simple table name
			
			//load tasks from existent file
			$WorkFlowTasksFileHandler = new WorkFlowTasksFileHandler($tasks_file_path);
			$WorkFlowTasksFileHandler->init();
			$tasks = $WorkFlowTasksFileHandler->getWorkflowData();
			
			//prepare tasks_by_table_name
			$tasks_by_table_name = array();
			
			foreach ($tasks["tasks"] as $idx => $task)
				if ($task["label"])
					$tasks_by_table_name[ $task["label"] ] = $idx;
			
			//remove tasks
			$removed = false;
			
			foreach ($removed_tables as $table_name) {
				$task_table_name = self::getTableTaskRealNameFromTasks($tasks_by_table_name, $table_name);
				$idx = $tasks_by_table_name[$task_table_name];
				
				if (array_key_exists($idx, $tasks["tasks"])) {
					$removed = true;
					
					//update settings[old_tables_names] and settings[old_tables_attributes_names]
					if ($tasks["settings"]) {
						$task_id = $tasks["tasks"][$idx]["id"];
						
						if (is_array($tasks["settings"]["old_tables_names"]) && $tasks["settings"]["old_tables_names"][$task_id])
							unset($tasks["settings"]["old_tables_names"][$task_id]);
						
						if (is_array($tasks["settings"]["old_tables_attributes_names"]) && $tasks["settings"]["old_tables_attributes_names"][$task_id])
							unset($tasks["settings"]["old_tables_attributes_names"][$task_id]);
					}
					
					unset($tasks["tasks"][$idx]);
				}
			}
			
			return $removed ? WorkFlowTasksFileHandler::createTasksFile($tasks_file_path, $tasks) : true;
		}
		
		return true;
	}
	
	public function executeSyncTaskDBDiagramWithDBServerSQLStatements($bean_file_name, $bean_name, $statements, &$errors = null) {
		$DBDriver = $this->getBeanObject($bean_file_name, $bean_name);
		$errors = array();
		
		if ($statements)
			foreach ($statements as $table_name => $table_statements) {
				$sql_statements = $table_statements["sql_statements"];
				
				if ($sql_statements)
					foreach ($sql_statements as $sql) 
						if ($sql) {
							$status = true;
							
							try {
								$status = $DBDriver->setData($sql);
								//echo str_replace('\n', "\n", $sql) . ";\n";
								
								if (!$status)
									$errors[] = $sql;
							}
							catch(Exception $e) {
								$errors[] = (is_a($e, "Exception") ? $e->getMessage() . "\n\n" : "") . $sql;
							}
							
							//only execute next statement if previously was successfully executed
							if (!$status)
								break;
						}
			}
		
		return empty($errors);
	}
	
	public function syncTaskDBDiagramWithDBServer($bean_file_name, $bean_name, $tasks, &$parsed_data = array(), &$errors = null) {
		//prepare sql
		$statements = $this->getSyncTaskDBDiagramWithDBServerSQLStatements($bean_file_name, $bean_name, $tasks, $parsed_data);
		
		//execute sql
		$DBDriver = $this->getBeanObject($bean_file_name, $bean_name);
		$errors = array();
		
		if ($statements)
			foreach ($statements as $table_name => $table_statements) {
				$sql_statements = $table_statements["sql_statements"];
				$parsed_data[$table_name]["sql_statements"] = $sql_statements;
				$parsed_data[$table_name]["sql_statements_labels"] = $table_statements["sql_statements_labels"];
				
				if ($sql_statements)
					foreach ($sql_statements as $sql) 
						if ($sql) {
							$status = true;
							
							try {
								$status = $DBDriver->setData($sql);
								//echo str_replace('\n', "\n", $sql) . ";\n";
								
								if (!$status)
									$errors[] = $sql;
							}
							catch(Exception $e) {
								$errors[] = (is_a($e, "Exception") ? $e->getMessage() . "\n\n" : "") . $sql;
							}
							
							//only execute next statement if previously was successfully executed
							if (!$status)
								break;
						}
			}
		
		return empty($errors);
	}
	
	public function getSyncTaskDBDiagramWithDBServerSQLStatements($bean_file_name, $bean_name, $tasks, &$parsed_data = array()) {
		$DBDriver = $this->getBeanObject($bean_file_name, $bean_name);
		
		if ($DBDriver && $tasks) {
			//prepare tasks tables
			$tasks_by_table_name = array();
			$new_tables_names = array();
			
			if ($tasks && $tasks["tasks"])
				foreach ($tasks["tasks"] as $task_id => $task) {
					$table_name = $task["old_label"] ? $task["old_label"] : $task["label"];
					
					$tasks_by_table_name[$table_name] = $task;
					
					//prepare new tables name
					if ($table_name != $task["label"])
						$new_tables_names[$table_name] = $task["label"];
				}
			
			$tasks_tables = self::getTasksAsTables($tasks_by_table_name);
			
			//prepare new attributes name
			foreach ($tasks_tables as $table_name => $attrs) {
				$task = $tasks_by_table_name[$table_name];
				$table_attr_names = $task["properties"]["table_attr_names"];
				$table_attr_old_names = $task["properties"]["table_attr_old_names"];
				
				foreach ($attrs as $attr_name => $attr) {
					$index = array_search($attr_name, $table_attr_names);
					
					if (is_numeric($index)) {
						$old_name = $table_attr_old_names[$index];
						
						if ($old_name)
							$tasks_tables[$table_name][$attr_name]["old_name"] = $old_name;
					}
				}
			}
			
			//get server data
			$tables = $DBDriver->listTables();
			$total = count($tables);
			$server_tables = array();
			
			for ($i = 0; $i < $total; $i++) {
				$table = $tables[$i];
				
				if (!empty($table)) {
					$table_name = $table["name"];
					$task_table = self::getTableFromTables($tasks_tables, $table_name);
					
					if ($task_table) {
						//get server attributes
						$attrs = $DBDriver->listTableFields($table_name);
						$fks = $DBDriver->listForeignKeys($table_name);
						
						if (is_array($fks))
							foreach ($fks as $fk)
								if (!empty($fk)) {
									$child_column = $fk["child_column"];
									
									if ($child_column && $attrs[$child_column]) {
										if (!$attrs[$child_column]["fk"])
											$attrs[$child_column]["fk"] = array();
										
										$attrs[$child_column]["fk"][] = array(
											"attribute" => $fk["parent_column"],
											"table" => $fk["parent_table"]
										);
									}
								}
						
						$server_tables[$table_name] = $attrs;
					}
				}
			}
			
			//prepare sql
			return self::getTablesUpdateSQLStatements($DBDriver, $server_tables, $tasks_tables, $new_tables_names, $parsed_data);
		}
		
		return false;
	}
	
	public static function getTablesUpdateSQLStatements($DBDriver, $old_tables, $new_tables, $new_tables_names = null, &$parsed_data = array()) {
		$statements = array();
		
		if (!is_array($parsed_data))
			$parsed_data = array();
		
		//echo "<pre>";print_r($new_tables);print_r($old_tables);print_r($new_tables_names);die();
		
		//prepare sqls for existent tables
		foreach ($old_tables as $table_name => $old_attrs) {
			$real_table_name = self::getTableTaskRealNameFromTasks($new_tables, $table_name);
			
			//get new table name
			$new_table_name = $new_tables_names[$real_table_name];
			
			//get task attributes
			$new_attrs = $new_tables[$real_table_name];
			
			//get statements
			$statements[$table_name] = self::getTableUpdateSQLStatements($DBDriver, $table_name, $old_attrs, $new_attrs, $new_table_name, $table_parsed_data);
			$parsed_data[$table_name] = $table_parsed_data;
		}
		
		//prepare sqls for new tables
		foreach ($new_tables as $table_name => $new_attrs) {
			$real_table_name = self::getTableTaskRealNameFromTasks($old_tables, $table_name);
			
			//get new table name
			$new_table_name = $new_tables_names[$table_name];
			
			//check if table already exists in server
			$table_already_exists = array_key_exists($real_table_name, $old_tables);
			
			if (!$table_already_exists) {
				$new_table_already_exists = false;
				
				//prepare sql for table which already exists based in the new name
				if ($new_table_name && $new_table_name != $table_name) {
					$real_new_table_name = self::getTableTaskRealNameFromTasks($old_tables, $new_table_name);
					$new_table_already_exists = array_key_exists($real_new_table_name, $old_tables);
					
					if ($new_table_already_exists) {
						//get task attributes
						$old_attrs = $old_tables[$real_new_table_name];
						
						//get statements
						$statements[$real_new_table_name] = self::getTableUpdateSQLStatements($DBDriver, $real_new_table_name, $old_attrs, $new_attrs, null, $table_parsed_data);
						$parsed_data[$real_new_table_name] = $table_parsed_data;
						
						continue;
					}
					else //if new name is different and still table doesn't exists, update $table_name with new name.
						$table_name = $new_table_name;
				}
				
				if (!$new_table_already_exists) {
					//get statements
					$statements[$table_name] = self::getTableAddSQLStatements($DBDriver, $table_name, $new_attrs, $table_parsed_data);
					$parsed_data[$table_name] = $table_parsed_data;
				}
			}
		}
		
		return $statements;
	}
	
	//used in edit_table.php and CommonModuleAdminTableExtraAttributesUtil.php
	public static function getTableAddSQLStatements($DBDriver, $table_name, $new_attrs, &$parsed_data = array()) {
		$sql_statements = array();
		$sql_statements_labels = array();
		
		//get some db driver settings
		$sql_options = $DBDriver->getOptions();
		
		//prepare attributes with array_values. Convert associative array into numeric index array
		$new_attrs = is_array($new_attrs) ? $new_attrs : array();
		
		//remove all attributes without name
		foreach ($new_attrs as $idx => $new_attr) 
			if (!trim($new_attr["name"]))
				unset($new_attrs[$idx]);
		
		//prepare attributes with array_values. This will be usefull to sort the attributes.
		$new_attrs = array_values($new_attrs);
		//echo "<pre>";print_r($new_attrs);die();
		
		$data = array(
			"table_name" => $table_name,
			"attributes" => $new_attrs
		);
		
		$sql_statements[] = $DBDriver->getCreateTableStatement($data, $sql_options);
		$sql_statements_labels[] = "Create table " . $table_name;
		
		$parsed_data = array(
			"sql_options" => $sql_options,
			"table_name" => $table_name,
			"new_attrs" => $new_attrs,
		);
		
		return array(
			"sql_statements" => $sql_statements,
			"sql_statements_labels" => $sql_statements_labels,
		);
	}
	
	//used in edit_table.php and CommonModuleAdminTableExtraAttributesUtil.php
	public static function getTableUpdateSQLStatements($DBDriver, $table_name, $old_attrs, $new_attrs, $new_table_name = null, &$parsed_data = array()) {
		$sql_statements = array();
		$sql_statements_labels = array();
		
		//get some db driver settings
		$sql_options = $DBDriver->getOptions();
		$allow_sort = $DBDriver->allowTableAttributeSorting();
		$column_types_ignored_props = $DBDriver->getDBColumnTypesIgnoredProps();
		
		//prepare attributes with array_values. Convert associative array into numeric index array
		$old_attrs = is_array($old_attrs) ? $old_attrs : array();
		$new_attrs = is_array($new_attrs) ? $new_attrs : array();
		
		//remove all attributes without name
		foreach ($old_attrs as $idx => $old_attr) 
			if (!trim($old_attr["name"]))
				unset($old_attrs[$idx]);
		
		foreach ($new_attrs as $idx => $new_attr) 
			if (!trim($new_attr["name"]))
				unset($new_attrs[$idx]);
		
		//prepare attributes with array_values. This will be usefull to sort the attributes.
		$old_attrs = array_values($old_attrs);
		$new_attrs = array_values($new_attrs);
		//echo "<pre>";print_r($old_attrs);print_r($new_attrs);die();
		
		//prepare differences
		$attributes_to_add = array();
		$attributes_to_modify = array();
		$attributes_to_rename = array();
		$attributes_data_to_rename = array();
		$attributes_to_delete = array();
		$new_pks = $new_pks_attrs = $old_pks = $auto_increment_pks = array();
		$sort_index_inc = 0;
		
		//get attributes to add and modify
		foreach ($new_attrs as $new_attr_idx => $new_attr) {
			$exists = false;
			$is_different = false;
			$old_attr_idx = -1;
			
			if ($old_attrs) {
				$lower_name = strtolower($new_attr["name"]);
				$lower_old_name = strtolower($new_attr["old_name"]);
				$old_name_index = -1;
				$name_index = -1;
				
				foreach ($old_attrs as $idy => $old_attr) {
					if ($lower_old_name == strtolower($old_attr["name"])) {
						$old_name_index = $idy;
						break;
					}
					else if ($lower_name == strtolower($old_attr["name"]))
						$name_index = $idy;
				}
				
				$old_attr = $old_name_index != -1 ? $old_attrs[$old_name_index] : ($name_index != -1 ? $old_attrs[$name_index] : null);
				
				if ($old_attr) {
					$exists = true;
					$old_attr_idx = $old_name_index != -1 ? $old_name_index : $name_index;
					
					if ($new_attr["name"] != $old_attr["name"]) { //in case the user change the case of some letter.
						$attributes_to_rename[ $old_attr["name"] ] = $new_attr["name"];
						$attributes_data_to_rename[ $old_attr["name"] ] = $old_attr;
					}
					
					//prepare new name with old_name, just in case the user changed the lettering case.
					$new_attr["name"] = $old_attr["name"];
					
					//prepare non-editable attributes in $new_attr. Sets the defaults from $old_attr.
					if (is_array($column_types_ignored_props[ $old_attr["type"] ]))
						foreach ($column_types_ignored_props[ $old_attr["type"] ] as $attr_to_ignore) {
							//but only update the old defaults if the type is the same or if is not the length, this is, if the type is different and the length exists in new_attr, then the length should be from the new_attr. Example: If I change an attribute with type=text to a type=varchar(50), I want to have the new length of 50 chars.
							if ($new_attr["type"] == $old_attr["type"] || $attr_to_ignore != "length")
								$new_attr[$attr_to_ignore] = $old_attr[$attr_to_ignore];
						}
					
					$was_auto_increment_pk = $old_attr["primary_key"] && ($old_attr["auto_increment"] || stripos($old_attr["extra"], "auto_increment") !== false);
					
					if ($new_attr["extra"] && stripos($new_attr["extra"], "auto_increment") !== false)
						$new_attr["extra"] = trim(preg_replace("/\s+/i", " ", preg_replace("/(^|\s)auto_increment(\s|$)/i", " ", $new_attr["extra"])));
					
					if ($old_attr["extra"] && stripos($old_attr["extra"], "auto_increment") !== false)
						$old_attr["extra"] = trim(preg_replace("/\s+/i", " ", preg_replace("/(^|\s)auto_increment(\s|$)/i", " ", $old_attr["extra"])));
					
					//check if old attribute is auto increment pk
					if ($was_auto_increment_pk) 
						$auto_increment_pks[ $old_attr["name"] ] = $old_attr;
					
					//check if there is something different
					if (
						$new_attr["primary_key"] != $old_attr["primary_key"] || 
						$new_attr["type"] != $old_attr["type"] || 
						$new_attr["length"] != $old_attr["length"] || 
						$new_attr["null"] != $old_attr["null"] || 
						$new_attr["unsigned"] != $old_attr["unsigned"] || 
						$new_attr["unique"] != $old_attr["unique"] || 
						$new_attr["auto_increment"] != $old_attr["auto_increment"] || 
						$new_attr["default"] != $old_attr["default"] || 
						$new_attr["extra"] != $old_attr["extra"] || 
						($new_attr["charset"] && $new_attr["charset"] != $old_attr["charset"]) || 
						($new_attr["collation"] && $new_attr["collation"] != $old_attr["collation"]) || 
						$new_attr["comment"] != $old_attr["comment"]
					)
						$is_different = true;
				}
			}
			
			//prepare attributes to sort - must be before add new_attr to attributes_to_add or attributes_to_modify
			if ($new_attr_idx != $old_attr_idx) {
				$previous_idx = $new_attr_idx - 1;
				
				if ($previous_idx < 0) {
					$new_attr["first"] = true;
					$sort_index_inc++;
					
					if ($exists)
						$is_different = true;
				}
				else if ($new_attr_idx - $sort_index_inc != $old_attr_idx) { //fix the sort bc all the attributes after the sorted attributes are getting sorted again and they don't need it.
					$new_attr["after"] = $new_attrs[$previous_idx]["name"];
					$sort_index_inc++;
					
					if ($exists)
						$is_different = true;
				}
			}
			
			if (!$exists)
				$attributes_to_add[] = $new_attr;
			else if ($is_different)
				$attributes_to_modify[] = $new_attr;
			
			if ($new_attr["primary_key"]) {
				$new_pks[] = $new_attr["name"];
				$new_pks_attrs[] = $new_attr;
			}
			
			if ($exists && $old_attr["primary_key"])
				$old_pks[] = $old_attr["name"];
		}
		
		//get attributes to delete
		foreach ($old_attrs as $idx => $old_attr) {
			$exists = false;
			$lower_name = strtolower($old_attr["name"]);
			
			if ($new_attrs)
				foreach ($new_attrs as $idy => $new_attr)
					if (strtolower($new_attr["old_name"]) == $lower_name || strtolower($new_attr["name"]) == $lower_name) {
						$exists = true;
						break;	
					}
			
			if (!$exists)
				$attributes_to_delete[] = $old_attr;
		}
		
		//update attributes
		if ($attributes_to_add || $attributes_to_modify || $attributes_to_rename || $attributes_to_delete) {
			$attrs_with_auto_increment_to_modify = array();
			$pks_dropped = false;
			
			//echo "<pre>attributes_to_add:".print_r($attributes_to_add, 1)."\nattributes_to_modify:".print_r($attributes_to_modify, 1)."\nattributes_to_rename:".print_r($attributes_to_rename, 1)."\nattributes_to_delete:".print_r($attributes_to_delete, 1); die();
			//echo "<pre>";print_r($new_pks);print_r($old_pks);die();
			//drop pks before modify attributes bc the drop will remove the sequences contraints and the modify will add them again
			//THIS IS VERY IMPORTANT TO BE HERE BEFORE THE getModifyTableAttributeStatement, OTHERWISE IN POSTGRES, WHEN CHANGING PKS, WE WILL LOOSE THE AUTO_INCREMENT SEQUENCES.
			//The getDropTablePrimaryKeysStatement should be also before the getAddTableAttributeStatement because this may contain a primary key too, and then we are erasing the that primary key, which is wrong.
			if (count($old_pks) && (
				count($new_pks) != count($old_pks) || array_diff($new_pks, $old_pks)
			)) {
				//Before removing the pks we must first delete all the auto increments keys, bc I cannot remove pk if there are auto_increment keys in Mysql. To remove the auto_increment key, we only need to execute a modify statement. For more info please check: https://www.techbrothersit.com/2019/01/how-to-drop-or-disable-autoincrement.html
				if ($auto_increment_pks)
					foreach ($auto_increment_pks as $attr_name => $attr) {
						$attr["auto_increment"] = false;
						$attr["extra"] = preg_replace("/(^|\s)auto_increment($|\s)/i", "", $attr["extra"]);
						
						$sql_statements[] = $DBDriver->getModifyTableAttributeStatement($table, $attr);
						$sql_statements_labels[] = "Remove auto_increment prop from primary key in table $table_name";
					}
				
				$sql_statements[] = $DBDriver->getDropTablePrimaryKeysStatement($table_name, $sql_options);
				$sql_statements_labels[] = "Drop primary keys in table $table_name";
				
				$pks_dropped = true;
			}
			
			foreach ($attributes_to_add as $attr) {
				//remove auto_increment property bc it can only be added to a KEY (primary key or other key)
				if ($attr["auto_increment"] || stripos($attr["extra"], "auto_increment") !== false) {
					$attrs_with_auto_increment_to_modify[] = $attr;
					
					$attr["extra"] = preg_replace("/(^|\s)auto_increment(\s|$)/i", " ", $attr["extra"]);
					$attr["auto_increment"] = false;
				}
				
				$sql_statements[] = $DBDriver->getAddTableAttributeStatement($table_name, $attr, $sql_options);
				$sql_statements_labels[] = "Add attribute " . $attr["name"] . " to table $table_name";
			}
			
			foreach ($attributes_to_modify as $attr) {
				//remove auto_increment property bc it can only be added to a KEY (primary key or other key)
				if ($pks_dropped && ($attr["auto_increment"] || stripos($attr["extra"], "auto_increment") !== false)) {
					$attrs_with_auto_increment_to_modify[] = $attr;
					
					$attr["extra"] = preg_replace("/(^|\s)auto_increment(\s|$)/i", " ", $attr["extra"]);
					$attr["auto_increment"] = false;
				}
				
				$sql_statements[] = $DBDriver->getModifyTableAttributeStatement($table_name, $attr, $sql_options);
				$sql_statements_labels[] = "Modify attribute " . $attr["name"] . " in table $table_name";
			}
			
			//add new pks
			if ($new_pks &&(count($new_pks) != count($old_pks) || array_diff($new_pks, $old_pks))) {
				$sql_statements[] = $DBDriver->getAddTablePrimaryKeysStatement($table_name, $new_pks_attrs, $sql_options);
				$sql_statements_labels[] = "Add primary key in table $table_name";
			}
			
			//add auto_increment to attrs after the getAddTablePrimaryKeysStatement gets executed
			if ($attrs_with_auto_increment_to_modify)
				foreach ($attrs_with_auto_increment_to_modify as $attr) {
					$sql_statements[] = $DBDriver->getModifyTableAttributeStatement($table_name, $attr, $sql_options);
					$sql_statements_labels[] = "Modify attribute " . $attr["name"] . " in table $table_name with auto_increment property";
				}
			
			//remove attrs must be first than the rename, so we can remove an attribute and then rename another one to the same name of the attribute that we removed.
			foreach ($attributes_to_delete as $attr) {
				$fks_to_add = array();
				$repeated_constraints_name = array();
				
				if ($attr["fk"]) {
					$fks = $fks ? $fks : $DBDriver->listForeignKeys($table_name);
					
					if ($fks) {
						//print_r($fks);
						for ($i = 0, $t = count($fks); $i < $t; $i++) {
							$fk = $fks[$i];
						
							if ($fk["child_column"] == $attr["name"])
								if ($fk["constraint_name"] && !in_array($fk["constraint_name"], $repeated_constraints_name)) {
									$repeated_constraints_name[] = $fk["constraint_name"];
									$sql_statements[] = $DBDriver->getDropTableForeignConstraintStatement($table_name, $fk["constraint_name"]);
									$sql_statements_labels[] = "Drop foreign key for " . $attr["name"] . " in table $table_name";
									
									//add foreign key for the other attributes, if apply
									for ($j = 0; $j < $t; $j++) 
										if ($j != $i) {
											$sub_fk = $fks[$j];
											
											if ($sub_fk["parent_table"] == $fk["parent_table"] && $sub_fk["child_column"] != $attr["name"]) {
												$fks_to_add[ $fk["parent_table"] ][ $sub_fk["child_column"] ] = $sub_fk["parent_column"];
											}
										}
								}
						}
					}
				}
				
				$sql_statements[] = $DBDriver->getDropTableAttributeStatement($table_name, $attr["name"], $sql_options);
				$sql_statements_labels[] = "Drop attribute " . $attr["name"] . " in table $table_name";
				
				//must be after the getDropTableAttributeStatement
				if ($fks_to_add)
					foreach ($fks_to_add as $fk_table => $fks_attr_name_to_add) {
						if (!empty($fks_attr_name_to_add)) {
							$constraint_name = $repeated_constraints_name[0];
							$fk = array(
								"child_column" => array_keys($fks_attr_name_to_add),
								"parent_column" => array_values($fks_attr_name_to_add),
								"parent_table" => $fk_table,
								"constraint_name" => $constraint_name ? $constraint_name : "fk_{$table_name}_{$fk_table}_" . implode("_", array_keys($fks_attr_name_to_add)),
							);
							
							$sql_statements[] = $DBDriver->getAddTableForeignKeyStatement($table_name, $fk);
							$sql_statements_labels[] = "Re-Add foregin key " . $fk["name"] . " with other attributes in table $table_name";
						}
					}
			}
			
			foreach ($attributes_to_rename as $old_name => $new_name) {
				$attr = $attributes_data_to_rename[$old_name];
				$sql_statements[] = $DBDriver->getRenameTableAttributeStatement($table_name, $old_name, $new_name, $attr, $sql_options);
				$sql_statements_labels[] = "Rename attribute $old_name in table $table_name";
			}
		}
		
		//update table name
		if ($new_table_name && $table_name != $new_table_name) {
			$sql_statements[] = $DBDriver->getRenameTableStatement($table_name, $new_table_name, $sql_options);
			$sql_statements_labels[] = "Rename table " . $table_name . " to " . $new_table_name;
		}
		
		$parsed_data = array(
			"sql_options" => $sql_options,
			"allow_sort" => $allow_sort,
			"table_name" => $table_name,
			"new_table_name" => $new_table_name,
			"old_attrs" => $old_attrs,
			"new_attrs" => $new_attrs,
			"attributes_to_add" => $attributes_to_add,
			"attributes_to_modify" => $attributes_to_modify,
			"attributes_to_rename" => $attributes_to_rename,
			"attributes_data_to_rename" => $attributes_data_to_rename,
			"attributes_to_delete" => $attributes_to_delete,
			"new_pks" => $new_pks,
			"new_pks_attrs" => $new_pks_attrs,
			"old_pks" => $old_pks
		);
		
		return array(
			"sql_statements" => $sql_statements,
			"sql_statements_labels" => $sql_statements_labels,
		);
	}
	
	public static function getTableTaskRealNameFromTasks($tasks, $table_name) {
		//if no table in tasks, check table without schema
		if (!array_key_exists($table_name, $tasks)) 
			foreach ($tasks as $task_table_name => $task)
				if (DB::isTheSameStaticTableName($table_name, $task_table_name, array("simple_comparison" => true)))
					return $task_table_name;
		
		return $table_name;
	}
	
	public static function getTableFromTables($tables, $table_name) {
		$real_table_name = self::getTableTaskRealNameFromTasks($tables, $table_name);
		
		return $tables[$real_table_name];
	}
	
	public static function getTableAttributes($tasks, $table_name) {
		$table = self::getTableFromTables($tasks, $table_name);
		return $table["properties"]["table_attr_names"];
	}
	
	public static function getTablePrimaryKeys($tasks, $table_name) {
		$table = self::getTableFromTables($tasks, $table_name);
		$table_attr_primary_keys = $table["properties"]["table_attr_primary_keys"];
		
		$pks = array();
		
		if ($table_attr_primary_keys) {
			$t = count($table_attr_primary_keys);
			for ($i = 0; $i < $t; $i++) {
				$is_pk = $table_attr_primary_keys[$i];
				
				if ($is_pk == "1" || strtolower($is_pk) == "true")
					$pks[] = $table["properties"]["table_attr_names"][$i];
			}
		}
		
		return $pks;
	}
	
	public static function getUpdateTaskDBDiagramFromTablesData($tables_data, $tasks = false) {
		$top = 10;
		$left = 10;
		
		$new_tasks = array();
		$tasks_by_table_name = array();
		
		if ($tasks && $tasks["tasks"])
			foreach ($tasks["tasks"] as $task_id => $task)
				if ($task["label"])
					$tasks_by_table_name[ $task["label"] ] = $task_id;
		
		if (isset($tasks["containers"])) {
			$new_tasks["containers"] = $tasks["containers"];
		}
		
		foreach ($tables_data as $table => $table_data) {
			$attrs = $table_data[0];
			$fks = $table_data[1];
			$table_info = is_array($table_data[2]) ? $table_data[2] : array();
			
			$task_table_name = self::getTableTaskRealNameFromTasks($tasks_by_table_name, $table);
			$task_id = $tasks_by_table_name[$task_table_name];
			
			if (!empty($task_id)) {
				$task_left = $tasks["tasks"][$task_id]["offset_left"];
				$task_top = $tasks["tasks"][$task_id]["offset_top"];
			}
			else {
				$left += 250;
				
				if ($left > 1200) {
					$top += 300;
					$left = 10;
				}
				
				$task_left = $left;
				$task_top = $top;
			}
			
			$real_task_id = !empty($task_id) ? $task_id : $table;
			
			$new_tasks["tasks"][$real_task_id] = array(
				"label" => $table,
				"id" => $real_task_id,
				"type" => self::TASK_TABLE_TYPE,
				"tag" => self::TASK_TABLE_TAG,
				"offset_left" => $task_left, 
				"offset_top" => $task_top,
				"properties" => array(
					"exits" => array(
						"layer_exit" => array(
							"color" => "#31498f",
							"type" => "Flowchart",
							"overlay" => "No Arrows",
						)
					),
					"table_charset" => $table_info["charset"],
					"table_collation" => $table_info["collation"],
					"table_storage_engine" => $table_info["engine"],
					"table_attr_primary_keys" => array(),
					"table_attr_names" => array(),
					"table_attr_types" => array(),
					"table_attr_lengths" => array(),
					"table_attr_nulls" => array(),
					"table_attr_unsigneds" => array(),
					"table_attr_uniques" => array(),
					"table_attr_auto_increments" => array(),
					"table_attr_has_defaults" => array(),
					"table_attr_defaults" => array(),
					"table_attr_extras" => array(),
					"table_attr_charsets" => array(),
					"table_attr_comments" => array(),
				)
			);
			
			if (is_array($attrs)) {
				foreach ($attrs as $attr_name => $attr) {
					if (!empty($attr_name)) {
						$idx = $new_tasks["tasks"][$real_task_id]["properties"]["table_attr_names"] ? count($new_tasks["tasks"][$real_task_id]["properties"]["table_attr_names"]) : 0;
						
						$new_tasks["tasks"][$real_task_id]["properties"]["table_attr_primary_keys"][$idx] = !empty($attr["primary_key"]) ? "1" : ""; //must be 1 or empty string bc the DB diagram saves the diagram with this values. Do not add true or false to the DB diagram xml.
						$new_tasks["tasks"][$real_task_id]["properties"]["table_attr_names"][$idx] = $attr_name;
						$new_tasks["tasks"][$real_task_id]["properties"]["table_attr_types"][$idx] = $attr["type"];
						$new_tasks["tasks"][$real_task_id]["properties"]["table_attr_lengths"][$idx] = $attr["length"];
						$new_tasks["tasks"][$real_task_id]["properties"]["table_attr_nulls"][$idx] = !empty($attr["null"]) ? "1" : "";
						$new_tasks["tasks"][$real_task_id]["properties"]["table_attr_unsigneds"][$idx] = !empty($attr["unsigned"]) ? "1" : "";
						$new_tasks["tasks"][$real_task_id]["properties"]["table_attr_uniques"][$idx] = !empty($attr["unique"]) ? "1" : "";
						$new_tasks["tasks"][$real_task_id]["properties"]["table_attr_auto_increments"][$idx] = !empty($attr["auto_increment"]) ? "1" : "";
						$new_tasks["tasks"][$real_task_id]["properties"]["table_attr_has_defaults"][$idx] = isset($attr["default"]) && empty($attr["primary_key"]) ? "1" : "";
						$new_tasks["tasks"][$real_task_id]["properties"]["table_attr_defaults"][$idx] = $attr["default"];
					
						$new_tasks["tasks"][$real_task_id]["properties"]["table_attr_extras"][$idx] = $attr["extra"];
						$new_tasks["tasks"][$real_task_id]["properties"]["table_attr_charsets"][$idx] = $attr["charset"];
						$new_tasks["tasks"][$real_task_id]["properties"]["table_attr_collations"][$idx] = $attr["collation"];		
						$new_tasks["tasks"][$real_task_id]["properties"]["table_attr_comments"][$idx] = $attr["comment"];
					}
				}
			}
			
			if (is_array($fks)) {
				$fks_by_table = array();
				
				foreach ($fks as $fk)
					if (!empty($fk))
						$fks_by_table[ $fk["parent_table"] ][] = $fk;
				
				foreach ($fks_by_table as $parent_table => $parent_fks) {
					$properties = array();
					$overlay = "Many To One";
					
					if ($parent_fks) {
						$parent_attrs = $tables_data[$parent_table][0];
						$child_columns_pks_count = 0;
						
						$t = count($parent_fks);
						for ($i = 0; $i < $t; $i++) {
							$parent_fk = $parent_fks[$i];
							
							//$properties["source_tables"][] = $table;
							$properties["source_columns"][] = $parent_fk["child_column"];
							//$properties["target_tables"][] = $parent_table;
							$properties["target_columns"][] = $parent_fk["parent_column"];
							
							$child_attr = $attrs[ $parent_fk["child_column"] ];
							
							if ($child_attr && !empty($child_attr["primary_key"]))
								$child_columns_pks_count++;
						}
						
						//if child is primary key it means that the connection is one-to-one. This code is used when the tables are getted directly from the DB server.
						if ($t == $child_columns_pks_count)
							$overlay = "One To One";
					}
					
					$new_tasks["tasks"][$real_task_id]["exits"]["layer_exit"][] = array(
						"task_id" => !empty($tasks_by_table_name[$parent_table]) ? $tasks_by_table_name[$parent_table] : $parent_table,
						"label" => null,
						"type" => $parent_table == $table ? "StateMachine" : "Flowchart",
						"overlay" => $overlay,
						"properties" => $properties
					);
				}
			}
		}
		
		return $new_tasks;
	}
	
	public static function getTasksAsTables($tasks) {
		$tables = array();
		
		if ($tasks) {
			$tables_foreign_keys = self::getTablesForeignKeys($tasks);
			
			foreach ($tasks as $table_name => $task) {
				$properties = $task["properties"];
				
				$table_attr_primary_keys = $properties["table_attr_primary_keys"];
				$table_attr_names = $properties["table_attr_names"];
				$table_attr_types = $properties["table_attr_types"];
				$table_attr_lengths = $properties["table_attr_lengths"];
				$table_attr_nulls = $properties["table_attr_nulls"];
				$table_attr_unsigneds = $properties["table_attr_unsigneds"];
				$table_attr_uniques = $properties["table_attr_uniques"];
				$table_attr_auto_increments = $properties["table_attr_auto_increments"];
				$table_attr_has_defaults = $properties["table_attr_has_defaults"];
				$table_attr_defaults = $properties["table_attr_defaults"];
				$table_attr_extras = $properties["table_attr_extras"];
				$table_attr_charsets = $properties["table_attr_charsets"];
				$table_attr_comments = $properties["table_attr_comments"];
				
				if ($table_attr_names) {
					if (is_array($table_attr_names)) {
						$t = count($table_attr_names);
						for ($i = 0; $i < $t; $i++) {
							$is_pk = strtolower($table_attr_primary_keys[$i]) == "true" || $table_attr_primary_keys[$i] == "1";
							$attr_name = $table_attr_names[$i];
							$attr_type = strtolower($table_attr_types[$i]);
							$attr_length = $table_attr_lengths[$i];
							$is_null = strtolower($table_attr_nulls[$i]) == "true" || $table_attr_nulls[$i] == "1";
							$is_unsigned = strtolower($table_attr_unsigneds[$i]) == "true" || $table_attr_unsigneds[$i] == "1";
							$is_unique = strtolower($table_attr_uniques[$i]) == "true" || $table_attr_uniques[$i] == "1";
							$is_auto_increment = strtolower($table_attr_auto_increments[$i]) == "true" || $table_attr_auto_increments[$i] == "1";
							$has_defaults = strtolower($table_attr_has_defaults[$i]) == "true" || $table_attr_has_defaults[$i] == "1";
							$attr_default = $table_attr_defaults[$i];
							$attr_extra = $table_attr_extras[$i];
							$attr_charset = $table_attr_charsets[$i];
							$attr_comment = $table_attr_comments[$i];
							
							$tables[$table_name][$attr_name] = array(
								"name" => $attr_name,
								"type" => $attr_type,
								"length" => $attr_length,
								"null" => $is_null,
								"primary_key" => $is_pk,
								"unsigned" => $is_unsigned,
								"unique" => $is_unique,
								"auto_increment" => $is_auto_increment,
								"default" => $has_defaults ? $attr_default : null,
								"extra" => $attr_extra,
								"charset" => $attr_charset,
								"comment" => $attr_comment,
							);
						}
					}
					else {
						$is_pk = strtolower($table_attr_primary_keys) == "true" || $table_attr_primary_keys == "1";
						$attr_name = $table_attr_names;
						$attr_type = strtolower($table_attr_types);
						$attr_length = $table_attr_lengths;
						$is_null = strtolower($table_attr_nulls) == "true" || $table_attr_nulls == "1";
						$is_unsigned = strtolower($table_attr_unsigneds) == "true" || $table_attr_unsigneds == "1";
						$is_unique = strtolower($table_attr_uniques) == "true" || $table_attr_uniques == "1";
						$is_auto_increment = strtolower($table_attr_auto_increments) == "true" || $table_attr_auto_increments == "1";
						$has_defaults = strtolower($table_attr_has_defaults) == "true" || $table_attr_has_defaults == "1";
						$attr_default = $table_attr_defaults;
						$attr_extra = $table_attr_extras;
						$attr_charset = $table_attr_charsets;
						$attr_comment = $table_attr_comments;
						
						$tables[$table_name][$attr_name] = array(
							"name" => $attr_name,
							"type" => $attr_type,
							"length" => $attr_length,
							"null" => $is_null,
							"primary_key" => $is_pk,
							"unsigned" => $is_unsigned,
							"unique" => $is_unique,
							"auto_increment" => $is_auto_increment,
							"default" => $has_defaults ? $attr_default : null,
							"extra" => $attr_extra,
							"charset" => $attr_charset,
							"comment" => $attr_comment,
						);
					}
				}
				
				$foreign_keys = self::getTableFromTables($tables_foreign_keys, $table_name);
				
				if ($foreign_keys) {
					$t2 = count($foreign_keys);
					for ($j = 0; $j < $t2; $j++) {
						$fk = $foreign_keys[$j];
						$type = $fk["type"];
						$keys = $fk["keys"];
						$t3 = $keys ? count($keys) : 0;
						
						switch($type) {
							case "1-1":
							case "*-*":
								/*
								 * if 1-1 or *-* always get the FKS of the other table.
								 */
								for ($w = 0; $w < $t3; $w++) {
									$key = $keys[$w];
									$attr_name = $fk["child_table"] == $table_name ? $key["child"] : $key["parent"];
									$attr_fk_props = array(
										"table" => $fk["child_table"] == $table_name ? $fk["parent_table"] : $fk["child_table"],
										"attribute" => $fk["child_table"] == $table_name ? $key["parent"] : $key["child"],
									);
									
									if ($fk["source"])
										$attr_fk_props["source"] = $fk["source"]; //it means the connection was done FROM $table_name
									
									if ($fk["target"])
										$attr_fk_props["target"] = $fk["target"]; //it means the connection was done TO $table_name
									
									$tables[$table_name][$attr_name]["fk"][] = $attr_fk_props;
								}
								break;
							
							case "*-1":
								//For external conections and inner connections too
								if ($fk["child_table"] == $table_name)
									for ($w = 0; $w < $t3; $w++) {
										$key = $keys[$w];
										$attr_name = $key["child"];
										$attr_fk_props = array(
											"table" => $fk["parent_table"],
											"attribute" => $key["parent"],
										);
										
										//in case of inner connection
										if ($fk["parent_table"] == $table_name) {
											$exists = false;
											
											if ($tables[$table_name][$attr_name]["fk"])
												foreach ($tables[$table_name][$attr_name]["fk"] as $attr_fk_props)
													if ($attr_fk_props["table"] == $table_name && $attr_fk_props["attribute"] == $key["parent"]) {
														$exists = true;
														break;
													}
											
											//only add if not added yet
											if (!$exists)
												$tables[$table_name][$attr_name]["fk"][] = $attr_fk_props;
										}
										else
											$tables[$table_name][$attr_name]["fk"][] = $attr_fk_props;
									}
								break;
							
							case "1-*": 
								/*
								 * Doesn't do anything unless is an inner connection, this is, 1-* means the $table_name is the parent table so we don't need to do anything ($fk["child_table"] != $table_name && $fk["parent_table"] == $table_name).
								 * However if is an inner connection ($fk["child_table"] == $table_name && $fk["parent_table"] == $table_name), then update the fks...
								 */
								 if ($fk["child_table"] == $table_name && $fk["parent_table"] == $table_name) 
									for ($w = 0; $w < $t3; $w++) {
										$key = $keys[$w];
										$attr_name = $key["child"];
										$exists = false;
										
										if ($tables[$table_name][$attr_name]["fk"])
											foreach ($tables[$table_name][$attr_name]["fk"] as $attr_fk_props)
												if ($attr_fk_props["table"] == $table_name && $attr_fk_props["attribute"] == $key["parent"]) {
													$exists = true;
													break;
												}
										
										//only add if not added yet
										if (!$exists)
											$tables[$table_name][$attr_name]["fk"][] = array(
												"table" => $table_name,
												"attribute" => $key["parent"],
											);
									}
								break;
						}
					}
				}	
			}
		}
		
		//echo "<pre>";print_r($tables);die();
		return $tables;
	}
	
	public static function getTablesForeignKeys($tasks) {
		$foreign_keys = array();
		
		if (is_array($tasks)) {
			foreach ($tasks as $table_name => $table) {
				$exits = $table["exits"]["layer_exit"];
				
				$properties = self::getTableExitsProperties($tasks, $table_name, $exits, $foreign_keys);
				
				$t = count($properties);
				for ($i = 0; $i < $t; $i++) {
					$props = $properties[$i];
					
					$foreign_keys[$table_name][] = $props;
					
					$foreign_table_name = $props["child_table"] == $table_name ? $props["parent_table"] : $props["child_table"];
					if ($foreign_table_name) {
						//for the "1-*" connections set the FKs in the foreign table too, otherwise the FKs won't be fully done. This is, the "1-*" connector should not exist! There should be instead the "*-1" connector like it happens in the Relational diagrams, so we need to create the correspondent FKs in the right table. This is, if a table has a "1-*" connection, it means that the child table will have this table FK and not the parent. So the FK should be in the child table. So we need to set this in the right table, which is the child table. Otherwise when we create the automatically Queries and UIs, the system won't create the files correctly.
						//However only set this FKs if the tables names are different or if is an inner connection to the same table, (this is if tables are the same and the connection is 1-*)
						if ($foreign_table_name != $table_name || $props["type"] == "1-*") {
							$props["type"] = $props["type"] == "*-1" ? "1-*" : ($props["type"] == "1-*" ? "*-1" : $props["type"]);
							$props["target"] = true; //target means that the connection is connected from this $table_name to the $foreign_table_name.
							unset($props["source"]); //remove source in case exists bc now is a target connection and not a source connection.
							
							$foreign_keys[$foreign_table_name][] = $props;
						}
					}
				}
			}
		}
		//echo "<pre>";print_r($foreign_keys);die();
		return $foreign_keys;
	}
	
	private static function getTableExitsProperties($tasks, $table_name, $exits, &$foreign_keys) {
		$foreign_keys_props = array();
		
		if ($exits) {
			$exits = isset($exits["task_id"]) ? array($exits) : $exits;
			$tables_connection_types = self::getTablesConnectionTypes();
			
			$t = count($exits);
			for ($i = 0; $i < $t; $i++) {
				$exit = $exits[$i];
				$properties = $exit["properties"];
				
				$type = $tables_connection_types[ $exit["overlay"] ];
				$type = $type ? $type : "1-1";
				
				$foreign_table_name = self::getTableNameFromTaskId($tasks, $exit["task_id"]);
				
				if ($foreign_table_name) {
					$keys = array();//$keys is an assoc array where the array keys correspond to the child_table's attributes and the values to the parent_table's attributes
				
					if (!$properties || !$properties["source_columns"]) {
						$properties = array();
				
						$table_attrs = self::getTableAttributes($tasks, $table_name);
						$foreign_table_attrs = self::getTableAttributes($tasks, $foreign_table_name);
					
						$table_pks = self::getTablePrimaryKeys($tasks, $table_name);
						$foreign_table_pks = self::getTablePrimaryKeys($tasks, $foreign_table_name);
						
						$table_fks = array_intersect($table_attrs, $foreign_table_pks);
						$foreign_table_fks = array_intersect($foreign_table_attrs, $table_pks);
						
						if ($type == "*-*" || $type == "1-1")
							$fks = array_merge($table_fks, $foreign_table_fks);
						else if ($type == "*-1")
							$fks = $foreign_table_fks;
						else if ($type == "1-*")
							$fks = $table_fks;
						
						if ($fks) {//prepare keys based in the similar attributes key of both tables
							$t2 = count($fks);
							for ($j = 0; $j < $t2; $j++)
								$keys[] = array("child" => $fks[$j], "parent" => $fks[$j]);
						}
						else if ($type == "*-1") {//prepare keys based in the primary keys of both tables
							$t2 = count($table_pks);
							for ($j = 0; $j < $t2; $j++)
								if ($foreign_table_pks[$j])
									$keys[] = array("child" => $table_pks[$j], "parent" => $foreign_table_pks[$j]);
						}
						else {
							$t2 = count($table_pks);
							for ($j = 0; $j < $t2; $j++)
								if ($foreign_table_pks[$j])
									$keys[] = array("child" => $foreign_table_pks[$j], "parent" => $table_pks[$j]);
						}
					}
					else {
						$source_columns = $properties["source_columns"];
						$target_columns = $properties["target_columns"];
				
						if ($source_columns) {
							if (!is_array($source_columns)) {
								$source_columns = array($source_columns);
								$target_columns = array($target_columns);
							}
					
							if ($type == "*-1") {
								$t2 = count($source_columns);
								for ($j = 0; $j < $t2; $j++)
									$keys[] = array("child" => $source_columns[$j], "parent" => $target_columns[$j]);
							}
							else {
								$t2 = count($source_columns);
								for ($j = 0; $j < $t2; $j++)
									$keys[] = array("child" => $target_columns[$j], "parent" => $source_columns[$j]);
							}
						}
					}
				
					//echo "\n$table_name->$foreign_table_name";print_r($keys);
					
					if ($keys) {
						/*
						 * if 1-1 or *-*, the parent should be $foreign_table_name, bc the arrow of the connection in the db diagram points to the parent table, so the parent table should be the $foreign_table_name.
						 * if 1-*, it doesn't really matter the connection arrow in the db diagram, bc the parent is always $table_name
						 * if *-1, it doesn't really matter the connection arrow in the db diagram, bc the parent is always $foreign_table_name
						 * source means that the connection is connected from the $table_name to the foreign_table_name.
						 * 
						 * Note: Probably the *-* won't be used for anything bc in the relational diagram the *-* doesn't exists!
						 */
						if ($type == "1-*") 
							$foreign_keys_props[] = array(
								"type" => $type,
								"child_table" => $foreign_table_name,
								"parent_table" => $table_name,
								"keys" => $keys,
								"source" => true,
							);
						else 
							$foreign_keys_props[] = array(
								"type" => $type,
								"child_table" => $table_name,
								"parent_table" => $foreign_table_name,
								"keys" => $keys,
								"source" => true,
							);
					}
				}
			}
		}
		
		return $foreign_keys_props;
	}
	
	private static function getTableNameFromTaskId($tasks, $task_id) {
		foreach ($tasks as $table_name => $task) {
			if ($task_id == $table_name)
				return $table_name;
			else if ($task["id"] == $task_id) 
				return $task["label"];
		}
		
		return null;
	}
}
?>
