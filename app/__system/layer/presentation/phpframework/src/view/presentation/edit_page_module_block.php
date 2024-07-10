<?php
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$title = 'Add Block in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($file_path, $P, true);
$title_icons = '<li class="go_back" title="Go Back"><a href="javascript:history.back();"><i class="icon go_back"></i> Go Back</a></li>';
$save_url = $project_url_prefix . "phpframework/presentation/save_page_module_block?bean_name=$bean_name&bean_file_name=$bean_file_name&path=$path";

include $EVC->getViewPath("presentation/edit_block_simple");

$main_content .= '<script>
$(function () {
	$(window).unbind("beforeunload");
	
	disableAutoSave(onToggleAutoSave);
	$(".top_bar").find("li.auto_save_activation").remove();
});
</script>';
?>
