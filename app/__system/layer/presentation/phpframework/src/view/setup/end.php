<?php
$manage_user_url = $user_id ? $project_url_prefix . 'user/edit_user?user_id=' . $user_id : $project_url_prefix . 'user/manage_users';

$main_content = '<div id="end">
		<div class="title">
			<h1>Congratulations. Setup is done!</h1>
		</div>
		<div class="info">
			Please don\'t forget to delete the setup.php file and change your login password <a href="' . $manage_user_url . '">here</a>.
			<br/>
			<br/>
			To go to your project please click here: <a href="' . $project_url_prefix . '../">here</a>
			<br/>
			To go to the admin panel please click here: <a href="' . $project_url_prefix . 'phpframework/admin/">here</a>
		</div>
</div>';

$continue_function = "";
?>
