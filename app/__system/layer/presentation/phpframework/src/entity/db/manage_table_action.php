<?php
include_once $EVC->getUtilPath("WorkFlowDBHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");

$layer_bean_folder_name = isset($_GET["layer_bean_folder_name"]) ? $_GET["layer_bean_folder_name"] : null;
$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$action = isset($_GET["action"]) ? $_GET["action"] : null;
$extra = isset($_GET["extra"]) ? $_GET["extra"] : null;

$status = false;

if ($bean_name && $action) {
	$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$bean_name";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_object_id, "layer", "access");
	
	$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
	$DBDriver = $WorkFlowDBHandler->getBeanObject($bean_file_name, $bean_name);
	
	$diagram_tables_to_be_updated = array();
	
	switch($action) {
		case "remove_table":
			$table = isset($_GET["table"]) ? $_GET["table"] : null;
			$diagram_tables_to_be_updated[] = $table;
			
			if ($table)
				$sql = $DBDriver->getDropTableStatement($table);
			else
				$status = "Undefined table name!";
			break;
		
		case "remove_attribute":
			$table = isset($_GET["table"]) ? $_GET["table"] : null;
			$attribute = isset($_GET["attribute"]) ? $_GET["attribute"] : null;
			
			if ($table && $attribute) {
				$sql = array();
				$diagram_tables_to_be_updated[] = $table;
				
				//check if there are foregin key constraints and if yes, remove them.
				$fks = $DBDriver->listForeignKeys($table);
				$fks_to_add = array();
				$repeated_constraints_name = array();
				
				if ($fks) {
					//print_r($fks);
					for ($i = 0, $t = count($fks); $i < $t; $i++) {
						$fk = $fks[$i];
					
						if (isset($fk["child_column"]) && $fk["child_column"] == $attribute)
							if (!empty($fk["constraint_name"]) && !in_array($fk["constraint_name"], $repeated_constraints_name)) {
								$repeated_constraints_name[] = $fk["constraint_name"];
								$sql[] = $DBDriver->getDropTableForeignConstraintStatement($table, $fk["constraint_name"]);
								
								$fk_parent_table = isset($fk["parent_table"]) ? $fk["parent_table"] : null;
								
								//add foreign key for the other attributes, if apply
								for ($j = 0; $j < $t; $j++) 
									if ($j != $i) {
										$sub_fk = $fks[$j];
										$sub_fk_child_column = isset($sub_fk["child_column"]) ? $sub_fk["child_column"] : null;
										$sub_fk_parent_table = isset($sub_fk["parent_table"]) ? $sub_fk["parent_table"] : null;
										
										if ($sub_fk_parent_table == $fk_parent_table && $sub_fk_child_column != $attribute)
											$fks_to_add[$fk_parent_table][$sub_fk_child_column] = isset($sub_fk["parent_column"]) ? $sub_fk["parent_column"] : null;
									}
							}
					}
				}
				
				$sql[] = $DBDriver->getDropTableAttributeStatement($table, $attribute);
				
				//must be after the getDropTableAttributeStatement
				if ($fks_to_add)
					foreach ($fks_to_add as $fk_table => $fks_attr_name_to_add) {
						if ($fks_attr_name_to_add) {
							$constraint_name = isset($repeated_constraints_name[0]) ? $repeated_constraints_name[0] : null;
							$fk = array(
								"child_column" => array_keys($fks_attr_name_to_add),
								"parent_column" => array_values($fks_attr_name_to_add),
								"parent_table" => $fk_table,
								"constraint_name" => $constraint_name ? $constraint_name : "fk_{$table}_{$fk_table}_" . implode("_", array_keys($fks_attr_name_to_add)),
							);
							
							$sql[] = $DBDriver->getAddTableForeignKeyStatement($table, $fk);
						}
					}
				
				//No need to create the rollback_sql bc the attribute was already deleted.
				
				//print_r($sql);die();
			}
			else
				$status = "Undefined table or attribute names!";
			break;
		
		case "add_table":
			if ($extra) {
				$diagram_tables_to_be_updated[] = $extra;
				$php_to_db_column_types = $DBDriver->getPHPToDBColumnTypes();
				$db_column_date_type = isset($php_to_db_column_types["timestamp"]) ? $php_to_db_column_types["timestamp"] : null;
				
				$db_column_simple_types = $DBDriver->getDBColumnSimpleTypes();
				$db_column_simple_type_pk = $db_column_simple_types && !empty($db_column_simple_types["simple_auto_primary_key"]) ? $db_column_simple_types["simple_auto_primary_key"] : null;
				
				if (!$db_column_simple_type_pk) {
					$db_column_mandatory_length_types = $DBDriver->getDBColumnMandatoryLengthTypes();
					
					$db_column_simple_type_pk = array(
						"type" => isset($php_to_db_column_types["bigint"]) ? $php_to_db_column_types["bigint"] : null, 
						"length" => !empty($db_column_mandatory_length_types["bigint"]) ? $db_column_mandatory_length_types["bigint"] : 20, 
						"null" => false, 
						"primary_key" => true, 
						"auto_increment" => true, 
						"unsigned" => true,
					);
				}
				
				$table_data = array(
					"table_name" => $extra,
					"attributes" => array(
						array(
							"name" => $extra . "_id",
							"type" => !empty($db_column_simple_type_pk["type"]) ? $db_column_simple_type_pk["type"] : "int",
							"length" => !empty($db_column_simple_type_pk["length"]) ? $db_column_simple_type_pk["length"] : 20,
							"primary_key" => !$db_column_simple_type_pk || !empty($db_column_simple_type_pk["primary_key"]) ? 1 : "",
							"auto_increment" => !$db_column_simple_type_pk || !empty($db_column_simple_type_pk["auto_increment"]) ? 1 : "",
							"unsigned" => !$db_column_simple_type_pk || !empty($db_column_simple_type_pk["unsigned"]) ? 1 : "",
							"null" => !empty($db_column_simple_type_pk["null"]) ? 1 : "",
						),
					),
				);
				
				if ($db_column_date_type) {
					$table_data["attributes"][] = array(
						"name" => "created_date",
						"type" => $db_column_date_type,
						"null" => 1,
						"default" => "0000-00-00 00:00:00",
					);
					
					$table_data["attributes"][] = array(
						"name" => "created_user_id",
						"type" => !empty($db_column_simple_type_pk["type"]) ? $db_column_simple_type_pk["type"] : "int",
						"length" => !empty($db_column_simple_type_pk["length"]) ? $db_column_simple_type_pk["length"] : 20,
						"unsigned" => !$db_column_simple_type_pk || !empty($db_column_simple_type_pk["unsigned"]) ? 1 : "",
						"null" => 1,
					);
					
					$table_data["attributes"][] = array(
						"name" => "modified_date",
						"type" => $db_column_date_type,
						"null" => 1,
						"default" => "0000-00-00 00:00:00",
					);
					
					$table_data["attributes"][] = array(
						"name" => "modified_user_id",
						"type" => !empty($db_column_simple_type_pk["type"]) ? $db_column_simple_type_pk["type"] : "int",
						"length" => !empty($db_column_simple_type_pk["length"]) ? $db_column_simple_type_pk["length"] : 20,
						"unsigned" => !$db_column_simple_type_pk || !empty($db_column_simple_type_pk["unsigned"]) ? 1 : "",
						"null" => 1,
					);
				}
				
				$sql = $DBDriver->getCreateTableStatement($table_data);
			}
			else
				$status = "Undefined table name!";
			break;
		
		case "add_attribute":
			$table = isset($_GET["table"]) ? $_GET["table"] : null;
			
			if ($table && $extra) {
				$diagram_tables_to_be_updated[] = $table;
				$php_to_db_column_types = $DBDriver->getPHPToDBColumnTypes();
				$db_column_default_values_by_type = $DBDriver->getDBColumnDefaultValuesByType();
				$db_column_mandatory_length_types = $DBDriver->getDBColumnMandatoryLengthTypes();
				$db_column_simple_types = $DBDriver->getDBColumnSimpleTypes();
				
				$attribute_data = array(
					"name" => $extra,
					"type" => isset($php_to_db_column_types["varchar"]) ? $php_to_db_column_types["varchar"] : null,
					"length" => isset($db_column_mandatory_length_types["varchar"]) ? $db_column_mandatory_length_types["varchar"] : null,
					"default" => isset($db_column_default_values_by_type["varchar"]) ? $db_column_default_values_by_type["varchar"] : null,
					"null" => 1,
				);
				
				//check if already exists an attribute with auto_increment key
				$attrs = $DBDriver->listTableFields($table);
				$exists_pk = false;
				$exists_auto_increment = false;
				
				if ($attrs)
					foreach ($attrs as $attr) {
						if (!empty($attr["primary_key"]))
							$exists_pk = true;
						
						if (!empty($attr["auto_increment"]))
							$exists_auto_increment = true;
						
						if ($exists_pk && $exists_auto_increment)
							break;
					}
					
				//set type and other props according with name
				$anl = strtolower($attribute_data["name"]);
				$an_length = strlen($attribute_data["name"]);
				$found_props = null;
				$max_length = null;
				
				foreach ($db_column_simple_types as $simple_type => $simple_props)
					if (is_array($simple_props) && !empty($simple_props["name"])) {
						$spn = is_array($simple_props["name"]) ? $simple_props["name"] : array($simple_props["name"]);
						
						foreach ($spn as $n) {
							if (strpos($anl, strtolower($n)) !== false) {
								//get the props with bigger name, which means is the best fit. Example if the attribute_name=="id" it could appear in "idade", but what we want is only the "id" as an identifier.
								$length = strlen($n);
								
								if ($max_length === null || $length > $max_length || $length == $an_length) {
									if ($exists_pk && !empty($simple_props["primary_key"]))
										continue; //if there is already a primary key, don't set another one automatically, so skip this prop
									
									if ($exists_auto_increment && !empty($simple_props["auto_increment"]))
										continue; //it can only be one attribute with auto_increment prop, so we need to skip this simple prop
									
									$max_length = $length;
									$found_props = $simple_props;
									
									if ($length == $an_length)
										break 2;
								}
							}
						}
					}
				//echo "found_props($anl):";print_r($found_props);die();
				
				if ($found_props)
					foreach ($found_props as $prop_name => $prop_value) 
						if ($prop_name != "name" && $prop_name != "label") {
							if ($prop_name == "type" && is_array($prop_value)) {
								if (!in_array($attribute_data["type"], $prop_value))
									$attribute_data["type"] = $prop_value[0];
							}
							else
								$attribute_data[$prop_name] = $prop_value;
						}
				
				//execute query
				$sql = $DBDriver->getAddTableAttributeStatement($table, $attribute_data);
			}
			else
				$status = "Undefined table!";
			break;
		
		case "add_fk_attribute":
			$table = isset($_GET["table"]) ? $_GET["table"] : null;
			$fk_table = isset($_GET["fk_table"]) ? $_GET["fk_table"] : null;
			$fk_attribute = isset($_GET["fk_attribute"]) ? $_GET["fk_attribute"] : null;
			$previous_attribute = isset($_GET["previous_attribute"]) ? $_GET["previous_attribute"] : null;
			$next_attribute = isset($_GET["next_attribute"]) ? $_GET["next_attribute"] : null;
			$attribute_index = isset($_GET["attribute_index"]) ? $_GET["attribute_index"] : null;
			
			if ($table && $fk_table) {
				$diagram_tables_to_be_updated[] = $table;
				$diagram_tables_to_be_updated[] = $fk_table;
				$fk_attrs = $DBDriver->listTableFields($fk_table);
				
				//if $fk_attribute does not exists,  get all PKs from $fk_table and add that attributes.
				if ($fk_attrs) {
					$attrs_to_add = array();
					
					if (!$fk_attribute) {
						$is_same_table = $table == $fk_table;
						
						foreach ($fk_attrs as $attr_name => $attr) 
							if (!empty($attr["primary_key"]))
								$attrs_to_add[$attr_name] = $attr;
					}
					else if (!empty($fk_attrs[$fk_attribute]))
						$attrs_to_add[$fk_attribute] = $fk_attrs[$fk_attribute];
					else
						$status = "Foreign attribute '$fk_attribute' does not exist anymore in table '$fk_table'!";
					
					if (!empty($attrs_to_add)) {
						$attrs = $DBDriver->listTableFields($table);
						$fks = $DBDriver->listForeignKeys($table);
						
						//preparing sorting attribute
						$r = prepareSortingAttributeSettings($previous_attribute, $next_attribute, $attribute_index, $attrs);
						$is_first_attribute = isset($r["is_first_attribute"]) ? $r["is_first_attribute"] : null;
						$previous_attribute = isset($r["previous_attribute"]) ? $r["previous_attribute"] : null;
						
						//preparing sql
						$sql = array();
						$rollback_sql = array();
						$fks_attr_name_to_add = array();
						$repeated_constraints_name = array();
						
						foreach ($attrs_to_add as $attr_name => $attr) {
							$new_attr_name = $attr_name;
							
							//if table and fk_table are the same, then add attribute name with parent_ prefix, because we want to add another attribute pointing to the same table it-self.
							if (!empty($is_same_table))
								do {
									$new_attr_name = "parent_" . $new_attr_name;
								}
								while ($attrs[$new_attr_name]);
							
							//prepare attribute sorting. If not previous_attribute neither next_attribute, appends new attributes which is the default behaviour.
							//add attribute, if not exists yet
							if (empty($attrs[$new_attr_name])) {
								$attribute_data = array(
									"name" => $new_attr_name,
									"type" => isset($attr["type"]) ? $attr["type"] : null,
									"length" => isset($attr["length"]) ? $attr["length"] : null,
									"null" => 1,
									"unsigned" => isset($attr["unsigned"]) ? $attr["unsigned"] : null,
									"charset" => isset($attr["charset"]) ? $attr["charset"] : null,
									"collation" => isset($attr["collation"]) ? $attr["collation"] : null,
								);
								
								//insert after attribute
								if ($previous_attribute || $is_first_attribute) {
									if ($previous_attribute)
										$attribute_data["after"] = $previous_attribute;
									else if ($is_first_attribute)
										$attribute_data["first"] = true;
									
									//update new previous attribute, in case the $fk_table contains more than 1 primary key
									$previous_attribute = $new_attr_name;
									$is_first_attribute = false;
								}
								
								$sql[] = $DBDriver->getAddTableAttributeStatement($table, $attribute_data);
							}
							
							//add foreign key, if not exists yet
							$fk_exists = false;
							
							if ($fks)
								foreach ($fks as $fk) {
									$fk_child_column = isset($fk["child_column"]) ? $fk["child_column"] : null;
									$fk_parent_table = isset($fk["parent_table"]) ? $fk["parent_table"] : null;
									$fk_parent_column = isset($fk["parent_column"]) ? $fk["parent_column"] : null;
									
									if ($fk_child_column == $new_attr_name && $fk_parent_table == $fk_table && $fk_parent_column == $attr_name) {
										$fk_exists = true;
										break;
									}
								}
							
							//Note that I cannot have separate foreign keys sets for the same foreign table, so I must delete first all foreign keys related with the $fk_table and then add them again with the new $new_attr_name
							if (!$fk_exists) {
								if ($fks) {
									foreach ($fks as $fk)
										if (isset($fk["parent_table"]) && $fk["parent_table"] == $fk_table) {
											$fk_child_column = isset($fk["child_column"]) ? $fk["child_column"] : null;
											$fks_attr_name_to_add[$fk_child_column] = isset($fk["parent_column"]) ? $fk["parent_column"] : null;
											
											if (!empty($fk["constraint_name"]) && !in_array($fk["constraint_name"], $repeated_constraints_name)) {
												$repeated_constraints_name[] = $fk["constraint_name"];
												$sql[] = $DBDriver->getDropTableForeignConstraintStatement($table, $fk["constraint_name"]);
											}
										}
								}
								
								$fks_attr_name_to_add[$new_attr_name] = $attr_name;
							}
						}
						
						if (!empty($fks_attr_name_to_add)) {
							$constraint_name = isset($repeated_constraints_name[0]) ? $repeated_constraints_name[0] : null;
							$fk = array(
								"child_column" => array_keys($fks_attr_name_to_add),
								"parent_column" => array_values($fks_attr_name_to_add),
								"parent_table" => $fk_table,
								"constraint_name" => $constraint_name ? $constraint_name : "fk_{$table}_{$fk_table}_" . implode("_", array_keys($fks_attr_name_to_add)),
							);
							
							$sql[] = $DBDriver->getAddTableForeignKeyStatement($table, $fk);
							
							array_pop($fks_attr_name_to_add); //remove added attribute
							$fk["child_column"] = array_keys($fks_attr_name_to_add);
							$fk["parent_column"] = array_values($fks_attr_name_to_add);
							$rollback_sql[] = $DBDriver->getAddTableForeignKeyStatement($table, $fk);
						}
					}
				}
				else
					$status = "Table '$fk_table' does not have any attributes!";
			}
			else
				$status = "Undefined table names!";
			break;
		
		case "rename_table":
			$table = isset($_GET["table"]) ? $_GET["table"] : null;
			
			if ($table && $extra) {
				$diagram_tables_to_be_updated[$table] = $extra;
				$sql = $DBDriver->getRenameTableStatement($table, $extra);
			}
			else
				$status = "Undefined table names!";
			break;
		
		case "rename_attribute":
			$table = isset($_GET["table"]) ? $_GET["table"] : null;
			$attribute = isset($_GET["attribute"]) ? $_GET["attribute"] : null;
			
			if ($table && $attribute && $extra) {
				$diagram_tables_to_be_updated[] = $table;
				$attrs = $DBDriver->listTableFields($table);
				$attr = $attrs[$attribute];
				
				if ($attr)
					$sql = $DBDriver->getRenameTableAttributeStatement($table, $attribute, $extra, $attr);
			}
			else
				$status = "Undefined table or attribute names!";
			break;
		
		case "sort_attribute":
			$table = isset($_GET["table"]) ? $_GET["table"] : null;
			$attribute = isset($_GET["attribute"]) ? $_GET["attribute"] : null;
			$previous_attribute = isset($_GET["previous_attribute"]) ? $_GET["previous_attribute"] : null;
			$next_attribute = isset($_GET["next_attribute"]) ? $_GET["next_attribute"] : null;
			$attribute_index = isset($_GET["attribute_index"]) ? $_GET["attribute_index"] : null;
			
			if ($DBDriver->allowTableAttributeSorting()) {
				$diagram_tables_to_be_updated[] = $table;
				$attrs = $DBDriver->listTableFields($table);
				$attr = isset($attrs[$attribute]) ? $attrs[$attribute] : null;
				
				if ($attr) {
					//preparing sorting attribute
					$r = prepareSortingAttributeSettings($previous_attribute, $next_attribute, $attribute_index, $attrs);
					$is_first_attribute = isset($r["is_first_attribute"]) ? $r["is_first_attribute"] : null;
					$previous_attribute = isset($r["previous_attribute"]) ? $r["previous_attribute"] : null;
					
					//insert after attribute
					if ($previous_attribute || $is_first_attribute) {
						if ($previous_attribute)
							$attr["after"] = $previous_attribute;
						else if ($is_first_attribute)
							$attr["first"] = true;
					}
					
					$sql = $DBDriver->getModifyTableAttributeStatement($table, $attr);
				}
				else
					$status = "Attribute '$attribute' does not exist anymore in table '$table'!";
			}
			else
				$status = $DBDriver->getLabel() . "'s driver does not allow attributes sorting!";
			break;
		
		case "set_primary_key":
			$table = isset($_GET["table"]) ? $_GET["table"] : null;
			$attribute = isset($_GET["attribute"]) ? $_GET["attribute"] : null;
			
			if ($table && $attribute) {
				$diagram_tables_to_be_updated[] = $table;
				$properties = isset($_GET["properties"]) ? json_decode($_GET["properties"], true) : null;
				$primary_key = isset($properties["primary_key"]) ? $properties["primary_key"] : null;
				
				$attrs = $DBDriver->listTableFields($table);
				$attr = isset($attrs[$attribute]) ? $attrs[$attribute] : null;
				$is_different = ($primary_key && empty($attr["primary_key"])) || (!$primary_key && !empty($attr["primary_key"])); //check if primary key is different
				
				if ($is_different)
					$sql = getPrimaryKeySQLs($DBDriver, $table, $attrs, $attribute, $primary_key);
				else
					$status = true;
			}
			else
				$status = "Undefined table or attribute names!";
			break;
		
		case "set_null":
		case "set_type":
			$table = isset($_GET["table"]) ? $_GET["table"] : null;
			$attribute = isset($_GET["attribute"]) ? $_GET["attribute"] : null;
			
			if ($table && $attribute) {
				$diagram_tables_to_be_updated[] = $table;
				$properties = isset($_GET["properties"]) ? json_decode($_GET["properties"], true) : null;
				$type = isset($properties["type"]) ? trim($properties["type"]) : null;
				
				$attrs = $DBDriver->listTableFields($table);
				$attr = isset($attrs[$attribute]) ? $attrs[$attribute] : null;
				
				if ($attr) {
					$is_different = false;
					$is_new_pk = false;
					
					if ($action == "set_type") {
						if (empty($properties["length"])) {
							$db_column_mandatory_length_types = $DBDriver->getDBColumnMandatoryLengthTypes();
							$properties["length"] = isset($db_column_mandatory_length_types[$type]) ? $db_column_mandatory_length_types[$type] : null;
						}
						
						$is_new_pk = !empty($properties["primary_key"]) && empty($attr["primary_key"]); //check if primary_key is different
						
						//if type is a simple type there will be other properties to replace in $attr
						foreach ($properties as $k => $v)
							if (array_key_exists($k, $attr) && $attr[$k] != $v) {
								$attr[$k] = $v;
								$is_different = true;
							}
					}
					else if ($action == "set_null") {
						$type = isset($attr["type"]) ? $attr["type"] : null;
						$is_different = (!empty($properties["null"]) && empty($attr["null"])) || (empty($properties["null"]) && !empty($attr["null"])); //check if null is different
						$attr["null"] = isset($properties["null"]) ? $properties["null"] : null;
					}
					
					if ($is_different) {
						if ($type) {
							$sql = array();
							
							//if new primary key
							if ($is_new_pk)
								$sql = getPrimaryKeySQLs($DBDriver, $table, $attrs, $attribute, true);
							
							$sql[] = $DBDriver->getModifyTableAttributeStatement($table, $attr);
						}
						else
							$status = "Undefined attribute type!";
					}
					else
						$status = true;
				}
				else
					$status = "Attribute does not exists in table!";
			}
			else
				$status = "Undefined table or attribute names!";
			
			break;
	}
	
	if (!empty($sql)) {
		//execute sql by order
		$sql = is_array($sql) ? $sql : array($sql);
		
		if ($sql) {
			$statements = array();
			
			foreach ($sql as $statement)
				if (trim($statement)) //ignore empty queries
					$statements[] = preg_replace("/;$/", "", trim($statement)) . ";"; //Do not remove the space before the ; because if we have this sql "DELIMITER ;", it will convert it to "DELIMITER;" which will not be recognized.
		}
		
		if (!empty($statements)) {
			//print_r($statements);die();
			$exception = null;
			
			try {
				$status = $DBDriver->setSQL($statements); //pass an array instead of a string
			}
			catch(Exception $e) {
				$exception = $e;
				$status = false;
			}
			
			if ($status) {
				//update correspondent in diagram if it exists for table: $diagram_tables_to_be_updated
				if (!empty($diagram_tables_to_be_updated)) {
					$tasks_file_path = WorkFlowTasksFileHandler::getDBDiagramTaskFilePath($workflow_paths_id, "db_diagram", $bean_name);
					
					//only update diagram if diagram settings is to sync with server
					$diagram_settings = WorkFlowDBHandler::getTaskDBDiagramSettings($tasks_file_path);
					
					if (!empty($diagram_settings["sync_with_db_server"]) || !array_key_exists("sync_with_db_server", $diagram_settings)) {
						$WorkFlowDBHandler = new WorkFlowDBHandler($user_beans_folder_path, $user_global_variables_file_path);
						
						if ($action == "remove_table")
							$WorkFlowDBHandler->removeFileTasksDBDiagramTables($tasks_file_path, $diagram_tables_to_be_updated);
						else if ($action == "rename_table")
							$WorkFlowDBHandler->renameFileTasksDBDiagramTables($tasks_file_path, $diagram_tables_to_be_updated);
						else
							$WorkFlowDBHandler->updateFileTasksDBDiagramTablesFromServer($bean_file_name, $bean_name, $tasks_file_path, $diagram_tables_to_be_updated);
					}
				}
			}
			else {
				//execute rollback sql
				if (!empty($rollback_sql)) {
					try {
						$DBDriver->setSQL($rollback_sql);
					}
					catch(Exception $e) {
						//Do nothing
					}
				}
				
				//prepare status
				$status = array(
					"sql" => $sql,
					"error" => $DBDriver->error(),
					"rollback_sql" => isset($rollback_sql) ? $rollback_sql : null
				);
				
				//if exception, launch it
				if ($exception)
					launch_exception($exception);
			}
		}
		else
			$status = "Undefined sql! Nothing to do!";
	}
	else if (!$status)
		$status = "Undefined sql! Nothing to do!";
}

$status = is_array($status) ? json_encode($status) : $status;
echo $status;
die();

function prepareSortingAttributeSettings($previous_attribute, $next_attribute, $attribute_index, $attrs) {
	$is_first_attribute = false;
	$previous_attribute = $previous_attribute && !empty($attrs[$previous_attribute]) ? $previous_attribute : null;
	
	if (!$previous_attribute) {
		$attrs_keys = array_keys($attrs);
		$attrs_indexes_by_keys = array_flip($attrs_keys);
		
		if ($next_attribute && !empty($attrs[$next_attribute])) {
			$index = $attrs_indexes_by_keys[$next_attribute];
			$index--;
			
			if ($index >= 0)
				$previous_attribute = isset($attrs_keys[$index]) ? $attrs_keys[$index] : null;
			else
				$is_first_attribute = true;
		}
		else if ($attribute_index && !empty($attrs_keys[$attribute_index])) {
			$index = $attribute_index - 1;
			
			if ($index >= 0)
				$previous_attribute = isset($attrs_keys[$index]) ? $attrs_keys[$index] : null;
			else
				$is_first_attribute = true;
		}
	}
	
	return array(
		"previous_attribute" => $previous_attribute,
		"is_first_attribute" => $is_first_attribute
	);
}

function getPrimaryKeySQLs($DBDriver, $table, $attrs, $attribute, $primary_key) {
	$pks = array();
	$auto_increment_attrs = array();
	$sql = array();
	
	if ($attrs)
		foreach ($attrs as $attr_name => $attr) 
			if (!empty($attr["primary_key"])) {
				$pks[$attr_name] = $attr_name;
				
				if (!empty($attr["auto_increment"]))
					$auto_increment_attrs[$attr_name] = $attr;
			}
	
	//Before removing the pks we must first delete all the auto increments keys, bc I cannot remove pk if there are auto_increment keys in Mysql. To remove the auto_increment key, we only need to execute a modify statement. For more info please check: https://www.techbrothersit.com/2019/01/how-to-drop-or-disable-autoincrement.html
	if ($auto_increment_attrs)
		foreach ($auto_increment_attrs as $attr_name => $attr) {
			$attr["auto_increment"] = false;
			$attr["extra"] = preg_replace("/(^|\s)auto_increment($|\s)/i", "", $attr["extra"]);
			
			$sql[] = $DBDriver->getModifyTableAttributeStatement($table, $attr);
		}
	
	//then delete all the pks and only after, add pk for other attributes if apply
	if ($pks)
		$sql[] = $DBDriver->getDropTablePrimaryKeysStatement($table); 
	
	if ($primary_key)
		$pks[$attribute] = $attribute;
	else
		unset($pks[$attribute]);
	
	if (!empty($pks))
		$sql[] = $DBDriver->getAddTablePrimaryKeysStatement($table, $pks);
	
	//Add auto_increment attributes again if still primary keys
	if ($auto_increment_attrs)
		foreach ($auto_increment_attrs as $attr_name => $attr) 
			if (!empty($pks[$attr_name])) {
				$attr["auto_increment"] = true;
				$sql[] = $DBDriver->getModifyTableAttributeStatement($table, $attr);
			}
	
	return $sql;
}
?>
