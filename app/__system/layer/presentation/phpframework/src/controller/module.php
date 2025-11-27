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

define("PROJECTS_CHECKED", 123); //otherwise the system will think that the user hacked the licence. This must be here, otherwise when the code check for the PROJECTS_CHECKED variable, will not find it and proceed to purge the __system folder and others...

include $EVC->getConfigPath("config");
include $EVC->getUtilPath("sanitize_html_in_post_request", $EVC->getCommonProjectName());
include $EVC->getConfigPath("authentication");

include $EVC->getControllerPath("module", $EVC->getCommonProjectName());
?>
