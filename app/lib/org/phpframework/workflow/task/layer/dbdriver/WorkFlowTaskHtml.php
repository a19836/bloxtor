<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

include_once get_lib("org.phpframework.db.DB");

$extensions = DB::getAllExtensionsByType();
$encodings = DB::getAllDBConnectionEncodingsByType();
$driver_labels = DB::getAllDriverLabelsByType();
$ignore_connection_options = DB::getAllIgnoreConnectionOptionsByType();
$ignore_connection_options_by_extension = DB::getAllIgnoreConnectionOptionsByExtensionAndType();

echo '<script>
DBDriverTaskPropertyObj.encodings = ' . json_encode($encodings) . ';
DBDriverTaskPropertyObj.extensions = ' . json_encode($extensions) . ';
DBDriverTaskPropertyObj.ignore_options = ' . json_encode($ignore_connection_options) . ';
DBDriverTaskPropertyObj.ignore_options_by_extension = ' . json_encode($ignore_connection_options_by_extension) . ';
</script>';
?>
<div class="db_driver_task_html">
	<div class="type">
		<label>DataBase Type:</label>
		<select class="task_property_field" name="type" onChange="DBDriverTaskPropertyObj.onChangeType(this)">
		<?php
			$first_driver_type = key($driver_labels);
			
			foreach ($driver_labels as $driver_type => $driver_label) 
				echo '<option value="' . $driver_type . '">' . $driver_label. '</option>';
		?>
		</select>
	</div>
	
	<div class="extension">
		<label>Connection Type:</label>
		<select class="task_property_field" name="extension" onChange="DBDriverTaskPropertyObj.onChangeExtension(this)">
		<?php
			if ($first_driver_type) {
				$first_driver_extensions = isset($extensions[$first_driver_type]) ? $extensions[$first_driver_type] : null;
				foreach ($first_driver_extensions as $idx => $value)
					echo '<option value="' . $value . '">' . $value . ($idx == 0 ? " - Default" : "") . '</option>';
			}
		?>
		</select>
	</div>

	<div class="host">
		<label>Host:</label>
		<input type="text" class="task_property_field" name="host" value="" autocomplete="new-password" />
	</div>

	<div class="port">
		<label>Port:</label>
		<input type="text" class="task_property_field" name="port" value="" autocomplete="new-password" />
	</div>

	<div class="db_name">
		<label>DB Name:</label>
		<input type="text" class="task_property_field" name="db_name" value="" autocomplete="new-password" />
	</div>

	<div class="user">
		<label>User:</label>
		<input type="text" class="task_property_field" name="username" value="" autocomplete="new-password" />
	</div>

	<div class="password">
		<label>Password:</label>
		<input type="password" class="task_property_field" name="password" value="" autocomplete="new-password" />
		<span class="icon toggle_password" onClick="DBDriverTaskPropertyObj.togglePasswordField(this)"></span>
	</div>

	<div class="persistent">
		<label>Persistent:</label>
		<select class="task_property_field" name="persistent">
			<option value="1">Yes</option>
			<option value="0">No</option>
		</select>
	</div>

	<div class="new_link">
		<label>New Link:</label>
		<select class="task_property_field" name="new_link">
			<option value="0">No</option>
			<option value="1">Yes</option>
		</select>
	</div>

	<div class="reconnect" title="Automatically reconnect if connection becomes stale.">
		<label>Reconnect:</label>
		<select class="task_property_field" name="reconnect">
			<option value="0">No</option>
			<option value="1">Yes</option>
		</select>
	</div>

	<div class="encoding">
		<label>Encoding:</label>
		<select class="task_property_field" name="encoding">
			<option value="">-- Default --</option>
		<?php
			if ($first_driver_type) {
				$first_driver_encodings = isset($encodings[$first_driver_type]) ? $encodings[$first_driver_type] : null;
				foreach ($first_driver_encodings as $enc => $label)
					echo '<option value="' . $enc . '">' . $label . '</option>';
			}
		?>
		</select>
	</div>
	
	<div class="schema">
		<label>Schema:</label>
		<input type="text" class="task_property_field" name="schema" value="" autocomplete="new-password" />
	</div>

	<div class="odbc_data_source" title="A Data Source Name (DSN) is the logical name that is used by Open Database Connectivity (ODBC) to refer to the driver and other information that is required to access data from a data source. Data sources are usually defined in /etc/odbc.ini">
		<label>ODBC Data Source:</label>
		<input type="text" class="task_property_field" name="odbc_data_source" value="" autocomplete="new-password" />
	</div>

	<div class="odbc_driver" title="Is the file path of the installed driver that connects to a data-base from ODBC protocol. Or the name of an ODBC instance that was defined in /etc/odbcinst.ini">
		<label>ODBC Driver:</label>
		<input type="text" class="task_property_field" name="odbc_driver" value="" autocomplete="new-password" />
	</div>

	<div class="extra_dsn" title="Other DSN attributes. Each attribute must be splitted by comma.">
		<label>Extra DSN:</label>
		<input type="text" class="task_property_field" name="extra_dsn" value="" autocomplete="new-password" />
	</div>

	<div class="extra_settings" title="Other settings attributes. Each setting must be splitted by & as a url query string.">
		<label>Extra Settings:</label>
		<input type="text" class="task_property_field" name="extra_settings" value="" autocomplete="new-password" />
	</div>
</div>
