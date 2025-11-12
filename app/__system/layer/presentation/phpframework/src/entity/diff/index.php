<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

include_once $EVC->getEntityPath("admin/admin_advanced");

//remove db_layers
unset($layers["db_layers"]);

//echo "<pre>";print_r($layers);die();

//delete all projects. Projects will be get from the get_sub_files request and with the proper permissions
if (!empty($layers["presentation_layers"]))
	foreach ($layers["presentation_layers"] as $layer_name => $layer)
		foreach ($layer as $fn => $f)
			if ($fn != "properties" && $fn != "aliases")
				unset($layers["presentation_layers"][$layer_name][$fn]);
?>
