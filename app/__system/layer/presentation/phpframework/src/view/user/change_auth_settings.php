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
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/db_driver_connection_props.js"></script>
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/user/user.js"></script>
<script>
	var drivers_encodings = ' . json_encode($drivers_encodings) . ';
	var drivers_extensions = ' . json_encode($drivers_extensions) . ';
	var drivers_ignore_connection_options = ' . json_encode($drivers_ignore_connection_options) . ';
	var drivers_ignore_connection_options_by_extension = ' . json_encode($drivers_ignore_connection_options_by_extension) . ';
	
	function onClickSaveBtn(elm) {
		elm = $(elm);
		elm.hide();
		
		elm.parent().append("Loading...");
	}
</script>';

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
								"value" => "Maximum # of Failed Attempts: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "maximum_failed_attempts",
								"value" => "#maximum_failed_attempts#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationType",
										"value" => "int"
									),
									2 => array(
										"name" => "min",
										"value" => "0"
									),
									3 => array(
										"name" => "validationMessage",
										"value" => "Maximum # of Failed Attempts must be numeric and bigger or equal than 0!"
									)
								),
							)
						)
					),
					1 => array(
						"field" => array(
							"class" => "form_field",
							"label" => array(
								"value" => "User Blocked Expired Time (secs): ",
							),
							"input" => array(
								"type" => "text",
								"name" => "user_blocked_expired_time",
								"value" => "#user_blocked_expired_time#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationType",
										"value" => "int"
									),
									2 => array(
										"name" => "min",
										"value" => "0"
									),
									3 => array(
										"name" => "validationMessage",
										"value" => "User Blocked Expired Time must be numeric and bigger or equal than 0!"
									)
								),
							)
						)
					),
					3 => array(
						"field" => array(
							"class" => "form_field",
							"label" => array(
								"value" => "Login Expired Time (secs): ",
							),
							"input" => array(
								"type" => "text",
								"name" => "login_expired_time",
								"value" => "#login_expired_time#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationType",
										"value" => "int"
									),
									2 => array(
										"name" => "min",
										"value" => "0"
									),
									3 => array(
										"name" => "validationMessage",
										"value" => "Login Expired Time must be numeric and bigger or equal than 0!"
									)
								),
							)
						)
					),
					4 => array(
						"field" => array(
							"class" => "form_field auth_db_path",
							"label" => array(
								"value" => "Auth DB Path: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "auth_db_path",
								"value" => "#auth_db_path#",
								"extra_attributes" => array(
									0 => array(
										"name" => "allowNull",
										"value" => "false"
									),
									1 => array(
										"name" => "validationMessage",
										"value" => "Auth DB Path cannot be empty"
									)
								),
							)
						)
					),
					5 => array(
						"field" => array(
							"class" => "form_field is_local_db",
							"label" => array(
								"value" => "Is Auth DB in Local File Systems: ",
							),
							"input" => array(
								"type" => "select",
								"name" => "is_local_db",
								"value" => "#is_local_db#",
								"options" => $local_and_remote_options,
								"extra_attributes" => array(
									array("name" => "onChange", "value" => "onChangeLocalDBSettings(this)")
								),
							)
						)
					),
					6 => array(
						"field" => array(
							"class" => "remote_db_credentials form_field_db" . ($is_local_db ? " hidden" : ""),
							"input" => array(
								"type" => "label",
								"value" => "Remote DB credentials:",
							)
						)
					),
					7 => array(
						"field" => array(
							"class" => "form_field form_field_db db_type" . ($is_local_db ? " hidden" : ""),
							"label" => array(
								"value" => "DB Driver: ",
							),
							"input" => array(
								"type" => "select",
								"name" => "authentication_db_driver",
								"value" => "#authentication_db_driver#",
								"options" => $available_drivers,
								"extra_attributes" => array(
									array("name" => "onChange", "value" => "onChangeDBType(this)")
								),
							)
						)
					),
					8 => array(
						"field" => array(
							"class" => "form_field form_field_db db_extension" . ($is_local_db ? " hidden" : ""),
							"label" => array(
								"value" => "Connection Type: ",
							),
							"input" => array(
								"type" => "select",
								"name" => "authentication_db_extension",
								"value" => "#authentication_db_extension#",
								"options" => $available_extensions_options,
								"extra_attributes" => array(
									array("name" => "onChange", "value" => "onChangeDBExtension(this)")
								),
							)
						)
					),
					9 => array(
						"field" => array(
							"class" => "form_field form_field_db db_host" . ($is_local_db ? " hidden" : ""),
							"label" => array(
								"value" => "DB Host: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "authentication_db_host",
								"value" => "#authentication_db_host#",
							)
						)
					),
					10 => array(
						"field" => array(
							"class" => "form_field form_field_db db_name" . ($is_local_db ? " hidden" : ""),
							"label" => array(
								"value" => "DB Name: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "authentication_db_name",
								"value" => "#authentication_db_name#",
							)
						)
					),
					11 => array(
						"field" => array(
							"class" => "form_field form_field_db db_username" . ($is_local_db ? " hidden" : ""),
							"label" => array(
								"value" => "DB Username: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "authentication_db_username",
								"value" => "#authentication_db_username#",
							)
						)
					),
					12 => array(
						"field" => array(
							"class" => "form_field form_field_db db_password" . ($is_local_db ? " hidden" : ""),
							"label" => array(
								"value" => "DB Password: ",
							),
							"input" => array(
								"type" => "password",
								"name" => "authentication_db_password",
								"value" => "#authentication_db_password#",
								"next_html" => '<span class="icon switch toggle_password" onclick="toggleDBPasswordField(this)" title="Toggle password and text field"></span>' /*. (!empty($db_settings_variables["password"]) ? '<span>...with the global value: "***"</span>' : '')*/,
							)
						)
					),
					13 => array(
						"field" => array(
							"class" => "form_field form_field_db show_advanced_db_options" . ($is_local_db ? " hidden" : ""),
							"input" => array(
								"type" => "label",
								"value" => '<a href="javascript:void(0);" onClick="toggleDBAdvancedFields(this)">Show Advanced Options</a>',
							)
						)
					),
					14 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_port" . ($is_local_db ? " hidden" : ""),
							"label" => array(
								"value" => "DB Port: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "authentication_db_port",
								"value" => "#authentication_db_port#",
							)
						)
					),
					15 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_persistent" . ($is_local_db ? " hidden" : ""),
							"label" => array(
								"value" => "DB Persistent: ",
							),
							"input" => array(
								"type" => "checkbox",
								"name" => "authentication_db_persistent",
								"value" => "#authentication_db_persistent#",
							)
						)
					),
					16 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_new_link" . ($is_local_db ? " hidden" : ""),
							"label" => array(
								"value" => "DB New Link: ",
							),
							"input" => array(
								"type" => "checkbox",
								"name" => "authentication_db_new_link",
								"value" => "#authentication_db_new_link#",
							)
						)
					),
					17 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_encoding" . ($is_local_db ? " hidden" : ""),
							"label" => array(
								"value" => "DB Encoding: ",
							),
							"input" => array(
								"type" => "select",
								"name" => "authentication_db_encoding",
								"value" => "#authentication_db_encoding#",
								"options" => $available_encodings_options,
							)
						)
					),
					18 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_schema" . ($is_local_db ? " hidden" : ""),
							"label" => array(
								"value" => "DB Schema: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "authentication_db_schema",
								"value" => "#authentication_db_schema#",
							)
						)
					),
					19 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_odbc_data_source" . ($is_local_db ? " hidden" : ""),
							"label" => array(
								"value" => "ODBC Data Source: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "authentication_db_odbc_data_source",
								"value" => "#authentication_db_odbc_data_source#",
								"title" => "A Data Source Name (DSN) is the logical name that is used by Open Database Connectivity (ODBC) to refer to the driver and other information that is required to access data from a data source. Data sources are usually defined in /etc/odbc.ini",
							)
						)
					),
					20 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_odbc_driver" . ($is_local_db ? " hidden" : ""),
							"label" => array(
								"value" => "ODBC Driver: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "authentication_db_odbc_driver",
								"value" => "#authentication_db_odbc_driver#",
								"title" => "Is the file path of the installed driver that connects to a data-base from ODBC protocol. Or the name of an ODBC instance that was defined in /etc/odbcinst.ini",
							)
						)
					),
					21 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_extra_dsn" . ($is_local_db ? " hidden" : ""),
							"label" => array(
								"value" => "Extra DSN: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "authentication_db_extra_dsn",
								"value" => "#authentication_db_extra_dsn#",
								"title" => "Other DSN attributes. Each attribute must be splitted by comma.",
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
							"class" => "submit_button",
							"input" => array(
								"type" => "submit",
								"name" => "change",
								"value" => "Save",
								"extra_attributes" => array(
									array("name" => "onClick", "value" => 'onClickSaveBtn(this);')
								)
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
	<div class="change_auth_settings">
		<div class="top_bar">
			<header>
				<div class="title">Change Auth Settings</div>
				<ul>
					<li class="save" data-title="Save"><a onClick="$(this).addClass(\'loading\'); submitForm(this);"><i class="icon save"></i> Save</a></li>
				</ul>
			</header>
		</div>
		
		<div class="label">This panel is to change the authentication\'s settings in the Admin Tool:</div>';
$main_content .= HtmlFormHandler::createHtmlForm($form_settings, $data);

if (!$is_local_db)
	$main_content .= '
		<script>
			var form_fields = $(".change_auth_settings .form_fields");
			onChangeDBType( form_fields.find(".db_type select")[0] );
			form_fields.children(".form_field_db_advanced").hide();
		</script>';

$main_content .= '
	</div>
</div>';
?>
