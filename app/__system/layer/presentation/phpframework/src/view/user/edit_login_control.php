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
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/user/user.js"></script>

<style>
#content form > .buttons {
	display:block !important;
}
#content form > .buttons > .delete_button {
	display:none;
}
</style>';

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
							"class" => "form_field",
							"label" => array(
								"value" => "Username: ",
							),
							"input" => array(
								"type" => "label",
								"value" => "#username#",
							)
						)
					),
					1 => array(
						"field" => array(
							"class" => "form_field",
							"label" => array(
								"value" => "Session id: ",
							),
							"input" => array(
								"type" => "label",
								"value" => "#session_id#",
							)
						)
					),
					2 => array(
						"field" => array(
							"class" => "form_field",
							"label" => array(
								"value" => "Failed Login Attempts: ",
							),
							"input" => array(
								"type" => "label",
								"value" => "#failed_login_attempts#",
							)
						)
					),
					3 => array(
						"field" => array(
							"class" => "form_field",
							"label" => array(
								"value" => "Failed Login Time: ",
							),
							"input" => array(
								"type" => "label",
								"value" => "#failed_login_time#",
							)
						)
					),
					4 => array(
						"field" => array(
							"class" => "form_field",
							"label" => array(
								"value" => "Login Expired Time: ",
							),
							"input" => array(
								"type" => "label",
								"value" => "#login_expired_time#",
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
							"class" => "submit_button reset_button",
							"input" => array(
								"type" => "submit",
								"name" => "reset_failed_login_attempts",
								"value" => "Reset Failed Login Attempts",
								"class" => "save",
							)
						)
					),
					1 => array(
						"field" => array(
							"class" => "submit_button delete_button" . ($username ? "" : " hidden"),
							"input" => array(
								"type" => "submit",
								"name" => "delete",
								"value" => "Delete",
								"class" => "delete",
								"extra_attributes" => array(
									0 => array(
										"name" => "confirmation",
										"value" => true
									),
									1 => array(
										"name" => "confirmationMessage",
										"value" => "Do you wish to remove this username data?"
									)
								),
							)
						)
					)
				)
			)
		)
	)
);

$main_content = '
<div id="menu">' . UserAuthenticationUIHandler::getMenu($UserAuthenticationHandler, $project_url_prefix, $entity) . '</div>
<div id="content">
	<div class="edit_login_control">
		<div class="top_bar">
			<header>
				<div class="title">Login Control</div>
				<ul>
					<li class="delete" data-title="Delete"><a onClick="submitForm(this, \'delete\')"><i class="icon delete"></i> Delete</a></li>
				</ul>
			</header>
		</div>';

if ($username && !empty($login_control_data))
	$main_content .= HtmlFormHandler::createHtmlForm($form_settings, $login_control_data);
else
	$main_content .= '<div class="error">No username data to reset.</div>';

$main_content .= '</div>
</div>';
?>
