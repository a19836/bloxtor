<?php
$head = "";

include $EVC->getViewPath($page);

$head .= '<link rel="stylesheet" href="' . $project_url_prefix . 'css/setup.css" type="text/css" charset="utf-8" />

<script>
function cancelSetup() {';

if ($is_inside_of_iframe) {
	$head .= '	
		if (confirm("Are you sure you wish to cancel?")) {
			var url = window.top.location;
			window.top.location = url;
			//window.parent.location = url;
		}';
}
else {
	$head .= '	
		if (confirm("Are you sure you wish to cancel and exit from this application?")) {
			window.open("", "_self", ""); //bug fix
			window.close();
		
			//If not closed:
			window.location = "' . $project_url_prefix . 'setup";
		}';
}

$head .= '
	return false;
}
</script>';

$main_content_aux = '<div id="setup">';
if (!empty($continue_function) || !empty($back_function)) {
	$main_content_aux .= '<div class="buttons">';
	
	if (!empty($continue_function)) {
		$main_content_aux .= '<input class="ok" type="button" name="continue" value="Continue" onClick="return ' . $continue_function . '" />';
	}
	
	if (!empty($back_function)) {
		$back_label = empty($back_label) ? "Back" : $back_label;
		$main_content_aux .= '<input class="back" type="button" name="back" value="' . $back_label . '" onClick="return ' . $back_function . '" />';
	}
	
	$main_content_aux .= '<input class="cancel" type="button" name="cancel" value="Cancel" onClick="return cancelSetup();" />
	</div>';
}
$main_content_aux .= $main_content . '
</div>';

$main_content = $main_content_aux;
?>
