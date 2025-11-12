<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.util.ShellCmdHandler");
include_once get_lib("org.phpframework.cms.laravel.LaravelInstallationHandler");
include_once $EVC->getUtilPath("WorkFlowBeansFileHandler");
include_once $EVC->getUtilPath("FlushCacheHandler");
include_once $EVC->getUtilPath("LayoutTypeProjectHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$bean_name = isset($_GET["bean_name"]) ? $_GET["bean_name"] : null;
$bean_file_name = isset($_GET["bean_file_name"]) ? $_GET["bean_file_name"] : null;
$path = isset($_GET["path"]) ? $_GET["path"] : null;
$item_type = isset($_GET["item_type"]) ? $_GET["item_type"] : null;
$filter_by_layout = isset($_GET["filter_by_layout"]) ? $_GET["filter_by_layout"] : null;

$path = str_replace("../", "", $path);//for security reasons
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons
$obj = $layer_path = null;
$global_variables_file_paths = array($user_global_variables_file_path);

if ($item_type == "dao") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/dao/$path", "layer", "access");
	
	$layer_path = DAO_PATH;
}
else if ($item_type == "lib") {
	$layer_path = LIB_PATH;
}
else if ($item_type == "vendor") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/$path", "layer", "access");
	
	$layer_path = VENDOR_PATH;
}
else if ($item_type == "other") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("other/$path", "layer", "access");
	
	$layer_path = OTHER_PATH;
}
else if ($item_type == "test_unit") {
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication("vendor/testunit/$path", "layer", "access");
	
	$layer_path = TEST_UNIT_PATH;
}
else {
	$layer_object_id = LAYER_PATH . WorkFlowBeansFileHandler::getLayerBeanFolderName($user_beans_folder_path . $bean_file_name, $bean_name, $user_global_variables_file_path) . "/";
	$layer_path_object_id = $layer_object_id . $path . "/";
	
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($layer_path_object_id, "layer", "access");
	
	$WorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $bean_file_name, $user_global_variables_file_path);
	
	if ($item_type != "presentation")
		$obj = $WorkFlowBeansFileHandler->getBeanObject($bean_name);
	else {
		$PEVC = $WorkFlowBeansFileHandler->getEVCBeanObject($bean_name, $path);
		
		if ($PEVC) {
			$obj = $PEVC->getPresentationLayer();
			$global_variables_file_paths[] = $PEVC->getConfigPath("pre_init_config");
		}
	}
	
	if ($obj)
		$layer_path = $obj->getLayerPathSetting();
}

if ($layer_path) {
	$folder_path = $layer_path . $path;
	
	$UserAuthenticationHandler->checkInnerFilePermissionAuthentication($folder_path, "layer", "access");
	
	$PHPVariablesFileHandler = new PHPVariablesFileHandler($global_variables_file_paths);
	$PHPVariablesFileHandler->startUserGlobalVariables();
	
	$db_drivers_names = $obj ? WorkFlowBeansFileHandler::getLayerDBDrivers($global_variables_file_paths, $user_beans_folder_path, $obj, true) : null;
	$default_db_driver = isset($GLOBALS["default_db_driver"]) ? $GLOBALS["default_db_driver"] : null;
	
	if ($db_drivers_names && $filter_by_layout) {
		$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $global_variables_file_paths, $user_beans_folder_path, $bean_file_name, $bean_name);
		$LayoutTypeProjectHandler->filterLayerBrokersDBDriversPropsFromLayoutName($db_drivers_names, $filter_by_layout); //filter db_drivers by $filter_by_layout
	}
	//echo "<pre>";print_r($db_drivers_names);die();
	
	if (!$db_drivers_names || !isset($db_drivers_names[$default_db_driver]))
		$default_db_driver = false;
	
	$laravel_bin_path = $laravel_cmd_is_installed = $apache_bin_path = null;
	$is_shell_cmd_allowed = ShellCmdHandler::isAllowed();
	
	if ($is_shell_cmd_allowed) {
		$which_cmd = ShellCmdHandler::getShellCommand("which");
		$laravel_bin_path = ShellCmdHandler::exec("$which_cmd laravel");
		$laravel_bin_path = $laravel_bin_path ? trim($laravel_bin_path) : "";
		$laravel_cmd_is_installed = strpos($laravel_bin_path, "laravel") !== false;
		
		$apache_bin_path = !$laravel_bin_path ? ShellCmdHandler::exec('echo $PATH') : "";
	}
	
	if (!empty($_POST["step_1"])) {
		$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "write");
		UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);
		UserAuthenticationHandler::checkActionsMaxNum($UserAuthenticationHandler);
		
		$project_name = isset($_POST["project_name"]) ? trim($_POST["project_name"]) : null;
		
		if ($project_name) {
			$project_kit = isset($_POST["project_kit"]) ? $_POST["project_kit"] : null;
			$project_stack = isset($_POST["project_stack"]) ? $_POST["project_stack"] : null;
			$project_features = isset($_POST["project_features"]) ? $_POST["project_features"] : null;
			$project_testing_framework = isset($_POST["project_testing_framework"]) ? $_POST["project_testing_framework"] : null;
			$project_database = isset($_POST["project_database"]) ? $_POST["project_database"] : null;
			
			$project_folder_path = "$folder_path/$project_name";
				
			if (is_dir($project_folder_path))
				$error_message = "This folder already exists. Please try again with a different name...";
			else if ($laravel_cmd_is_installed) {
				//prepare project_folder_path
				$parent_path = dirname($project_folder_path);
				$relative_parent_path = substr($parent_path, strlen($folder_path));
				$relative_parent_path = preg_replace("/^\/+/", "", $relative_parent_path) . "/";
				$first_relative_parent_path = substr($relative_parent_path, 0, strpos($relative_parent_path, "/"));
				
				if (!is_dir($parent_path))
					mkdir($parent_path, 0775, true);
				
				if ($relative_parent_path && !is_dir($parent_path))
					$error_message = "Could not create parent folders for project '" . substr($project_folder_path, strlen(CMS_PATH)) . "'.";
				else {
					if ($first_relative_parent_path) {
						//check if $folder_path belongs to filter_by_layout and if not, add it.
						$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $global_variables_file_paths, $user_beans_folder_path, $bean_file_name, $bean_name);
						$LayoutTypeProjectHandler->createLayoutTypePermissionsForFilePathAndLayoutTypeName($filter_by_layout, "$folder_path$first_relative_parent_path");
					}
					
					//prepare shell command
					$cmd = "$laravel_bin_path new";
					
					if ($project_kit)
						$cmd .= " --" . $project_kit;
					
					if ($project_stack)
						$cmd .= " --stack " . $project_stack;
					
					if ($project_features)
						foreach ($project_features as $v)
							$cmd .= " --" . $v;
					
					if ($project_testing_framework)
						$cmd .= " --" . $project_testing_framework;
					
					if ($project_database)
						$cmd .= " --database " . $project_database;
					
					if ($project_kit != "--no-interaction")
						$cmd .= " --no-interaction";
					
					$cmd .= " $project_name 2>&1";
					
					$output = ShellCmdHandler::exec("cd $folder_path; $cmd");
					$status = !empty($output) && preg_match("/Application ready/i", $output);
					
					if (!$status && preg_match("/The HOME or COMPOSER_HOME environment variable must be set/", $output)) {
						$apache_home_path = strstr($laravel_bin_path, "/.config/", true);
						$apache_composer_home_path = $apache_home_path . "/.config/composer/";
						$cmd = "cd $folder_path; HOME=$apache_home_path COMPOSER_HOME=/var/www/.config/composer $cmd";
						$output = ShellCmdHandler::exec("cd $folder_path; $cmd");
						$status = !empty($output) && preg_match("/Application ready/i", $output);
					}
					
					if ($status) {
						if (is_dir($project_folder_path)) {
							if (!$first_relative_parent_path) {
								//check if $folder_path belongs to filter_by_layout and if not, add it.
								$LayoutTypeProjectHandler = new LayoutTypeProjectHandler($UserAuthenticationHandler, $global_variables_file_paths, $user_beans_folder_path, $bean_file_name, $bean_name);
								$LayoutTypeProjectHandler->createLayoutTypePermissionsForFilePathAndLayoutTypeName($filter_by_layout, $project_folder_path);
							}
							
							//prepare db details
							$project_db_driver = isset($_POST["project_db_driver"]) ? $_POST["project_db_driver"] : null;
							$db_details = array();
							
							if (is_numeric($project_db_driver) && (int)$project_db_driver === 1)
								$db_details = array(
									"DB_HOST" => isset($_POST["db_host"]) ? $_POST["db_host"] : null,
									"DB_PORT" => isset($_POST["db_port"]) ? $_POST["db_port"] : null,
									"DB_DATABASE" => isset($_POST["db_name"]) ? $_POST["db_name"] : null,
									"DB_USERNAME" => isset($_POST["db_user"]) ? $_POST["db_user"] : null,
									"DB_PASSWORD" => isset($_POST["db_pass"]) ? $_POST["db_pass"] : null
								);
							else {
								$db_driver_name = !$project_db_driver ? $default_db_driver : $project_db_driver;
								
								if ($db_driver_name && $db_drivers_names && isset($db_drivers_names[$db_driver_name])) {
									$db_driver_props = $db_drivers_names[$db_driver_name];
									$DBDriverWorkFlowBeansFileHandler = new WorkFlowBeansFileHandler($user_beans_folder_path . $db_driver_props[1], $global_variables_file_paths);
									$DBDriverWorkFlowBeansFileHandler->init();
									$db_driver_settings = $DBDriverWorkFlowBeansFileHandler->getDBSettings($db_driver_props[2]);
									
									if ($db_driver_settings)
										$db_details = array(
											"DB_HOST" => isset($db_driver_settings["host"]) ? $db_driver_settings["host"] : null,
											"DB_PORT" => isset($db_driver_settings["port"]) ? $db_driver_settings["port"] : null,
											"DB_DATABASE" => isset($db_driver_settings["db_name"]) ? $db_driver_settings["db_name"] : null,
											"DB_USERNAME" => isset($db_driver_settings["username"]) ? $db_driver_settings["username"] : null,
											"DB_PASSWORD" => isset($db_driver_settings["password"]) ? $db_driver_settings["password"] : null
										);
								}
							}
							
							//check db details
							if ($db_details) {
								//prepare laravel installation
								$status = LaravelInstallationHandler::hackLaravelInstallation($project_folder_path, $project_url_prefix, $db_details);
								
								//check if the db_details are correct
								if (!$status && !LaravelInstallationHandler::testDBConnection($project_folder_path, false, $error_message))
										$status = false;
								
								//migrate DB
								$php_cmd = ShellCmdHandler::getShellCommand("php");
								$output = $php_cmd ? ShellCmdHandler::exec("cd $project_folder_path; $php_cmd artisan migrate -n;") : null;
								
								if (!$output && !preg_match("/(DONE|Nothing to migrate)/", $output))
									$status = false;
							}
							else {
								$error_message = "DB Credentials cannot be undefined";
								$status = false;
							}
							
							//delete cache bc of the previously cached business logic services
							FlushCacheHandler::flushCache($EVC, $webroot_cache_folder_path, $webroot_cache_folder_url, $workflow_paths_id, $user_global_variables_file_path, $user_beans_folder_path, $css_and_js_optimizer_webroot_cache_folder_path, $deployments_temp_folder_path); //flush cache
						}
						else {
							$error_message = "Error trying to create folder '$project_name' in '" . substr($folder_path, strlen(CMS_PATH)) . "'. Please try again...";
							$status = false;
						}
					}
					else
						$error_message = "Error executing the following command:<pre>$cmd</pre> with output:<pre>$output</pre>";
				}
			}
			else if (!$is_shell_cmd_allowed)
				$error_message = "PHP '" . ShellCmdHandler::FUNCTION_NAME . "' function is disabled. please enable this function to proceed.";
			else if (!$laravel_bin_path)
				$error_message = "Laravel bin path is undefined. Please be sure that laravel is installed globally and your web server has access to it. The current web server path is: '$apache_bin_path'.";
			else
				$error_message = "Laravel command is not installed. Please install laravel command line to proceed.";
		}
		else
			$error_message = "Project name cannot be empty";
	}
	else {
		//laravel new [--dev] [--git] [--branch BRANCH] [--github [GITHUB]] [--organization ORGANIZATION] [--database DATABASE] [--stack [STACK]] [--breeze] [--jet] [--dark] [--typescript] [--eslint] [--ssr] [--api] [--teams] [--verification] [--pest] [--phpunit] [--prompt-breeze] [--prompt-jetstream] [-f|--force] [--] <name>
		//--stack: blade, livewire, livewire-functional, react, vue, api
		//--database: mysql, mariadb, pgsql, sqlite, sqlsrv
		/*
		[--no-interaction]
			[--database] [--pest || --phpunit]
		
		[--breeze]
			--stack blade
				[--dark] [--database] [--pest || --phpunit]
			--stack livewire
			--stack livewire-functional
				[--dark] [--database] [--pest || --phpunit]
			--stack react
				[--dark] [--database] [--typescript] [--eslint] [--ssr] [--pest || --phpunit]
			--stack vue
				[--dark] [--database] [--typescript] [--eslint] [--ssr] [--pest || --phpunit]
			--stack api
				[--dark] [--database] [--pest || --phpunit]
		
		[--jet]
			--stack livewire
				[--dark] [--database] [--api] [--teams] [--verification] [--pest || --phpunit]
			--stack vue
				[--dark] [--database] [--api] [--teams] [--verification] [--ssr] [--pest || --phpunit]
		*/
		$laravel_kits = array(
			"no-interaction" => "No Starter Kit", 
			"breeze" => "Laravel Breeze", 
			"jet" => "Laravel Jetstream"
		);
		$laravel_kit_stacks = array(
			"breeze" => array(
				"blade" => "Blade with Alpine",
				"livewire" => "Livewire (Volt Class API) with Alpine",
				"livewire-functional" => "Livewire (Volt Functional API) with Alpine",
				"react" => "React with Inertia",
				"vue" => "Vue with Inertia",
				"api" => "API only",
			),
			"jet" => array(
				"livewire" => "Livewire",
				"vue" => "Vue with Inertia",
			)
		);
		$laravel_kit_stack_features = array(
			"breeze" => array(
				"blade" => array(
					"dark" => "Dark Mode"
				),
				"livewire" => array(
					"dark" => "Dark Mode"
				),
				"livewire-functional" => array(
					"dark" => "Dark Mode"
				),
				"react" => array(
					"dark" => "Dark Mode",
					"ssr" => "Inertia SSR",
					"typescript" => "TypeScript",
					"eslint" => "ESLint with Prettier"
				),
				"vue" => array(
					"dark" => "Dark Mode",
					"ssr" => "Inertia SSR",
					"typescript" => "TypeScript",
					"eslint" => "ESLint with Prettier"
				),
				"api" => array(
					"dark" => "Dark Mode"
				),
			),
			"jet" => array(
				"livewire" => array(
					"api" => "API support",
					"dark" => "Dark Mode",
					"verification" => "Email verification",
					"teams" => "Team support"
				),
				"vue" => array(
					"api" => "API support",
					"dark" => "Dark Mode",
					"verification" => "Email verification",
					"teams" => "Team support",
					"ssr" => "Inertia SSR"
				),
			)
		);
		$laravel_testing_frameworks = array(
			"pest" => "Pest",
			"phpunit" => "PHPUnit"
		);
		$laravel_databases = array(
			"mysql" => "MySQL",
			"mariadb" => "MariaDB",
			"pgsql" => "PostGres",
			"sqlite" => "SQLite",
			"sqlsrv" => "SQL Server"
		);
	}

	$PHPVariablesFileHandler->endUserGlobalVariables();
}
?>
