<div class="call_hibernate_object_task_html">
	<div class="get_automatically">
		<label>Get hibernate obj from File Manager: </label>
		<span class="icon update" onClick="CallHibernateObjectTaskPropertyObj.onChooseHibernateObject(this)" title="Get hibernate object from File Manager">Update</span>
	</div>
	<div class="broker_method_obj" title="Write here the Hibernate Broker variable">
		<label>Hbn Broker Obj:</label>
		<select onChange="BrokerOptionsUtilObj.onBrokerChange(this)"></select>
		<input type="text" class="task_property_field" name="method_obj" />
		<span class="icon search" onClick="BrokerOptionsUtilObj.chooseCreatedBrokerVariable(this)">Search</span>
	</div>
	<div class="module_id">
		<label>Module Id: <span class="icon edit edit_source" onClick="CallHibernateObjectTaskPropertyObj.onEditFile(this)" title="Edit file">Edit</span></label>
		<input type="text" class="task_property_field" name="module_id" />
		<select class="task_property_field" name="module_id_type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	<div class="service_id">
		<label>Object Id: <span class="icon edit edit_source" onClick="CallHibernateObjectTaskPropertyObj.onEditObject(this)" title="Edit object">Edit</span></label>
		<input type="text" class="task_property_field" name="service_id" />
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
	
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
	<?php include dirname(dirname($file_path)) . "/common/CommentsHtml.php"; ?>
		
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
