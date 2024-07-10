<?php
$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Icons CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local JS and CSS files -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/db/db_dump.css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/db/db_dump.js"></script>';

$main_content .= '<div class="db_dump">
	<div class="top_bar">
		<header>
			<div class="title">DB Dump for DB: \'' . $bean_name . '\'</div>
			<ul>
				<li class="execute" data-title="Execute Dump"><a onClick="submitForm(this)"><i class="icon continue"></i> Execute Dump</a></li>
			</ul>
		</header>
	</div>';

if ($_POST && $error_messsage)
	$main_content .= '<div class="error">' . $error_messsage . '</div>';

$main_content .= '	
	<form class="with_top_bar_section" method="post">
		<div class="tables">
			<label>Select the tables you wish to dump:</label>
			<div class="select_buttons">
				<a onclick="$(\'.tables ul li input\').attr(\'checked\', \'checked\')">Select All</a>
				<a onclick="$(\'.tables ul li input\').removeAttr(\'checked\')">Deselect All</a>
			</div>
			
			<ul>';

if ($tables)
	foreach ($tables as $table) {
		$main_content .= '<li title="' . $table["name"] . '">
				<input type="checkbox" name="tables[]" value="' . $table["name"] . '"' . (!$_POST && (!$selected_table || $selected_table == $table["name"]) ? ' checked' : '') . ' /> ' . $table["name"] . '
		</li>';
	}

$main_content .= '
			</ul>
		</div>
		
		<div class="settings">
			<label>Settings:</label>
			
			<div class="compress">
				<label>Compression:</label>
				<select name="settings[compress]">
					<option value="">-- None --</option>
					<option value="bzip2">BZip 2</option>
					<option value="gzip">GZip</option>
					<option value="gzipstream">GZip Stream</option>
					<option value="zip">Zip</option>
				</select>
			</div>
			<div class="no-data">
				<label>No data:</label>
				<input type="checkbox" name="settings[no-data]" value="1" />
			</div>
			<div class="reset-auto-increment">
				<label>Reset auto-increment:</label>
				<input type="checkbox" name="settings[reset-auto-increment]" value="1" />
			</div>
			<div class="add-drop-database">
				<label>Add drop database:</label>
				<input type="checkbox" name="settings[add-drop-database]" value="1" />
			</div>
			<div class="add-drop-table">
				<label>Add drop table:</label>
				<input type="checkbox" name="settings[add-drop-table]" value="1" />
			</div>
			<div class="add-drop-trigger">
				<label>Add drop trigger:</label>
				<input type="checkbox" name="settings[add-drop-trigger]" value="1" />
			</div>
			<div class="add-drop-routine">
				<label>Add drop routine:</label>
				<input type="checkbox" name="settings[add-drop-routine]" value="1" />
			</div>
			<div class="add-drop-event">
				<label>Add drop event:</label>
				<input type="checkbox" name="settings[add-drop-event]" value="1" />
			</div>
			<div class="add-locks">
				<label>Add locks:</label>
				<input type="checkbox" name="settings[add-locks]" value="1" />
			</div>
			<div class="complete-insert">
				<label>Complete insert:</label>
				<input type="checkbox" name="settings[complete-insert]" value="1" checked />
			</div>
			<div class="extended-insert">
				<label>Extended insert:</label>
				<input type="checkbox" name="settings[extended-insert]" value="1" />
			</div>
			<div class="disable-keys">
				<label>Disable keys:</label>
				<input type="checkbox" name="settings[disable-keys]" value="1" checked />
			</div>
			<div class="events">
				<label>Events:</label>
				<input type="checkbox" name="settings[events]" value="1" />
			</div>
			<div class="hex-blob">
				<label>Hex blob:</label>
				<input type="checkbox" name="settings[hex-blob]" value="1" />
			</div>
			<div class="insert-ignore">
				<label>Insert ignore:</label>
				<input type="checkbox" name="settings[insert-ignore]" value="1" checked />
			</div>
			<div class="no-autocommit">
				<label>No autocommit:</label>
				<input type="checkbox" name="settings[no-autocommit]" value="1" />
			</div>
			<div class="no-create-info">
				<label>No create info:</label>
				<input type="checkbox" name="settings[no-create-info]" value="1" />
			</div>
			<div class="lock-tables">
				<label>Lock tables:</label>
				<input type="checkbox" name="settings[lock-tables]" value="1" checked />
			</div>
			<div class="routines">
				<label>Routines and procedures:</label>
				<input type="checkbox" name="settings[routines]" value="1" checked />
			</div>
			<div class="single-transaction">
				<label>Single transaction:</label>
				<input type="checkbox" name="settings[single-transaction]" value="1" />
			</div>
			<div class="skip-triggers">
				<label>Skip triggers:</label>
				<input type="checkbox" name="settings[skip-triggers]" value="1" />
			</div>
			<div class="skip-tz-utc">
				<label>Skip tz utc:</label>
				<input type="checkbox" name="settings[skip-tz-utc]" value="1" checked />
			</div>
			<div class="skip-comments">
				<label>Skip comments:</label>
				<input type="checkbox" name="settings[skip-comments]" value="1" checked />
			</div>
			<div class="skip-dump-date">
				<label>Skip dump date:</label>
				<input type="checkbox" name="settings[skip-dump-date]" value="1" />
			</div>
			<div class="skip-definer">
				<label>Skip definer:</label>
				<input type="checkbox" name="settings[skip-definer]" value="1" />
			</div>
			<div class="where">
				<label>Table where statement:</label>
				<input type="text" name="settings[where]" value="" />
			</div>
		</div>
	</form>
</div>
';
?>
