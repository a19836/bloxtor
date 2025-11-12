<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.layer.presentation.PresentationLayer");
include get_lib("org.phpframework.layer.presentation.evc.exception.EVCException");

class EVC {
	private $PresentationLayer;
	private $CMSLayer;
	
	private $controllers = array();
	private $entities = array();
	private $views = array();
	private $templates = array();
	
	private $entities_params = array();
	private $views_params = array();
	private $templates_params = array();
	
	private $default_controller;
	
	/******** PRESENTATION ********/
	public function setPresentationLayer(PresentationLayer $PresentationLayer) {$this->PresentationLayer = $PresentationLayer;}
	public function getPresentationLayer() {return $this->PresentationLayer;}
	
	/********* PRESENTATION SETTINGS ********/
	public function getCommonProjectName() {
		return $this->PresentationLayer->getCommonProjectName();
	}
	
	/******** CMSLAYER ********/
	public function setCMSLayer($CMSLayer) {$this->CMSLayer = $CMSLayer;}
	public function getCMSLayer() {return $this->CMSLayer;}
	
	/******** CONTROLLER ********/
	public function getControllersPath($project_id = false) {
		if (!$project_id)
			return $this->PresentationLayer->getSelectedPresentationSetting("presentation_controllers_path");
		
		if (empty($this->PresentationLayer->settings["presentation_controllers_path"]))
			launch_exception(new EVCException(5, "EVC->PresentationLayer->settings[presentation_controllers_path]"));
		
		return $this->PresentationLayer->getPresentationPath($project_id) . $this->PresentationLayer->settings["presentation_controllers_path"];
	}
	public function getControllerPath($controller_code, $project_id = false) { return $this->getControllersPath($project_id) . $controller_code . "." . $this->PresentationLayer->getPresentationFileExtension(); }
	public function controllerExists($controller_code, $project_id = false) { return file_exists($this->getControllerPath($controller_code, $project_id)); }
	public function getController() { return isset($this->controllers[0]) ? $this->controllers[0] : null; }
	public function getControllers() { return $this->controllers; }
	public function setController($controller_code) { $this->controllers = array($controller_code); }
	public function addController($controller_code) { $this->controllers[] = $controller_code; }
	
	public function getDefaultController() { return $this->default_controller; }
	public function setDefaultController($default_controller) { $this->default_controller = $default_controller; }
	
	/******** ENTITY ********/
	public function getEntitiesPath($project_id = false) {
		if (!$project_id)
			return $this->PresentationLayer->getSelectedPresentationSetting("presentation_entities_path");
		
		if (empty($this->PresentationLayer->settings["presentation_entities_path"]))
			launch_exception(new EVCException(5, "EVC->PresentationLayer->settings[presentation_entities_path]"));
		
		return $this->PresentationLayer->getPresentationPath($project_id) . $this->PresentationLayer->settings["presentation_entities_path"];
	}
	public function getEntityPath($entity_code, $project_id = false) { return $this->getEntitiesPath($project_id) . $entity_code . "." . $this->PresentationLayer->getPresentationFileExtension(); }
	public function entityExists($entity_code, $project_id = false) { return file_exists($this->getEntityPath($entity_code, $project_id)); }
	
	public function getEntity() { return isset($this->entities[0]) ? $this->entities[0] : null; }
	public function getEntities() { return $this->entities; }
	public function getEntityParams() { return isset($this->entities_params[0]) ? $this->entities_params[0] : null; }
	public function getEntitiesParams() { return $this->entities_params; }
	
	public function resetEntities() { 
		$this->entities = array();
		$this->entities_params = array();
	}
	
	public function setEntity($entity_code, $entity_params = null) { 
		$this->entities = array($entity_code); 
		$this->entities_params = array($entity_params); 
	}
	public function addEntity($entity_code, $entity_params = null) { 
		$this->entities[] = $entity_code; 
		$this->entities_params[] = $entity_params; 
	}
	
	/******** VIEW ********/
	public function getViewsPath($project_id = false) {
		if (!$project_id)
			return $this->PresentationLayer->getSelectedPresentationSetting("presentation_views_path");
		
		if (empty($this->PresentationLayer->settings["presentation_views_path"]))
			launch_exception(new EVCException(5, "EVC->PresentationLayer->settings[presentation_views_path]"));
		
		return $this->PresentationLayer->getPresentationPath($project_id) . $this->PresentationLayer->settings["presentation_views_path"];
	}
	public function getViewPath($view_code, $project_id = false) { return $this->getViewsPath($project_id) . $view_code . "." . $this->PresentationLayer->getPresentationFileExtension(); }
	public function viewExists($view_code, $project_id = false) { return file_exists($this->getViewPath($view_code, $project_id)); }
	
	public function getView() { return isset($this->views[0]) ? $this->views[0] : null; }
	public function getViews() { return $this->views; }
	public function getViewParams() { return isset($this->views_params[0]) ? $this->views_params[0] : null; }
	public function getViewsParams() { return $this->views_params; }
	
	public function resetViews() { 
		$this->views = array(); 
		$this->views_params = array(); 
	}
	
	public function setView($view_code, $view_params = null) { 
		$this->views = array($view_code); 
		$this->views_params = array($view_params); 
	}
	public function addView($view_code, $view_params = null) { 
		$this->views[] = $view_code; 
		$this->views_params[] = $view_params; 
	}
	
	/******** TEMPLATE ********/
	public function getTemplatesPath($project_id = false) {
		if (!$project_id)
			return $this->PresentationLayer->getSelectedPresentationSetting("presentation_templates_path");
		
		if (empty($this->PresentationLayer->settings["presentation_templates_path"]))
			launch_exception(new EVCException(5, "EVC->PresentationLayer->settings[presentation_templates_path]"));
		
		return $this->PresentationLayer->getPresentationPath($project_id) . $this->PresentationLayer->settings["presentation_templates_path"];
	}
	public function getTemplatePath($template_code, $project_id = false) { return $this->getTemplatesPath($project_id) . $template_code . "." . $this->PresentationLayer->getPresentationFileExtension(); }
	public function templateExists($template_code, $project_id = false) { return file_exists($this->getTemplatePath($template_code, $project_id)); }
	
	public function getTemplate() { return isset($this->templates[0]) ? $this->templates[0] : null; }
	public function getTemplates() { return $this->templates; }
	public function getTemplateParams() { return isset($this->templates_params[0]) ? $this->templates_params[0] : null; }
	public function getTemplatesParams() { return $this->templates_params; }
	
	public function resetTemplates() { 
		$this->templates = array();
		$this->templates_params = array();
	}
	
	public function setTemplate($template_code, $template_params = null) { 
		$this->templates = array($template_code); 
		$this->templates_params = array($template_params); 
	}
	public function addTemplate($template_code, $template_params = null) { 
		$this->templates[] = $template_code; 
		$this->templates_params[] = $template_params; 
	}
	
	/********* CONFIG ********/
	public function getConfigsPath($project_id = false) {
		if (!$project_id)
			return $this->PresentationLayer->getSelectedPresentationSetting("presentation_configs_path");
		
		if (empty($this->PresentationLayer->settings["presentation_configs_path"]))
			launch_exception(new EVCException(5, "EVC->PresentationLayer->settings[presentation_configs_path]"));
		
		return $this->PresentationLayer->getPresentationPath($project_id) . $this->PresentationLayer->settings["presentation_configs_path"];
	}
	public function getConfigPath($config_code, $project_id = false) { return $this->getConfigsPath($project_id) . $config_code . "." . $this->PresentationLayer->getPresentationFileExtension(); }
	public function configExists($config_code, $project_id = false) { return file_exists($this->getConfigPath($config_code, $project_id)); }
	
	/********* UTILS ********/
	public function getUtilsPath($project_id = false) {
		if (!$project_id)
			return $this->PresentationLayer->getSelectedPresentationSetting("presentation_utils_path");
		
		if (empty($this->PresentationLayer->settings["presentation_utils_path"]))
			launch_exception(new EVCException(5, "EVC->PresentationLayer->settings[presentation_utils_path]"));
		
		return $this->PresentationLayer->getPresentationPath($project_id) . $this->PresentationLayer->settings["presentation_utils_path"];
	}
	public function getUtilPath($util_code, $project_id = false) { return $this->getUtilsPath($project_id) . $util_code . "." . $this->PresentationLayer->getPresentationFileExtension(); }
	public function utilExists($util_code, $project_id = false) { return file_exists($this->getUtilPath($util_code, $project_id)); }
	
	/********* BLOCKS ********/
	public function getBlocksPath($project_id = false) {
		if (!$project_id)
			return $this->PresentationLayer->getSelectedPresentationSetting("presentation_blocks_path");
		
		if (empty($this->PresentationLayer->settings["presentation_blocks_path"]))
			launch_exception(new EVCException(5, "EVC->PresentationLayer->settings[presentation_blocks_path]"));
		
		return $this->PresentationLayer->getPresentationPath($project_id) . $this->PresentationLayer->settings["presentation_blocks_path"];
	}
	public function getBlockPath($block_code, $project_id = false) { return $this->getBlocksPath($project_id) . $block_code . "." . $this->PresentationLayer->getPresentationFileExtension(); }
	
	/********* MODULES ********/
	public function getModulesPath($project_id = false) {
		if (!$project_id)
			return $this->PresentationLayer->getSelectedPresentationSetting("presentation_modules_path");
		
		if (empty($this->PresentationLayer->settings["presentation_modules_path"]))
			launch_exception(new EVCException(5, "EVC->PresentationLayer->settings[presentation_modules_path]"));
		
		return $this->PresentationLayer->getPresentationPath($project_id) . $this->PresentationLayer->settings["presentation_modules_path"];
	}
	public function getModulePath($module_code, $project_id = false) { return $this->getModulesPath($project_id) . $module_code . "." . $this->PresentationLayer->getPresentationFileExtension(); }
	
	/********* WEBROOT ********/
	public function getWebrootPath($project_id = false) {
		if (!$project_id)
			return $this->PresentationLayer->getSelectedPresentationSetting("presentation_webroot_path");
		
		if (empty($this->PresentationLayer->settings["presentation_webroot_path"]))
			launch_exception(new EVCException(5, "EVC->PresentationLayer->settings[presentation_webroot_path]"));
		
		return $this->PresentationLayer->getPresentationPath($project_id) . $this->PresentationLayer->settings["presentation_webroot_path"];
	}
	
	/********* BROKER ********/
	public function addBroker($broker, $broker_name = false) {$this->PresentationLayer->addBroker($broker, $broker_name);}
	public function getBroker($broker_name = false) {return $this->PresentationLayer->getBroker($broker_name);}
	public function getBrokers() {return $this->PresentationLayer->getBrokers();}
	
	/******** CONTROLLER ********/
	public function getProjectsId($prefix_path = "", &$project_folders = array()) {
		$projects = array();
		
		$layer_path = $this->PresentationLayer->getLayerPathSetting();
		$prefix_path .= $prefix_path && substr($prefix_path, -1) != "/" ? "/" : "";
		
		if (empty($this->PresentationLayer->settings["presentation_webroot_path"]))
			launch_exception(new EVCException(5, "EVC->PresentationLayer->settings[presentation_webroot_path]"));
		
		if (($dir = opendir($layer_path . $prefix_path)) ) {
			while( ($file = readdir($dir)) !== false) {
				if (substr($file, 0, 1) != ".") {
					$folder_path = $prefix_path . $file;
					
					if (is_dir($layer_path . $folder_path)) {
						if (is_dir($layer_path . $folder_path . "/" . $this->PresentationLayer->settings["presentation_webroot_path"]))
							$projects[] = $folder_path;
						else {
							$project_folders[] = $folder_path;
							
							$sub_projects = $this->getProjectsId($folder_path . "/", $project_folders);
							$projects = array_merge($projects, $sub_projects);
						}
					}
				}
			}
			
			closedir($dir);
		}
		
		return $projects;
	}
}
?>
