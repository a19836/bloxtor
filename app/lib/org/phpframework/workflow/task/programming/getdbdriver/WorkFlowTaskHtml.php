<div class="get_db_driver_task_html">
	<div class="broker_method_obj" title="Write here the DB Object variable">
		<label>Broker Obj:</label>
		<select onChange="BrokerOptionsUtilObj.onBrokerChange(this)"></select>
		<input type="text" class="task_property_field" name="method_obj" />
		<span class="icon add_variable inline" onClick="BrokerOptionsUtilObj.chooseCreatedBrokerVariable(this)">Search</span>
	</div>
	<div class="db_driver">
		<label>DB Driver:</label>
		<input class="task_property_field db_driver_value" name="db_driver" />
		<input type="text" class="db_driver_variable" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Add Variable</span>
		<select class="db_driver_options"></select>
		<select class="task_property_field" name="db_driver_type" onChange="GetDBDriverTaskPropertyObj.onChangeDBDriverType(this)">
			<option>string</option>
			<option>variable</option>
			<option value="">code</option>
			<option value="db_drivers">db drivers</option>
		</select>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
		
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
