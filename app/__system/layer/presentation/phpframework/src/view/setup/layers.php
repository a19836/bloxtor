<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include $EVC->getViewPath("/layer/diagram");

$confirm_msg = $diagram_already_exists ? "If you added new DataBases in this diagram, you can have some issues in the future, because when you did the installation, there were some tables that were created automatically, which will not be created in the new DataBases, this is, tables from installed Modules and maybe from the CMS authentication system...\\n\\nDo you still wish to continue?" : "We will save this workflow automatically. Do you wish to continue?";

$head .= '
<script>
	function continueSetup(do_not_confirm) {
		if (do_not_confirm || confirm("' . $confirm_msg . '")) {
			var popup = taskFlowChartObj.getMyFancyPopupObj();
			MyFancyPopup.init();
			MyFancyPopup.showOverlay();
			MyFancyPopup.showLoading();
			
			$(window).unbind("beforeunload");
			
			var save_options = {
				success: function(data, textStatus, jqXHR) {
					if (jquery_native_xhr_object && isAjaxReturnedResponseLogin(jquery_native_xhr_object.responseURL))
						showAjaxLoginPopup(jquery_native_xhr_object.responseURL, taskFlowChartObj.TaskFile.set_tasks_file_url, function() {
							taskFlowChartObj.StatusMessage.removeLastShownMessage("error");
							continueSetup(true);
						});
				},
			};
			
			if (taskFlowChartObj.TaskFile.save(null, save_options))
				$("#layer_form form").submit();
			else
				MyFancyPopup.hidePopup();
		}
	}
</script>';

if ($hide_cancel_btn)
	$head .= '<style> #setup .buttons .cancel {display:none;} </style>';

if ($hide_beginner_btn)
	$head .= '<style> #setup .buttons .back {display:none;} </style>';

if ($strict_connections_to_one_level)
	$head .= '<script> allow_connections_to_multiple_levels = false; //allow connections to only 1 level below. </script>';

$main_content = '<div id="layer_form"' . ($hide_setup ?'class="hide_setup"' : '') . '">
	' . $main_content . '
	
	<form method="post" style="display:none">
		<input type="hidden" name="create_layers_workflow" value="1" />';

if (!empty($tasks_folders))
	foreach ($tasks_folders as $task_id => $folder)
		$main_content .= '
		<input type="hidden" name="tasks_folders[' . $task_id . ']" value="' . $folder . '" />';

if (!empty($tasks_labels))
	foreach ($tasks_labels as $task_id => $task_label)
		$main_content .= '
		<input type="hidden" name="tasks_labels[' . $task_id . ']" value="' . $task_label . '" />';

$main_content .= '
	</form>
</div>';


if ($hide_setup) {
	$main_content .= '<script>
		var html = \'<li class="continue" title="Save and Rebuild Layers"><a onclick="return continueSetup();"><i class="icon continue"></i> Save and Rebuild Layers</a></li>\';
		$(".taskflowchart .workflow_menu li.save").after(html);
		$(".top_bar li.save").attr("title", "Save and Rebuild Layers").children("a").attr("onClick", "continueSetup();").html(\'<i class="icon continue"></i> Save and Rebuild Layers\');
	</script>
	<style> #setup .buttons {display:none;} </style>';
}

$continue_function = "continueSetup()";
$back_function = "document.location='?step=3&iframe=$is_inside_of_iframe'";
$back_label = "Go to beginner settings";
?>
