<div class="call_hibernate_method_task_html">
	<div class="get_automatically">
		<label>Get hibernate obj from File Manager: </label>
		<span class="icon update" onClick="CallHibernateMethodTaskPropertyObj.onChooseHibernateObjectMethod(this)" title="Get hibernate object method from File Manager">Update</span>
	</div>
	<div class="broker_method_obj" title="Write here the Hibernate Object variable">
		<label>Broker Obj:</label>
		<select class="task_property_field" onChange="CallHibernateMethodTaskPropertyObj.onChangeBrokerMethodObj(this)" name="broker_method_obj_type"></select>
		<input type="text" class="task_property_field" name="method_obj" />
		<span class="icon add_variable inline" onClick="CallHibernateMethodTaskPropertyObj.chooseCreatedBrokerVariable(this)">Search</span>
	</div>
	<div class="module_id">
		<label>Module Id: <span class="icon edit edit_source" onClick="CallHibernateMethodTaskPropertyObj.onEditFile(this)" title="Edit file">Edit</span></label>
		<input type="text" class="task_property_field" name="module_id" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field" name="module_id_type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	<div class="service_id">
		<label>Object Id: <span class="icon edit edit_source" onClick="CallHibernateMethodTaskPropertyObj.onEditObject(this)" title="Edit object">Edit</span></label>
		<input type="text" class="task_property_field" name="service_id" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="task_property_field" name="service_id_type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
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
	
	<div class="service_method">
		<label>Method:</label>
		<input type="hidden" class="task_property_field service_method" name="service_method" />
		<input type="text" class="service_method_code" name="service_method_code" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="service_method_string" name="service_method_string" onChange="CallHibernateMethodTaskPropertyObj.onChangeServiceMethod(this)">
		</select>
		<select class="task_property_field service_method_type" name="service_method_type" onChange="CallHibernateMethodTaskPropertyObj.onChangeServiceMethodType(this)">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	<div class="service_method_args">
		<label class="service_method_args_main_label">Method Args:</label>
		
		<div class="sma_query_type">
			<label class="service_method_arg_label">Query Type:</label>
			<input type="text" class="task_property_field service_method_arg_code" name="sma_query_type" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
			<select class="task_property_field sma_query_type_string" name="sma_query_type">
				<option>insert</option>
				<option>update</option>
				<option>delete</option>
				<option>select</option>
				<option>procedure</option>
			</select>
			<select class="task_property_field service_method_arg_type" name="sma_query_type_type" onChange="CallHibernateMethodTaskPropertyObj.onChangeSMAQueryTypeType(this)">
				<option>string</option>
				<option>variable</option>
				<option value="">code</option>
			</select>
		</div>
		<div class="sma_query_id">
			<label class="service_method_arg_label">Query Id: <span class="icon edit edit_source" onClick="CallHibernateMethodTaskPropertyObj.onEditQuery(this)" title="Edit query">Edit</span></label>
			<input type="text" class="task_property_field service_method_arg_code" name="sma_query_id" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
			<select class="task_property_field service_method_arg_type" name="sma_query_id_type">
				<option>string</option>
				<option>variable</option>
				<option value="">code</option>
			</select>
		</div>
		<div class="sma_function_name">
			<label class="service_method_arg_label">Function Name:</label>
			<input type="text" class="task_property_field service_method_arg_code" name="sma_function_name" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
			<select class="task_property_field service_method_arg_type" name="sma_function_name_type">
				<option>string</option>
				<option>variable</option>
				<option value="">code</option>
			</select>
		</div>
		<div class="sma_data">
			<label class="service_method_arg_label">Data:</label>
			<input type="text" class="task_property_field service_method_arg_code" name="sma_data" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
			<select class="task_property_field service_method_arg_type" name="sma_data_type" onChange="CallHibernateMethodTaskPropertyObj.onChangeSMAType(this)">
				<option>string</option>
				<option>variable</option>
				<option value="">code</option>
				<option>array</option>
			</select>
			<div class="sma_data array_items"></div>
		</div>
		<div class="sma_statuses">
			<label class="service_method_arg_label">Statuses Variable:</label>
			<input type="text" class="task_property_field service_method_arg_code" name="sma_statuses" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
			<select class="task_property_field service_method_arg_type" name="sma_statuses_type">
				<option>variable</option>
				<option>string</option>
				<option value="">code</option>
			</select>
		</div>
		<div class="sma_ids">
			<label class="service_method_arg_label">Ids Variable:</label>
			<input type="text" class="task_property_field service_method_arg_code" name="sma_ids" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
			<select class="task_property_field service_method_arg_type" name="sma_ids_type">
				<option>variable</option>
				<option>string</option>
				<option value="">code</option>
			</select>
		</div>
		<div class="sma_rel_name">
			<label class="service_method_arg_label">Rel Name:</label>
			<input type="text" class="task_property_field service_method_arg_code" name="sma_rel_name" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
			<select class="task_property_field service_method_arg_type" name="sma_rel_name_type">
				<option>string</option>
				<option>variable</option>
				<option value="">code</option>
			</select>
		</div>
		<div class="sma_parent_ids">
			<label class="service_method_arg_label">Parent Ids:</label>
			<input type="text" class="task_property_field service_method_arg_code" name="sma_parent_ids" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
			<select class="task_property_field service_method_arg_type" name="sma_parent_ids_type" onChange="CallHibernateMethodTaskPropertyObj.onChangeSMAType(this)">
				<option>string</option>
				<option>variable</option>
				<option value="">code</option>
				<option>array</option>
			</select>
			<div class="sma_parent_ids array_items"></div>
		</div>
		<div class="sma_sql">
			<label class="service_method_arg_label">SQL:</label>
			<textarea class="task_property_field" name="sma_sql"></textarea>
			<input type="text" class="service_method_arg_code" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
			<select class="task_property_field service_method_arg_type" name="sma_sql_type" onChange="CallHibernateMethodTaskPropertyObj.onChangeSMASQLType(this)">
				<option>string</option>
				<option>variable</option>
				<option value="">code</option>
			</select>
			<textarea class="sql_editor"></textarea>
		</div>
		
		<div class="sma_options">
			<label class="service_method_arg_label">Options</label>
			<input type="text" class="task_property_field service_method_arg_code" name="sma_options" />
			<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
			<select class="task_property_field service_method_arg_type" name="sma_options_type" onChange="CallHibernateMethodTaskPropertyObj.onChangeSMAOptionsType(this)">
				<option value="">code</option>
				<option>string</option>
				<option>variable</option>
				<option>array</option>
			</select>
			<div class="sma_options array_items"></div>
		</div>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
		
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
