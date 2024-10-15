<?php
include $EVC->getUtilPath("BreadCrumbsUIHandler");

$upload_url = $project_url_prefix . "admin/manage_file?bean_name=$bean_name&bean_file_name=$bean_file_name&filter_by_layout=$filter_by_layout&path=$path&action=upload&item_type=$item_type";

$on_success_js_func = $on_success_js_func ? $on_success_js_func : "refreshAndShowLastNodeChilds";

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icon CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Adding DropZone plugin -->
<script src="' . $project_common_url_prefix . 'vendor/dropzone/dropzone.js"></script>
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/dropzone/min/dropzone.min.css">

<!-- Adding Local files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/upload_file.css" type="text/css" charset="utf-8" />
<script>
Dropzone.autoDiscover = false; // Disabling autoDiscover, otherwise Dropzone will try to attach twice.

$(function() {
	var default_error_msg = "Error uploading file! Please try again and if the problem persists, contact the sysadmin...";
	
	var myDropzone = new Dropzone(".dropzone", {
		success: function(file, response, progress_event) {
			//console.log(response);
			if (response != 1) {
				var dz_error_message = $(file.previewElement).removeClass("dz-success").addClass("dz-error").children(".dz-error-message");
				dz_error_message.addClass("show");
				dz_error_message.children("[data-dz-errormessage]").html(response ? response : default_error_msg);
				
				//myDropzone.removeFile(file);
			}
			else if (typeof window.parent.' . $on_success_js_func . ' == "function")
				window.parent.' . $on_success_js_func . '();
		},
		error: function(file, response) {
			var dz_error_message = $(file.previewElement).find(".dz-error-message");
			dz_error_message.addClass("show");
			dz_error_message.children("[data-dz-errormessage]").html(response ? response : default_error_msg);
		}
	});
});
</script>';

$main_content = '
<div class="top_bar' . ($popup ? " in_popup" : "") . '">
	<header>
		<div class="title" title="' . $path . '">Upload Files into  in ' . BreadCrumbsUIHandler::getFilePathBreadCrumbsHtml($file_path, $obj) . '</div>
	</header>
</div>

<div class="upload_files' . ($popup ? " in_popup" : "") . '">
	<form action="' . $upload_url . '" class="dropzone"></form>
</div>';
?>
