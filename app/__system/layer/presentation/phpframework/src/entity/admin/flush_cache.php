<?php
include_once $EVC->getUtilPath("FlushCacheHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "delete");

$status = FlushCacheHandler::flushCache($EVC, $webroot_cache_folder_path, $webroot_cache_folder_url, $workflow_paths_id, $user_global_variables_file_path, $user_beans_folder_path, $css_and_js_optimizer_webroot_cache_folder_path, $deployments_temp_folder_path);
?>
