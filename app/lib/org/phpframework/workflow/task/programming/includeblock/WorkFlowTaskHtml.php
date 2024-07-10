<div class="include_block_task_html">
	<div class="broker_method_obj" title="Write here the EVC variable">
		<label>EVC Obj:</label>
		<select onChange="BrokerOptionsUtilObj.onBrokerChange(this)"></select>
		<input type="text" class="task_property_field" name="method_obj" />
		<span class="icon add_variable inline" onClick="BrokerOptionsUtilObj.chooseCreatedBrokerVariable(this)">Search</span>
	</div>
	<div class="project">
		<label>Project: </label>
		<input type="text" class="task_property_field" name="project" />
		<select class="task_property_field project" name="project">
		</select>
		<select class="task_property_field type" name="project_type" onChange="IncludeBlockTaskPropertyObj.onChangeBlockType(this)">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
			<option>options</option>
		<select>
	</div>
	<div class="block">
		<label>Block: </label>
		<input type="text" class="task_property_field" name="block" />
		<select class="task_property_field" name="block_type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		<select>
		<span class="icon search" onClick="IncludeBlockTaskPropertyObj.onChooseFile(this)">Search</span>
	</div>
	<div class="once">
		<label>Include Once: </label>
		<input type="checkbox" class="task_property_field" name="once" value="1" />
	</div>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
