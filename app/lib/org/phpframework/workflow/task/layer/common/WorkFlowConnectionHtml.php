<div class="layer_connection_html">
	<div class="connection_type">
		<label>Connection Type: </label>
		<select class="connection_property_field" name="connection_type" onChange="onChangeLayerConnectionPropertiesType(this)">
			<option value="">Local</option>
			<option value="rest">REST</option>
			<option value="soap">SOAP</option>
		</select>
	</div>
	
	<div class="connection_response_type">
		<label>Connection Response Type: </label>
		<select class="connection_property_field" name="connection_response_type">
			<option value="">PHP Serialize</option>
			<option value="xml">XML</option>
			<option value="json">JSON</option>
		</select>
	</div>
	
	<div class="connection_settings">
		<label>Other Connection Settings: </label>
		<table>
			<thead>
				<tr>
					<th class="setting_name table_header">Setting Name</th>
					<th class="setting_value table_header">Setting Value</th>
					<th class="table_attr_icons">
						<a class="icon add" onClick="addLayerConnectionPropertiesSetting(this)" title="Add">Add</a>
					</th>
				</tr>
			</thead>
			<tbody class="table_attrs"></tbody>
		</table>
	</div>
	
	<div class="connection_global_variables_name">
		<label>Connection Global Variables Name: </label>
		<div class="info">"Connection Global Variables Name" are global variables name that were changed dynamically in the Rest Client side and you wish to set as Global Variables in the REST Server side. So we must pass them from the REST Client side to the REST Server side.</div>
		<table>
			<thead>
				<tr>
					<th class="var_name table_header">Global Variable Name</th>
					<th class="table_attr_icons">
						<a class="icon add" onClick="addLayerConnectionGLobalVarName(this)" title="Add Rest Global Variable Name">Add</a>
					</th>
				</tr>
			</thead>
			<tbody class="table_attrs"></tbody>
		</table>
	</div>
</div>
