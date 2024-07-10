<?php
include_once get_lib("org.phpframework.util.web.html.HtmlFormHandler");

$next_html = '<a class="icon delete" onClick="$(this.parentNode).remove();">REMOVE</a>';

$form_settings = array(
	"with_form" => 1,
	"form_id" => "",
	"form_method" => "post",
	"form_class" => "",
	"form_on_submit" => "",
	"form_action" => "",
	"form_containers" => array(
		0 => array(
			"container" => array(
				"class" => "form_fields",
				"previous_html" => "",
				"next_html" => "",
				"elements" => array(
					0 => array(
						"field" => array(
							"class" => "form_field var_name",
							"label" => array(
								"value" => "Default timezone: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[default_timezone]",
								"value" => "#default_timezone#",
								"next_html" => $next_html
							)
						)
					),
					1 => array(
						"field" => array(
							"class" => "form_field var_name",
							"label" => array(
								"value" => "Die when throw exception: ",
							),
							"input" => array(
								"type" => "select",
								"name" => "data[die_when_throw_exception]",
								"value" => "#die_when_throw_exception#",
								"options" => array(
									array("value" => "true", "label" => "YES"), 
									array("value" => "false", "label" => "NO")
								),
								"next_html" => $next_html
							)
						)
					),
					2 => array(
						"field" => array(
							"class" => "form_field var_name",
							"label" => array(
								"value" => "Log level: ",
							),
							"input" => array(
								"type" => "select",
								"name" => "data[log_level]",
								"value" => "#log_level#",
								"options" => array(
									array("value" => 0, "label" => "NONE"), 
									array("value" => 1, "label" => "EXCEPTION"), 
									array("value" => 2, "label" => "EXCEPTION+ERROR"), 
									array("value" => 3, "label" => "EXCEPTION+ERROR+INFO"), 
									array("value" => 4, "label" => "EXCEPTION+ERROR+INFO+DEBUG")
								),
								"next_html" => $next_html
							)
						)
					),
					3 => array(
						"field" => array(
							"class" => "form_field var_name",
							"label" => array(
								"value" => "Log echo active: ",
							),
							"input" => array(
								"type" => "select",
								"name" => "data[log_echo_active]",
								"value" => "#log_echo_active#",
								"options" => array(
									array("value" => "true", "label" => "YES"), 
									array("value" => "false", "label" => "NO")
								),
								"next_html" => $next_html
							)
						)
					),
					4 => array(
						"field" => array(
							"class" => "form_field var_name",
							"label" => array(
								"value" => "Log file path: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[log_file_path]",
								"value" => "#log_file_path#",
								"next_html" => $next_html
							)
						)
					),
					5 => array(
						"field" => array(
							"class" => "form_field var_name",
							"label" => array(
								"value" => "Temporary folder path: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[tmp_path]",
								"value" => "#tmp_path#",
								"next_html" => $next_html
							)
						)
					)
				)
			)
		),
		1 => array(
			"container" => array(
				"class" => "buttons",
				"elements" => array(
					0 => array(
						"field" => array(
							"class" => "save",
							"input" => array(
								"type" => "submit",
								"name" => "",
								"value" => "save",
							)
						)
					)
				)
			)
		)
	)
);

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" />

<!-- Add Layout CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" />

<!-- Add Layout CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layer/list_global_settings.css" type="text/css" charset="utf-8" />

<script>
function onSubmitButtonClick(elm) {
	elm = $(elm);
	
	var on_click = elm.attr("onClick");
	elm.addClass("loading").removeAttr("onClick");
	
	elm.parent().closest(".top_bar").parent().find(".global_settings form .buttons .save input[type=submit]").click();
}
</script>';

$main_content = '
<div class="top_bar' . ($popup ? " in_popup" : "") . '">
	<header>
		<div class="title">Global Settings</div>
		<ul>
			<li class="save" data-title="Save"><a onclick="onSubmitButtonClick(this);"><i class="icon continue"></i> Save</a></li>
		</ul>
	</header>
</div>

<div class="global_settings' . ($deployment ? " from_deployment" : "") . '">';
$main_content .= HtmlFormHandler::createHtmlForm($form_settings, $vars);
$main_content .= '</div>';
?>
