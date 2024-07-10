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
							"class" => "form_field" . (!$user_id ? " hidden" : ""),
							"label" => array(
								"value" => "User Id: ",
							),
							"input" => array(
								"type" => "label",
								"value" => "#user_id#",
							)
						)
					),
					1 => array(
						"field" => array(
							"class" => "form_field",
							"label" => array(
								"value" => "Username: ",
							),
							"input" => array(
								"type" => $is_own_user || $is_user_editable ? "text" : "label",
								"name" => "user_data[username]",
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
					2 => array(
						"field" => array(
							"class" => "form_field" . ($is_own_user || $is_user_editable ? "" : " hidden"),
							"label" => array(
								"value" => "Password: ",
							),
							"input" => array(
								"type" => $is_own_user || $is_user_editable ? "password" : "label",
								"name" => "user_data[password]",
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
							"class" => "form_field",
							"label" => array(
								"value" => "Name: ",
							),
							"input" => array(
								"type" => $is_own_user || $is_user_editable ? "text" : "label",
								"name" => "user_data[name]",
								"value" => "#name#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "Name cannot be undefined!"
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
							"class" => "submit_button" . ($is_own_user || $is_user_editable ? "" : " hidden"),
							"input" => array(
								"type" => "submit",
								"name" => "save",
								"value" => "Save",
								"class" => "save",
							)
						)
					),
					1 => array(
						"field" => array(
							"class" => "submit_button" . ($user_id && $is_user_deletable ? "" : " hidden"),
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
										"value" => "Do you wish to remove this user?"
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
<script>
function deleteUser(elm, input_class) {
	$(elm).parent().closest(".top_bar").parent().find("form").find("input").removeAttr("allownull");
	
	submitForm(elm, input_class);
}
</script>
<div id="menu">' . UserAuthenticationUIHandler::getMenu($UserAuthenticationHandler, $project_url_prefix, $entity) . '</div>
<div id="content">
	<div class="edit_user">
		<div class="top_bar">
			<header>
				<div class="title">' . ($user_id ? 'Edit' : 'Add') . ' User</div>
				<ul>
					<li class="delete" data-title="Delete"><a onClick="deleteUser(this, \'delete\')"><i class="icon delete"></i> Delete</a></li>
					<li class="save" data-title="Save"><a onClick="submitForm(this, \'save\')"><i class="icon save"></i> Save</a></li>
				</ul>
			</header>
		</div>';
		
if ($is_own_user || $is_user_editable || $is_user_deletable)
	$main_content .= HtmlFormHandler::createHtmlForm($form_settings, $user_data);
else 
	$main_content .= '<div class="error">User undefined!</div>';

$main_content .= '</div>
</div>';
?>
