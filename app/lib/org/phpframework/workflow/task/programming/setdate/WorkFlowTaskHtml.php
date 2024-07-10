<div class="set_date_task_html">
	<div class="sample">
		<label>Sample: </label>
		<select onChange="SetDateTaskPropertyObj.onChangeSample(this)">
			<option value="">-- User defined --</option>
			<option value="Y-m-d H:i:s">Current date + time</option>
			<option value="Y-m-d">Current date</option>
			<option value="H:i:s">Current time</option>
			<option value="Y">Current year</option>
			<option value="m">Current month</option>
			<option value="d">Current day</option>
			<option value="H">Current hour</option>
			<option value="i">Current minutes</option>
			<option value="s">Current seconds</option>
		</select>
	</div>
	<div class="format">
		<label>Format: </label>
		<input type="text" class="task_property_field" name="format" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
		<div class="info">More formats <a href="https://www.php.net/manual/en/datetime.format.php" target="php_datetime_format">here</a>.</div>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/ResultVariableHtml.php"; ?>
	
	<div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div>
</div>
