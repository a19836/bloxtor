<div class="server_task_html">
	<ul>
		<li class="details_tab"><a href="#details_container" onClick="ServerTaskPropertyObj.onClickServerDetailsTab(this)">Details</a></li>
		<li class="templates_tab"><a href="#templates_container" onClick="ServerTaskPropertyObj.onClickServerTemplatesTab(this)">Templates</a></li>
		<li class="deployments_tab"><a href="#deployments_container" onClick="ServerTaskPropertyObj.onClickServerDeploymentsTab(this)">Deployments</a></li>
	</ul>
	
	<div class="details_container" id="details_container">
		<div class="host">
			<label>Host:</label>
			<input class="task_property_field host" type="text" name="host" value="" placeHolder="server domain" /> : <input class="task_property_field port" type="text" name="port" value="" placeHolder="port" />
		</div>
		<div class="username">
			<label>Username:</label>
			<input class="task_property_field" type="text" name="username" value="" placeHolder="server username" autocomplete="new-password" />
		</div>
		<div class="authentication_type">
			<label>Authentication Type:</label>
			<select class="task_property_field" name="authentication_type" onChange="ServerTaskPropertyObj.onServerAuthenticationTypeChange(this)">
				<option value="password">Password</option>
				<option value="key_files">Files with SSH Keys</option>
				<option value="key_strings">SSH Keys in string</option>
			</select>
		</div>
		<div class="password">
			<label>Password:</label>
			<input class="task_property_field" type="password" name="password" value="" placeHolder="server password" autocomplete="new-password" />
		</div>
		<div class="ssh_auth_pub">
			<label>SSH Auth Public:</label>
			<textarea class="task_property_field" name="ssh_auth_pub" placeHolder="server ssh authentication public key"></textarea>
		</div>
		<div class="ssh_auth_pri">
			<label>SSH Auth Private:</label>
			<textarea class="task_property_field" name="ssh_auth_pri" placeHolder="server ssh authentication private key"></textarea>
		</div>
		<div class="ssh_auth_pub_file">
			<label>SSH Auth Public File:</label>
			<input class="task_property_field" name="ssh_auth_pub_file" placeHolder="server ssh authentication public key file" />
			<div class="info">This file path must be relative to the CMS_PATH</div>
		</div>
		<div class="ssh_auth_pri_file">
			<label>SSH Auth Private File:</label>
			<input class="task_property_field" name="ssh_auth_pri_file" placeHolder="server ssh authentication private key file" />
			<div class="info">This file path must be relative to the CMS_PATH</div>
		</div>
		<div class="ssh_auth_passphrase">
			<label>Server Auth Passphrase:</label>
			<input class="task_property_field" type="text" name="ssh_auth_passphrase" value="" placeHolder="server ssh authentication passphrase" />
		</div>
		<div class="server_fingerprint">
			<label>Server Fingerprint:</label>
			<input class="task_property_field" type="text" name="server_fingerprint" value="" placeHolder="server fingerprint" />
		</div>
	</div>
	
	<div class="templates_container" id="templates_container">
		<table>
			<thead>
				<tr>
					<th class="name">Template Name</th>
					<th class="created_date">Created Date</th>
					<th class="modified_date">Modified Date</th>
					<th class="actions">
						<i class="icon add" onclick="ServerTaskPropertyObj.addUserTemplate(this)" title="Add new template"></i>
					</th>
				</tr>
			</thead>
			<tbody index_prefix="templates">
				<tr class="no_items"><td colspan="4">No items</td></tr>
			</tbody>
		</table>
		
		<div class="myfancypopup template_properties">
			<ul>
				<li class="template_workflow_tab"><a href="#template_workflow">Workflow</a></li>
				<li class="template_actions_tab"><a href="#template_actions">Actions</a></li>
			</ul>
			
			<div id="template_workflow" class="template_workflow"></div>
			
			<div id="template_actions" class="template_actions">
				<div class="server_installation_folder_path">
					<label>Server Installation Folder Path:</label>
					<input class="task_property_field" name="server_installation_folder_path" value="" />
					<div class="info">Server absolute path where installation will happen...</div>
				</div>
				
				<div class="server_installation_url">
					<label>Server Installation URL:</label>
					<input class="task_property_field" name="server_installation_url" value="" />
					<div class="info">Server url where this installation can be accessable...</div>
				</div>
				
				<div class="available_actions">
					<label>Available Actions:</label>
					<select>
						<option value="" disabled>Please select an action</option>
						<option value="run_test_units">Run Test-Units</option>
						<option value="migrate_dbs">Migrate DBs</option>
						<option value="copy_layers">Copy Layers</option>
						<option value="copy_files">Copy Files</option>
						<option value="execute_shell_cmds">Execute Shell Commands</option>
					</select>
					<i class="icon add" onclick="ServerTaskPropertyObj.addUserTemplateAction(this)" title="Add new action"></i>
				</div>
				
				<ol class="actions" index_prefix="actions">
					<li class="no_actions">There are no actions</li>
				</ol>
			</div>
		</div>
	</div>
	
	<div class="deployments_container" id="deployments_container">
		<div class="deploy_template">
			<label>Deploy Template:</label>
			<select></select>
			<i class="icon right" onclick="ServerTaskPropertyObj.executeDeploymentServerActionDeploy(this)" title="Deploy template to the server"></i>
			<span class="info">Only the saved templates will be deployed!</span>
		</div>
		
		<table>
			<thead>
				<tr>
					<th class="selected"></th>
					<th class="deployment_id">Version</th>
					<th class="template">Template</th>
					<th class="created_date">Created Date</th>
					<th class="status">Status</th>
					<th class="error_message">Error Message</th>
					<th class="actions"></th>
				</tr>
			</thead>
			<tbody index_prefix="deployments">
				<tr class="no_items"><td colspan="7">No items</td></tr>
			</tbody>
		</table>
	</div>
</div>
