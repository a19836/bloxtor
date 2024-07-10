<div class="reset_region_block_join_points_task_html">
	<div class="broker_method_obj" title="Write here the CMS Block Layer variable">
		<label>CMS Block Layer Obj:</label>
		<select onChange="BrokerOptionsUtilObj.onBrokerChange(this)"></select>
		<input type="text" class="task_property_field" name="method_obj" />
		<span class="icon add_variable inline" onClick="BrokerOptionsUtilObj.chooseCreatedBrokerVariable(this)">Search</span>
	</div>
	<div class="region_id">
		<label>Region Id:</label>
		<input type="text" class="task_property_field" name="region_id" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
		<select class="task_property_field" name="region_id_type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
	<div class="block_id">
		<label>Block Id:</label>
		<input type="text" class="task_property_field" name="block_id" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
		<select class="task_property_field" name="block_id_type">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
		</select>
	</div>
		
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
