<?php
//This file should be called in the entities to directly include a block file into a specific region, through the method: CMSTemplateLayer::includeRegionBlockPathOutput.
if (!empty($GLOBALS["BLOCK_FILE_PATH"]) && !empty($GLOBALS["BLOCK_ID"])) {
	ob_start(null, 0);
	
	include $GLOBALS["BLOCK_FILE_PATH"];
	
	$block_output = ob_get_contents();
	ob_end_clean();
	
	$EVC->getCMSLayer()->getCMSBlockLayer()->createBlockHtml($GLOBALS["BLOCK_ID"], $block_output);
}
?>
