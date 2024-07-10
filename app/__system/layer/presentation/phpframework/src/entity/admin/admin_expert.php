<?php
include_once $EVC->getUtilPath("CMSPresentationLayerHandler");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

include $EVC->getUtilPath("admin_uis_layers_and_permissions");
//echo "<pre>";print_r($layers);die();
?>
