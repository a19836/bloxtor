<div class="layer_rest_server_html">
	<label>Is Rest Server Active:</label>
	<input class="task_property_field active" type="checkbox" name="layer_brokers[rest][active]" value="1" title="Activate Rest Server" onClick="activateLayerRestServer(this)" />
	
	<div class="url">
		<label>URL: </label>
		<input class="task_property_field" type="text" name="layer_brokers[rest][url]" placeHolder="http://..." autocomplete="new-password" />
		<div class="info">Url of this layer web-server.</div>
	</div>
	
	<div class="http_auth">
		<label>Http Authentication Type: </label>
		<select class="task_property_field" name="layer_brokers[rest][http_auth]">
			<option value="">empty</option>
			<option value="basic">Http Basic Authentication</option>
		</select>
		<div class="info">Http Access authentication type configured in the web-server. Usually this is done in the htaccess file.</div>
	</div>
	
	<div class="user_pwd">
		<label>Http User And Pwd: </label>
		<input class="task_property_field" type="text" name="layer_brokers[rest][user_pwd]" placeHolder="empty or username:password" autocomplete="new-password" />
		<div class="info">Http Access authentication username and password configured in the web-server. Usually this is done in the htaccess file.</div>
	</div>
	
	<div class="response_type">
		<label>Response Type: </label>
		<select class="task_property_field" name="layer_brokers[rest][response_type]">
			<option value="">PHP Serialize</option>
			<option value="xml">XML</option>
			<option value="json">JSON</option>
		</select>
		<div class="info">Type of the response data</div>
	</div>
	
	<div class="rest_auth_user">
		<label>Rest Authentication Username: </label>
		<input class="task_property_field" type="text" name="layer_brokers[rest][rest_auth_user]" autocomplete="new-password" />
		<div class="info">Username that wil be authenticated in the server side</div>
	</div>
	
	<div class="rest_auth_pass with_toggle_icon">
		<label>Rest Authentication Password: </label>
		<input class="task_property_field" type="password" name="layer_brokers[rest][rest_auth_pass]" autocomplete="new-password" />
		<a class="icon toggle_password" onClick="toggleLayerRestConnectionPropertiesSettingPasswordField(this)">Toggle Password</a>
		<div class="info">Password that wil be authenticated in the server side</div>
	</div>
	
	<div class="request_encryption_key">
		<label>Encryption Key for Request: </label>
		<input class="task_property_field" type="text" name="layer_brokers[rest][request_encryption_key]" autocomplete="new-password" />
		<div class="info">Empty or hexadecimal encryption key to encrypt the request sent data. This key should be created by the CryptoKeyHandler class.</div>
	</div>
	
	<div class="response_encryption_key">
		<label>Encryption Key for Response: </label>
		<input class="task_property_field" type="text" name="layer_brokers[rest][response_encryption_key]" autocomplete="new-password" />
		<div class="info">Empty or hexadecimal encryption key to encrypt the request response. This key should be created by the CryptoKeyHandler class.</div>
	</div>
	
	<div class="other_settings">
		<label>Other Settings: </label>
		<table>
			<thead>
				<tr>
					<th class="setting_name table_header">Setting Name</th>
					<th class="setting_value table_header">Setting Value</th>
					<th class="table_attr_icons">
						<a class="icon add" onClick="addLayerTypeServerPropertiesOtherSetting(this, 'rest')" title="Add">Add</a>
					</th>
				</tr>
			</thead>
			<tbody class="table_attrs"></tbody>
		</table>
	</div>
	
	<div class="global_variables">
		<label>Global Variables: </label>
		<table>
			<thead>
				<tr>
					<th class="var_name table_header">Var Name</th>
					<th class="var_value table_header">Var Value</th>
					<th class="table_attr_icons">
						<a class="icon add" onClick="addLayerTypeServerPropertiesGlobalVariable(this, 'rest')" title="Add">Add</a>
					</th>
				</tr>
			</thead>
			<tbody class="table_attrs"></tbody>
		</table>
	</div>
</div>

<!--div class="layer_soap_server_html">
	<label>Is Soap Server Active:</label>
	<input class="task_property_field active" type="checkbox" name="layer_brokers[soap][active]" value="1" title="Activate Soap Server" onClick="activateLayerSoapServer(this)" />
	
	<div class="other_settings">
		<label>Other Settings: </label>
		<table>
			<thead>
				<tr>
					<th class="setting_name table_header">Setting Name</th>
					<th class="setting_value table_header">Setting Value</th>
					<th class="table_attr_icons">
						<a class="icon add" onClick="addLayerTypeServerPropertiesOtherSetting(this, 'soap')" title="Add">Add</a>
					</th>
				</tr>
			</thead>
			<tbody class="table_attrs"></tbody>
		</table>
	</div>
	
	<div class="global_variables">
		<label>Global Variables: </label>
		<table>
			<thead>
				<tr>
					<th class="var_name table_header">Var Name</th>
					<th class="var_value table_header">Var Value</th>
					<th class="table_attr_icons">
						<a class="icon add" onClick="addLayerTypeServerPropertiesOtherSetting(this, 'soap')" title="Add">Add</a>
					</th>
				</tr>
			</thead>
			<tbody class="table_attrs"></tbody>
		</table>
	</div>
</div-->
