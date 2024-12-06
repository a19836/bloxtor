<div class="break_task_html">
	<div class="value">
		<label>Value: </label>
		<input type="number" class="task_property_field" name="value" minValue="1" />
		<span class="icon add_variable inline" onClick="ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(this)">Search</span>
	</div>
	
	<?php include dirname(dirname($file_path)) . "/common/CommentsHtml.php"; ?>
	
	<!--div class="task_property_exit" exit_id="default_exit" exit_color="#426efa"></div-->
</div>
