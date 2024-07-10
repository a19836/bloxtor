<?php
include $EVC->getConfigPath("config");
include_once $EVC->getUtilPath("include_text_translator_handler", $EVC->getCommonProjectName());
include $EVC->getUtilPath("sanitize_html_in_post_request", $EVC->getCommonProjectName());

include $EVC->getControllerPath("module", $EVC->getCommonProjectName());
?>
