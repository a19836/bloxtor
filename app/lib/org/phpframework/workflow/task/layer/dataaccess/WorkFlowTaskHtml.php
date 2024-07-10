<div class="data_access_layer_task_html">
	<div class="type">
		<label>Type:</label>
		<select class="task_property_field" name="type">
			<option value="ibatis">Ibatis</option>
			<option value="hibernate">Hibernate</option>
		</select>
	</div>

	<?php
		include dirname(dirname($file_path)) . "/common/BrokersHtml.php";
	?>
	
	<div class="task_property_exit" exit_id="layer_exit" exit_color="#31498f"></div>
</div>

