<?php
include_once get_lib("org.phpframework.workflow.WorkFlowTask");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;
$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;
$class_id = isset($_GET["class"]) ? $_GET["class"] : null;
$method_id = isset($_GET["method"]) ? $_GET["method"] : null;

$data = isset($_POST["data"]) ? $_POST["data"] : null;

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

$data = json_decode($data, true);
//echo "<br/><br/><br/><pre>";print_r($_GET);print_r($data);echo "</pre>";die();

if ($bean_name && $bean_file_name && $path && $data) {
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	
	if ($obj) {
		$PEVC = is_a($obj, "PresentationLayer") ? $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path) : null;
		
		//start php variables
		$ugvfps = array($user_global_variables_file_path);
		
		if ($PEVC)
			$ugvfps[] = $PEVC->getConfigPath("pre_init_config");
		
		$system_project_url_prefix = $project_url_prefix;
		
		$old_defined_vars = get_defined_vars();
		
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($ugvfps);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		try {
			$task_layer_path = $task_layer_bean_name = $task_layer_bean_file_name = null;
			
			//prepare external vars
			$new_defined_vars = get_defined_vars();
			$external_vars = array_diff_key($new_defined_vars, $old_defined_vars);
			unset($external_vars["PHPVariablesFileHandler"]);
			
			if (is_a($obj, "BusinessLogicLayer")) {
				$bean_objs = $obj->getPHPFrameWork()->getObjects();
				$vars = isset($bean_objs["vars"]) && is_array($bean_objs["vars"]) ? array_merge($bean_objs["vars"], $obj->settings) : $obj->settings;
				$external_vars["vars"] = $vars;
				//echo "<pre>";print_r($vars);
			}
			else if (is_a($obj, "PresentationLayer")) 
				$external_vars["EVC"] = $PEVC;
			
			//prepare task_layer_obj
			$method_obj = isset($data["method_obj"]) ? $data["method_obj"] : null;
			
			if ($method_obj) {
				$static_pos = strpos($method_obj, "::");
				$non_static_pos = strpos($method_obj, "->");
				$method_obj = substr($method_obj, 0, 1) != '$' && substr($method_obj, 0, 2) != '@$' && (!$static_pos || ($non_static_pos && $static_pos > $non_static_pos)) ? '$' . $method_obj : $method_obj;
				//echo "method_obj:$method_obj<br>";
				
				$task_layer_obj = parseCode($path, $class_id, $method_id, $obj, $external_vars, $method_obj);
				//echo "task_layer_obj:".get_class($task_layer_obj)."<br>";die();
				
				if ($task_layer_obj) {
					if (is_a($task_layer_obj, "LocalBrokerClient")) {
						$layer_props = WorkFlowBeansFileHandler::getLocalBeanLayerFromBroker($user_global_variables_file_path, $user_beans_folder_path, $task_layer_obj);
						$task_layer_bean_name = isset($layer_props[0]) ? $layer_props[0] : null;
						$task_layer_bean_file_name = isset($layer_props[1][0]) ? $layer_props[1][0] : null;
						//$task_layer_obj = $layer_props[2]; //Do not use this bc it won't have all beans file loaded and when we use the task_layer_obj it will give a Bean exception
						$task_layer_obj = $task_layer_obj->getBrokerServer()->getBrokerLayer();
					}
					else if (is_a($task_layer_obj, "BrokerClient"))
						$task_layer_obj = null;
					else {
						if (is_a($obj, "BusinessLogicLayer") && substr($class_id, -7) === "Service" && method_exists($task_layer_obj, "getBusinessLogicLayer"))
							$task_layer_obj = $task_layer_obj->getBusinessLogicLayer();
						
						$task_layer_bean_name = WorkFlowBeansFileHandler::getBeanName($user_global_variables_file_path, $user_beans_folder_path, $task_layer_obj);
						$task_layer_bean_file_name = WorkFlowBeansFileHandler::getBeanFilePath($user_global_variables_file_path, $user_beans_folder_path, $task_layer_bean_name);
					}
					//echo "task_layer_obj:".get_class($task_layer_obj)."<br>";die();
					
					if ($task_layer_obj) {
						$task_layer_bean_file_name = basename($task_layer_bean_file_name);
						$task_layer_path = $task_layer_obj->getLayerPathSetting();
						//echo "task_layer_bean_name:$task_layer_bean_name<br>";
						//echo "task_layer_bean_file_name:$task_layer_bean_file_name<br>";
						//echo "task_layer_path:$task_layer_path<br>";
						//die();
					}
				}
			}
			
			//prepare url to redirect page
			$task_tag = isset($data["task_tag"]) ? $data["task_tag"] : null;
			$edit_type = isset($data["edit_type"]) ? $data["edit_type"] : null;
			$task_edit_url = null;
			
			switch ($task_tag) {
				case "includefile": 
					$file_path = isset($data["file_path"]) ? $data["file_path"] : null;
					$file_path_type = isset($data["type"]) ? $data["type"] : null;
					
					if ($file_path) {
						$file_path_code = WorkFlowTask::getVariableValueCode($file_path, $file_path_type);
						$file_path = parseCode($path, $class_id, $method_id, $obj, $external_vars, $file_path_code);
						
						$props = getFilePathLayerProps($user_global_variables_file_path, $user_beans_folder_path, $file_path);
						$task_edit_url = getFilePathLayerPropsUrl($system_project_url_prefix, $filter_by_layout, $props);
						//echo "url:$task_edit_url<br><pre>";print_r($props);die();
					}
					break;
				case "callfunction": 
					$include_file_path = isset($data["include_file_path"]) ? $data["include_file_path"] : null;
					$include_file_path_type = isset($data["include_file_path_type"]) ? $data["include_file_path_type"] : null;
					
					if ($include_file_path) {
						$include_file_path_code = WorkFlowTask::getVariableValueCode($include_file_path, $include_file_path_type);
						$include_file_path = parseCode($path, $class_id, $method_id, $obj, $external_vars, $include_file_path_code);
						//echo "include_file_path:$include_file_path<br>";
						
						$props = getFilePathLayerProps($user_global_variables_file_path, $user_beans_folder_path, $include_file_path);
						//echo "props:";print_r($props);
						
						if ($props) {
							if ($edit_type == "file")
								$task_edit_url = getFilePathLayerPropsUrl($system_project_url_prefix, $filter_by_layout, $props);
							else if ($edit_type == "function") {
								$func_name = isset($data["func_name"]) ? $data["func_name"] : null; //function name
								
								$query_string = "bean_name=" . (isset($props["bean_name"]) ? $props["bean_name"] : null) . "&bean_file_name=" . (isset($props["bean_file_name"]) ? $props["bean_file_name"] : null) . "&filter_by_layout=$filter_by_layout&item_type=" . (isset($props["item_type"]) ? $props["item_type"] : null) . "&path=" . (isset($props["path"]) ? $props["path"] : null);
								
								if (isset($props["item_type"]) && $props["item_type"] == "businesslogic")
									$task_edit_url = $system_project_url_prefix . "phpframework/businesslogic/edit_function?$query_string&function=$func_name";
								else
									$task_edit_url = $system_project_url_prefix . "phpframework/admin/edit_file_function?$query_string&function=$func_name";
							}
						}
					}
					break;
				case "callobjectmethod": 
					$include_file_path = isset($data["include_file_path"]) ? $data["include_file_path"] : null;
					$include_file_path_type = isset($data["include_file_path_type"]) ? $data["include_file_path_type"] : null;
					
					if ($include_file_path) {
						$include_file_path_code = WorkFlowTask::getVariableValueCode($include_file_path, $include_file_path_type);
						$include_file_path = parseCode($path, $class_id, $method_id, $obj, $external_vars, $include_file_path_code);
						//echo "include_file_path_code:$include_file_path_code<br>include_file_path:$include_file_path";die();
					}
					
					if (($method_obj == "\$this" || $method_obj == "self") && (!$include_file_path || $include_file_path == $task_layer_path . $path)) {
						if (!$include_file_path)
							$include_file_path = $task_layer_path . $path;
						
						$data["method_obj"] = $class_id;
						//echo "<pre>include_file_path:$include_file_path";print_r($data);die();
					}
					
					//check if class is a ResourceUtil or another Util.
					if (!$include_file_path && is_a($obj, "PresentationLayer") && !empty($data["method_obj"])) {
						$method_obj = $data["method_obj"]; //class name
						
						if (preg_match("/ResourceUtil$/", $method_obj))
							$include_file_path = $PEVC->getUtilPath("resource/$method_obj");
						
						//if class path doesn't exists, tries to find it
						if (!file_exists($include_file_path))
							$include_file_path = findsClassPath( $PEVC->getUtilsPath(), $method_obj);
					}
					
					//echo "include_file_path:$include_file_path<br>";die();
					$props = getFilePathLayerProps($user_global_variables_file_path, $user_beans_folder_path, $include_file_path);
					//echo "$edit_type<pre>";print_r($props);die();
					
					if ($props) {
						$query_string = "bean_name=" . (isset($props["bean_name"]) ? $props["bean_name"] : null) . "&bean_file_name=" . (isset($props["bean_file_name"]) ? $props["bean_file_name"] : null) . "&filter_by_layout=$filter_by_layout&item_type=" . (isset($props["item_type"]) ? $props["item_type"] : null) . "&path=" . (isset($props["path"]) ? $props["path"] : null);
						
						if ($edit_type == "file")
							$task_edit_url = getFilePathLayerPropsUrl($system_project_url_prefix, $filter_by_layout, $props);
						else if ($edit_type == "class") {
							$method_obj = $data["method_obj"]; //class name
							//echo "method_obj:$method_obj";die();
							
							if (isset($props["item_type"]) && $props["item_type"] == "businesslogic")
								$task_edit_url = $system_project_url_prefix . "phpframework/businesslogic/edit_service?$query_string&service=$method_obj";
							else
								$task_edit_url = $system_project_url_prefix . "phpframework/admin/edit_file_class?$query_string&class=$method_obj";
						}
						else if ($edit_type == "method") {
							$method_obj = $data["method_obj"]; //class name
							$method_name = isset($data["method_name"]) ? $data["method_name"] : null; //method name
							
							if (isset($props["item_type"]) && $props["item_type"] == "businesslogic")
								$task_edit_url = $system_project_url_prefix . "phpframework/businesslogic/edit_method?$query_string&service=$method_obj&method=$method_name";
							else
								$task_edit_url = $system_project_url_prefix . "phpframework/admin/edit_file_class_method?$query_string&class=$method_obj&method=$method_name";
							
							//echo "task_edit_url:$task_edit_url";die();
						}
					}
					break;
				case "callbusinesslogic": 
					$module_id = isset($data["module_id"]) ? $data["module_id"] : null;
					$module_id_type = isset($data["module_id_type"]) ? $data["module_id_type"] : null;
					$service_id = isset($data["service_id"]) ? $data["service_id"] : null;
					$service_id_type = isset($data["service_id_type"]) ? $data["service_id_type"] : null;
					
					if (!empty($task_layer_obj) && $module_id && $service_id) {
						$module_id_code = WorkFlowTask::getVariableValueCode($module_id, $module_id_type);
						eval('$module_id = ' . $module_id_code . ';');
						//echo "module_id:$module_id<br>";
						
						$service_id_code = WorkFlowTask::getVariableValueCode($service_id, $service_id_type);
						eval('$service_id = ' . $service_id_code . ';');
						//echo "service_id:$service_id<br>";
						
						if ($module_id && $service_id) {
							$props = $task_layer_obj->getBusinessLogicServiceProps($module_id, $service_id);
							$class_name = isset($props["class_name"]) ? $props["class_name"] : null;
							$method_name = isset($props["method_name"]) ? $props["method_name"] : null;
							$function_name = isset($props["function_name"]) ? $props["function_name"] : null;
							$service_file_path = isset($props["service_file_path"]) ? $props["service_file_path"] : null;
							//echo "<pre>";print_r($props);
							
							$task_layer_file_path = substr($service_file_path, strlen($task_layer_path));
							$query_string = "bean_name=" . $task_layer_bean_name . "&bean_file_name=" . $task_layer_bean_file_name . "&filter_by_layout=$filter_by_layout&item_type=businesslogic&path=" . $task_layer_file_path;
							
							if ($edit_type == "file")
								$task_edit_url = $system_project_url_prefix . "phpframework/businesslogic/edit_file?$query_string";
							else if ($edit_type == "class")
								$task_edit_url = $system_project_url_prefix . "phpframework/businesslogic/edit_service?$query_string&service=$class_name";
							else if ($edit_type == "service") {
								if ($class_name && $method_name)
									$task_edit_url = $system_project_url_prefix . "phpframework/businesslogic/edit_method?$query_string&service=$class_name&method=$method_name";
								else if ($function_name)
									$task_edit_url = $system_project_url_prefix . "phpframework/businesslogic/edit_function?$query_string&function=$function_name";
							}
						}
					}
					break;
				case "callibatisquery": 
					$module_id = isset($data["module_id"]) ? $data["module_id"] : null;
					$module_id_type = isset($data["module_id_type"]) ? $data["module_id_type"] : null;
					$service_type = isset($data["service_type"]) ? $data["service_type"] : null;
					$service_type_type = isset($data["service_type_type"]) ? $data["service_type_type"] : null;
					$service_id = isset($data["service_id"]) ? $data["service_id"] : null;
					$service_id_type = isset($data["service_id_type"]) ? $data["service_id_type"] : null;
					
					if (!empty($task_layer_obj) && $module_id && $service_type && $service_id) {
						$module_id_code = WorkFlowTask::getVariableValueCode($module_id, $module_id_type);
						eval('$module_id = ' . $module_id_code . ';');
						//echo "module_id:$module_id<br>";
						
						$service_type_code = WorkFlowTask::getVariableValueCode($service_type, $service_type_type);
						eval('$service_type = ' . $service_type_code . ';');
						//echo "service_type:$service_type<br>";
						
						$service_id_code = WorkFlowTask::getVariableValueCode($service_id, $service_id_type);
						eval('$service_id = ' . $service_id_code . ';');
						//echo "service_id:$service_id<br>";
						
						if ($module_id && $service_type && $service_id) {
							$props = $task_layer_obj->getQueryProps($module_id, $service_type, $service_id);
							$query_path = isset($props["query_path"]) ? $props["query_path"] : null;
							$query_id = isset($props["query_id"]) ? $props["query_id"] : null;
							
							$task_layer_file_path = substr($query_path, strlen($task_layer_path));
							$query_string = "bean_name=" . $task_layer_bean_name . "&bean_file_name=" . $task_layer_bean_file_name . "&filter_by_layout=$filter_by_layout&item_type=ibatis&path=" . $task_layer_file_path;
							
							if ($edit_type == "file")
								$task_edit_url = $system_project_url_prefix . "phpframework/dataaccess/edit_file?$query_string";
							else if ($edit_type == "query")
								$task_edit_url = $system_project_url_prefix . "phpframework/dataaccess/edit_query?$query_string&obj=&query_id=$query_id&query_type=$service_type&relationship_type=queries";
						}
					}
					break;
				case "callhibernateobject": 
					$module_id = isset($data["module_id"]) ? $data["module_id"] : null;
					$module_id_type = isset($data["module_id_type"]) ? $data["module_id_type"] : null;
					$service_id = isset($data["service_id"]) ? $data["service_id"] : null; //object id
					$service_id_type = isset($data["service_id_type"]) ? $data["service_id_type"] : null;
					
					if (!empty($task_layer_obj) && $module_id && $service_id) {
						$module_id_code = WorkFlowTask::getVariableValueCode($module_id, $module_id_type);
						eval('$module_id = ' . $module_id_code . ';');
						//echo "module_id:$module_id<br>";
						
						$service_id_code = WorkFlowTask::getVariableValueCode($service_id, $service_id_type);
						eval('$service_id = ' . $service_id_code . ';');
						//echo "service_id:$service_id<br>";
						
						if ($module_id && $service_id) {
							$props = $task_layer_obj->getObjectProps($module_id, $service_id);
							$obj_path = isset($props["obj_path"]) ? $props["obj_path"] : null;
							$obj_name = isset($props["obj_name"]) ? $props["obj_name"] : null;
							
							$task_layer_file_path = substr($obj_path, strlen($task_layer_path));
							$query_string = "bean_name=" . $task_layer_bean_name . "&bean_file_name=" . $task_layer_bean_file_name . "&filter_by_layout=$filter_by_layout&item_type=hibernate&path=" . $task_layer_file_path;
							
							if ($edit_type == "file")
								$task_edit_url = $system_project_url_prefix . "phpframework/dataaccess/edit_file?$query_string";
							else if ($edit_type == "object")
								$task_edit_url = $system_project_url_prefix . "phpframework/dataaccess/edit_hbn_obj?$query_string&obj=$obj_name";
						}
					}
					break;
				case "callhibernatemethod": 
					$module_id = isset($data["module_id"]) ? $data["module_id"] : null;
					$module_id_type = isset($data["module_id_type"]) ? $data["module_id_type"] : null;
					$service_id = isset($data["service_id"]) ? $data["service_id"] : null; //object
					$service_id_type = isset($data["service_id_type"]) ? $data["service_id_type"] : null;
					
					if (!empty($task_layer_obj) && $module_id && $service_id) {
						$module_id_code = WorkFlowTask::getVariableValueCode($module_id, $module_id_type);
						eval('$module_id = ' . $module_id_code . ';');
						//echo "module_id:$module_id<br>";
						
						$service_id_code = WorkFlowTask::getVariableValueCode($service_id, $service_id_type);
						eval('$service_id = ' . $service_id_code . ';');
						//echo "service_id:$service_id<br>";
						
						if ($module_id && $service_id) {
							$props = $task_layer_obj->getObjectProps($module_id, $service_id);
							$obj_path = isset($props["obj_path"]) ? $props["obj_path"] : null;
							$obj_name = isset($props["obj_name"]) ? $props["obj_name"] : null;
							
							$task_layer_file_path = substr($obj_path, strlen($task_layer_path));
							$query_string = "bean_name=" . $task_layer_bean_name . "&bean_file_name=" . $task_layer_bean_file_name . "&filter_by_layout=$filter_by_layout&item_type=hibernate&path=" . $task_layer_file_path;
							
							if ($edit_type == "file")
								$task_edit_url = $system_project_url_prefix . "phpframework/dataaccess/edit_file?$query_string";
							else if ($edit_type == "object")
								$task_edit_url = $system_project_url_prefix . "phpframework/dataaccess/edit_hbn_obj?$query_string&obj=$obj_name";
							else if ($edit_type == "query") {
								$service_method = isset($data["service_method"]) ? $data["service_method"] : null; //hbn service method to get the query type
								$service_method_type = isset($data["service_method_type"]) ? $data["service_method_type"] : null;
								
								$service_method_code = WorkFlowTask::getVariableValueCode($service_method, $service_method_type);
								eval('$service_method = ' . $service_method_code . ';');
								//echo "service_method:$service_method<br>";
								
								switch ($service_method) {
									case "callQuerySQL":
									case "callQuery":
										$sma_query_type = isset($data["sma_query_type"]) ? $data["sma_query_type"] : null; //query type
										$sma_query_type_type = isset($data["sma_query_type_type"]) ? $data["sma_query_type_type"] : null;
										
										$sma_query_type_code = WorkFlowTask::getVariableValueCode($sma_query_type, $sma_query_type_type);
										eval('$sma_query_type = ' . $sma_query_type_code . ';');
										//echo "sma_query_type:$sma_query_type<br>";
										break;
									case "callInsertSQL":
									case "callInsert":
										$sma_query_type = "insert";
										break;
									case "callUpdateSQL":
									case "callUpdate":
										$sma_query_type = "update";
										break;
									case "callDeleteSQL":
									case "callDelete":
										$sma_query_type = "delete";
										break;
									case "callSelectSQL":
									case "callSelect":
										$sma_query_type = "select";
										break;
									case "callProcedureSQL":
									case "callProcedure":
										$sma_query_type = "procedure";
										break;
								}
								
								if (!empty($sma_query_type)) {
									$sma_query_id = isset($data["sma_query_id"]) ? $data["sma_query_id"] : null; //query id
									$sma_query_id_type = isset($data["sma_query_id_type"]) ? $data["sma_query_id_type"] : null;
									
									$sma_query_id_code = WorkFlowTask::getVariableValueCode($sma_query_id, $sma_query_id_type);
									eval('$sma_query_id = ' . $sma_query_id_code . ';');
									//echo "sma_query_id:$sma_query_id<br>";
									
									if ($sma_query_id)
										$task_edit_url = $system_project_url_prefix . "phpframework/dataaccess/edit_query?$query_string&obj=$obj_name&query_id=$sma_query_id&query_type=$sma_query_type&relationship_type=queries";
								}
							}
						}
					}
					break;
			}
		}
		catch (Error $e) {
			$error_message = "PHP error: " . $e->getMessage();
			debug_log("[__system/layer/presentation/phpframework/src/entity/edit_task_source.php] $error_message");
	   	}
		catch(ParseError $e) {
			$error_message = "Parse error: " . $e->getMessage();
			debug_log("[__system/layer/presentation/phpframework/src/entity/edit_task_source.php] $error_message");
		}
		catch(ErrorException $e) {
			$error_message = "Error exception: " . $e->getMessage();
			debug_log("[__system/layer/presentation/phpframework/src/entity/edit_task_source.php] $error_message");
		}
		catch(Exception $e) {
			$error_message = $e->getMessage();
			debug_log("[__system/layer/presentation/phpframework/src/entity/edit_task_source.php] $error_message");
		}
		
		//end php variables
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		//redirect to url
		if (!empty($task_edit_url)) {
			$task_edit_url .= "&popup=$popup";
			//echo "task_edit_url:$task_edit_url";die();
			
			header("Location: $task_edit_url");
			echo "<script>document.location='$task_edit_url';</script>";
			die();
		}
	}
}

function getFilePathLayerProps($user_global_variables_file_path, $user_beans_folder_path, $file_path) {
	if (!$file_path)
		return null;
	
	$all_layers_bean_objs = WorkFlowBeansFileHandler::getAllLayersBeanObjs($user_global_variables_file_path, $user_beans_folder_path);
	
	if ($all_layers_bean_objs)
		foreach ($all_layers_bean_objs as $bean_name => $obj) {
			$layer_path = $obj->getLayerPathSetting();
			
			if (strpos($file_path, $layer_path) === 0) {
				$item_type = "";
				
				if (is_a($obj, "DataAccessLayer"))
					$item_type = is_a($obj, "HibernateDataAccessLayer") ? "hibernate" : "ibatis";
				else if (is_a($obj, "BusinessLogicLayer")) 
					$item_type = "businesslogic";
				else if (is_a($obj, "PresentationLayer"))
					$item_type = "presentation";
				
				if ($item_type) {
					$bean_file_name = WorkFlowBeansFileHandler::getBeanFilePath($user_global_variables_file_path, $user_beans_folder_path, $bean_name);
					$bean_file_name = basename($bean_file_name);
					$path = substr($file_path, strlen($layer_path));
					$folder_type = null;
					
					//echo "path:$path<br>";
					
					if ($item_type == "presentation") {
						//prepare folder_type: page, block, template, etc...
						$PresentationCacheLayer = $obj->getCacheLayer();
						$common_project_name = $obj->getCommonProjectName();
						$is_common_project = substr($path, 0, strlen("$common_project_name/")) == "$common_project_name/";
						
						if ($PresentationCacheLayer && !empty($PresentationCacheLayer->settings["presentation_caches_path"]) && strpos($path, $PresentationCacheLayer->settings["presentation_caches_path"]) !== false && !$is_common_project)
							$folder_type = "cache";
						else if (strpos($path, "/src/config/") !== false)
							$folder_type = "config";
						else if (strpos($path, "/src/entity/") !== false)
							$folder_type = "entity";
						else if (strpos($path, "/src/template/") !== false)
							$folder_type = "template";
						else if (strpos($path, "/src/view/") !== false)
							$folder_type = "view";
						else if (strpos($path, "/src/block/") !== false)
							$folder_type = "block";
						else if (strpos($path, "/src/util/") !== false)
							$folder_type = "util";
						else if (strpos($path, "/src/controller/") !== false)
							$folder_type = "controller";
						else if (strpos($path, "/src/module/") !== false)
							$folder_type = "module";
						else if (strpos($path, "/webroot/") !== false)
							$folder_type = "webroot";
					}
					
					return array(
						"path" => $path,
						"bean_file_name" => $bean_file_name,
						"bean_name" => $bean_name,
						"item_type" => $item_type,
						"folder_type" => $folder_type
					);
				}
			}
		}
	
	if (strpos($file_path, LIB_PATH) === 0) {
		$item_type = "lib";
		$path = substr($file_path, strlen(LIB_PATH));
	}
	if (strpos($file_path, DAO_PATH) === 0) {
		$item_type = "dao";
		$path = substr($file_path, strlen(DAO_PATH));
	}
	else if (strpos($file_path, VENDOR_PATH) === 0) {
		$item_type = "vendor";
		$path = substr($file_path, strlen(VENDOR_PATH));
	}
	else if (strpos($file_path, TEST_UNIT_PATH) === 0) {
		$item_type = "test_unit";
		$path = substr($file_path, strlen(TEST_UNIT_PATH));
	}
	else if (strpos($file_path, OTHER_PATH) === 0) {
		$item_type = "other";
		$path = substr($file_path, strlen(OTHER_PATH));
	}
	
	if (!empty($item_type))
		return array(
			"path" => isset($path) ? $path : null,
			"bean_file_name" => "",
			"bean_name" => $item_type,
			"item_type" => $item_type,
		);
	
	return null;
}

function getFilePathLayerPropsUrl($system_project_url_prefix, $filter_by_layout, $props) {
	if ($props && !empty($props["item_type"])) {
		$props["bean_name"] = isset($props["bean_name"]) ? $props["bean_name"] : null;
		$props["bean_file_name"] = isset($props["bean_file_name"]) ? $props["bean_file_name"] : null;
		$props["path"] = isset($props["path"]) ? $props["path"] : null;
		$props["folder_type"] = isset($props["folder_type"]) ? $props["folder_type"] : null;
		
		$query_string = "bean_name=" . $props["bean_name"] . "&bean_file_name=" . $props["bean_file_name"] . "&filter_by_layout=$filter_by_layout&item_type=" . $props["item_type"] . "&path=" . $props["path"];
		
		switch ($props["item_type"]) {
			case "lib":
				return $system_project_url_prefix . "phpframework/docbook/file_code?path=lib/" . $props["path"];
			case "ibatis":
			case "hibernate":
				return $system_project_url_prefix . "phpframework/dataaccess/edit_file?$query_string";
			case "businesslogic":
				return $system_project_url_prefix . "phpframework/businesslogic/edit_file?$query_string";
			case "presentation":
				switch ($props["folder_type"]) {
					case "config":
						return $system_project_url_prefix . "phpframework/presentation/edit_config?$query_string";
					case "entity":
						return $system_project_url_prefix . "phpframework/presentation/edit_entity?$query_string";
					case "template":
						return $system_project_url_prefix . "phpframework/presentation/edit_template?$query_string";
					case "view":
						return $system_project_url_prefix . "phpframework/presentation/edit_view?$query_string";
					case "block":
						return $system_project_url_prefix . "phpframework/presentation/edit_block?$query_string";
					case "util":
						return $system_project_url_prefix . "phpframework/presentation/edit_util?$query_string";
				}
		}
		
		return $system_project_url_prefix . "phpframework/admin/edit_raw_file?$query_string";
	}
	
	return null;
}

function parseCode($path, $class_id, $method_id, $obj, $external_vars, $code) {
	$result = null;
	//echo "$path, $class_id, $method_id,".get_class($obj).", $external_vars, $code";
	
	//prepare task_layer_obj
	if ($class_id && $method_id) { //if is inside of a class method
		if (is_a($obj, "BusinessLogicLayer")) {
			$module_id = dirname($path);
			$props = $obj->getBusinessLogicServiceProps($module_id, "$class_id.$method_id");
			$class_obj = isset($props["obj"]) ? $props["obj"] : null;
			
			//echo "<pre>";print_r($props);
			//echo "class_obj:".get_class($class_obj);die();
		}
		else if (is_a($obj, "PresentationLayer")) {
			$file_path = $obj->getLayerPathSetting() . $path;
			
			if (file_exists($file_path)) {
				include_once $file_path;
				$class_obj = new $class_id();
				//echo "file_path:$file_path<br>";
				//echo "class_obj:".get_class($class_obj);die();
			}
		}
		
		if (!empty($class_obj)) {
			eval('$getBrokerDummyFunc = function($external_vars) {
				if ($external_vars)
					foreach ($external_vars as $k => $v)
						${$k} = $v;
				
				return ' . $code . ';
			};');
			
			$closure_func = Closure::bind($getBrokerDummyFunc, $class_obj, $class_id);
			$result = $closure_func($external_vars);
		}
	}
	else {
		if ($external_vars)
			foreach ($external_vars as $k => $v)
				${$k} = $v;
		
		eval('$result = ' . $code . ';');
	}
	
	return $result;
}

function findsClassPath($folder_path, $class_name) {
	if ($folder_path && is_dir($folder_path)) {
		$files = array_diff(scandir($folder_path), array('..', '.'));
		
		//check first the files
		foreach ($files as $file) {
			$file_path = $folder_path . $file;
			
			if (!is_dir($file_path) && pathinfo($file, PATHINFO_FILENAME) == $class_name)
				return $file_path;
		}
		
		//check then the folders
		foreach ($files as $file) {
			$file_path = $folder_path . $file;
			
			if (is_dir($file_path)) {
				$ret = findsClassPath($file_path . "/", $class_name);
				
				if ($ret)
					return $ret;
			}
		}
	}
	
	return null;
}
?>
