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
					2 => array(
						"field" => array(
							"class" => "form_field form_field_key",
							"label" => array(
								"value" => "Permission Table Key: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "permission_table_key",
								"value" => "#permission_table_key#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "Permission Table Key cannot be undefined!"
									)
								),
							)
						)
					),
					3 => array(
						"field" => array(
							"class" => "form_field form_field_key",
							"label" => array(
								"value" => "User Table Key: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "user_table_key",
								"value" => "#user_table_key#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "User Table Key cannot be undefined!"
									)
								),
							)
						)
					),
					4 => array(
						"field" => array(
							"class" => "form_field form_field_key",
							"label" => array(
								"value" => "User Type Table Key: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "user_type_table_key",
								"value" => "#user_type_table_key#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "User Type Table Key cannot be undefined!"
									)
								),
							)
						)
					),
					5 => array(
						"field" => array(
							"class" => "form_field form_field_key",
							"label" => array(
								"value" => "Object Type Table Key: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "object_type_table_key",
								"value" => "#object_type_table_key#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "Object Type Table Key cannot be undefined!"
									)
								),
							)
						)
					),
					6 => array(
						"field" => array(
							"class" => "form_field form_field_key",
							"label" => array(
								"value" => "User Type Permission Table Key: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "user_type_permission_table_key",
								"value" => "#user_type_permission_table_key#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "User Type Permission Table Key cannot be undefined!"
									)
								),
							)
						)
					),
					7 => array(
						"field" => array(
							"class" => "form_field form_field_key",
							"label" => array(
								"value" => "User User Type Table Key: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "user_user_type_table_key",
								"value" => "#user_user_type_table_key#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "User User Type Table Key cannot be undefined!"
									)
								),
							)
						)
					),
					8 => array(
						"field" => array(
							"class" => "form_field form_field_key",
							"label" => array(
								"value" => "Login Control Table Key: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "login_control_table_key",
								"value" => "#login_control_table_key#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "Login Control Table Key cannot be undefined!"
									)
								),
							)
						)
					),
					9 => array(
						"field" => array(
							"class" => "form_field form_field_key",
							"label" => array(
								"value" => "User Stats Table Key: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "user_stats_table_key",
								"value" => "#user_stats_table_key#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "User Stats Table Key cannot be undefined!"
									)
								),
							)
						)
					),
					10 => array(
						"field" => array(
							"class" => "form_field form_field_key",
							"label" => array(
								"value" => "Layout Type Table Key: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "layout_type_table_key",
								"value" => "#layout_type_table_key#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "Layout Type Table Key cannot be undefined!"
									)
								),
							)
						)
					),
					11 => array(
						"field" => array(
							"class" => "form_field form_field_key",
							"label" => array(
								"value" => "Layout Type Permission Table Key: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "layout_type_permission_table_key",
								"value" => "#layout_type_permission_table_key#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "Layout Type Permission Table Key cannot be undefined!"
									)
								),
							)
						)
					),
					12 => array(
						"field" => array(
							"class" => "form_field form_field_key",
							"label" => array(
								"value" => "Reserved DB Table Name Table Key: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "reserved_db_table_name_table_key",
								"value" => "#reserved_db_table_name_table_key#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "Reserved DB Table Name Table Key cannot be undefined!"
									)
								),
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
								"value" => "Change DB Keys",
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
	<div class="change_db_keys">
		<div class="top_bar">
			<header>
				<div class="title">Change DB Keys Automatically</div>
				<ul>
					<li class="save" data-title="Change DB Keys"><a onClick="$(this).addClass(\'loading\'); submitForm(this)"><i class="icon save"></i> Save</a></li>
				</ul>
			</header>
		</div>';

if ($is_local_db) {
	$main_content .= '<div class="label">In order to proceed, please confirm the current DB keys and your CMS login:</div>';
	$main_content .= HtmlFormHandler::createHtmlForm($form_settings, $data);
	$main_content .= '<div class="info">By clicking in the "save" button, the system will auto generate new keys for this Users-Authentication DB.<br/>You should only execute this action to change security...</div>';
}
else {
	$main_content .= '<div class="label">You can only change the DB\'s keys if the Authentication DB is a local DB.</div>';
}

$main_content .= '
	</div>
</div>';
?>
