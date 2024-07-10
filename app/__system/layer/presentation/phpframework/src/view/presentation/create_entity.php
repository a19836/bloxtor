<?php
include_once $EVC->getUtilPath("HeatMapHandler");

$get_bkp = $_GET;
unset($get_bkp["creation_step"]);
$query_string = http_build_query($get_bkp);

$edit_entity_url = $project_url_prefix . "phpframework/presentation/edit_entity?bean_name=$bean_name&bean_file_name=$bean_file_name&filter_by_layout=$filter_by_layout&path=$path";

$top_bar_title = "Create new Page";

if (!$creation_step) { //show 2 cards: one with empty page or browse pages
	$main_content = '
		<div class="top_bar create_entity_top_bar popup_with_iframe_left_popup_close popup_with_iframe_popup_close_button' . ($popup ? ' in_popup' : '') . '">
			<header>
				<div class="title" title="' . $top_bar_title . '">' . $top_bar_title . '</div>
				<ul>
					<li class="cancel button" data-title="Close"><a class="active" href="javascript:void(0)" onClick="cancel()">Close</a></li>
				</ul>
			</header>
		</div>
		<div class="title">How do you want to build your page?</div>
		<div class="page_type">
			<div class="title">For citizen-developers</div>
			<div class="description">Create your page from scratch, designing your custom layouts or using our templates, all done effortlessly with drag and drop.</div>
			<button onClick="$(this).parent().addClass(\'selected\');goToUrl(\'?' . $query_string . '&creation_step=2\')">User-defined Page</button>
		</div>
		<div class="page_type">
			<div class="title">For no-coders</div>
			<div class="description">Boost your development pace with a ready-made, customizable page, requiring just a few settings to be configured.</div>
			<button onClick="$(this).parent().addClass(\'selected\');goToUrl(\'?' . $query_string . '&creation_step=1\')">Browse Pre-built Pages</button>
		</div>';
}
else if ($creation_step == 1) { //list pages from store
	//call install_page
	include_once $EVC->getViewPath("presentation/install_page");
	
	/*if ($_POST && $status)
		$main_content = '
		<div class="top_bar create_entity_top_bar popup_with_iframe_left_popup_close popup_with_iframe_popup_close_button' . ($popup ? ' in_popup' : '') . '">
			<header>
				<div class="title" title="' . $top_bar_title . '">' . $top_bar_title . '</div>
				<ul>
					<li class="continue button" data-title="Continue"><a class="active" href="?' . $query_string . '&creation_step=2">Continue</a></li>
					<li class="back button" data-title="Back to choose a new pre-built page"><a href="?' . $query_string . '&creation_step=1">Back</a></li>
					<li class="cancel button" data-title="Close"><a class="active" href="javascript:void(0)" onClick="cancel()">Close</a></li>
				</ul>
			</header>
		</div>
		' . $main_content;
	else*/
		$main_content = '
		<div class="top_bar create_entity_top_bar popup_with_iframe_left_popup_close popup_with_iframe_popup_close_button' . ($popup ? ' in_popup' : '') . '">
			<header>
				<div class="title" title="' . $top_bar_title . '">' . $top_bar_title . '</div>
				<ul>
					<li class="continue button" data-title="Continue to next step after selecting a pre-built page"><a' . ($_POST && $status ? ' class="active"' : '') . ' href="javascript:void(0)" onClick="choosePage(this, \'?' . $query_string . '&creation_step=1\')">Continue</a></li>
					<li class="back button" data-title="Back to choose a new pre-built page"><a class="active" href="?' . $query_string . '&creation_step=0">Back</a></li>
					<li class="cancel button" data-title="Close"><a class="active" href="javascript:void(0)" onClick="cancel()">Close</a></li>
				</ul>
			</header>
		</div>
		<div class="title">Please choose a pre-built page you wish to create:</div>
		<div class="sub_title"><a href="javascript:void(0)" onClick="toggleLocalUpload(this)">Show Advanced Features</a></div>
		' . $main_content . '
		<script>initInstallPages();</script>';
}
else if ($creation_step == 2) { //show success message and call on_success_js_func
	$main_content = '
	<div class="top_bar create_entity_top_bar' . ($popup ? ' in_popup' : '') . '">
		<header>
			<div class="title" title="' . $top_bar_title . '">' . $top_bar_title . '</div>
			<ul>
				<li class="continue button" data-title="Go to the Page Editor"><a class="active" href="javascript:void(0)" onClick="onSucessfullPageCreation()">Continue</a></li>
			</ul>
		</header>
	</div>';
	
	if ($from_step_1) 
		$main_content .= '
		<div class="message">
			<div class="title">Your page was created successfully!</div>
			<div class="sentence_1">Please click the button below to go to the page editor.</div>
			<div class="sentence_2">Happy development...</div>
			<button onClick="onSucessfullPageCreation()">Go to your Page Editor</button>
		</div>';
	else
		$main_content .= '
		<div class="message">
			<div class="title">You are almost done...</div>
			<div class="sentence_1">The next step will open the page editor and choose how you want to design your page.</div>
			<div class="sentence_2">Happy development...</div>
		</div>';
}

$head .= '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/presentation/create_entity.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/presentation/create_entity.js"></script>

<script>
var on_success_js_func_name = "' . $on_success_js_func . '";
var popup = ' . ($popup ? "true" : "false") . ';

var edit_entity_url = \'' . $edit_entity_url . '\';
</script>';
$head .= HeatMapHandler::getHtml($project_url_prefix);

$main_content = '
<div class="create_entity changing_to_step">
	<div class="creation_step creation_step_' . $creation_step . '">
		' . $main_content . '
	</div>
	
	<div class="loading"></div>
</div>';
?>
