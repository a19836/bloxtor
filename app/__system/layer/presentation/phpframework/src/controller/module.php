<?php
define("PROJECTS_CHECKED", 123); //otherwise the system will think that the user hacked the licence. This must be here, otherwise when the code check for the PROJECTS_CHECKED variable, will not find it and proceed to purge the __system folder and others...

include $EVC->getConfigPath("config");
include $EVC->getUtilPath("sanitize_html_in_post_request", $EVC->getCommonProjectName());
include $EVC->getConfigPath("authentication");

include $EVC->getControllerPath("module", $EVC->getCommonProjectName());
?>
