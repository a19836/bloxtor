<?php
echo CMSPresentationLayerJoinPointsUIHandler::getBlockJoinPointsHtml(isset($module_join_points) ? $module_join_points : null, $block, false, isset($module["module_handler_impl_file_path"]) ? $module["module_handler_impl_file_path"] : null);

$EVC->setTemplate("empty");
?>
