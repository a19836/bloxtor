<?php
include_once get_lib("org.phpframework.phpscript.PHPCodePrintingHandler");
include_once get_lib("org.phpframework.phpscript.docblock.DocBlockParser");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");

class WorkFlowTestUnitHandler {
	private $global_variables_file_path;
	private $beans_folder_path;
	private $bean_objects;
	
	public function __construct($global_variables_file_path, $beans_folder_path) {
		$this->global_variables_file_path = $global_variables_file_path;
		$this->beans_folder_path = $beans_folder_path; //BEAN_PATH
	}
	
	public function setBeanObjects($bean_objects) {
		$this->bean_objects = $bean_objects;
	}
	
	public function getBeanObjects($bean_objects) {
		return $this->bean_objects;
	}
	
	public function initBeanObjects() {
		$this->bean_objects = WorkFlowBeansFileHandler::getAllBeanObjects($this->global_variables_file_path, $this->beans_folder_path);
	}
	
	public static function getAllLayersBrokersSettings($global_variables_file_path, $beans_folder_path) {
		$db_driver_brokers = array();
		$db_driver_brokers_obj = array();

		$db_brokers = array();
		$db_brokers_obj = array();

		$data_access_brokers = array();
		$data_access_brokers_obj = array();

		$ibatis_brokers = array();
		$ibatis_brokers_obj = array();

		$hibernate_brokers = array();
		$hibernate_brokers_obj = array();

		$business_logic_brokers = array();
		$business_logic_brokers_obj = array();

		$presentation_brokers = array();
		$presentation_brokers_obj = array();
		
		$presentation_evc_brokers = array();
		$presentation_evc_brokers_obj = array();
		
		$presentation_evc_template_brokers = array();
		$presentation_evc_template_brokers_obj = array();
		
		$available_projects = array();
		
		//SET USER GLOBAL VARIABLES
		$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_path);
		$PHPVariablesFileHandler->startUserGlobalVariables();
		
		//PREPARE BEANS
		if (is_dir($beans_folder_path) && ($dir = opendir($beans_folder_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, strlen($file) - 4) == ".xml" && $file != "app.xml" && !strstr($file, "_bll_common_services.xml", true)) {
					$BeanFactory = new BeanFactory();
					$BeanFactory->init(array("file" => $beans_folder_path . $file));
					$beans = $BeanFactory->getBeans();
					$BeanFactory->initObjects();
					
					foreach ($beans as $bean_name => $bean) {
						$obj = $BeanFactory->getObject($bean_name);
						
						if (is_a($obj, "ILayer")) {
							$layer_name = WorkFlowBeansFileHandler::getLayerNameFromBeanObject($bean_name, $obj);
							$broker_name = WorkFlowBeansConverter::getBrokerNameFromRawLabel($layer_name);
							
							$props = array(
								$broker_name, //broker_name
								$file, //bean_file_name
								$bean_name, //bean_name
							);
							
							if (is_a($obj, "DBLayer")) {
								$db_brokers[] = $props;
								$db_brokers_obj[$broker_name] = '$this->getDBLayer("' . $broker_name . '")';
							}
							else if (is_a($obj, "DataAccessLayer")) {
								$data_access_brokers[] = $props;
								$data_access_brokers_obj[$broker_name] = '$this->getDataAcessLayer("' . $broker_name . '")';
								
								if (is_a($obj, "HibernateDataAccessLayer")) {
									$hibernate_brokers[] = $props;
									$hibernate_brokers_obj[$broker_name] = '$this->getHibernateLayer("' . $broker_name . '")';
								}
								else {
									$ibatis_brokers[] = $props;
									$ibatis_brokers_obj[$broker_name] = '$this->getIbatisLayer("' . $broker_name . '")';
								}
							}
							else if (is_a($obj, "BusinessLogicLayer")) {
								$business_logic_brokers[] = $props;
								$business_logic_brokers_obj[$broker_name] = '$this->getBusinessLogicLayer("' . $broker_name . '")';
							}
							else if (is_a($obj, "PresentationLayer")) {
								$presentation_brokers[] = $props;
								$presentation_brokers_obj[$broker_name] = '$this->getPresentationLayer("' . $broker_name . '")';
							}
						}
						else if (is_a($obj, "EVC")) {
							$presentation_bean_name = null;
							
							if ($bean->properties)
								foreach ($bean->properties as $property)
									if ($property->name == "presentationLayer") {
										$presentation_bean_name = $property->reference;
										break;
									}
							
							if ($presentation_bean_name) { //$property->reference can be null
								$layer_name = WorkFlowBeansFileHandler::getLayerNameFromBeanObject($presentation_bean_name, $obj->getPresentationLayer());
								$broker_name = WorkFlowBeansConverter::getBrokerNameFromRawLabel($layer_name);
								
								$presentation_evc_brokers[] = array(
									$broker_name,//broker_name
									$file,//bean_file_name
									$presentation_bean_name,//bean_name
								);
								$presentation_evc_brokers_obj[$broker_name] = '$this->getPresentationLayerEVC("' . $broker_name . '")';
								
								$presentation_evc_template_brokers[] = $presentation_evc_brokers[ count($presentation_evc_brokers) - 1 ];
								$presentation_evc_template_brokers_obj[$broker_name] = '$this->getPresentationLayerEVC("' . $broker_name . '")->getCMSLayer()->getCMSTemplateLayer()';
								
								$available_projects = array_merge($available_projects, $obj->getProjectsId());
							}
						}
						else if (is_a($obj, "DB")) {
							$broker_name = WorkFlowBeansConverter::getBrokerNameFromRawLabel($bean_name);
							
							$props = array(
								$broker_name, //broker_name
								$file, //bean_file_name
								$bean_name, //bean_name
							);
							
							$db_driver_brokers[] = $props;
							$db_driver_brokers_obj[$broker_name] = '$this->getDBDriver("' . $broker_name . '")';
						}
					}
				}
			}
			closedir($dir);
		}
		
		//ROLLBACK TO ORIGINAL GLOBAL VARIABLES
		$PHPVariablesFileHandler->endUserGlobalVariables();
		
		return array(
			"db_driver_brokers" => $db_driver_brokers,
			"db_driver_brokers_obj" => $db_driver_brokers_obj,
			"db_brokers" => $db_brokers,
			"db_brokers_obj" => $db_brokers_obj,
			"data_access_brokers" => $data_access_brokers,
			"data_access_brokers_obj" => $data_access_brokers_obj,
			"ibatis_brokers" => $ibatis_brokers,
			"ibatis_brokers_obj" => $ibatis_brokers_obj,
			"hibernate_brokers" => $hibernate_brokers,
			"hibernate_brokers_obj" => $hibernate_brokers_obj,
			"business_logic_brokers" => $business_logic_brokers,
			"business_logic_brokers_obj" => $business_logic_brokers_obj,
			"presentation_brokers" => $presentation_brokers,
			"presentation_brokers_obj" => $presentation_brokers_obj,
			"presentation_evc_brokers" => $presentation_evc_brokers,
			"presentation_evc_brokers_obj" => $presentation_evc_brokers_obj,
			"presentation_evc_template_brokers" => $presentation_evc_template_brokers,
			"presentation_evc_template_brokers_obj" => $presentation_evc_template_brokers_obj,
			"available_projects" => $available_projects,
		);
	}
	
	public function executeTest($test_path, &$responses_by_path, $class_name = null) {
		if (isset($responses_by_path[$test_path]) && is_array($responses_by_path[$test_path]))
			return $responses_by_path[$test_path]["status"];
		
		$response = array();
		$file_path = TEST_UNIT_PATH . $test_path;
		
		if (!file_exists($file_path)) 
			$response["error"] = "File not found! Path: $test_path";
		else {
			if (is_dir($file_path)) {
				$files = scandir($file_path);
				$sub_test_files = array();
				
				if ($files)
					foreach ($files as $file)
						if ($file != "." && $file != ".." && (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == "php" || is_dir("$file_path/$file")))
							$sub_test_files[] = $file;
				
				if ($sub_test_files) {
					$status = true;
					$tp = $test_path . ($test_path && substr($test_path, -1) != "/" ? "/" : "");
					
					foreach ($sub_test_files as $file) {
						$sub_test_path = $tp . $file . (is_dir("$file_path/$file") ? "/" : "");
						
						if (!$this->executeTest($sub_test_path, $responses_by_path, $class_name))
							$status = false;
					}
					
					$response["status"] = $status;
					
					if (!$status)
						$response["error"] = "There was a sub test inside of this folder that didn't executed successfuly!";
				}
				else {
					unset($responses_by_path[$test_path]);
					return true;
				}
			}
			else {
				$class_name = $class_name ? $class_name : pathinfo($test_path, PATHINFO_FILENAME);
				
				//parse doc comments annotations
				$file_data = PHPCodePrintingHandler::getFunctionFromFile($file_path, "execute", $class_name);
				$comments = !empty($file_data["doc_comments"]) ? implode("\n", $file_data["doc_comments"]) : "";
				$DocBlockParser = new DocBlockParser();
				$DocBlockParser->ofComment($comments);
				$doc_comments_objects = $DocBlockParser->getObjects();
				$is_enabled = !empty($doc_comments_objects["enabled"]);
				
				//check if test is enabled
				if (!$is_enabled)
					$response["error"] = "Test is disabled";
				else {
					include_once $file_path;
					
					if ($class_name && is_subclass_of($class_name, "TestUnit")) {
						//get global_variables_files_path
						$test_global_variables_files_path = array();
						$global_variables_files_path = isset($doc_comments_objects["global_variables_files_path"]) ? $doc_comments_objects["global_variables_files_path"] : null;
						if (is_array($global_variables_files_path))
							foreach ($global_variables_files_path as $global_variables_file_path)
								$test_global_variables_files_path[] = trim($global_variables_file_path->getArgs());
						
						//create test class object
						$obj = new $class_name();
						
						//set bean objects
						if ($test_global_variables_files_path) {
							$test_global_variables_files_path = is_array($test_global_variables_files_path) ? $test_global_variables_files_path : array($test_global_variables_files_path);
							
							if (is_array($this->global_variables_file_path))
								$test_global_variables_files_path = array_merge($this->global_variables_file_path, $test_global_variables_files_path);
							else
								array_unshift($test_global_variables_files_path, $this->global_variables_file_path);
							
							$test_bean_objects = WorkFlowBeansFileHandler::getAllBeanObjects($test_global_variables_files_path, $this->beans_folder_path);
							
							$obj->setBeanObjects($test_bean_objects);
						}
						else
							$obj->setBeanObjects($this->bean_objects);
						
						//get dependencies
						$continue = true;
						$depends = isset($doc_comments_objects["depends"]) ? $doc_comments_objects["depends"] : null;
						
						if (is_array($depends))
							foreach ($depends as $depend) {
								$args = $depend->getArgs();
								$depend_path = isset($args["path"]) ? trim($args["path"]) : "";
								
								if ($depend_path) {
									$depend_extension = pathinfo($depend_path, PATHINFO_EXTENSION);
									$depend_path .= ($depend_extension ? "" : ".php");
									
									$depend_response = $this->executeTest($depend_path, $responses_by_path);
									
									if (!$depend_response) {
										$continue = false;
										$response["error"] = "The following dependencie didn't executed correctly: $depend_path!" . (!empty($depend_response["error"]) ? "\n" . $depend_response["error"] : "");
										break;
									}
								}
							}
							
						//execute test
						if ($continue) {
							$response["status"] = $obj->execute();
							$errors = $obj->getErrors();
							
							if (!$response["status"])
								$response["error"] = "$class_name didn't execute correctly!";
							
							if ($errors)
								$response["error"] .= (!empty($response["error"]) ? "\n" : "") . implode("\n", $errors);
						}
					}
					else
						$response["error"] = "$class_name class must implement the TestUnit class!";
				}
			}
		}
		
		$responses_by_path[$test_path] = $response;
		
		return isset($response["status"]) ? $response["status"] : null;
	}
	
	public static function getGlobalVariablesFilePathHTML($path = false) {
		$html = '
		<tr class="global_variables_file_path">
			<td class="path">
				<input type="text" value="' . str_replace('"', "&quot;", $path) . '" />
			</td>
			<td class="icon_cell table_header"><span class="icon delete" onClick="$(this).parent().parent().remove();" title="Remove">Remove</span></td>
		</tr>';
		
		return $html;
	}
	
	public static function getAnnotationHTML($attrs = false, $annotation_type = false) {
		$path = $description = $others = "";
		
		if (is_array($attrs)) {
			$path = isset($attrs["path"]) ? $attrs["path"] : null;
			$description = isset($attrs["desc"]) ? str_replace('\\"', '"', $attrs["desc"]) : "";
			
			foreach ($attrs as $k => $v) 
				if ($k != "path" && $k != "desc")
					$others .= ($others ? ", " : "") . "$k=$v";
		}
		
		$html = '
		<tr class="annotation">
			<td class="annotation_type">
				<select>
					<option value="depends"' . ($annotation_type == "depends" ? ' selected' : '') . '>Depends</option>
				</select>
			</td>
			<td class="path">
				<input type="text" value="' . str_replace('"', "&quot;", $path) . '" />
			</td>
			<td class="description">
				<input type="text" value="' . str_replace('"', "&quot;", $description) . '" />
			</td>
			<td class="others">
				<input type="text" value="' . str_replace('"', "&quot;", $others) . '" />
			</td>
			<td class="icon_cell table_header"><span class="icon delete" onClick="$(this).parent().parent().remove();" title="Remove">Remove</span></td>
		</tr>';
		
		return $html;
	}
	
	public static function saveTestFile($file_path, $object, $class_name) {
		if ($file_path && is_file($file_path) && $class_name) {
			self::prepareObjectComments($object);
			
			$object = $object ? $object : array();
			$object["name"] = "execute";
			$object["type"] = "public";
			//$object["arguments"] = array("settings" => "false"); //"execute" method doesn't have any arguments
			
			if (PHPCodePrintingHandler::getFunctionFromFile($file_path, $object["name"], $class_name)) {
				PHPCodePrintingHandler::editFunctionCommentsFromFile($file_path, $object["name"], "", $class_name);
				$status = PHPCodePrintingHandler::editFunctionFromFile($file_path, array("name" => $object["name"]), $object, $class_name);
			}
			else
				$status = PHPCodePrintingHandler::addFunctionToFile($file_path, $object, $class_name);
		}
		
		return isset($status) ? $status : null;
	}
	
	private static function prepareObjectComments(&$object) {
		$comments = isset($object["comments"]) && trim($object["comments"]) ? " * " . str_replace("\n", "\n * ", trim($object["comments"])) . "\n" : "";
		
		if (!empty($object["enabled"])) {
			$comments .= $comments ? " * \n" : "";
			$comments .= " * @enabled\n";
		}
		
		if (isset($object["global_variables_files_path"]) && is_array($object["global_variables_files_path"])) {
			$comments .= $comments ? " * \n" : "";
			
			foreach ($object["global_variables_files_path"] as $global_variables_file_path) {
				$path = trim($global_variables_file_path["path"]);
				
				if ($path)
					$comments .= " * @global_variables_files_path " . $path . "\n";
			}
		}
		
		if (isset($object["annotations"]) && is_array($object["annotations"])) {
			$comments .= $comments ? " * \n" : "";
			
			foreach ($object["annotations"] as $annotation_type => $annotations) {
				$at = strtolower($annotation_type);
				
				$t = $annotations ? count($annotations) : 0;
				for ($i = 0; $i < $t; $i++) {
					$annotation = $annotations[$i];
					$path = isset($annotation["path"]) ? trim($annotation["path"]) : "";
					$desc = isset($annotation["desc"]) ? trim($annotation["desc"]) : "";
					
					$args = "";
					$args .= $path ? ($args ? ", " : "") . "path=" . $path : "";
					$args .= isset($annotation["others"]) && trim($annotation["others"]) ? ($args ? ", " : "") . $annotation["others"] : "";
					
					if ($args || trim($desc))
						$comments .= " * @$at ($args) " . addcslashes($desc, '"') . "\n";
				}
			}
		}
		
		$object["comments"] = $comments ? "/**\n$comments */" : "";
	}
}
?>
