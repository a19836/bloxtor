<?php
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
			$k .= $parts[$i][0];
		
		if (!array_key_exists($k, $li))
			$li_data[$k] = $v;
	}

unset($li);
?>
