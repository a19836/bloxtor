<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

include_once $EVC->getEntityPath("admin/admin_advanced");

//remove db_layers
unset($layers["db_layers"]);

//echo "<pre>";print_r($layers);die();

//delete all projects. Projects will be get from the get_sub_files request and with the proper permissions
if ($layers["presentation_layers"])
	foreach ($layers["presentation_layers"] as $layer_name => $layer)
		foreach ($layer as $fn => $f)
			if ($fn != "properties" && $fn != "aliases")
				unset($layers["presentation_layers"][$layer_name][$fn]);
?>
