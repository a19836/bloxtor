<?php
class LayoutTypeProjectUIHandler {
	
	public static function getHeader() {
		return '
<script>
function prepareGetSubFilesUrlToFilterOnlyByBelongingFiles(url) {
	if (url.indexOf("?filter_by_layout=") != -1 || url.indexOf("&filter_by_layout=") != -1) {
		//remove all filter_by_layout_permission
		url = url.replace(/&?filter_by_layout_permission=([^&]*)/g, "");
		url = url.replace(/&?filter_by_layout_permission\\[\\]=([^&]*)/g, "");
		
		url += "&filter_by_layout_permission=' . UserAuthenticationHandler::$PERMISSION_BELONG_NAME . '"; //add filter_by_layout_permission=referenced
	}
	
	return url;
}

function prepareGetSubFilesUrlToFilterOnlyByReferencedFiles(url) {
	if (url.indexOf("?filter_by_layout=") != -1 || url.indexOf("&filter_by_layout=") != -1) {
		//remove all filter_by_layout_permission
		url = url.replace(/&?filter_by_layout_permission=([^&]*)/g, "");
		url = url.replace(/&?filter_by_layout_permission\\[\\]=([^&]*)/g, "");
		
		url += "&filter_by_layout_permission=' . UserAuthenticationHandler::$PERMISSION_REFERENCED_NAME . '"; //add filter_by_layout_permission=referenced
	}
	
	return url;
}
</script>
		';
	}
	
	public static function getJavascriptHandlerToParseGetSubFilesUrlWithOnlyBelongingFiles() {
		return "prepareGetSubFilesUrlToFilterOnlyByBelongingFiles";
	}
	
	public static function getJavascriptHandlerToParseGetSubFilesUrlWithOnlyReferencedFiles() {
		return "prepareGetSubFilesUrlToFilterOnlyByReferencedFiles";
	}
	
	public static function getFilterByLayoutURLQuery($filter_by_layout) {
		return $filter_by_layout ? "&filter_by_layout=$filter_by_layout&filter_by_layout_permission[]=" . UserAuthenticationHandler::$PERMISSION_BELONG_NAME . "&filter_by_layout_permission[]=" . UserAuthenticationHandler::$PERMISSION_REFERENCED_NAME : "";
	}
}
?>
