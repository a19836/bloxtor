<?php
include_once $EVC->getUtilPath("AdminMenuHandler");

class LayoutTypeProjectHandler {
	private $UserAuthenticationHandler;
	private $global_variables_file_path;
	private $beans_folder_path;
	private $bean_file_name;
	private $bean_name;
	
	private $loaded_layouts = array();
	private $loaded_layouts_type_permissions_by_object = array();
	
	public function __construct($UserAuthenticationHandler, $global_variables_file_path, $beans_folder_path, $bean_file_name = null, $bean_name = null) {
		$this->UserAuthenticationHandler = $UserAuthenticationHandler;
		$this->global_variables_file_path = $global_variables_file_path;
		$this->beans_folder_path = $beans_folder_path;
		$this->bean_file_name = $bean_file_name;
		$this->bean_name = $bean_name;
	}
	
	/* SETUP PUBLIC METHODS (used when setuping the PHPFramework) */
	
	//used in the util/WorkFlowBeansConverter.php
	public function createPresentationLayerSetupDefaultProjectLayouts($setup_project_name, $create_layers_project_folder_if_not_exists = true) {
		$status = true;
		
		$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($this->beans_folder_path . $this->bean_file_name, $this->global_variables_file_path);
		$PresentationLayer = $WorkFlowBeansFileHandler->getBeanObject($this->bean_name);
		$layer_path = $PresentationLayer->getLayerPathSetting();
		$common_project_name = $PresentationLayer->getCommonProjectName();
		
		//create layout for common project
		if ($common_project_name) {
			$project_path = $layer_path . $common_project_name;
			
			if (!$this->createNewLayoutFromProjectPath($project_path, $create_layers_project_folder_if_not_exists))
				$status = false;
		}
		
		//create layout for default project
		if ($setup_project_name) {
			$project_path = $layer_path . $setup_project_name;
			
			if (!$this->createNewLayoutFromProjectPath($project_path, $create_layers_project_folder_if_not_exists))
				$status = false;
		}
		
		return $status;
	}
	
	/* FILTER PUBLIC METHODS */
	
	public function filterPresentationLayersProjectsByUserAndLayoutPermissions(&$layers_projects, $filter_by_layout = null, $filter_by_layout_permission = null, $options = null) {
		if ($layers_projects) {
			if ($filter_by_layout) {
				if (!$filter_by_layout_permission)
					$filter_by_layout_permission = array(UserAuthenticationHandler::$PERMISSION_BELONG_NAME, UserAuthenticationHandler::$PERMISSION_REFERENCED_NAME);
				
				//load permissions
				$this->UserAuthenticationHandler->loadLayoutPermissions($filter_by_layout, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
				
				$dnf = $options && $options["do_not_filter_by_layout"] ? $options["do_not_filter_by_layout"] : null;
			}
			
			foreach ($layers_projects as $bean_name => $props) {
				$bean_file_name = $props["bean_file_name"];
				
				$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($this->beans_folder_path . $bean_file_name, $bean_name, $this->global_variables_file_path);
				$layer_object_id = LAYER_PATH . $layer_bean_folder_name;
				
				$do_not_filter_layer_by_layout = $dnf && $dnf["bean_name"] == $bean_name && $dnf["bean_file_name"] == $bean_file_name;
				//echo "do_not_filter_layer_by_layout:$do_not_filter_layer_by_layout\n<br>";
				
				if (!$this->UserAuthenticationHandler->isInnerFilePermissionAllowed($layer_object_id, "layer", "access"))
					unset($layers_projects[$bean_name]);
				else if ($filter_by_layout && !$do_not_filter_layer_by_layout && !$this->UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($layer_object_id, $filter_by_layout, "layer", $filter_by_layout_permission))
					unset($layers_projects[$bean_name]);
				else {
					$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($this->UserAuthenticationHandler, $this->global_variables_file_path, $this->beans_folder_path, $bean_file_name, $bean_name);
					$LayoutTypeProjectHandler->filterPresentationLayerProjectsByUserAndLayoutPermissions($layers_projects[$bean_name]["projects"], $filter_by_layout, $filter_by_layout_permission, $options);
					//echo "<pre>";print_r($layers_projects[$bean_name]["projects"]);die();
				}
			}
		}
	}
	
	public function filterPresentationLayerProjectsByUserAndLayoutPermissions(&$projects, $filter_by_layout = null, $filter_by_layout_permission = null, $options = null) {
		$this->validateBeanNameAndBeanFileName();
		
		if ($projects) {
			$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($this->beans_folder_path . $this->bean_file_name, $this->bean_name, $this->global_variables_file_path);
			$layer_object_id = LAYER_PATH . $layer_bean_folder_name;
			
			if ($filter_by_layout) {
				if (!$filter_by_layout_permission)
					$filter_by_layout_permission = array(UserAuthenticationHandler::$PERMISSION_BELONG_NAME, UserAuthenticationHandler::$PERMISSION_REFERENCED_NAME);
			
				//load permissions
				$this->UserAuthenticationHandler->loadLayoutPermissions($filter_by_layout, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
				
				$dnf = $options && $options["do_not_filter_by_layout"] ? $options["do_not_filter_by_layout"] : null;
				$do_not_filter_layer_by_layout = $dnf && $dnf["bean_name"] == $this->bean_name && $dnf["bean_file_name"] == $this->bean_file_name;
				//echo "do_not_filter_layer_by_layout:$do_not_filter_layer_by_layout\n<br>";
			}
			
			foreach ($projects as $project_name => $project_props) {
				$fn_layer_object_id = "$layer_object_id/$project_name";
				$do_not_filter_project_by_layout = $do_not_filter_layer_by_layout && $project_name == $dnf["project"];
				//echo "$project_name:".$dnf["project"]."\n<br>";
				//echo "do_not_filter_project_by_layout:$do_not_filter_project_by_layout\n<br>";
				
				if (!$this->UserAuthenticationHandler->isInnerFilePermissionAllowed($fn_layer_object_id, "layer", "access")) //check if user has permission to this project
					unset($projects[$project_name]);
				else if ($filter_by_layout && !$do_not_filter_project_by_layout && !$this->UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($fn_layer_object_id, $filter_by_layout, "layer", $filter_by_layout_permission)) //if filter_by_layout: check if sub_files belong or are referenced to selected project
					unset($projects[$project_name]);
			}
		}
	}
	
	public function filterLayerBrokersDBDriversPropsBasedInUrl(&$db_drivers, $url) {
		if ($db_drivers && $url && strpos($url, "?") !== false) {
			$query_string = substr($url, strpos($url, "?") + 1); //Do not use parse_url bc the $url contains bean_name=#bean_name# and the parse_url will parse incorrect the url bc of the "#" char.
			parse_str($query_string, $arr);
			$filter_by_layout = $arr["filter_by_layout"];
			//echo "filter_by_layout:$filter_by_layout\n";die();
			
			if ($filter_by_layout)
				$this->filterLayerBrokersDBDriversPropsFromLayoutName($db_drivers, $filter_by_layout, $arr["filter_by_layout_permission"]);
		}
	}
	
	//$db_drivers is a numeric array with names
	public function filterLayerBrokersDBDriversNamesFromLayoutName($Layer, &$db_drivers, $filter_by_layout, $filter_by_layout_permission = null) {
		if ($db_drivers && $filter_by_layout) {
			if (!$this->layer_db_drivers_props)
				$this->layer_db_drivers_props = WorkFlowBeansFileHandler::getLayerDBDrivers($this->global_variables_file_path, $this->beans_folder_path, $Layer, true);
			//echo "<pre>layer_db_drivers_props:".print_r($this->layer_db_drivers_props, 1);die();
			
			if ($this->layer_db_drivers_props) {
				$db_drivers_flipped = array_flip($db_drivers);
				$db_drivers_filtered = array_intersect_key($this->layer_db_drivers_props, $db_drivers_flipped);
				//echo "<pre>db_drivers_filtered:".print_r($db_drivers_filtered, 1);die();
			
				$this->filterLayerBrokersDBDriversPropsFromLayoutName($db_drivers_filtered, $filter_by_layout, $filter_by_layout_permission);
				$db_drivers = array_keys($db_drivers_filtered);
				//echo "<pre>db_drivers:".print_r($db_drivers, 1);die();
			}
		}
	}
	
	//$db_drivers is an associative array with names => props
	public function filterLayerBrokersDBDriversPropsFromLayoutName(&$db_drivers, $filter_by_layout, $filter_by_layout_permission = null) {
		if ($db_drivers && $filter_by_layout) {
			//get db layers folders
			if (!$this->layer_objs)
				$this->layer_objs = WorkFlowBeansFileHandler::getAllLayersBeanObjs($this->global_variables_file_path, $this->beans_folder_path);
			//echo "<pre>layer_objs:".print_r(array_keys($this->layer_objs), 1);die();
			
			$db_layers_prefix_paths = array();
			foreach ($this->layer_objs as $bean_name => $layer_obj)
				if (is_a($layer_obj, "DBLayer"))
					$db_layers_prefix_paths[] = $layer_obj->getLayerPathSetting();
			//echo "<pre>db_layers_prefix_paths:".print_r($db_layers_prefix_paths, 1);die();
			
			if ($db_layers_prefix_paths) {
				//load permissions
				$this->UserAuthenticationHandler->loadLayoutPermissions($filter_by_layout, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
				
				$filter_by_layout_permission = $filter_by_layout_permission ? $filter_by_layout_permission : UserAuthenticationHandler::$PERMISSION_BELONG_NAME;
				//echo "filter_by_layout_permission:".print_r($filter_by_layout_permission, 1);die();
				//filter db_drivers by $filter_by_layout
				foreach ($db_drivers as $k => $db_driver_props) //$k could be the db_driver_name or a numeric index
					if ($db_driver_props) {
						$is_allowed = false;
						
						foreach ($db_layers_prefix_paths as $db_layer_prefix_path) {
							$layer_db_driver_object_path = $db_layer_prefix_path . "/" . $db_driver_props[2]; //$db_driver_props => db_driver bean name
							//echo "layer_db_driver_object_path:$layer_db_driver_object_path\n";die();
							$layer_db_driver_object_id = $this->getLayoutTypePermissionObjectIdFromFilePath($layer_db_driver_object_path);
							//echo "layer_db_driver_object_id:$layer_db_driver_object_id\n";die();
							
							if ($this->UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($layer_db_driver_object_id, $filter_by_layout, "layer", $filter_by_layout_permission)) 
								$is_allowed = true;
						}
						
						if (!$is_allowed)
							unset($db_drivers[$k]);
					}
			}
		}
	}
	
	/* VALIDATION PUBLIC METHODS */
	
	public function isPathAPresentationProjectPath($project_path) {
		$this->validateBeanNameAndBeanFileName();
		
		if (is_dir($project_path)) {
			$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($this->beans_folder_path . $this->bean_file_name, $this->global_variables_file_path);
			$PresentationLayer = $WorkFlowBeansFileHandler->getBeanObject($this->bean_name);
			
			if ($PresentationLayer) {
				$webroot_file_relative_path = $PresentationLayer->settings["presentation_webroot_path"];
				$is_path_a_project = $project_path ? is_dir("$project_path/$webroot_file_relative_path") : false;
				
				return $is_path_a_project;
			}
		}
		
		return false;
	}
	
	public function isPathAPresentationProjectFolderPath($project_path) {
		$this->validateBeanNameAndBeanFileName();
		
		if (is_dir($project_path)) {
			$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($this->beans_folder_path . $this->bean_file_name, $this->global_variables_file_path);
			$PresentationLayer = $WorkFlowBeansFileHandler->getBeanObject($this->bean_name);
			
			if ($PresentationLayer) {
				$layer_path = $PresentationLayer->getLayerPathSetting();
				
				if (strpos($project_path, $layer_path) === 0 && strpos($project_path, "/src/") === false && strpos($project_path, "/webroot/") === false)
					return true;
			}
		}
		
		return false;
	}
	
	public function existsLayoutFromProjectPath($project_path) {
		$layout_type_name = $this->getLayoutTypeNameFromFilePath($project_path);
		
		if ($layout_type_name) {
			$layout_type_data = $this->getLayoutTypeDataByName($layout_type_name);
			return $layout_type_data && $layout_type_data["layout_type_id"];
		}
		
		return false;
	}
	
	public function getLayoutFromProjectPath($project_path) {
		$layout_type_name = $this->getLayoutTypeNameFromFilePath($project_path);
		
		if ($layout_type_name)
			return $this->getLayoutTypeDataByName($layout_type_name);
		
		return false;
	}
	
	/* EDIT PUBLIC METHODS */
	
	public function refreshLoadedLayouts() {
		$previous_loaded_layouts = $this->loaded_layouts;
		$this->loaded_layouts = array();
		
		if ($previous_loaded_layouts)
			foreach ($previous_loaded_layouts as $layout_type_name => $layout)
				$this->getLayoutTypeDataByName($layout_type_name);
	}
	
	public function refreshLoadedLayoutsTypePermissionsByObject() {
		$previous_loaded_layouts_type_permissions_by_object = $this->loaded_layouts_type_permissions_by_object;
		$this->loaded_layouts_type_permissions_by_object = array();
		
		if ($previous_loaded_layouts_type_permissions_by_object)
			foreach ($previous_loaded_layouts_type_permissions_by_object as $layout_type_id => $ltp)
				$this->getLayoutTypePermissionsByObject($layout_type_id);
	}
	
	public function createNewLayoutFromProjectPath($project_path, $create_layers_project_folder_if_not_exists = true) {
		$this->validateBeanNameAndBeanFileName();
		
		$layout_type_name = $this->getLayoutTypeNameFromFilePath($project_path);
		//echo "project_path:$project_path\nlayout_type_name:$layout_type_name\n";
		
		if ($layout_type_name) {
			$layout_type_data = $this->getLayoutTypeDataByName($layout_type_name);
			$layout_type_id = $layout_type_data ? $layout_type_data["layout_type_id"] : null;
			
			//add new layout if not exists
			if (!$layout_type_id)
				$layout_type_id = $this->UserAuthenticationHandler->insertLayoutType(array(
					"type_id" => UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID,
					"name" => $layout_type_name,
				));
			
			//add default permissions across all layers
			return $layout_type_id && $this->createDefaultLayoutTypeProjectPermissions($layout_type_id, $create_layers_project_folder_if_not_exists, true);
		}
	}
	
	public function createNewLayoutFromProjectFolderPath($project_path, $create_layers_project_folder_if_not_exists = true) {
		$this->validateBeanNameAndBeanFileName();
		$status = false;
		
		if ($project_path && is_dir($project_path)) {
			$status = true;
			$sub_files = array_diff(scandir($project_path), array('..', '.'));
			
			foreach ($sub_files as $sub_file) {
				$sub_project_path = "$project_path/$sub_file";
				
				if ($this->isPathAPresentationProjectPath($sub_project_path) && !$this->createNewLayoutFromProjectPath($sub_project_path, $create_layers_project_folder_if_not_exists))
					$status = false;
				else if ($this->isPathAPresentationProjectFolderPath($sub_project_path) && !$this->createNewLayoutFromProjectFolderPath($sub_project_path, $create_layers_project_folder_if_not_exists))
					$status = false;
			}
		}
		
		return $status;
	}
	
	public function renameLayoutTypesFromLayerPath($layer_old_path, $layer_new_path, $refresh_cache = true) {
		$old_name_prefix = $this->getLayoutTypeNameFromFilePath($layer_old_path) . "/";
		$new_name_prefix = $this->getLayoutTypeNameFromFilePath($layer_new_path) . "/";
		
		$status = $this->UserAuthenticationHandler->updateLayoutTypesByNamePrefix($old_name_prefix, $new_name_prefix);
		
		//update $this->loaded_layouts
		if ($refresh_cache)
			$this->refreshLoadedLayouts();
		
		return $status;
	}
	
	public function renameLayoutTypePermissionsFromLayerPath($layer_old_path, $layer_new_path, $refresh_cache = true) {
		$old_object_id_prefix = $this->getLayoutTypePermissionObjectIdFromFilePath($layer_old_path) . "/";
		$new_object_id_prefix = $this->getLayoutTypePermissionObjectIdFromFilePath($layer_new_path) . "/";
		
		$status = $this->UserAuthenticationHandler->updateLayoutTypePermissionsByObjectPrefix($old_object_id_prefix, $new_object_id_prefix);
		
		//update $this->loaded_layouts_type_permissions_by_object
		if ($refresh_cache) 
			$this->refreshLoadedLayoutsTypePermissionsByObject();
		
		return $status;
	}
	
	public function renameLayoutTypePermissionsFromDBDriverPath($db_driver_old_path, $db_driver_new_path) {
		$old_object_id = $this->getLayoutTypePermissionObjectIdFromFilePath($db_driver_old_path);
		$new_object_id = $this->getLayoutTypePermissionObjectIdFromFilePath($db_driver_new_path);
		
		$layout_type_permissions = $this->UserAuthenticationHandler->searchLayoutTypePermissions(array("object_id" => $old_object_id));
		
		$status = true;
		
		if (is_array($layout_type_permissions)) { //it could be with no permissions...
			foreach ($layout_type_permissions as $ltp) {
				$data = array(
					"layout_type_id" => $ltp["layout_type_id"], 
					"permission_id" => $ltp["permission_id"], 
					"object_type_id" => $ltp["object_type_id"], 
					"old_object_id" => $ltp["object_id"], 
					"new_object_id" => $new_object_id
				);
				
				if (!$this->UserAuthenticationHandler->updateLayoutTypePermission($data))
					$status = false;
				
				//update $this->loaded_layouts_type_permissions_by_object
				if ($this->layoutTypePermissionObjectExists($ltp["layout_type_id"], $ltp["object_type_id"], $ltp["object_id"])) {
					$this->loaded_layouts_type_permissions_by_object[ $ltp["layout_type_id"] ][ $ltp["object_type_id"] ][$new_object_id] = $this->loaded_layouts_type_permissions_by_object[ $ltp["layout_type_id"] ][ $ltp["object_type_id"] ][ $ltp["object_id"] ];
					
					unset($this->loaded_layouts_type_permissions_by_object[ $ltp["layout_type_id"] ][ $ltp["object_type_id"] ][ $ltp["object_id"] ]);
				}
			}
		}
		
		return $status;
	}
	
	public function renameLayoutFromProjectPath($project_old_path, $project_new_path) {
		$layout_type_old_name = $this->getLayoutTypeNameFromFilePath($project_old_path);
		$layout_type_new_name = $this->getLayoutTypeNameFromFilePath($project_new_path);
		
		if ($layout_type_old_name && $layout_type_new_name) {
			$layout_type_data = $this->getLayoutTypeDataByName($layout_type_old_name);
			$layout_type_id = $layout_type_data ? $layout_type_data["layout_type_id"] : null;
			
			if ($layout_type_id) {
				$layout_type_data["name"] = $layout_type_new_name;
				
				if ($this->UserAuthenticationHandler->updateLayoutType($layout_type_data)) {
					//rename project permission
					$object_types = $this->UserAuthenticationHandler->getAvailableObjectTypes();
					$layer_object_type_id = $object_types["layer"];
					
					//$permissions = $this->UserAuthenticationHandler->getAvailablePermissions();
					//$belong_permission_id = $permissions[ UserAuthenticationHandler::$PERMISSION_BELONG_NAME ];
					
					$old_object_id = $this->getLayoutTypePermissionObjectIdFromFilePath(LAYER_PATH . $layout_type_old_name);
					$new_object_id = $this->getLayoutTypePermissionObjectIdFromFilePath(LAYER_PATH . $layout_type_new_name);
					
					if (!$this->renameLayoutTypePermissionObjectId($layout_type_id, $layer_object_type_id, $old_object_id, $new_object_id))
						return false;
					else
						$this->renameDefaultLayoutTypeNameProjectFolders($layout_type_old_name, $layout_type_new_name);
					
					return true;
				}
			}
		}
		
		return false;
	}
	
	public function renameLayoutFromProjectFolderPath($project_old_path, $project_new_path) {
		$status = false;
		
		if ($project_new_path && is_dir($project_new_path)) {
			$sub_files = array_diff(scandir($project_new_path), array('..', '.'));
			
			foreach ($sub_files as $idx => $sub_file) {
				$sub_project_new_path = "$project_new_path/$sub_file";
				
				if (!$this->isPathAPresentationProjectPath($sub_project_new_path) && !$this->isPathAPresentationProjectFolderPath($sub_project_new_path))
					unset($sub_files[$idx]);
			}
			
			$layout_type_old_name = $this->getLayoutTypeNameFromFilePath($project_old_path);
			$layout_type_new_name = $this->getLayoutTypeNameFromFilePath($project_new_path);
			$status = $this->renameDefaultLayoutTypeNameProjectFolders($layout_type_old_name, $layout_type_new_name, $sub_files);
		}
		
		return $status;
	}
	
	public function removeLayoutFromProjectPath($project_path) {
		$layout_type_name = $this->getLayoutTypeNameFromFilePath($project_path);
		
		if ($layout_type_name) {
			$this->removeDefaultLayoutTypeNameProjectFolders($layout_type_name);
			
			$layout_type_data = $this->getLayoutTypeDataByName($layout_type_name);
			$layout_type_id = $layout_type_data ? $layout_type_data["layout_type_id"] : null;
			
			if ($layout_type_id)
				return $this->UserAuthenticationHandler->deleteLayoutTypePermissionsByConditions(array("layout_type_id" => $layout_type_id))
					&& $this->UserAuthenticationHandler->deleteLayoutType($layout_type_id);
		}
		
		return false;
	}
	
	public function removeLayoutFromProjectFolderPath($project_path) {
		$status = false;
		
		if ($project_path && is_dir($project_path)) {
			$status = true;
			$sub_files = array_diff(scandir($project_path), array('..', '.'));
			
			foreach ($sub_files as $sub_file) {
				$sub_project_path = "$project_path/$sub_file";
				
				if ($this->isPathAPresentationProjectPath($sub_project_path) && !$this->removeLayoutFromProjectPath($sub_project_path))
					$status = false;
				else if ($this->isPathAPresentationProjectFolderPath($sub_project_path) && !$this->removeLayoutFromProjectFolderPath($sub_project_path))
					$status = false;
			}
			
			$layout_type_name = $this->getLayoutTypeNameFromFilePath($project_path);
			$this->removeDefaultLayoutTypeNameProjectFolders($layout_type_name);
		}
		
		return $status;
	}
	
	//add layout module permission to all correspondent layers in a project
	public function createLayoutTypePermissionsForModuleInLayersFromProjectPath($project_path, $layer_objs, $module_id) {
		$this->validateBeanNameAndBeanFileName();
		
		$status = true;
		
		if ($layer_objs) {
			$layout_type_name = $this->getLayoutTypeNameFromFilePath($project_path);
			
			if ($layout_type_name) {
				$layout_type_data = $this->getLayoutTypeDataByName($layout_type_name);
				$layout_type_id = $layout_type_data ? $layout_type_data["layout_type_id"] : null;
				
				//add new layout if not exists
				if (!$layout_type_id) {
					$layout_type_id = $this->UserAuthenticationHandler->insertLayoutType(array(
						"type_id" => UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID,
						"name" => $layout_type_name,
					));
					
					//add default permissions across all layers. Only add the presentation layer if the layout_type is a new one.
					if ($layout_type_id && !$this->createDefaultLayoutTypeProjectPermissions($layout_type_id, false, true))
						$status = false;
				}
				
				if ($layout_type_id) {
					$object_types = $this->UserAuthenticationHandler->getAvailableObjectTypes();
					$layer_object_type_id = $object_types["layer"];
					
					$permissions = $this->UserAuthenticationHandler->getAvailablePermissions();
					$belong_permission_id = $permissions[ UserAuthenticationHandler::$PERMISSION_BELONG_NAME ];
					$referenced_permission_id = $permissions[ UserAuthenticationHandler::$PERMISSION_REFERENCED_NAME ];
					
					//add reference for this specific module. this will be used in "modules list" in the "WorkFlowPresentationHandler::getCodeEditorHtml", in case the user doesn't have access to the common project but has this modules referenced.
					$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($this->beans_folder_path . $this->bean_file_name, $this->global_variables_file_path);
					$PresentationLayer = $WorkFlowBeansFileHandler->getBeanObject($this->bean_name);
					$common_project_name = $PresentationLayer->getCommonProjectName();
					
					if ($common_project_name) {
						$layer_path_prefix = $PresentationLayer->getLayerPathSetting();
						$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath($layer_path_prefix . $common_project_name . "/src/module/$module_id");
						
						if (!$this->addLayoutTypePermissionIfNotExists($layout_type_id, $layer_object_type_id, $object_id, $referenced_permission_id))
							$status = false;
					}
					
					//Only add permissions for business_logic and data_access layers. The presentation layer already has access when we added the common project in the self::createNewLayoutFromProjectPath method.
					foreach ($layer_objs as $bean_name => $layer_obj)
						if (is_a($layer_obj, "BusinessLogicLayer") || is_a($layer_obj, "DataAccessLayer")) {
							$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerObjFolderName($layer_obj);
							
							//add layer module project permission
							$layer_object_path = LAYER_PATH . "$layer_bean_folder_name/module/$module_id";
							
							if (file_exists($layer_object_path)) {
								$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath($layer_object_path);
								
								if (!$this->addLayoutTypePermissionIfNotExists($layout_type_id, $layer_object_type_id, $object_id, $belong_permission_id))
									$status = false;
							}
						}
				}
				else
					$status = false;
			}
			else
				$status = false;
		}
		else
			$status = false;
		
		return $status;
	}
	
	//add layout program permission to all correspondent layers in a project
	public function createLayoutTypePermissionsForProgramInLayersFromProjectPath($projects, $layer_objs, $program_name) {
		$this->validateBeanNameAndBeanFileName();
		
		$status = true;
		
		if ($layer_objs && $projects) {
			$object_types = $this->UserAuthenticationHandler->getAvailableObjectTypes();
			$layer_object_type_id = $object_types["layer"];
			
			$permissions = $this->UserAuthenticationHandler->getAvailablePermissions();
			$belong_permission_id = $permissions[ UserAuthenticationHandler::$PERMISSION_BELONG_NAME ];
			
			foreach ($layer_objs as $broker_name => $layer_obj) 
				if (is_a($layer_obj, "PresentationLayer")) {
					$layer_projects = $projects[$broker_name];
					
					if ($layer_projects) {
						$layer_path = $layer_obj->getLayerPathSetting();
						
						foreach ($layer_projects as $project_name) {
							$project_path = $layer_path . $project_name;
							$layout_type_name = $this->getLayoutTypeNameFromFilePath($project_path);
							
							if ($layout_type_name) {
								$layout_type_data = $this->getLayoutTypeDataByName($layout_type_name);
								$layout_type_id = $layout_type_data ? $layout_type_data["layout_type_id"] : null;
								
								//add new layout if not exists
								if (!$layout_type_id) {
									$layout_type_id = $this->UserAuthenticationHandler->insertLayoutType(array(
										"type_id" => UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID,
										"name" => $layout_type_name,
									));
									
									//add default permissions across all layers. Only add the presentation layer if the layout_type is a new one.
									if ($layout_type_id && !$this->createDefaultLayoutTypeProjectPermissions($layout_type_id, false, true))
										$status = false;
								}
								
								if ($layout_type_id) {
									//Only add permissions for business_logic and data_access layers. The presentation layer already has access when we added the common project in the self::createDefaultLayoutTypeProjectPermissions method.
									foreach ($layer_objs as $bean_name => $layer_obj)
										if (is_a($layer_obj, "BusinessLogicLayer") || is_a($layer_obj, "DataAccessLayer")) {
											$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerObjFolderName($layer_obj);
											
											//add layer module project permission
											$layer_object_path = LAYER_PATH . "$layer_bean_folder_name/program/$program_name";
											
											if (file_exists($layer_object_path)) {
												$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath($layer_object_path);
												
												if (!$this->addLayoutTypePermissionIfNotExists($layout_type_id, $layer_object_type_id, $object_id, $belong_permission_id))
													$status = false;
											}
										}
								}
								else
									$status = false;
							}
							else
								$status = false;
						}
					}
				}
		}
		else
			$status = false;
		
		return $status;
	}
	
	//$db_driver_name is the db driver broker name 
	public function createLayoutTypePermissionsForDBDriverFromProjectPath($project_path, $db_driver_name) {
		if (!$project_path || !$db_driver_name)
			return false;
		
		$this->validateBeanNameAndBeanFileName();
		
		$layout_type_name = $this->getLayoutTypeNameFromFilePath($project_path);
		//echo "project_path:$project_path\nlayout_type_name:$layout_type_name\n";
		
		return $this->createLayoutTypePermissionsForDBDriverAndLayoutTypeName($layout_type_name, $db_driver_name);
	}
	
	public function createLayoutTypePermissionsForFilePathAndLayoutTypeName($layout_type_name, $file_path, $check_parent_permission = true) {
		if (!$layout_type_name || !$file_path)
			return false;
		
		$this->validateBeanNameAndBeanFileName();
		
		$layout_type_data = $this->getLayoutTypeDataByName($layout_type_name);
		$layout_type_id = $layout_type_data ? $layout_type_data["layout_type_id"] : null;
		
		//if layout type doesnt exists, returns false (Do not create layout type bc it means the user removed it before on purpose!)
		if (!$layout_type_id)
			return false;
		
		//check permission already exists for some parent folder
		if ($check_parent_permission) {
			$this->UserAuthenticationHandler->loadLayoutPermissions($layout_type_name, UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID);
			
			if ($this->UserAuthenticationHandler->isLayoutInnerFilePermissionAllowed($file_path, $layout_type_name, "layer", UserAuthenticationHandler::$PERMISSION_BELONG_NAME))
				return true;
		}
		
		//insert new permission object
		$object_types = $this->UserAuthenticationHandler->getAvailableObjectTypes();
		$layer_object_type_id = $object_types["layer"];
		
		$permissions = $this->UserAuthenticationHandler->getAvailablePermissions();
		$belong_permission_id = $permissions[ UserAuthenticationHandler::$PERMISSION_BELONG_NAME ];
		
		$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath($file_path);
		
		return $this->addLayoutTypePermissionIfNotExists($layout_type_id, $layer_object_type_id, $object_id, $belong_permission_id);
	}
	
	public function renameLayoutTypePermissionsForFilePath($old_file_path, $new_file_path) {
		if (!$old_file_path || !$new_file_path)
			return false;
		
		$this->validateBeanNameAndBeanFileName();
		
		$old_object_id = $this->getLayoutTypePermissionObjectIdFromFilePath($old_file_path);
		$new_object_id = $this->getLayoutTypePermissionObjectIdFromFilePath($new_file_path);
		$layout_type_permissions = $this->UserAuthenticationHandler->searchLayoutTypePermissions(array("object_id" => $old_object_id));
		$repeated = array();
		$status = true;
		
		if (is_array($layout_type_permissions)) //it could be with no permissions...
			foreach ($layout_type_permissions as $ltp)
				if (!$repeated[ $ltp["layout_type_id"] ][ $ltp["object_type_id"] ]) {
					$repeated[ $ltp["layout_type_id"] ][ $ltp["object_type_id"] ] = 1;
					
					if (!$this->renameLayoutTypePermissionObjectId($ltp["layout_type_id"], $ltp["object_type_id"], $old_object_id, $new_object_id))
						$status = false;
				}
		
		return $status;
	}
	
	public function removeLayoutTypePermissionsForFilePath($file_path) {
		if (!$file_path)
			return false;
		
		$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath($file_path);
		
		return $this->UserAuthenticationHandler->deleteLayoutTypePermissionsByConditions(array("object_id" => $object_id));
	}
	
	//$db_driver_name is the db driver broker name 
	public function createLayoutTypePermissionsForDBDriverAndLayoutTypeName($layout_type_name, $db_driver_name) {
		if (!$layout_type_name || !$db_driver_name)
			return false;
		
		$this->validateBeanNameAndBeanFileName();
		
		$layout_type_data = $this->getLayoutTypeDataByName($layout_type_name);
		$layout_type_id = $layout_type_data ? $layout_type_data["layout_type_id"] : null;
		
		//if layout type doesnt exists, returns false (Do not create layout type bc it means the user removed it before on purpose!)
		if (!$layout_type_id)
			return false;
		
		$layers = AdminMenuHandler::getLayersFiles($this->global_variables_file_path);
		$status = false;
		
		if ($layers) {
			$presentation_layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($this->beans_folder_path . $this->bean_file_name, $this->bean_name, $this->global_variables_file_path) . "/";
			$layout_type_name_suffix = substr($layout_type_name, 0, strlen($presentation_layer_bean_folder_name)) == $presentation_layer_bean_folder_name ? substr($layout_type_name, strlen($presentation_layer_bean_folder_name)) : null;
			
			$object_types = $this->UserAuthenticationHandler->getAvailableObjectTypes();
			$layer_object_type_id = $object_types["layer"];
			
			$permissions = $this->UserAuthenticationHandler->getAvailablePermissions();
			$belong_permission_id = $permissions[ UserAuthenticationHandler::$PERMISSION_BELONG_NAME ];
			
			$db_layers_type = $layers["db_layers"];
			$exists = false;
			$status = true;
			
			foreach ($db_layers_type as $layer_name => $layer) {
				foreach ($layer as $driver_name_bean_name => $driver_attrs) {
					$driver_name = WorkFlowBeansConverter::getBrokerNameFromRawLabel($driver_name_bean_name);
					
					if ($driver_name == $db_driver_name) {
						$exists = true;
						
						$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($this->beans_folder_path . $layer["properties"]["bean_file_name"], $layer["properties"]["bean_name"], $this->global_variables_file_path);
						$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/"; //layer_bean_folder_name is for the db_data
						
						$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath($layer_object_id . $driver_name_bean_name);
						
						if ($this->addLayoutTypePermissionIfNotExists($layout_type_id, $layer_object_type_id, $object_id, $belong_permission_id)) {
							//add db layer permission
							$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath($layer_object_id);
							
							if (!$this->UserAuthenticationHandler->deleteLayoutTypePermission($layout_type_id, $belong_permission_id, $layer_object_type_id, $object_id))
								$status = false;
						}
						else
							$status = false;
					}
				}
			}
			
			if (!$exists)
				$status = false;
		}
		
		return $status;
	}
	
	/* PRIVATE METHODS */
	
	//add default permissions across all layers
	private function createDefaultLayoutTypeProjectPermissions($layout_type_id, $create_layers_project_folder_if_not_exists = true, $filter_layers_by_access = true) {
		if ($layout_type_id) {
			$layout_type_data = $this->UserAuthenticationHandler->getLayoutType($layout_type_id);
			$layout_type_name = $layout_type_data ? $layout_type_data["name"] : null;
			
			if ($layout_type_name) {
				$layers = AdminMenuHandler::getLayersFiles($this->global_variables_file_path);
				$status = true;
				
				if ($layers) {
					$presentation_layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($this->beans_folder_path . $this->bean_file_name, $this->bean_name, $this->global_variables_file_path) . "/";
					$layout_type_name_suffix = substr($layout_type_name, 0, strlen($presentation_layer_bean_folder_name)) == $presentation_layer_bean_folder_name ? substr($layout_type_name, strlen($presentation_layer_bean_folder_name)) : null;
					
					$object_types = $this->UserAuthenticationHandler->getAvailableObjectTypes();
					$layer_object_type_id = $object_types["layer"];
					
					$permissions = $this->UserAuthenticationHandler->getAvailablePermissions();
					$belong_permission_id = $permissions[ UserAuthenticationHandler::$PERMISSION_BELONG_NAME ];
					
					//add presentation self project permission
					$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath(LAYER_PATH . $layout_type_name);
					
					if (!$this->addLayoutTypePermissionIfNotExists($layout_type_id, $layer_object_type_id, $object_id, $belong_permission_id))
						$status = false;
					
					//add presentation common project permission, if project_name is not "common", otherwise it was already added before.
					$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($this->beans_folder_path . $this->bean_file_name, $this->global_variables_file_path);
					$PresentationLayer = $WorkFlowBeansFileHandler->getBeanObject($this->bean_name);
					$common_project_name = $PresentationLayer->getCommonProjectName();
					
					if ($common_project_name && $common_project_name != $layout_type_name_suffix) {
						$layer_path_prefix = $PresentationLayer->getLayerPathSetting();
						$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath($layer_path_prefix . $common_project_name);
						
						if (!$this->addLayoutTypePermissionIfNotExists($layout_type_id, $layer_object_type_id, $object_id, $belong_permission_id))
							$status = false;
					}
					
					//filter layers to only get the layers that the current presentation layer has access to.
					if ($filter_layers_by_access)
						WorkFlowBeansFileHandler::getLocalBeanLayersFromBrokers($this->global_variables_file_path, $this->beans_folder_path, $PresentationLayer->getBrokers(), true, false, $beans_files_path);
					
					//add permissions to bl, dal and dbl layers
					foreach ($layers as $layer_type_name => $layer_type)
						if (in_array($layer_type_name, array("business_logic_layers", "data_access_layers", "db_layers")))
							foreach ($layer_type as $layer_name => $layer) {
								$exists = true;
								
								if ($filter_layers_by_access)
									$exists = $beans_files_path && $beans_files_path[ $layer["properties"]["bean_name"] ][0] == $this->beans_folder_path . $layer["properties"]["bean_file_name"];
								
								if ($exists) {
									$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($this->beans_folder_path . $layer["properties"]["bean_file_name"], $layer["properties"]["bean_name"], $this->global_variables_file_path);
									
									//add db layer permission
									if ($layer_type_name == "db_layers") {
										$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath(LAYER_PATH . $layer_bean_folder_name);
										
										if (!$this->addLayoutTypePermissionIfNotExists($layout_type_id, $layer_object_type_id, $object_id, $belong_permission_id))
											$status = false;
									}
									else { //add layer self project permission
										if ($layout_type_name_suffix) {
											$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$layout_type_name_suffix";
											$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath($layer_object_id);
											
											//create project folder in each layer
											if ($create_layers_project_folder_if_not_exists && !file_exists($layer_object_id))
												mkdir($layer_object_id, 0755, true);
											
											//add correspondent layout permission
											if (!$this->addLayoutTypePermissionIfNotExists($layout_type_id, $layer_object_type_id, $object_id, $belong_permission_id))
												$status = false;
										}
										
										//add layer common project permission, if project_name is not "common", otherwise it was already added before.
										if ($common_project_name && $common_project_name != $layout_type_name_suffix) {
											$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath(LAYER_PATH . "$layer_bean_folder_name/$common_project_name");
											
											if (!$this->addLayoutTypePermissionIfNotExists($layout_type_id, $layer_object_type_id, $object_id, $belong_permission_id))
												$status = false;
										}
										
										//Maybe the Module's permission should not be added here! When Module is installed, then the permission should be added, for that specific Program! However a project can have multiple DBs and the Modules folders sould be shared to all projects. Just in case add this the main Module folder permission here and then everytime each module is installed, add that specific module permission too.
										//add layer module project permission
										$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath(LAYER_PATH . "$layer_bean_folder_name/module");
										
										if (!$this->addLayoutTypePermissionIfNotExists($layout_type_id, $layer_object_type_id, $object_id, $belong_permission_id))
											$status = false;
										
										//Maybe the Program's permission should not be added here! When Program is installed, then the permission should be added, for that specific Program! However a project can have multiple DBs and the Programs folders sould be shared to all projects. Just in case add this the main Program folder permission here and then everytime each program is installed, add that specific program permission too.
										//add layer program project permission
										$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath(LAYER_PATH . "$layer_bean_folder_name/program");
										
										if (!$this->addLayoutTypePermissionIfNotExists($layout_type_id, $layer_object_type_id, $object_id, $belong_permission_id))
											$status = false;
										
										//Maybe the Resource's permission should not be added here! When a Resource is created, then the permission should be added, for that specific Resource! However a project can have multiple DBs and the Resource folders sould be shared to all projects. Just in case add this the main Resource folder permission here and then everytime each resource is created, add that specific resource permission too.
										//add layer resource project permission
										$object_id = $this->getLayoutTypePermissionObjectIdFromFilePath(LAYER_PATH . "$layer_bean_folder_name/resource");
										
										if (!$this->addLayoutTypePermissionIfNotExists($layout_type_id, $layer_object_type_id, $object_id, $belong_permission_id))
											$status = false;
									}
								}
							}
				}
				
				return $status;
			}
		}
		
		return false;
	}
	
	//rename project default folders across all layers
	private function renameDefaultLayoutTypeNameProjectFolders($layout_type_old_name, $layout_type_new_name, $sub_files_to_ignore = null) {
		if ($layout_type_old_name && $layout_type_new_name && $layout_type_old_name != $layout_type_new_name) {
			$presentation_layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($this->beans_folder_path . $this->bean_file_name, $this->bean_name, $this->global_variables_file_path) . "/";
			$layout_type_old_name_suffix = substr($layout_type_old_name, 0, strlen($presentation_layer_bean_folder_name)) == $presentation_layer_bean_folder_name ? substr($layout_type_old_name, strlen($presentation_layer_bean_folder_name)) : null;
			$layout_type_new_name_suffix = substr($layout_type_new_name, 0, strlen($presentation_layer_bean_folder_name)) == $presentation_layer_bean_folder_name ? substr($layout_type_new_name, strlen($presentation_layer_bean_folder_name)) : null;
			$status = true;
			
			if ($layout_type_old_name_suffix && $layout_type_new_name) {
				$layers = AdminMenuHandler::getLayersFiles($this->global_variables_file_path);
				
				if ($layers) {
					foreach ($layers as $layer_type_name => $layer_type)
						if (in_array($layer_type_name, array("business_logic_layers", "data_access_layers")))
							foreach ($layer_type as $layer_name => $layer) {
								$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($this->beans_folder_path . $layer["properties"]["bean_file_name"], $layer["properties"]["bean_name"], $this->global_variables_file_path);
								$old_layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$layout_type_old_name_suffix";
								$new_layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$layout_type_new_name_suffix";
								
								if (is_dir($old_layer_object_id) && !file_exists($new_layer_object_id)) {
									$sub_files = array_diff(scandir($old_layer_object_id), array('..', '.'));
									
									if (is_array($sub_files_to_ignore))
										$sub_files = array_diff($sub_files, $sub_files_to_ignore);
									
									if (count($sub_files) == 0 && !rename($old_layer_object_id, $new_layer_object_id))
										$status = false;
								}
							}
				}
			}
			
			return $status;
		}
		
		return false;
	}
	
	//remove project default folders across all layers
	private function removeDefaultLayoutTypeNameProjectFolders($layout_type_name) {
		if ($layout_type_name) {
			$presentation_layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($this->beans_folder_path . $this->bean_file_name, $this->bean_name, $this->global_variables_file_path) . "/";
			$layout_type_name_suffix = substr($layout_type_name, 0, strlen($presentation_layer_bean_folder_name)) == $presentation_layer_bean_folder_name ? substr($layout_type_name, strlen($presentation_layer_bean_folder_name)) : null;
			$status = true;
			
			if ($layout_type_name_suffix) {
				$layers = AdminMenuHandler::getLayersFiles($this->global_variables_file_path);
				
				if ($layers) {
					foreach ($layers as $layer_type_name => $layer_type)
						if (in_array($layer_type_name, array("business_logic_layers", "data_access_layers")))
							foreach ($layer_type as $layer_name => $layer) {
								$layer_bean_folder_name = WorkFlowBeansFileHandler::getLayerBeanFolderName($this->beans_folder_path . $layer["properties"]["bean_file_name"], $layer["properties"]["bean_name"], $this->global_variables_file_path);
								$layer_object_id = LAYER_PATH . "$layer_bean_folder_name/$layout_type_name_suffix";
								
								if (is_dir($layer_object_id)) {
									$sub_files = array_diff(scandir($layer_object_id), array('..', '.'));
									
									if (count($sub_files) == 0 && !CMSModuleUtil::deleteFolder($layer_object_id))
										$status = false;
								}
							}
				}
			}
			
			return $status;
		}
		
		return false;
	}
	
	private function addLayoutTypePermissionIfNotExists($layout_type_id, $object_type_id, $object_id, $permission_id) {
		if ($layout_type_id && $object_type_id && $object_id && $permission_id) {
			$layouts_type_permissions_by_object = $this->getLayoutTypePermissionsByObject($layout_type_id);
			
			//check if already exists
			if ($layouts_type_permissions_by_object && $this->layoutTypePermissionObjectExists($layout_type_id, $object_type_id, $object_id, $permission_id))
				return true;
			
			//add layout type permission
			$status = $this->UserAuthenticationHandler->insertLayoutTypePermission(array(
				"layout_type_id" => $layout_type_id,
				"object_type_id" => $object_type_id,
				"object_id" => $object_id,
				"permission_id" => $permission_id,
			));
			
			//refresh loaded layout_type_permissions
			if ($status)
				$this->getLayoutTypePermissionsByObject($layout_type_id, true);
			
			return $status;
		}
		
		return false;
	}
	
	private function renameLayoutTypePermissionObjectId($layout_type_id, $object_type_id, $old_object_id, $new_object_id, $return_if_not_exists = true) {
		if ($layout_type_id && $object_type_id && $old_object_id && $new_object_id) {
			$layouts_type_permissions_by_object = $this->getLayoutTypePermissionsByObject($layout_type_id);
			
			//check if already exists
			if ($layouts_type_permissions_by_object && $this->layoutTypePermissionObjectExists($layout_type_id, $object_type_id, $old_object_id)) {
				$this->loaded_layouts_type_permissions_by_object[$layout_type_id][$object_type_id][$new_object_id] = $this->loaded_layouts_type_permissions_by_object[$layout_type_id][$object_type_id][$old_object_id];
				
				unset($this->loaded_layouts_type_permissions_by_object[$layout_type_id][$object_type_id][$old_object_id]);
				
				return $this->UserAuthenticationHandler->updateLayoutTypePermissionsByObjectsPermissions($layout_type_id, $this->loaded_layouts_type_permissions_by_object[$layout_type_id]);
			}
		}
		
		return $return_if_not_exists;
	}
	
	private function getLayoutTypeDataByName($layout_type_name, $force = false) {
		if (!$force && $this->loaded_layouts[$layout_type_name])
			return $this->loaded_layouts[$layout_type_name];
		
		$layouts = $this->UserAuthenticationHandler->searchLayoutTypes(array(
			"type_id" => UserAuthenticationHandler::$LAYOUTS_TYPE_FROM_PROJECT_ID, 
			"name" => $layout_type_name
		));
		
		if ($layouts && $layouts[0]) {
			$this->loaded_layouts[$layout_type_name] = $layouts[0];
			
			return $layouts[0];
		}
		
		return null;
	}
	
	private function getLayoutTypePermissionsByObject($layout_type_id, $force = false) {
		if ($layout_type_id && ($force || !$this->loaded_layouts_type_permissions_by_object[$layout_type_id])) {
			$layout_type_permissions = $this->UserAuthenticationHandler->searchLayoutTypePermissions(array("layout_type_id" => $layout_type_id));
			
			if (is_array($layout_type_permissions)) {//it could be with no permissions...
				foreach ($layout_type_permissions as $ltp)
					$this->loaded_layouts_type_permissions_by_object[$layout_type_id][ $ltp["object_type_id"] ][ $ltp["object_id"] ][] = $ltp["permission_id"];
			}
		}
		
		return $layout_type_id ? $this->loaded_layouts_type_permissions_by_object[$layout_type_id] : null;
	}
		
	/* PRIVATE UTILS */
	
	private function layoutTypePermissionObjectExists($layout_type_id, $object_type_id, $object_id, $permission_id = null) {
		if ($layout_type_id && $object_type_id && $object_id 
			&& !empty($this->loaded_layouts_type_permissions_by_object[$layout_type_id][$object_type_id][$object_id]) 
			&& is_array($this->loaded_layouts_type_permissions_by_object[$layout_type_id][$object_type_id][$object_id])
		) { 
			return !$permission_id || in_array($permission_id, $this->loaded_layouts_type_permissions_by_object[$layout_type_id][$object_type_id][$object_id]);
		}
		
		return false;
	}
	
	private function getLayoutTypeNameFromFilePath($project_path) {
		if (substr($project_path, 0, strlen(LAYER_PATH)) == LAYER_PATH)
			$layout_type_name = substr($project_path, strlen(LAYER_PATH));
		else
			$layout_type_name = $project_path;
		
		$layout_type_name = preg_replace("/\/+$/", "", $layout_type_name); //remove last slashes
		$layout_type_name = preg_replace("/^\/+/", "", $layout_type_name); //remove first slashes
		$layout_type_name = preg_replace("/\/+/", "/", $layout_type_name); //remove duplicated slashes
		
		return $layout_type_name;
	}
	
	private function getLayoutTypePermissionObjectIdFromFilePath($project_path) {
		if (substr($project_path, 0, strlen(APP_PATH)) == APP_PATH)
			$object_id = substr($project_path, strlen(APP_PATH));
		else
			$object_id = $project_path;
		
		$object_id = preg_replace("/\/+$/", "", $object_id); //remove last slashes
		$object_id = preg_replace("/^\/+/", "", $object_id); //remove first slashes
		$object_id = preg_replace("/\/+/", "/", $object_id); //remove duplicated slashes
		
		return $object_id;
	}
	
	private function validateBeanNameAndBeanFileName() {
		if (!$this->bean_name || !$this->bean_file_name)
			launch_exception(new Exception("Bean name or bean file name in LayoutTypeProjectHandler constructor cannot be empty!"));
	}
}
?>
