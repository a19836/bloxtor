<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

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
';

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
								"value" => "User: ",
							),
							"input" => array(
								"type" => ($user_id && $user_type_id ? "label" : "select"),
								"name" => "user_user_type_data[user_id]",
								"value" => "#user_id#",
								"options" => $users_options,
								"available_values" => $available_users,
							)
						)
					),
					1 => array(
						"field" => array(
							"class" => "form_field",
							"label" => array(
								"value" => "User Type: ",
							),
							"input" => array(
								"type" => ($user_id && $user_type_id ? "label" : "select"),
								"name" => "user_user_type_data[user_type_id]",
								"value" => "#user_type_id#",
								"options" => $user_types_options,
								"available_values" => $available_user_types,
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
							"class" => "submit_button" . ($user_id && $user_type_id ? " hidden" : ""),
							"input" => array(
								"type" => "submit",
								"name" => "save",
								"value" => "Add",
								"class" => "save",
							)
						)
					),
					1 => array(
						"field" => array(
							"class" => "submit_button" . ($user_id && $user_type_id ? "" : " hidden"),
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
										"name" => "confirmation_message",
										"value" => "Do you wish to remove this user user type?"
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
	<div class="edit_user_user_type">
		<div class="top_bar">
			<header>
				<div class="title">' . ($user_id && $user_type_id ? 'Edit' : 'Add') . ' User USer Type</div>
				<ul>
					<li class="delete" data-title="Delete"><a onClick="submitForm(this, \'delete\')"><i class="icon delete"></i> Delete</a></li>
					<li class="save" data-title="Save"><a onClick="submitForm(this, \'save\')"><i class="icon save"></i> Save</a></li>
				</ul>
			</header>
		</div>';
$main_content .= HtmlFormHandler::createHtmlForm($form_settings, $user_user_type_data);
$main_content .= '</div>
</div>';
?>
