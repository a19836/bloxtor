<?php
$project_protocol = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? "https://" : "http://"; //Do not add " || $_SERVER['SERVER_PORT'] == 443" bc the ssl port may not be 443 depending of the server configuration

$parts = explode("/__system/", (isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : ""));
if (count($parts) > 1) {
	$project_relative_url_prefix = $parts[0] . "/__system/";
	$project_common_relative_url_prefix = $parts[0] . "/__system/" . $EVC->getCommonProjectName() . "/";
}
else {
	$document_root = str_replace("//", "/", (isset($_SERVER["CONTEXT_DOCUMENT_ROOT"]) ? $_SERVER["CONTEXT_DOCUMENT_ROOT"] : (isset($_SERVER["DOCUMENT_ROOT"]) ? $_SERVER["DOCUMENT_ROOT"] : "") ) . "/");
	$project_relative_url_prefix = "/" . (strpos($document_root, "/__system/") !== false ? "" : "__system/"); //if is a direct domain to the project, doesn't add the __system.
	$project_common_relative_url_prefix = "/" . (strpos($document_root, "/__system/") !== false ? "" : "__system/") . $EVC->getCommonProjectName() . "/"; //if is a direct domain to the project, the vhosts need to have the /common/ path defined to the right folder, otherwise this won't work correctly.
}

$http_host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : null;
$project_url_prefix = $project_protocol . $http_host . $project_relative_url_prefix;
$project_common_url_prefix = $project_protocol . $http_host . $project_common_relative_url_prefix;
//echo $_SERVER["DOCUMENT_ROOT"]."|$project_url_prefix|$project_common_url_prefix";die();

$presentation_webroot_path = $EVC->getPresentationLayer()->getSelectedPresentationSetting("presentation_webroot_path");
$webroot_cache_folder_path = $presentation_webroot_path . "__system/cache/";
$webroot_cache_folder_url = $project_url_prefix . "phpframework/__system/cache/";

$css_and_js_optimizer_webroot_cache_folder_path = $presentation_webroot_path . "__system/cache/cssandjsoptimizer/";
$css_and_js_optimizer_webroot_cache_folder_url = $project_url_prefix . "phpframework/__system/cache/cssandjsoptimizer/";

$workflow_paths_id = array(
	"layer" => CMS_PATH . "other/workflow/layer/layers.xml",
	"db_diagram" => CMS_PATH . "other/workflow/db_diagram/db_diagram.xml",
	"php_file_workflow" => CMS_PATH . "other/workflow/php_file_flow/php_file_workflow.xml",
	"php_file_workflow_tmp" => TMP_PATH . "workflow/php_file_flow/php_file_workflow.xml",
	"business_logic_workflow" => CMS_PATH . "other/workflow/business_logic_flow/business_logic_workflow.xml",
	"business_logic_workflow_tmp" => TMP_PATH . "workflow/business_logic_flow/business_logic_workflow.xml",
	"presentation_workflow" => CMS_PATH . "other/workflow/presentation_flow/presentation_workflow.xml",
	"presentation_workflow_tmp" => TMP_PATH . "workflow/presentation_flow/presentation_workflow.xml",
	"presentation_ui" => CMS_PATH . "other/workflow/presentation_ui/presentation_uis_diagram.xml",
	"presentation_block_workflow" => CMS_PATH . "other/workflow/presentation_block_flow/presentation_workflow.xml",
	"presentation_block_workflow_tmp" => TMP_PATH . "workflow/presentation_block_flow/presentation_workflow.xml",
	"presentation_block_form_sla" => CMS_PATH . "other/workflow/presentation_block_form_sla/presentation_workflow.xml",
	"presentation_block_form_sla_tmp" => TMP_PATH . "workflow/presentation_block_form_sla/presentation_workflow.xml",
	"presentation_entity_sla" => CMS_PATH . "other/workflow/presentation_entity_sla/presentation_workflow.xml",
	"presentation_entity_sla_tmp" => TMP_PATH . "workflow/presentation_entity_sla/presentation_workflow.xml",
	"presentation_template_sla" => CMS_PATH . "other/workflow/presentation_template_sla/presentation_workflow.xml",
	"presentation_template_sla_tmp" => TMP_PATH . "workflow/presentation_template_sla/presentation_workflow.xml",
	"test_unit_workflow" => CMS_PATH . "other/workflow/test_unit_flow/test_unit_workflow.xml",
	"test_unit_workflow_tmp" => TMP_PATH . "workflow/test_unit_flow/test_unit_workflow.xml",
	"deployment" => CMS_PATH . "other/workflow/deployment/deployment.xml",
);

$deployments_temp_folder_path = TMP_PATH . "deployment/";

$code_workflow_editor_user_tasks_folders_path = array(CODE_WORKFLOW_EDITOR_TASK_PATH);
$layout_ui_editor_user_widget_folders_path = array(LAYOUT_UI_EDITOR_WIDGET_PATH);

$user_global_variables_file_path = CONFIG_PATH . "global_variables.php";
$user_global_settings_file_path = CONFIG_PATH . "global_settings.php";
$user_beans_folder_path = BEAN_PATH;

$cms_page_cache_path_prefix = "presentation_cms_pages_file_modified_date/";

$sanitize_html_in_post_request = false; //This is very important bc it protects against xss attacks

include_once get_lib("org.phpframework.util.web.html.CssAndJSFilesOptimizer");
$CssAndJSFilesOptimizer = new CssAndJSFilesOptimizer($css_and_js_optimizer_webroot_cache_folder_path, $css_and_js_optimizer_webroot_cache_folder_url, array(
	"urls_prefix" => array($project_url_prefix, $project_common_url_prefix), 
	"url_strings_to_avoid" => array("/cssandjsoptimizer/", "highlight.pack.js"),
));

$version = 1; //framework version

$bloxtor_home_page_url = "https://bloxtor.com/";
$modules_download_page_url = "https://bloxtor.com/store#modules";
$templates_download_page_url = "https://bloxtor.com/store#templates";
$programs_download_page_url = "https://bloxtor.com/store#programs";
$pages_download_page_url = "https://bloxtor.com/store#pages";
$get_store_modules_url = "https://bloxtor.com/get_store_type_content?type=modules&data_type=json";
$get_store_templates_url = "https://bloxtor.com/get_store_type_content?type=templates&data_type=json";
$get_store_programs_url = "https://bloxtor.com/get_store_type_content?type=programs&data_type=json";
$get_store_pages_url = "https://bloxtor.com/get_store_type_content?type=pages&data_type=json";
$dependencies_repo_url = "https://bloxtor.com/framework_store/dependencies/";
$dependency_wordpress_zip_file_url = $dependencies_repo_url . "wordpress.zip";
$dependency_phpmyadmin_zip_file_url = $dependencies_repo_url . "phpmyadmin.zip";
$send_email_action_url = "https://bloxtor.com/send_email";

$include_js_plumb = false;
$remote_addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
$is_localhost = strpos($http_host, "localhost") !== false || strpos($http_host, "127.0.0.1") !== false || in_array($remote_addr, array("127.0.0.1", "::1"));
$external_libs_url_prefix = array(
	"jsplumb" => $include_js_plumb ? "//jamapconsult.pt/gpl_js/jqueryjsplumb/" : null,
	"taskflowchart" => ($is_localhost || !$include_js_plumb ? $project_url_prefix . "lib/" : "//jplpinto.com/others/onlineitframework/proprietary_js/") . "jquerytaskflowchart/",
);
$online_tutorials_url_prefix = "https://bloxtor.com/onlineitframeworktutorial/?block_id=";
//$online_tutorials_url_prefix = "http://jplpinto.localhost/onlineitframeworktutorial/?block_id=";

$license_path = CMS_PATH . "LICENSE.md";
?>
