<?php
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$item_type = isset($_GET["item_type"]) ? $_GET["item_type"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;
$pathname = isset($_GET["pathname"]) ? $_GET["pathname"] : null;

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

//prepare file_path
if ($item_type == "dao") {
	$file_path = "vendor/dao/$path";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($file_path, "layer", "access");
}
else if ($item_type == "lib") {
	$file_path = $path;
}
else if ($item_type == "vendor") {
	$file_path = "vendor/$path";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($file_path, "layer", "access");
}
else if ($item_type == "other") {
	$file_path = "other/$path";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($file_path, "layer", "access");
}
else if ($item_type == "test_unit") {
	$file_path = "vendor/testunit/$path";
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($file_path, "layer", "access");
}
else if ($bean_name && $bean_file_name) {
	$layer_object_id = LAYER_PATH . WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bean_file_name, $bean_name, $user_global_variables_file_path);
	$layer_object_id = substr($layer_object_id, strlen(APP_PATH)) . "/";
	$file_path = $layer_object_id . $path;
}

//prepare completions
$completions = array();

if ($file_path) {
	$file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
	
	if ($file_extension == "php") {
		//get some serialized files with completions
		//To generate new serialized files please execute the shell command: php other/script/create_php_editor_auto_completions.php app/__system/layer/presentation/phpframework/src/entity/autocomplete/files/
		$ser_folder_path = $EVC->getEntitiesPath() . "/autocomplete/files/";
		$globals_ser_file_path = $ser_folder_path . "globals.ser";
		$internal_functions_ser_file_path = $ser_folder_path . "internal_functions.ser";
		$constants_ser_file_path = $ser_folder_path . "constants.ser";
		$classes_ser_file_path = $ser_folder_path . "classes.ser";
		$interfaces_ser_file_path = $ser_folder_path . "interfaces.ser";
		$libs_ser_file_path = $ser_folder_path . "libs.ser";

		//prepare generic completions
		$serialized_completions_files = array($globals_ser_file_path, $internal_functions_ser_file_path, $constants_ser_file_path, $classes_ser_file_path, $interfaces_ser_file_path, $libs_ser_file_path);
		
		foreach ($serialized_completions_files as $ser_file) {
			if (file_exists($ser_file)) {
				$content = file_get_contents($ser_file);
				$arr = unserialize($content);
				
				$completions = array_merge($completions, $arr);
			}
		}
		
		$global_dynamic_vars = array(
			"_GET" => isset($_GET) ? $_GET : null,
			"_POST" => isset($_POST) ? $_POST : null,
			"_REQUEST" => isset($_REQUEST) ? $_REQUEST : null,
			"_FILES" => isset($_FILES) ? $_FILES : null,
			"_COOKIE" => isset($_COOKIE) ? $_COOKIE : null,
			"_ENV" => isset($_ENV) ? $_ENV : null,
			"_SERVER" => isset($_SERVER) ? $_SERVER : null,
			"_SESSION" => isset($_SESSION) ? $_SESSION : null,
		);
		foreach ($global_dynamic_vars as $global_var_name => $global_var)
			if ($global_var && is_array($global_var)) {
				$meta = strtolower(substr($global_var_name, 1));
				$arr = getGlobalDynamicVarValues($meta, $global_var, '$' . $global_var_name);
				
				$completions = array_merge($completions, $arr);
			}
		
		//add current file completions
		$file_abs_path = APP_PATH . $file_path;
		
		if (file_exists($file_abs_path) && $bean_name && $bean_file_name) {
			$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
			$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
			
			$classes = PHPCodePrintingHandler::getPHPClassesFromFile($file_abs_path);
			
			if (is_a($obj, "PresentationLayer")) {
				//by default EVC and all the inner methods are already added
				
				$config_path = $obj->getLayerPathSetting() . "/" . $obj->getCommonProjectName() . "/" . $obj->settings["presentation_configs_path"] . "/config.php";
				$config_completions = getFileVariablesCompletions($config_path);
				
				$evc_completions = array();
				$evc_file_abs_path = get_lib("org.phpframework.layer.presentation.PresentationLayer");
				$evc_classes = file_exists($evc_file_abs_path) ? PHPCodePrintingHandler::getPHPClassesFromFile($evc_file_abs_path) : null;
				
				if ($evc_classes)
					foreach ($evc_classes as $class_name => $class) {
						$methods = isset($class["methods"]) ? $class["methods"] : null;
						
						//add evc methods
						if ($methods)
							foreach ($methods as $method)
								if (empty($method["type"]) || $method["type"] == "public") {
									$defs = getMethodArgsDefinitions($method);
									$definition_args = $defs[0];
									$callable_args = $defs[1];
									
									$evc_completions[] = array(
										"caption" => '$EVC->getPresentationLayer()->' . $method["name"] . '(' . implode(', ', $definition_args) . ')',
										"value" => '$EVC->getPresentationLayer()->' . $method["name"] . '(' . implode(', ', $callable_args) . ')',
										"meta" => "method",
										"score" => 8
									);
								}
						
						//add evc properties
						if ($class_name) {
							$props = PHPCodePrintingHandler::getClassPropertiesFromFile($evc_file_abs_path, $class_name);
							
							if ($props)
								foreach ($props as $prop)
									if ( (empty($prop["type"]) || $prop["type"] == "public") && !$prop["static"] && !$prop["const"])
										$evc_completions[] = array(
											"caption" => '$EVC->getPresentationLayer()->' . $prop["name"],
											"value" => '$EVC->getPresentationLayer()->' . $prop["name"],
											"meta" => "property",
											"score" => 9
										);
						}
					}
				
				$evc_file_abs_path = get_lib("org.phpframework.layer.Layer");
				$evc_classes = file_exists($evc_file_abs_path) ? PHPCodePrintingHandler::getPHPClassesFromFile($evc_file_abs_path) : null;
				
				if ($evc_classes)
					foreach ($evc_classes as $class_name => $class) {
						$methods = isset($class["methods"]) ? $class["methods"] : null;
						
						//add evc methods
						if ($methods)
							foreach ($methods as $method)
								if (empty($method["type"]) || $method["type"] == "public") {
									$defs = getMethodArgsDefinitions($method);
									$definition_args = $defs[0];
									$callable_args = $defs[1];
									
									$evc_completions[] = array(
										"caption" => '$EVC->getPresentationLayer()->' . $method["name"] . '(' . implode(', ', $definition_args) . ')',
										"value" => '$EVC->getPresentationLayer()->' . $method["name"] . '(' . implode(', ', $callable_args) . ')',
										"meta" => "method",
										"score" => 8
									);
								}
						
						//add evc properties
						if ($class_name) {
							$props = PHPCodePrintingHandler::getClassPropertiesFromFile($evc_file_abs_path, $class_name);
							
							if ($props)
								foreach ($props as $prop)
									if ( (empty($prop["type"]) || $prop["type"] == "public") && !$prop["static"] && !$prop["const"])
										$evc_completions[] = array(
											"caption" => '$EVC->getPresentationLayer()->' . $prop["name"],
											"value" => '$EVC->getPresentationLayer()->' . $prop["name"],
											"meta" => "property",
											"score" => 9
										);
						}
					}
				
				if (strpos($pathname, "/admin/edit_file_class_method") !== false) {
					$class = isset($_GET["class"]) ? $_GET["class"] : null;
					$class_method = isset($_GET["method"]) ? $_GET["method"] : null;
					
					//add methods and properties from the same file and arguments of the current method
					$completions = array_merge($completions, getClassesPropertiesCompletions($file_abs_path, $classes, $class));
					$completions = array_merge($completions, getClassesMethodCompletions($classes, $class, $class_method));
					
					//add evc_completions if EVC arg exists
					if (existsEVCArg($classes, $class, $class_method))
						$completions = array_merge($completions, $evc_completions);
				}
				else if (strpos($pathname, "/admin/edit_file_function") !== false) {
					$function = isset($_GET["function"]) ? $_GET["function"] : null;
					
					//add methods from the same file and arguments of the current function
					$completions = array_merge($completions, getClassesMethodCompletions($classes, null, $function));
					
					//add evc_completions if EVC arg exists
					if (existsEVCArg($classes, 0, $function))
						$completions = array_merge($completions, $evc_completions);
				}
				else if (strpos($pathname, "/presentation/edit_util") !== false) {
					$completions = array_merge($completions, $config_completions);
					
					//add functions, methods and properties from the file
					$completions = array_merge($completions, getClassesPropertiesCompletions($file_abs_path, $classes, null));
					$completions = array_merge($completions, getClassesMethodCompletions($classes, null, null));
					$completions = array_merge($completions, $evc_completions);
					
					if ($classes)
						foreach ($classes as $class_name => $class) {
							$methods = isset($class["methods"]) ? $class["methods"] : null;
							
							//add local methods
							if ($methods)
								foreach ($methods as $method)
									$completions = array_merge($completions, getLocalMethodCompletions($method));
							
							//add local properties
							if ($class_name) {
								$props = PHPCodePrintingHandler::getClassPropertiesFromFile($file_abs_path, $class_name);
								
								if ($props)
									foreach ($props as $prop)
										$completions = array_merge($completions, getLocalPropertyCompletions($prop));
							}
						}
				}
				else {
					$completions = array_merge($completions, $config_completions);
				}
			}
			else if (is_a($obj, "BusinessLogicLayer")) {
				$common_file_abs_path = $obj->settings["business_logic_modules_service_common_file_path"];
				$common_classes = file_exists($common_file_abs_path) ? PHPCodePrintingHandler::getPHPClassesFromFile($common_file_abs_path) : null;
				$common_completions = array();
				
				if ($common_classes)
					foreach ($common_classes as $class_name => $class) {
						$methods = isset($class["methods"]) ? $class["methods"] : null;
						
						//add common methods
						if ($methods)
							foreach ($methods as $method)
								if (empty($method["type"]) || $method["type"] == "public")
									$common_completions = array_merge($common_completions, getLocalMethodCompletions($method));
						
						//add common properties
						if ($class_name) {
							$props = PHPCodePrintingHandler::getClassPropertiesFromFile($common_file_abs_path, $class_name);
							
							if ($props)
								foreach ($props as $prop)
									$common_completions = array_merge($common_completions, getLocalPropertyCompletions($prop));
						}
					}
				
				if (strpos($pathname, "/businesslogic/edit_method") !== false) {
					$service = isset($_GET["service"]) ? $_GET["service"] : null;
					$service_method = isset($_GET["method"]) ? $_GET["method"] : null;
					
					//add methods and properties from the same file and arguments of the current method
					$completions = array_merge($completions, $common_completions);
					$completions = array_merge($completions, getClassesPropertiesCompletions($file_abs_path, $classes, $service));
					$completions = array_merge($completions, getClassesMethodCompletions($classes, $service, $service_method));
					
					//add some inner $data variable
					$completions[] = array(
						"caption" => '$data["options"]',
						"value" => '$data["options"]',
						"meta" => "local",
						"score" => 10
					);
					
					//add annotations from $data variable
					if ($classes)
						foreach ($classes as $class_name => $class) {
							$methods = isset($class["methods"]) ? $class["methods"] : null;
							
							if ($methods)
								foreach ($methods as $method) {
									if ($class_name == $service && $method["name"] == $service_method && $method["doc_comments"]) {
										$doc_comments = implode("", $method["doc_comments"]);
										
										if (preg_match_all("/name=data\[([\w\]\[]+)\]/", $doc_comments, $matches, PREG_OFFSET_CAPTURE) && $matches[1]) {
											$names = array();
											
											foreach ($matches[1] as $m) {
												$parts = explode("][", str_replace(array("'", '"'), "", $m[0]));
												$name = '$data';
												
												foreach ($parts as $part) {
													$name .= '["' . $part . '"]';
													
													$names[$name] = 1;
												}
											}
												
											foreach ($names as $name => $aux)
												$completions[] = array(
													"caption" => $name,
													"value" => $name,
													"meta" => "local",
													"score" => 10
												);
										}
									}
								}
						}
						
					//add methods from BusinessLogic file for '$this->getBusinessLogicLayer()' and '$this->BusinessLogicLayer' variable
					$bl_file_abs_path = get_lib("org.phpframework.layer.businesslogic.BusinessLogicLayer");
					$bl_classes = file_exists($bl_file_abs_path) ? PHPCodePrintingHandler::getPHPClassesFromFile($bl_file_abs_path) : null;
					
					if ($bl_classes)
						foreach ($bl_classes as $class_name => $class) {
							if ($class_name == "BusinessLogicLayer") {
								$methods = isset($class["methods"]) ? $class["methods"] : null;
								
								//add common methods
								if ($methods)
									foreach ($methods as $method)
										if (empty($method["type"]) || $method["type"] == "public") {
											$defs = getMethodArgsDefinitions($method);
											$definition_args = $defs[0];
											$callable_args = $defs[1];
											
											$completions[] = array(
												"caption" => '$this->getBusinessLogicLayer()->' . $method["name"] . '(' . implode(', ', $definition_args) . ')',
												"value" => '$this->getBusinessLogicLayer()->' . $method["name"] . '(' . implode(', ', $callable_args) . ')',
												"meta" => "method",
												"score" => 8
											);
											
											$completions[] = array(
												"caption" => '$this->BusinessLogicLayer->' . $method["name"] . '(' . implode(', ', $definition_args) . ')',
												"value" => '$this->BusinessLogicLayer->' . $method["name"] . '(' . implode(', ', $callable_args) . ')',
												"meta" => "method",
												"score" => 8
											);
										}
							
								//add common properties
								$props = PHPCodePrintingHandler::getClassPropertiesFromFile($bl_file_abs_path, $class_name);
								
								if ($props)
									foreach ($props as $prop) 
										if ( (empty($prop["type"]) || $prop["type"] == "public") && !$prop["static"] && !$prop["const"]) {
											$completions[] = array(
												"caption" => '$this->getBusinessLogicLayer()->' . $prop["name"],
												"value" => '$this->getBusinessLogicLayer()->' . $prop["name"],
												"meta" => "property",
												"score" => 9
											);
											$completions[] = array(
												"caption" => '$this->BusinessLogicLayer->' . $prop["name"],
												"value" => '$this->BusinessLogicLayer->' . $prop["name"],
												"meta" => "property",
												"score" => 9
											);
										}
							}
					}
				
					//add methods from BusinessLogic file for '$this->getBusinessLogicLayer()' and '$this->BusinessLogicLayer' variable
					$bl_file_abs_path = get_lib("org.phpframework.layer.Layer");
					$bl_classes = file_exists($bl_file_abs_path) ? PHPCodePrintingHandler::getPHPClassesFromFile($bl_file_abs_path) : null;
					
					if ($bl_classes)
						foreach ($bl_classes as $class_name => $class) {
							if ($class_name == "Layer") {
								$methods = isset($class["methods"]) ? $class["methods"] : null;
								
								//add common methods
								if ($methods)
									foreach ($methods as $method)
										if (empty($method["type"]) || $method["type"] == "public") {
											$defs = getMethodArgsDefinitions($method);
											$definition_args = $defs[0];
											$callable_args = $defs[1];
											
											$completions[] = array(
												"caption" => '$this->getBusinessLogicLayer()->' . $method["name"] . '(' . implode(', ', $definition_args) . ')',
												"value" => '$this->getBusinessLogicLayer()->' . $method["name"] . '(' . implode(', ', $callable_args) . ')',
												"meta" => "method",
												"score" => 8
											);
											
											$completions[] = array(
												"caption" => '$this->BusinessLogicLayer->' . $method["name"] . '(' . implode(', ', $definition_args) . ')',
												"value" => '$this->BusinessLogicLayer->' . $method["name"] . '(' . implode(', ', $callable_args) . ')',
												"meta" => "method",
												"score" => 8
											);
										}
							
								//add common properties
								$props = PHPCodePrintingHandler::getClassPropertiesFromFile($bl_file_abs_path, $class_name);
								
								if ($props)
									foreach ($props as $prop) 
										if ( (empty($prop["type"]) || $prop["type"] == "public") && !$prop["static"] && !$prop["const"]) {
											$completions[] = array(
												"caption" => '$this->getBusinessLogicLayer()->' . $prop["name"],
												"value" => '$this->getBusinessLogicLayer()->' . $prop["name"],
												"meta" => "property",
												"score" => 9
											);
											$completions[] = array(
												"caption" => '$this->BusinessLogicLayer->' . $prop["name"],
												"value" => '$this->BusinessLogicLayer->' . $prop["name"],
												"meta" => "property",
												"score" => 9
											);
										}
							}
						}
				}
				else if (strpos($pathname, "/businesslogic/edit_function") !== false) {
					$function = isset($_GET["function"]) ? $_GET["function"] : null;
					
					//add methods from the same file and arguments of the current function
					$completions = array_merge($completions, getClassesMethodCompletions($classes, null, $function));
				}
				else if (strpos($pathname, "/businesslogic/edit_file") !== false) {
					//add functions, methods and properties from the file
					$completions = array_merge($completions, $common_completions);
					$completions = array_merge($completions, getClassesPropertiesCompletions($file_abs_path, $classes, null));
					$completions = array_merge($completions, getClassesMethodCompletions($classes, null, null));
					
					if ($classes)
						foreach ($classes as $class_name => $class) {
							$methods = isset($class["methods"]) ? $class["methods"] : null;
							
							//add local methods
							if ($methods)
								foreach ($methods as $method)
									$completions = array_merge($completions, getLocalMethodCompletions($method));
							
							//add local properties
							if ($class_name) {
								$props = PHPCodePrintingHandler::getClassPropertiesFromFile($file_abs_path, $class_name);
								
								if ($props)
									foreach ($props as $prop)
										$completions = array_merge($completions, getLocalPropertyCompletions($prop));
							}
						}
				}
			}
		}
	}
}

echo json_encode($completions);
die();

function getGlobalDynamicVarValues($meta, $vars, $prefix) {
	$arr = array();
	
	foreach ($vars as $k => $v) {
			$var_caption = $prefix . '["' . $k . '"]';
			
			$arr[] = array(
				"caption" => $var_caption,
				"value" => $var_caption,
				"meta" => $meta,
				"score" => 5
			);
			
			if (is_array($v)) {
				$sub_arr = getGlobalDynamicVarValues($meta, $vars, $var_caption);
				$arr = array_merge($arr, $sub_arr);
			}
		}
	
	return $arr;
}

function getFileVariablesCompletions($file_path) {
	$completions = array();
	
	$vars = PHPVariablesFileHandler::getVarsFromFileContent($file_path);
	
	if ($vars)
		foreach ($vars as $var_name => $var_value)
			$completions[] = array(
				"caption" => $var_name,
				"value" => $var_name,
				"meta" => "local",
				"score" => 11
			);
	
	return $completions;
}

function getClassesMethodCompletions($classes, $current_class, $current_method) {
	$completions = array();
	
	if ($classes)
		foreach ($classes as $class_name => $class) {
			$methods = isset($class["methods"]) ? $class["methods"] : null;
			
			if ($methods)
				foreach ($methods as $method) {
					//if class is the same than current_class
					if ($class_name && $class_name == $current_class)
						$completions = array_merge($completions, getLocalMethodCompletions($method));
					
					//add arguments as completions for methods and functions
					if ((!$class_name || $class_name == $current_class) && $method["name"] == $current_method) {
						if ($method["arguments"])
							foreach ($method["arguments"] as $arg_expr => $default_value) {
								$parts = explode(" ", $arg_expr);
								$arg_name = $parts[count($parts) - 1];
								$arg_name = substr($arg_name, 0, 1) == "&" ? substr($arg_name, 1) : $arg_name;
								
								$completions[] = array(
									"caption" => $arg_name,
									"value" => $arg_name,
									"meta" => "local",
									"score" => 10
								);
							}
					}
					
					//static methods
					$defs = getMethodArgsDefinitions($method);
					$definition_args = $defs[0];
					$callable_args = $defs[1];
					
					if ($class_name && $method["static"] && (empty($method["type"]) || $method["type"] == "public")) {
						$method_name = $class_name . "::" . $method["name"];
						
						$completions[] = array(
							"caption" => $method_name . '(' . implode(', ', $definition_args) . ')',
							"value" => $method_name . '(' . implode(', ', $callable_args) . ')',
							"meta" => "method",
							"score" => 7
						);
					}
					else if (!$class_name) //functions
						$completions[] = array(
							"caption" => $method["name"] . '(' . implode(', ', $definition_args) . ')',
							"value" => $method["name"] . '(' . implode(', ', $callable_args) . ')',
							"meta" => "method",
							"score" => 7
						);
				}
		}
	
	return $completions;
}


function getClassesPropertiesCompletions($path, $classes, $current_class) {
	$completions = array();
	
	if ($classes)
		foreach ($classes as $class_name => $class) {
			if ($class_name) {
					$props = PHPCodePrintingHandler::getClassPropertiesFromFile($path, $class_name);
					
					if ($props)
						foreach ($props as $prop) {
							//if class is the same than current_class
							if ($class_name == $current_class)
								$completions = array_merge($completions, getLocalPropertyCompletions($prop));
							
							if (empty($prop["type"]) || $prop["type"] == "public") {
								$prop_name = $prop["name"];
								
								if ($prop["static"])
									$prop_expr = $class_name . '::$' . $prop_name;
								else if ($prop["const"])
									$prop_expr = $class_name . '::' . $prop_name;
								else
									$prop_expr = '$' . $class_name . '->' . $prop_name;
								
								$completions[] = array(
									"caption" => $prop_expr,
									"value" => $prop_expr,
									"meta" => "property",
									"score" => 9
								);
							}
						}
				}
		}
	
	return $completions;
}

function getLocalPropertyCompletions($prop) {
	$completions = array();
	$prop_name = $prop["name"];
	
	if ($prop["static"])
		$prop_expr = 'self::$' . $prop_name;
	else if ($prop["const"])
		$prop_expr = 'self::' . $prop_name;
	else
		$prop_expr = '$this->' . $prop_name;
	
	$completions[] = array(
		"caption" => $prop_expr,
		"value" => $prop_expr,
		"meta" => "property",
		"score" => 9
	);
	
	return $completions;
}

function getLocalMethodCompletions($method) {
	$completions = array();
	
	$defs = getMethodArgsDefinitions($method);
	$definition_args = $defs[0];
	$callable_args = $defs[1];
	
	$method_name = '$this->' . $method["name"];
	
	$completions[] = array(
		"caption" => $method_name . '(' . implode(', ', $definition_args) . ')',
		"value" => $method_name . '(' . implode(', ', $callable_args) . ')',
		"meta" => "method",
		"score" => 8
	);
	
	if ($method["static"]) {
		$method_name = "self::" . $method["name"];
		
		$completions[] = array(
			"caption" => $method_name . '(' . implode(', ', $definition_args) . ')',
			"value" => $method_name . '(' . implode(', ', $callable_args) . ')',
			"meta" => "method",
			"score" => 8
		);
	}
	
	return $completions;
}

function getMethodArgsDefinitions($method) {
	$definition_args = array();
	$callable_args = array();
	
	if ($method["arguments"])
		foreach ($method["arguments"] as $arg_expr => $default_value) {
			$parts = explode(" ", $arg_expr);
			$arg_name = $parts[count($parts) - 1];
			$arg_name = substr($arg_name, 0, 1) == "&" ? substr($arg_name, 1) : $arg_name;
			
			$definition_arg .= $arg_expr;
			$callable_arg = $arg_name;
			
			if (!isset($default_value))
				$default_value = "null";
			else if (is_string($default_value))
				$default_value = "\"$default_value\"";
			else if (is_bool($default_value))
				$default_value = $default_value ? "true" : "false";
			else if (!is_numeric($default_value))
				$default_value = json_encode($default_value);
			
			$definition_arg .= ' = ' . $default_value;
			
			$definition_args[] = $definition_arg;
			$callable_args[] = $callable_arg;
		}
	
	return array($definition_args, $callable_args);
}

function existsEVCArg($classes, $current_class, $current_method) {
	if ($classes)
		foreach ($classes as $class_name => $class) {
			$methods = isset($class["methods"]) ? $class["methods"] : null;
			
			if ($methods)
				foreach ($methods as $method)
					if ($class_name == $current_class && $method["name"] == $current_method && $method["arguments"])
						foreach ($method["arguments"] as $arg_expr => $default_value) {
							$parts = explode(" ", $arg_expr);
							$arg_name = $parts[count($parts) - 1];
							$arg_name = substr($arg_name, 0, 1) == "&" ? substr($arg_name, 1) : $arg_name;
							$arg_name = substr($arg_name, 0, 1) == "$" ? substr($arg_name, 1) : $arg_name;
							
							if ($arg_name == "EVC")
								return true;
						}
		}
	
	return false;
}
?>
