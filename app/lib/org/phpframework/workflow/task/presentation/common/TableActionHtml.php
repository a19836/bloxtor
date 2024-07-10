<div class="actions">
	<label>Actions:</label>
	
	<ul>
		<li class="sub_actions_group single_actions">
			<label>Individual Item Actions:</label>
			<ul>
				<li class="single_insert">
					<i class="icon maximize" onClick="PresentationTaskUtil.toggleAdvancedActionSettings(this)"></i>
					<label>Add Action:</label>
					<input class="task_property_field action_active" type="checkbox" name="action[single_insert]" value="1" onClick="PresentationTaskUtil.onChangeFormAction(this)" />
					
					<ul class="advanced_action_settings">
						<li class="ok_msg_message"><label>Ok Message: </label><input class="task_property_field" type="text" name="action[single_insert_ok_msg_message]" /></li>
						<li class="ok_msg_redirect_url"><label>OK Redirect Url: </label><input class="task_property_field" type="text" name="action[single_insert_ok_msg_redirect_url]" /></li>
						<li class="error_msg_message"><label>Error Message: </label><input class="task_property_field" type="text" name="action[single_insert_error_msg_message]" /></li>
						<li class="error_msg_redirect_url"><label>Error Redirect Url: </label><input class="task_property_field" type="text" name="action[single_insert_error_msg_redirect_url]" /></li>
					</ul>
				</li>
				<li class="single_update">
					<i class="icon maximize" onClick="PresentationTaskUtil.toggleAdvancedActionSettings(this)"></i>
					<label>Update Action:</label>
					<input class="task_property_field action_active" type="checkbox" name="action[single_update]" value="1" onClick="PresentationTaskUtil.onChangeFormAction(this)" />
					
					<ul class="advanced_action_settings">
						<li class="ok_msg_message"><label>Ok Message: </label><input class="task_property_field" type="text" name="action[single_update_ok_msg_message]" /></li>
						<li class="ok_msg_redirect_url"><label>OK Redirect Url: </label><input class="task_property_field" type="text" name="action[single_update_ok_msg_redirect_url]" /></li>
						<li class="error_msg_message"><label>Error Message: </label><input class="task_property_field" type="text" name="action[single_update_error_msg_message]" /></li>
						<li class="error_msg_redirect_url"><label>Error Redirect Url: </label><input class="task_property_field" type="text" name="action[single_update_error_msg_redirect_url]" /></li>
					</ul>
				</li>
				<li class="single_delete">
					<i class="icon maximize" onClick="PresentationTaskUtil.toggleAdvancedActionSettings(this)"></i>
					<label>Delete Action:</label>
					<input class="task_property_field action_active" type="checkbox" name="action[single_delete]" value="1" />
					<input class="task_property_field confirmation_message" type="text" name="action[single_delete_confirmation_message]" placeHolder="Write here a confirmation message..." />
					
					<ul class="advanced_action_settings">
						<li class="ok_msg_message"><label>Ok Message: </label><input class="task_property_field" type="text" name="action[single_delete_ok_msg_message]" /></li>
						<li class="ok_msg_redirect_url"><label>OK Redirect Url: </label><input class="task_property_field" type="text" name="action[single_delete_ok_msg_redirect_url]" /></li>
						<li class="error_msg_message"><label>Error Message: </label><input class="task_property_field" type="text" name="action[single_delete_error_msg_message]" /></li>
						<li class="error_msg_redirect_url"><label>Error Redirect Url: </label><input class="task_property_field" type="text" name="action[single_delete_error_msg_redirect_url]" /></li>
					</ul>
				</li>
			</ul>
		</li>
		<li class="sub_actions_group multiple_actions">
			<label>Multiple Items Actions:</label>
			<ul>
				<li class="multiple_insert">
					<i class="icon maximize" onClick="PresentationTaskUtil.toggleAdvancedActionSettings(this)"></i>
					<label>Add Action:</label>
					<input class="task_property_field action_active" type="checkbox" name="action[multiple_insert]" value="1" onClick="PresentationTaskUtil.onChangeFormAction(this)" />
					
					<ul class="advanced_action_settings">
						<li class="ok_msg_message"><label>Ok Message: </label><input class="task_property_field" type="text" name="action[multiple_insert_ok_msg_message]" /></li>
						<li class="ok_msg_redirect_url"><label>OK Redirect Url: </label><input class="task_property_field" type="text" name="action[multiple_insert_ok_msg_redirect_url]" /></li>
						<li class="error_msg_message"><label>Error Message: </label><input class="task_property_field" type="text" name="action[multiple_insert_error_msg_message]" /></li>
						<li class="error_msg_redirect_url"><label>Error Redirect Url: </label><input class="task_property_field" type="text" name="action[multiple_insert_error_msg_redirect_url]" /></li>
					</ul>
				</li>
				<li class="multiple_update">
					<i class="icon maximize" onClick="PresentationTaskUtil.toggleAdvancedActionSettings(this)"></i>
					<label>Update Action:</label>
					<input class="task_property_field action_active" type="checkbox" name="action[multiple_update]" value="1" onClick="PresentationTaskUtil.onChangeFormAction(this)" />
					
					<ul class="advanced_action_settings">
						<li class="ok_msg_message"><label>Ok Message: </label><input class="task_property_field" type="text" name="action[multiple_update_ok_msg_message]" /></li>
						<li class="ok_msg_redirect_url"><label>OK Redirect Url: </label><input class="task_property_field" type="text" name="action[multiple_update_ok_msg_redirect_url]" /></li>
						<li class="error_msg_message"><label>Error Message: </label><input class="task_property_field" type="text" name="action[multiple_update_error_msg_message]" /></li>
						<li class="error_msg_redirect_url"><label>Error Redirect Url: </label><input class="task_property_field" type="text" name="action[multiple_update_error_msg_redirect_url]" /></li>
					</ul>
				</li>
				<li class="multiple_delete">
					<i class="icon maximize" onClick="PresentationTaskUtil.toggleAdvancedActionSettings(this)"></i>
					<label>Delete Action:</label>
					<input class="task_property_field action_active" type="checkbox" name="action[multiple_delete]" value="1" />
					<input class="task_property_field confirmation_message" type="text" name="action[multiple_delete_confirmation_message]" placeHolder="Write here a confirmation message..." />
					
					<ul class="advanced_action_settings">
						<li class="ok_msg_message"><label>Ok Message: </label><input class="task_property_field" type="text" name="action[multiple_delete_ok_msg_message]" /></li>
						<li class="ok_msg_redirect_url"><label>OK Redirect Url: </label><input class="task_property_field" type="text" name="action[multiple_delete_ok_msg_redirect_url]" /></li>
						<li class="error_msg_message"><label>Error Message: </label><input class="task_property_field" type="text" name="action[multiple_delete_error_msg_message]" /></li>
						<li class="error_msg_redirect_url"><label>Error Redirect Url: </label><input class="task_property_field" type="text" name="action[multiple_delete_error_msg_redirect_url]" /></li>
					</ul>
				</li>
			</ul>
		</li>
	</ul>
</div>
