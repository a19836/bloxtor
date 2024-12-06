<div class="call_business_logic_task_html">
	<div class="get_automatically">
		<label>Get business logic from File Manager: </label>
		<span class="icon update" onClick="CallBusinessLogicTaskPropertyObj.onChooseBusinessLogic(this)" title="Get business logic from File Manager">Update</span>
	</div>
	<div class="broker_method_obj" title="Write here the business logic Broker variable">
		<label>Ctx Broker Obj:</label>
		<select onChange="BrokerOptionsUtilObj.onBrokerChange(this)"></select>
		<input type="text" class="task_property_field" name="method_obj" />
		<span class="icon add_variable inline" onClick="BrokerOptionsUtilObj.chooseCreatedBrokerVariable(this)">Search</span>
	</div>
	<div class="module_id">
		<label>Module Id: <span class="icon edit edit_source" onClick="CallBusinessLogicTaskPropertyObj.onEditFile(this)" title="Edit file">Edit</span></label>
		<input type="text" class="task_property_field" name="module_id" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field" name="module_id_type">
			<option></option>
			<option>string</option>
			<option>variable</option>
		</select>
	</div>
	<div class="service_id">
		<label>Service Id: <span class="icon edit edit_source" onClick="CallBusinessLogicTaskPropertyObj.onEditService(this)" title="Edit service logic">Edit</span></label>
		<input type="text" class="task_property_field" name="service_id" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field" name="service_id_type">
			<option></option>
			<option>string</option>
			<option>variable</option>
		</select>
	</div>
	
	<div class="params">
		<label class="main_label">Parameters:</label>
		<input type="text" class="task_property_field parameters_code" name="parameters" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field parameters_type" name="parameters_type" onChange="CallBusinessLogicTaskPropertyObj.onChangeParametersType(this)">
			<option></option>
			<option>string</option>
			<option>variable</option>
			<option>array</option>
		</select>
		<div class="parameters array_items"></div>
	</div>
	<div class="opts">
		<label class="main_label">Options:</label>
		<input type="text" class="task_property_field options_code" name="options" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field options_type" name="options_type" onChange="LayerOptionsUtilObj.onChangeOptionsType(this)">
			<option></option>
			<option>string</option>
			<option>variable</option>
			<option>array</option>
		</select>
		<div class="options array_items"></div>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
	<?php include dirname(dirname($file_path)) . "/common/CommentsHtml.php"; ?>
		
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
