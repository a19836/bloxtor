<?php
include_once get_lib("org.phpframework.util.web.html.HtmlFormHandler");

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
							"class" => "form_field setup_input form_field_db db_type",
							"label" => array(
								"value" => "DataBase Type: ",
							),
							"input" => array(
								"type" => "select",
								"name" => "data[db_type]",
								"value" => "#db_type#",
								"options" => $available_db_types, 
								"extra_attributes" => array(
									array("name" => "onChange", "value" => "onChangeDBType(this)")
								),
							)
						)
					),
					1 => array(
						"field" => array(
							"class" => "form_field setup_input form_field_db db_extension",
							"label" => array(
								"value" => "Connection Type: ",
							),
							"input" => array(
								"type" => "select",
								"name" => "data[db_extension]",
								"value" => "#db_extension#",
								"options" => $available_extensions_options,
								"extra_attributes" => array(
									array("name" => "onChange", "value" => "onChangeDBExtension(this)")
								),
							)
						)
					),
					2 => array(
						"field" => array(
							"class" => "form_field setup_input form_field_db db_host",
							"label" => array(
								"value" => "Host: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[db_host]",
								"value" => "#db_host#",
							)
						)
					),
					3 => array(
						"field" => array(
							"class" => "form_field setup_input form_field_db db_name",
							"label" => array(
								"value" => "DataBase name: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[db_name]",
								"value" => "#db_name#",
							)
						)
					),
					4 => array(
						"field" => array(
							"class" => "form_field setup_input form_field_db db_username",
							"label" => array(
								"value" => "Username: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[db_username]",
								"value" => "#db_username#",
								"extra_attributes" => array(
									array("name" => "autocomplete", "value" => "new-password")
								),
							)
						)
					),
					5 => array(
						"field" => array(
							"class" => "form_field setup_input form_field_db db_password",
							"label" => array(
								"value" => "Password: ",
							),
							"input" => array(
								"type" => "password",
								"name" => "data[db_password]",
								"value" => "#db_password#",
								"next_html" => '<span class="icon switch toggle_password" onclick="toggleDBPasswordField(this)"></span>' /*. (!empty($db_settings_variables["password"]) ? '<span>...with the global value: "***"</span>' : '')*/,
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
							"class" => "form_field setup_input form_field_db form_field_db_advanced db_port",
							"label" => array(
								"value" => "Port: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[db_port]",
								"value" => "#db_port#",
							)
						)
					),
					8 => array(
						"field" => array(
							"class" => "form_field setup_input form_field_db form_field_db_advanced db_persistent",
							"label" => array(
								"value" => "Persistent: ",
							),
							"input" => array(
								"type" => "checkbox",
								"name" => "data[db_persistent]",
								"value" => "#db_persistent#",
							)
						)
					),
					9 => array(
						"field" => array(
							"class" => "form_field setup_input form_field_db form_field_db_advanced db_new_link",
							"label" => array(
								"value" => "New Link: ",
							),
							"input" => array(
								"type" => "checkbox",
								"name" => "data[db_new_link]",
								"value" => "#db_new_link#",
							)
						)
					),
					10 => array(
						"field" => array(
							"class" => "form_field setup_input form_field_db form_field_db_advanced db_encoding",
							"label" => array(
								"value" => "Encoding: ",
							),
							"input" => array(
								"type" => "select",
								"name" => "data[db_encoding]",
								"value" => "#db_encoding#",
								"options" => $available_encodings_options
							)
						)
					),
					11 => array(
						"field" => array(
							"class" => "form_field setup_input form_field_db form_field_db_advanced db_schema",
							"label" => array(
								"value" => "Schema: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[db_schema]",
								"value" => "#db_schema#",
							)
						)
					),
					12 => array(
						"field" => array(
							"class" => "form_field setup_input form_field_db form_field_db_advanced db_odbc_data_source",
							"label" => array(
								"value" => "ODBC Data Source: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[db_odbc_data_source]",
								"value" => "#db_odbc_data_source#",
								"title" => "A Data Source Name (DSN) is the logical name that is used by Open Database Connectivity (ODBC) to refer to the driver and other information that is required to access data from a data source. Data sources are usually defined in /etc/odbc.ini",
							)
						)
					),
					13 => array(
						"field" => array(
							"class" => "form_field setup_input form_field_db form_field_db_advanced db_odbc_driver",
							"label" => array(
								"value" => "ODBC Driver: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[db_odbc_driver]",
								"value" => "#db_odbc_driver#",
								"title" => "Is the file path of the installed driver that connects to a data-base from ODBC protocol. Or the name of an ODBC instance that was defined in /etc/odbcinst.ini",
							)
						)
					),
					14 => array(
						"field" => array(
							"class" => "form_field setup_input form_field_db form_field_db_advanced db_extra_dsn",
							"label" => array(
								"value" => "Extra DSN: ",
							),
							"input" => array(
								"type" => "text",
								"name" => "data[db_extra_dsn]",
								"value" => "#db_extra_dsn#",
								"title" => "Other DSN attributes. Each attribute must be splitted by comma.",
							)
						)
					)
				)
			)
		),
	)
);

$head .= '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icons CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/db_driver_connection_props.js"></script>
<script>
	var drivers_encodings = ' . json_encode($drivers_encodings) . ';
	var drivers_extensions = ' . json_encode($drivers_extensions) . ';
	var drivers_ignore_connection_options = ' . json_encode($drivers_ignore_connection_options) . ';
	var drivers_ignore_connection_options_by_extension = ' . json_encode($drivers_ignore_connection_options_by_extension) . ';
</script>';

$main_content = '
<div class="db_settings">
		<h1>DataBase Settings</h1>
		<div class="info">
			If you are an advanced user, please click <a class="advanced" href="?step=3.1&iframe=' . (isset($is_inside_of_iframe) ? $is_inside_of_iframe : "") . '">here</a>, otherwise please fill the fields bellow.<br/>
			If you need a DataBase in your project, please choose the DataBase\'s type and correspondent settings, otherwise click continue...<br/>
			Note that if you previously set any db settings, you will need to do it again.
		</div>';

$main_content .= HtmlFormHandler::createHtmlForm($form_settings, $data);

if ($diagram_already_exists && $already_did_setup)
	$main_content .= '
		<div class="warning">
			Note that: if you did some changes from the advanced layers panel (as an advanced user), these changes will be overwritten and lost. <br/>
			Do you still wish to proceed with this simple ui? <br/>
			To edit as an advanced user, please click <a class="advanced" href="?step=3.1&iframe=' . (isset($is_inside_of_iframe) ? $is_inside_of_iframe : "") . '">here</a>.
		</div>';
	
$main_content .= '
</div>
<script>
	var form_fields = $(".db_settings .form_fields");
	onChangeDBType( form_fields.find(".db_type select")[0] );
	form_fields.children(".form_field_db_advanced").hide();
</script>';

$continue_function = $diagram_already_exists ? "confirm('We detected that a previously defined diagram already exists. If this is your first installation, it is OK, otherwise by changing the DB name, you can have some issues in the future, because when you did the previous installation, there were some tables that were created automatically, which will not be created in the new DB, if apply...\\n\\nDo you still wish to continue?') ? $('.db_settings form').submit() : false" : "$('.db_settings form').submit()";
?>
