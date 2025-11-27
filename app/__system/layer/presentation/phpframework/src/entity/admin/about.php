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

//include_once get_lib("org.phpframework.layer.presentation.cms.module.CMSModuleInstallationHandler");
//include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");
$user_actions_count = $UserAuthenticationHandler->getUsedActionsTotal();

$li = $EVC->getPresentationLayer()->getPHPFrameWork()->getLicenceInfo();
$li_data = array();

if (is_array($li))
	foreach ($li as $k => $v) {
		$parts = explode("_", $k);
		
		$k = "";
		for ($i = count($parts) - 1; $i >= 0; $i--)
			$k .= isset($parts[$i][0]) ? $parts[$i][0] : null;
		
		if (!array_key_exists($k, $li))
			$li_data[$k] = $v;
	}

unset($li);
?>
