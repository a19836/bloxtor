<div class="loop_task_html">
	<div class="init_counters">
		<label>Init Counters:</label>
		<a class="icon variable_add" onclick="LoopTaskPropertyObj.addInitCounterVariable(this)" title="Add Variable">Add Var</a>
		<a class="icon code_add" onclick="LoopTaskPropertyObj.addInitCounterCode(this)" title="Add Code">Add Code</a>
		
		<div class="fields">
			<ul></ul>
		</div>
	</div>
	
	<div class="test_counters">
		<label>Test Counters:</label>
		
		<div class="conditions"></div>
	</div>
	
	<div class="increment_counters">
		<label>Increment Counters:</label>
		<a class="icon variable_add" onclick="LoopTaskPropertyObj.addIncrementCounterVariable(this)" title="Add Variable">Add Var</a>
		<a class="icon code_add" onclick="LoopTaskPropertyObj.addIncrementCounterCode(this)" title="Add Code">Add Code</a>
		
		<div class="fields">
			<ul></ul>
		</div>
	</div>
	
	<div class="other_settings">
		<label>Other Settings:</label>
	
		<div class="execute_first_iteration">
			<label>Always execute the first iteration:</label>
			<input class="task_property_field" type="checkbox" name="execute_first_iteration" value="1" />
		</div>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/CommentsHtml.php"; ?>
	
	<div class="task_property_exit" exit_id="start_exit" exit_color="#31498f" exit_label="Start loop"></div>
	<div class="task_property_exit" exit_id="default_exit" exit_color="#2C2D34" exit_label="End loop"></div>
</div>
