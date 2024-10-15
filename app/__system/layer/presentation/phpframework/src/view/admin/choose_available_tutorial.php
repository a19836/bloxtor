<?php
$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Filemanager CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/file_manager.css" type="text/css" charset="utf-8" />

<!-- Icons CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS and JS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/layout.js"></script>

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/choose_available_tutorial.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/choose_available_tutorial.js"></script>

<script>
var is_popup = ' . ($popup ? 1 : 0) . ';
</script>';

$main_content = '<div class="choose_available_tutorial ' . ($popup ? " in_popup" : "") . '">
	<div class="title' . ($popup ? " inside_popup_title" : "") . '">Video Tutorials</div>
	<div class="toggle_advanced_videos"><a href="javascript:void(0)" onClick="toggleAdvancedTutorials(this);">Show Advanced Videos</a></div>
	<ul class="simple_tutorials">';

foreach ($simple_tutorials as $tutorial)
	$main_content .= getTutorialHtml($tutorial);
		
$main_content .= '<li class="next"><a href="javascript:void(0)" onClick="toggleAdvancedTutorials(this);">Next you should watch the videos from the Advanced Tutorials</a>.</li>
	</ul>
	<ul class="advanced_tutorials">';

foreach ($advanced_tutorials as $tutorial)
	$main_content .= getTutorialHtml($tutorial);

$main_content .= '
	</ul>
	
	<div class="myfancypopup with_title show_video_popup">
		<div class="title"></div>
		<div class="content">
			<div class="video">
				<iframe width="560" height="315" title="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
			</div>
			
			<div class="details">
				<img class="image" alt="Card image cap" onError="$(this).hide()">
				<div class="description"></div>
			</div>
		</div>
	</div>
</div>';

function getTutorialHtml($tutorial) {
	$html = '';
	
	if (!empty($tutorial["video"]) || !empty($tutorial["items"])) {
		$attrs = '';
		$collapse_icon = '';
		//$tutorial["image"] = "http://jplpinto.localhost/__system/img/logo_full_white.svg";
		
		$tutorial_video = isset($tutorial["video"]) ? $tutorial["video"] : null;
		$tutorial_image = isset($tutorial["image"]) ? $tutorial["image"] : null;
		$tutorial_items = isset($tutorial["items"]) ? $tutorial["items"] : null;
		$tutorial_title = isset($tutorial["title"]) ? $tutorial["title"] : null;
		$tutorial_description = isset($tutorial["description"]) ? $tutorial["description"] : null;
		
		if ($tutorial_items) {
			$attrs = 'onClick="toggleSubTutorials(this)"';
			$collapse_icon = '<span class="icon dropdown_arrow"></span>';
		}
		else
			$attrs = 'onClick="openVideoPopup(this)" video_url="' . $tutorial_video . '" image_url="' . $tutorial_image . '"';
		
		$html = '<li' . ($tutorial_items ? ' class="with_sub_tutorials"' : '') . '>
					<div class="tutorial_header" ' . $attrs . '>
						<div class="tutorial_title"' . ($tutorial_description ? ' title="' . str_replace('"', '&quot;', strip_tags($tutorial_description)) . '"' : '') . '><span class="icon video"></span>' . $tutorial_title . $collapse_icon . '</div>
						' . ($tutorial_description ? '<div class="tutorial_description">' . $tutorial_description . '</div>' : '') . '
					</div>';
		
		if ($tutorial_items) {
			$html .= '<ul class="sub_tutorials">';
			
			foreach ($tutorial_items as $sub_tutorial)
				$html .= getTutorialHtml($sub_tutorial);
			
			$html .= '</ul>';
		}
		
		$html .= '</li>';
	}
	
	return $html;
}

/*foreach ($advanced_tutorials as $tutorial) {
	$main_content .= '
	<div class="card shadow ' . ($tutorial["items"] && !$tutorial["video"] ? "border_bottom" : "") . '">
		<div class="card_header">';
	
	if ($tutorial["image"])
		$main_content .= '<img class="card_img_top" src="' . $tutorial["image"] . '" alt="Card image cap" onError="$(this).parent().remove()">';
	
	$main_content .= '</div>
		<div class="card_body">
			<p class="card_title mb-0">' . $tutorial["title"] . '</p>
			' . ($tutorial["description"] ? '<p class="card_description">' . $tutorial["description"] . '</p>' : '') . '
		</div>';
	
	if ($tutorial["items"]) {
		$main_content .= '<ul class="list_group list_group_flush">';
		
		foreach ($tutorial["items"] as $sub_tutorial) {
			$main_content .= '<li class="list_group_item collapsed">
				<div class="list_group_item_header" onClick="$(this).parent().toggleClass(\'collapsed\')">

					' . $sub_tutorial["title"] . '
					<span class="dropdown_toggle"></span>
				</div>
				
				<div class="list_group_item_body">';
			
			if ($sub_tutorial["image"])
				$main_content .= '<img class="card_img" src="' . $sub_tutorial["image"] . '" alt="Card image cap" onError="$(this).remove()">';		
			
			if ($sub_tutorial["description"])
				$main_content .= '<span class="description">' . $sub_tutorial["description"] . '</span>';
			
			if ($sub_tutorial["video"])
				$main_content .= '<a class="video_link" href="javascript:void(0)" onClick="openVideoPopup(this)" video_url="' . $sub_tutorial["video"] . '" image_url="' . $tutorial["image"] . '"><small>Watch video</small></a>';
			
			$main_content .= '
				</div>
			</li>';
		}
		
		$main_content .= '</ul>';
	}
	
	if ($tutorial["video"])
		$main_content .= '
		<div class="card_footer">
			<a class="video_link" href="javascript:void(0)" onClick="openVideoPopup(this)" video_url="' . $tutorial["video"] . '" image_url="' . $tutorial["image"] . '"><small>Watch video</small></a>
		</div>';
	
	$main_content .= '
	</div>';
}*/
?>
