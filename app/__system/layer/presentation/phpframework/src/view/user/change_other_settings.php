<?php
include_once get_lib("org.phpframework.util.web.html.HtmlFormHandler");
include $EVC->getUtilPath("UserAuthenticationUIHandler");

$head = '
<!-- Add MD5 JS File -->
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquery/js/jquery.md5.js"></script>

<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/user/user.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/user/user.js"></script>';

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
							"class" => "form_field form_field_login",
							"label" => array(
								"value" => "Username: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "username",
								"value" => "#username#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "Username cannot be undefined!"
									)
								),
							)
						)
					),
					1 => array(
						"field" => array(
							"class" => "form_field form_field_login password",
							"label" => array(
								"value" => "Password: ",
							),
							"input" => array(
								"type" => "password",
								"name" => "password",
								"value" => "#password#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "Password cannot be undefined!"
									)
								),
							)
						)
					),
					3 => array(
						"field" => array(
							"class" => "form_field form_field_key",
							"label" => array(
								"value" => "OpenAI Key: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "openai_key",
								"value" => "#openai_key#",
								"next_html" => '<div class="info">To take advantage of the Artificial Intelligence features, please add your OpenAI key here.</div>'
							)
						)
					),
				)
			)
		),
		1 => array(
			"container" => array(
				"class" => "buttons",
				"elements" => array(
					0 => array(
						"field" => array(
							"class" => "submit_button",
							"input" => array(
								"type" => "submit",
								"name" => "change",
								"value" => "Save",
							)
						)
					),
				)
			)
		)
	)
);

$main_content = '
<div id="menu">' . UserAuthenticationUIHandler::getMenu($UserAuthenticationHandler, $project_url_prefix, $entity) . '</div>
<div id="content">
	<div class="change_other_settings">
		<div class="top_bar">
			<header>
				<div class="title">Change Other Settings</div>
				<ul>
					<li class="save" data-title="Save"><a onClick="$(this).addClass(\'loading\'); submitForm(this);"><i class="icon save"></i> Save</a></li>
				</ul>
			</header>
		</div>
		
		<div class="label">This panel is to change some other settings in the Admin Tool:</div>';
$main_content .= HtmlFormHandler::createHtmlForm($form_settings, $data);
$main_content .= '
	</div>
</div>';
?>
