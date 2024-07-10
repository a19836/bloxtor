<div class="call_ibatis_query_task_html">
	<div class="get_automatically">
		<label>Get query from File Manager: </label>
		<span class="icon update" onClick="CallIbatisQueryTaskPropertyObj.onChooseQuery(this)" title="Get query from File Manager">Update</span>
	</div>
	<div class="broker_method_obj" title="Write here the Ibatis Object variable">
		<label>Broker Obj:</label>
		<select onChange="BrokerOptionsUtilObj.onBrokerChange(this)"></select>
		<input type="text" class="task_property_field" name="method_obj" />
		<span class="icon add_variable inline" onClick="BrokerOptionsUtilObj.chooseCreatedBrokerVariable(this)">Search</span>
	</div>
	<div class="module_id">
		<label>Module Id: <span class="icon edit edit_source" onClick="CallIbatisQueryTaskPropertyObj.onEditFile(this)" title="Edit file">Edit</span></label>
		<input type="text" class="task_property_field" name="module_id" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field" name="module_id_type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	<div class="service_type">
		<label>Query Type:</label>
		<input type="hidden" class="task_property_field service_type" name="service_type" />
		<input type="text" class="service_type_code" name="service_type_code" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="service_type_string" name="service_type_string">
			<option>insert</option>
			<option>update</option>
			<option>delete</option>
			<option>select</option>
			<option>procedure</option>
		</select>
		<select class="task_property_field service_type_type" name="service_type_type" onChange="CallIbatisQueryTaskPropertyObj.onChangeServiceType(this)">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	<div class="service_id">
		<label>Query Id: <span class="icon edit edit_source" onClick="CallIbatisQueryTaskPropertyObj.onEditQuery(this)" title="Edit query">Edit</span></label>
		<input type="text" class="task_property_field" name="service_id" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field" name="service_id_type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	
	<div class="params">
		<label class="main_label">Parameters:</label>
		<input type="text" class="task_property_field parameters_code" name="parameters" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field parameters_type" name="parameters_type" onChange="CallIbatisQueryTaskPropertyObj.onChangeParametersType(this)">
			<option value="">code</option>
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
	
	<div class="query_type">
		<label>Execution Type:</label>
		<select class="task_property_field" name="query_type">
			<option value="0">Execute Query and get DB data</option>
			<option value="1">Simply return SQL statement</option>
		</select>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
		
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
