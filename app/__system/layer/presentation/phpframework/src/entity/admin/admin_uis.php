<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$filter_by_layout = $_GET["filter_by_layout"];
$filter_by_layout = str_replace("../", "", $filter_by_layout);//for security reasons

include $EVC->getUtilPath("admin_uis_permissions");
?>
