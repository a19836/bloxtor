<?php
include_once get_lib("org.phpframework.util.web.html.HtmlFormHandler");

$form_settings = array(
	"with_form" => 1,
	"form_id" => "",
	"form_method" => "post",
	"form_class" => "with_top_bar_section",
	"form_on_submit" => "",
	"form_action" => "",
	"extra_attributes" => array(
		array("name" => "autocomplete", "value" => "new-password")
	),
	"form_containers" => array(
		0 => array(
			"container" => array(
				"class" => "form_fields",
				"previous_html" => "",
				"next_html" => "",
				"elements" => array(
					0 => array(
						"field" => array(
							"class" => "form_field form_field_db db_type",
							"label" => array(
								"value" => "DataBase Type: ",
							),
							"input" => array(
								"type" => "select",
								"name" => "data[type]",
								"value" => (!empty($db_settings_variables["type"]) ? '$' . $db_settings_variables["type"] : "#type#"),
								"options" => isset($available_types_options) ? $available_types_options : null,
								"next_html" => (!empty($db_settings_variables["type"]) ? '<span>...with the global value: "#type#"</span>' : ''),
								"extra_attributes" => array(
									array("name" => "onChange", "value" => "onChangeDBType(this)")
								),
							)
						)
					),
					1 => array(
						"field" => array(
							"class" => "form_field form_field_db db_extension",
							"label" => array(
								"value" => "Connection Type: ",
							),
							"input" => array(
								"type" => "select",
								"name" => "data[extension]",
								"value" => (!empty($db_settings_variables["extension"]) ? '$' . $db_settings_variables["extension"] : "#extension#"),
								"options" => isset($available_extensions_options) ? $available_extensions_options : null,
								"next_html" => (!empty($db_settings_variables["extension"]) ? '<span>...with the global value: "#extension#"</span>' : ''),
								"extra_attributes" => array(
									array("name" => "onChange", "value" => "onChangeDBExtension(this)")
								),
							)
						)
					),
					2 => array(
						"field" => array(
							"class" => "form_field form_field_db db_host",
							"label" => array(
								"value" => "Host: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[host]",
								"value" => (!empty($db_settings_variables["host"]) ? '$' . $db_settings_variables["host"] : "#host#"),
								"next_html" => (!empty($db_settings_variables["host"]) ? '<span>...with the global value: "#host#"</span>' : ''),
							)
						)
					),
					3 => array(
						"field" => array(
							"class" => "form_field form_field_db db_name",
							"label" => array(
								"value" => "DataBase name: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[db_name]",
								"value" => (!empty($db_settings_variables["db_name"]) ? '$' . $db_settings_variables["db_name"] : "#db_name#"),
								"next_html" => (!empty($db_settings_variables["db_name"]) ? '<span>...with the global value: "#db_name#"</span>' : ''),
							)
						)
					),
					4 => array(
						"field" => array(
							"class" => "form_field form_field_db db_username",
							"label" => array(
								"value" => "Username: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[username]",
								"value" => (!empty($db_settings_variables["username"]) ? '$' . $db_settings_variables["username"] : "#username#"),
								"next_html" => (!empty($db_settings_variables["username"]) ? '<span>...with the global value: "#username#"</span>' : ''),
								"extra_attributes" => array(
									array("name" => "autocomplete", "value" => "new-password")
								),
							)
						)
					),
					5 => array(
						"field" => array(
							"class" => "form_field form_field_db db_password",
							"label" => array(
								"value" => "Password: ",
							),
							"input" => array(
								"type" => !empty($db_settings_variables["password"]) ? "text" : "password",
								"name" => "data[password]",
								"value" => (!empty($db_settings_variables["password"]) ? '$' . $db_settings_variables["password"] : "#password#"),
								"next_html" => '<span class="icon switch toggle_password" onClick="toggleDBPasswordField(this)" title="Toggle password and text field">Toggle password field</span>' . (!empty($db_settings_variables["password"]) ? '<span>...with the global value: "***"</span>' : ''),
								"extra_attributes" => array(
									array("name" => "autocomplete", "value" => "new-password")
								),
							)
						)
					),
					6 => array(
						"field" => array(
							"class" => "form_field form_field_db show_advanced_db_options",
							"input" => array(
								"type" => "label",
								"value" => '<a href="javascript:void(0);" onClick="toggleDBAdvancedFields(this)">Show Advanced Options</a>',
							)
						)
					),
					7 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_port",
							"label" => array(
								"value" => "Port: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[port]",
								"value" => "#port#",
								"value" => (!empty($db_settings_variables["port"]) ? '$' . $db_settings_variables["port"] : "#port#"),
								"next_html" => (!empty($db_settings_variables["port"]) ? '<span>...with the global value: "#port#"</span>' : ''),
							)
						)
					),
					8 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_persistent",
							"label" => array(
								"value" => "Persistent: ",
							),
							"input" => array(
								"type" => !empty($db_settings_variables["persistent"]) ? "text" : "checkbox",
								"name" => "data[persistent]",
								"value" => (!empty($db_settings_variables["persistent"]) ? '$' . $db_settings_variables["persistent"] : "#persistent#"),
								"next_html" => (!empty($db_settings_variables["persistent"]) ? '<span>...with the global value: "#persistent#"</span>' : ''),
							)
						)
					),
					9 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_new_link",
							"label" => array(
								"value" => "New Link: ",
							),
							"input" => array(
								"type" => !empty($db_settings_variables["new_link"]) ? "text" : "checkbox",
								"name" => "data[new_link]",
								"value" => (!empty($db_settings_variables["new_link"]) ? '$' . $db_settings_variables["new_link"] : "#new_link#"),
								"next_html" => (!empty($db_settings_variables["new_link"]) ? '<span>...with the global value: "#new_link#"</span>' : ''),
							)
						)
					),
					10 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_encoding",
							"label" => array(
								"value" => "Encoding: ",
							),
							"input" => array(
								"type" => "select",
								"name" => "data[encoding]",
								"value" => (!empty($db_settings_variables["encoding"]) ? '$' . $db_settings_variables["encoding"] : "#encoding#"),
								"options" => isset($available_encodings_options) ? $available_encodings_options : null,
								"next_html" => (!empty($db_settings_variables["encoding"]) ? '<span>...with the global value: "#encoding#"</span>' : ''),
							)
						)
					),
					11 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_schema",
							"label" => array(
								"value" => "Schema: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[schema]",
								"value" => (!empty($db_settings_variables["schema"]) ? '$' . $db_settings_variables["schema"] : "#schema#"),
								"next_html" => (!empty($db_settings_variables["schema"]) ? '<span>...with the global value: "#schema#"</span>' : ''),
							)
						)
					),
					12 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_odbc_data_source",
							"label" => array(
								"value" => "ODBC Data Source: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[odbc_data_source]",
								"value" => (!empty($db_settings_variables["odbc_data_source"]) ? '$' . $db_settings_variables["odbc_data_source"] : "#odbc_data_source#"),
								"next_html" => (!empty($db_settings_variables["odbc_data_source"]) ? '<span>...with the global value: "#odbc_data_source#"</span>' : ''),
								"title" => "A Data Source Name (DSN) is the logical name that is used by Open Database Connectivity (ODBC) to refer to the driver and other information that is required to access data from a data source. Data sources are usually defined in /etc/odbc.ini",
							)
						)
					),
					13 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_odbc_driver",
							"label" => array(
								"value" => "ODBC Driver: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[odbc_driver]",
								"value" => (!empty($db_settings_variables["odbc_driver"]) ? '$' . $db_settings_variables["odbc_driver"] : "#odbc_driver#"),
								"next_html" => (!empty($db_settings_variables["odbc_driver"]) ? '<span>...with the global value: "#odbc_driver#"</span>' : ''),
								"title" => "Is the file path of the installed driver that connects to a data-base from ODBC protocol. Or the name of an ODBC instance that was defined in /etc/odbcinst.ini",
							)
						)
					),
					14 => array(
						"field" => array(
							"class" => "form_field form_field_db form_field_db_advanced db_extra_dsn",
							"label" => array(
								"value" => "Extra DSN: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[extra_dsn]",
								"value" => (!empty($db_settings_variables["extra_dsn"]) ? '$' . $db_settings_variables["extra_dsn"] : "#extra_dsn#"),
								"next_html" => (!empty($db_settings_variables["extra_dsn"]) ? '<span>...with the global value: "#extra_dsn#"</span>' : ''),
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
								"name" => "save",
								"value" => "Save",
								"extra_attributes" => array(
									0 => array(
										"name" => "confirmation",
										"value" => true
									),
									1 => array(
										"name" => "confirmation_message",
										"value" => "By changing the DB settings, you can have some issues in the future, because when you did the installation, there were some tables that were created automatically, which will not be created in this new DB.\n\nDo you still wish to continue?"
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

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Icons CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/db/set_db_settings.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/db/set_db_settings.js"></script>

<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/db_driver_connection_props.js"></script>

<script>
	var drivers_encodings = ' . (isset($drivers_encodings) ? json_encode($drivers_encodings) : "null") . ';
	var drivers_extensions = ' . (isset($drivers_extensions) ? json_encode($drivers_extensions) : "null") . ';
	var drivers_ignore_connection_options = ' . (isset($drivers_ignore_connection_options) ? json_encode($drivers_ignore_connection_options) : "null") . ';
	var drivers_ignore_connection_options_by_extension = ' . (isset($drivers_ignore_connection_options_by_extension) ? json_encode($drivers_ignore_connection_options_by_extension) : "null") . ';
</script>';

$main_content = '<div class="db_settings">
	<div class="top_bar">
		<header>
			<div class="title">DataBase Settings for "' . $bean_name . '"</div>
			<ul>
				<li class="save" data-title="Save"><a onClick="submitForm(this)"><i class="icon save"></i> Save</a></li>
			</ul>
		</header>
	</div>';
$main_content .= !empty($error) ? "<h2>$error</h2>" : HtmlFormHandler::createHtmlForm($form_settings, isset($db_settings) ? $db_settings : null);
$main_content .= '</div>';
$main_content .= '<script>
	var form_fields = $(".db_settings .form_fields");
	onChangeDBType( form_fields.find(".db_type select")[0] );
	form_fields.children(".form_field_db_advanced").hide();
</script>';
?>
