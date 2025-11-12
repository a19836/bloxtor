<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once $EVC->getUtilPath("VideoTutorialHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$popup = isset($_GET["popup"]) ? $_GET["popup"] : null;

$simple_tutorials = VideoTutorialHandler::getSimpleTutorials($project_url_prefix, $online_tutorials_url_prefix);
$advanced_tutorials = VideoTutorialHandler::getAdvancedTutorials($project_url_prefix, $online_tutorials_url_prefix);
?>
