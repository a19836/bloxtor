<div class="call_presentation_layer_web_service_task_html">
	<div class="get_automatically disabled">
		<label>Get Page from File Manager: </label>
		<span class="icon update" onClick="ProgrammingTaskUtil.onProgrammingTaskChoosePageUrl(this)" title="Get page from File Manager">Update</span>
	</div>
	<!--div class="broker_method_obj" title="Write here the EVC variable">
		<label>Presentation Layer:</label>
		<select onChange="BrokerOptionsUtilObj.onBrokerChange(this)"></select>
		<input type="text" class="task_property_field" name="method_obj" />
		<span class="icon search" onClick="BrokerOptionsUtilObj.chooseCreatedBrokerVariable(this)">Search</span>
	</div-->
	<div class="project">
		<label>Project: </label>
		<input type="text" class="task_property_field" name="project" />
		<select class="task_property_field" name="project_type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	<div class="page">
		<label>Page: </label>
		<input type="text" class="task_property_field" name="page" />
		<select class="task_property_field" name="page_type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	<div class="advanced_settings">
		<label class="advanced_settings_label">Advanced Settings (Optional)</label>
		
		<div class="extvars">
			<label class="main_label">External Vars:</label>
			<input type="text" class="task_property_field external_vars_code" name="external_vars" />
			<select class="task_property_field external_vars_type" name="external_vars_type" onChange="callPresentationLayerWebServiceTaskPropertyObj.onChangeParametersType(this, 'external_vars')">
				<option></option>
				<option>string</option>
				<option>variable</option>
				<option>array</option>
			</select>
			<div class="external_vars array_items"></div>
		</div>
		<div class="incs">
			<label class="main_label">Includes:</label>
			<input type="text" class="task_property_field includes_code" name="includes" />
			<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseFilePath(this)">Search</span>
			<select class="task_property_field includes_type" name="includes_type" onChange="callPresentationLayerWebServiceTaskPropertyObj.onChangeParametersType(this, 'includes')">
				<option></option>
				<option>string</option>
				<option>variable</option>
				<option>array</option>
			</select>
			<div class="includes array_items"></div>
		</div>
		<div class="incs_once">
			<label class="main_label">Includes Once:</label>
			<input type="text" class="task_property_field includes_once_code" name="includes_once" />
			<span class="icon search" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseFilePath(this)">Search</span>
			<select class="task_property_field includes_once_type" name="includes_once_type" onChange="callPresentationLayerWebServiceTaskPropertyObj.onChangeParametersType(this, 'includes_once')">
				<option></option>
				<option>string</option>
				<option>variable</option>
				<option>array</option>
			</select>
			<div class="includes_once array_items"></div>
		</div>
	</div>
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
	<?php include dirname(dirname($file_path)) . "/common/CommentsHtml.php"; ?>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
