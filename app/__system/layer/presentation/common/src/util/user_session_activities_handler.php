<?php
/*
 * This file will be used in the modules or entities to check if the logged user has the necessary permission to continue...
 * This file only makes sense if the user module is installed.
 * The functions in this file will use the UserSessionActivitiesHandler class in the module/user/UserSessionActivitiesHandler.php file.
 */

function validateModuleUserActivity($EVC, $activity, $module_path) {
	if ($activity && $module_path) {
		initUserSessionActivitiesHandler($EVC);
		
		if (!empty($GLOBALS["UserSessionActivitiesHandler"])) {
			return $GLOBALS["UserSessionActivitiesHandler"]->validateUserActivityByModule($activity, $module_path);
		}
	}
}

function validatePageUserActivity($EVC, $activity, $entity_path) {
	if ($activity && $entity_path) {
		initUserSessionActivitiesHandler($EVC);
		
		if (!empty($GLOBALS["UserSessionActivitiesHandler"])) {
			return $GLOBALS["UserSessionActivitiesHandler"]->validateUserActivityByPage($activity, $entity_path);
		}
	}
}

function moduleUserActivityExists($EVC, $activity, $module_path) {
	if ($activity && $module_path) {
		initUserSessionActivitiesHandler($EVC);
		
		if (!empty($GLOBALS["UserSessionActivitiesHandler"])) {
			return $GLOBALS["UserSessionActivitiesHandler"]->userActivityExistsByModule($activity, $module_path);
		}
	}
}

function pageUserActivityExists($EVC, $activity, $entity_path) {
	if ($activity && $entity_path) {
		initUserSessionActivitiesHandler($EVC);
		
		if (!empty($GLOBALS["UserSessionActivitiesHandler"])) {
			return $GLOBALS["UserSessionActivitiesHandler"]->userActivityExistsByPage($activity, $entity_path);
		}
	}
}

function initUserSessionActivitiesHandler($EVC) {
	if (empty($GLOBALS["UserSessionActivitiesHandler"])) {
		$init_user_data = $EVC->getCMSLayer()->getCMSPagePropertyLayer()->getInitUserData();

		if ($init_user_data === false)
			return null;
		
		$fp = $EVC->getModulePath("user/include_user_session_activities_handler", $EVC->getCommonProjectName());
		
		if (file_exists($fp)) {
			try {
				include $fp;
			}
			catch(Exception $e) {
				//if module user is installed and active in the framework, but for a specific project this module is not installed in the correspondent DB, this will give an exception. So, in this case, we need to catch the exception.
				//If init_user_data is true launch exception, otherwise (if null|auto) ignore it, so the code can be executed correctly.
				//By default ignore the exception
				if ($init_user_data)
					launch_exception($e);
				else if (!$EVC->getPresentationLayer()->getErrorHandler()->ok())
				    $EVC->getPresentationLayer()->getErrorHandler()->start();
			}
		}
	}
}
?>
