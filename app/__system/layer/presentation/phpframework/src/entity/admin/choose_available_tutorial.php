<?php
include_once $EVC->getUtilPath("VideoTutorialHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

$simple_tutorials = VideoTutorialHandler::getSimpleTutorials($project_url_prefix, $online_tutorials_url_prefix);
$advanced_tutorials = VideoTutorialHandler::getAdvancedTutorials($project_url_prefix, $online_tutorials_url_prefix);
?>
