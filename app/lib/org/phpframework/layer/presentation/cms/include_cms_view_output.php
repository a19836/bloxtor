<?php
//This file should be called in the entities to directly include a view file into a specific region, through the method: CMSTemplateLayer::includeRegionViewPathOutput.
if ($GLOBALS["VIEW_FILE_PATH"] && $GLOBALS["VIEW_ID"]) {
	ob_start(null, 0);
	
	include $GLOBALS["VIEW_FILE_PATH"];
	
	$view_output = ob_get_contents();
	ob_end_clean();
	
	$EVC->getCMSLayer()->getCMSViewLayer()->createViewHtml($GLOBALS["VIEW_ID"], $view_output);
}
?>
