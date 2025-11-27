<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

//PREPARE CONTROLLER
$EVC->setController( basename(__FILE__, ".php") );

//PREPARE PAGE
$page_prefix = "";
$page = "";
$parameters_count = $parameters ? count($parameters) : 0;
$entities_path = $EVC->getEntitiesPath();
$controller_exists_in_url = preg_match("/^index\//", $url);

//if url is something like "example.com/index/article/list" the parameters will be array(article, list), without the index, bc index is a controller. So we want to add the index to the path_prefix if it is a folder. If index folder exists, takes priority!
if ($controller_exists_in_url && is_dir($entities_path . "index"))
	$page_prefix = "index/";

for ($i = 0; $i < $parameters_count; $i++) {
	$page = $parameters[$i];
	
	if ($page) {
		if (is_dir($entities_path . $page_prefix . $page)) {
			if ($i + 1 == $parameters_count && is_file($EVC->getEntityPath($page_prefix . $page))) //if last parameter and is a php file (besides a directory), gives priority to the file.
				break;
			
			$page_prefix .= $page . "/";
			$page = "";
		}
		else
			break;
	}
}

//PREPARE DEFAULTS
$default_entity = $page ? $page : (!empty($GLOBALS["project_default_entity"]) ? $GLOBALS["project_default_entity"] : "index");
$default_view = $default_entity && !empty($GLOBALS["project_with_auto_view"]) ? $default_entity : (!empty($GLOBALS["project_default_view"]) ? $GLOBALS["project_default_view"] : null);
$default_template = isset($GLOBALS["project_default_template"]) ? $GLOBALS["project_default_template"] : null;

$EVC->setEntity($default_entity);

if ($default_view) 
	$EVC->setView($default_view);

if ($default_template)
	$EVC->setTemplate($default_template);

$CMSHtmlParserLayer = $EVC->getCMSLayer()->getCMSHtmlParserLayer();
$default_entity_code = substr($default_entity, 0, 1) == "/" ? $default_entity : $page_prefix . $default_entity;
$CMSHtmlParserLayer->init($default_entity_code, $project_url_prefix, $project_common_url_prefix);

//PREPARE ENTITIES
$entities = $EVC->getEntities();
$entities_params = $EVC->getEntitiesParams();

for ($entity_index = 0; $entity_index < count($entities); ++$entity_index) {
	$entity = $entities[$entity_index];
	
	if ($entity) {
		$entity_params = isset($entities_params[$entity_index]) ? $entities_params[$entity_index] : null;
		$entity_project_id = $entity_params && isset($entity_params["project_id"]) ? $entity_params["project_id"] : false;
		$entity = substr($entity, 0, 1) == "/" ? $entity : $page_prefix . $entity;
		$entity_path = $EVC->getEntityPath($entity, $entity_project_id);
		
		if (file_exists($entity_path)) 
			include $entity_path;
		else {
			$is_reserve = false;
			
			//avoid log exception when the edit_entity_simple, edit_template_simple, edit_view and edit_block_simple tries to open an image with a dynamic url containing the $project_url_prefix var.
			if (defined("IS_SYSTEM_PHPFRAMEWORK")) {
				//'presentation/{$project_url_prefix}', 'presentation/${project_url_prefix}', 'presentation/<?= $project_url_prefix', 'presentation/<? echo $original_project_url_prefix', etc...
				$layer_folder_name = preg_replace("/(^\/+|\/+$)/", "", substr($EVC->getPresentationLayer()->getLayerPathSetting(), strlen(SYSTEM_LAYER_PATH)));
				$is_reserve = strpos($entity, "$layer_folder_name/\$project_url_prefix") === 0 || strpos($entity, "$layer_folder_name/{\$project_url_prefix}") === 0 || strpos($entity, "$layer_folder_name/\${project_url_prefix}") === 0 || strpos($entity, "$layer_folder_name/<") === 0;
				//echo "entity:$entity\n<br>";print_r($parameters);
			}
			
			if (!$is_reserve) {
				header("HTTP/1.0 404 Not Found");
				launch_exception(new EVCException(2, $entity_path));
			}
		}
		
		//Each entity can add or remove other entities, so we need to update the $entities everytime we call an entity
		$entities = $EVC->getEntities(); 
		$entities_params = $EVC->getEntitiesParams();
	}
}

//PREPARE VIEWS
$views = $EVC->getViews();
$views_params = $EVC->getViewsParams();

for ($view_index = 0; $view_index < count($views); ++$view_index) {
	$view = $views[$view_index];
	
	if ($view) {
		$view_params = isset($views_params[$view_index]) ? $views_params[$view_index] : null;
		$view_project_id = $view_params && isset($view_params["project_id"]) ? $view_params["project_id"] : false;
		$view = substr($view, 0, 1) == "/" ? $view : $page_prefix . $view;
		$view_path = $EVC->getViewPath($view, $view_project_id);
		
		if (file_exists($view_path)) 
			include $view_path;
		else if ($views[$view_index] != $default_view) { //if is equal to $default_entity or $project_default_view, means that the $default_view is optional and only gets included if exists. Note that the $project_default_view may exists but only for root, and not for a specific folder, so it must be optional.
			header("HTTP/1.0 404 Not Found");
			launch_exception(new EVCException(3, $view_path));
		}
		
		//Each view can add or remove other views, so we need to update the $views everytime we call an view
		$views = $EVC->getViews(); 
		$views_params = $EVC->getViewsParams();
	}
}

//PREPARE TEMPLATES
$templates = $EVC->getTemplates();
$templates_params = $EVC->getTemplatesParams();

for ($template_index = 0; $template_index < count($templates); ++$template_index) {
	$template = $templates[$template_index];
	
	if ($template) {
		$template_params = isset($templates_params[$template_index]) ? $templates_params[$template_index] : null;
		$template_project_id = $template_params && isset($template_params["project_id"]) ? $template_params["project_id"] : false;
		$template_path = $EVC->getTemplatePath($template, $template_project_id);
		
		if (file_exists($template_path)) {
			$CMSHtmlParserLayer->beforeIncludeTemplate($template_path);
			
			include $template_path;
			
			$CMSHtmlParserLayer->afterIncludeTemplate($template_path);
		}
		else {
			header("HTTP/1.0 404 Not Found");
			launch_exception(new EVCException(4, $template_path));
		}
		
		//Each template can add or remove other templates, so we need to update the $templates everytime we call an template
		$templates = $EVC->getTemplates(); 
		$templates_params = $EVC->getTemplatesParams();
	}
}
?>
