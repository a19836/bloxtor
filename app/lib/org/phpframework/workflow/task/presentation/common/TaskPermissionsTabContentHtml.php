<div class="users_perms">
	<table>
		<thead>
			<tr>
				<th class="user_type_id">User</th>
				<th class="activity_id">Permission</th>
				<th class="actions">
					<i class="icon add" onClick="PresentationTaskUtil.addUserPerm(this)"></i>
				</th>
			</tr>
		</thead>
		<tbody index_prefix="users_perms">
			<tr class="no_users"><td colspan="3">There are no configured users...</td></tr>
		</tbody>
	</table>
</div>

<div class="users_management_admin_panel">
	<a href="javascript:void(0)" onClick="PresentationTaskUtil.openUsersManagementAdminPanelPopup(this)">Users Management Admin Panel</a>
	
	<div class="users_management_admin_panel_popup myfancypopup with_iframe_title">
		<iframe></iframe>
	</div>
</div>
