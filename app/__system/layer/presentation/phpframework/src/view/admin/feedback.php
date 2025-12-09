<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Original Bloxtor Repo: https://github.com/a19836/bloxtor
 *
 * YOU ARE NOT AUTHORIZED TO MODIFY OR REMOVE ANY PART OF THIS NOTICE!
 */

$logged_user = $UserAuthenticationHandler->auth["user_data"]["username"];
$logged_name = $UserAuthenticationHandler->auth["user_data"]["name"];

$max_upload_files_size = 1024 * 1024 * 20; //20MB in bytes
$send_feedback_url = $send_email_action_url;
$from_framework = "123987"; //123987 is set in the file: app/__system/layer/presentation/phpframework/src/view/admin/feedback.php

$head = '
<!-- Add Fontawsome Icons CSS -->
<link rel="stylesheet" href="' . $project_common_url_prefix . 'vendor/fontawesome/css/all.min.css">

<!-- Add Icon CSS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/icons.css" type="text/css" charset="utf-8" />

<!-- Add Layout CSS file -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/layout.css" type="text/css" charset="utf-8" />

<!-- Add Local CSS and JS -->
<link rel="stylesheet" href="' . $project_url_prefix . 'css/admin/feedback.css" type="text/css" charset="utf-8" />
<script language="javascript" type="text/javascript" src="' . $project_url_prefix . 'js/admin/feedback.js"></script>
';

$main_content = '
<div class="top_bar' . ($popup ? " in_popup" : "") . '">
	<header>
		<div class="title">Feedback - Send us your questions</div>
	</header>
</div>
<div class="feedback">
	<div class="title">We welcome your valuable feedback and suggestions regarding the framework.<br/>Please feel free to share your insights, optimizations, or report any errors you may encounter, as this will greatly contribute to our ongoing efforts to enhance and refine the system.</div>
	
	<form method="post" enctype="multipart/form-data" onSubmit="return MyJSLib.FormHandler.formCheck(this) && sendEmail(this);" action="' . $send_feedback_url . '">
		<input type="hidden" name="MAX_FILE_SIZE" value="' . $max_upload_files_size . '">
		<input type="hidden" name="redirect" value="' . urlencode($project_url_prefix . "admin/feedback" . ($popup ? "?popup=$popup" : "")) . '">
		<input type="hidden" name="from_framework" value="' . $from_framework . '">
		
		<input type="hidden" name="msg[subject]" value="Feedback from developer - directly from the framework">
		<input type="hidden" name="msg[framework_url]" value="' . str_replace('"', '&quot;', $project_url_prefix) . '">
		<input type="hidden" name="msg[logged_user]" value="' . $logged_user . '">
		<input type="hidden" name="msg[logged_name]" value="' . $logged_name . '">
		
		<div class="name">
			<label>Name: <span class="mandatory">*</span></label>
			<input type="text" name="msg[name]" value="' . (isset($data["name"]) ? $data["name"] : "") . '" data-allow-null="0" data-validation-message="Name cannot be undefined." placeHolder="Please write your name here..." required>
		</div>
		<div class="email">
			<label>Email: <span class="mandatory">*</span></label>
			<input type="email" name="msg[email]" value="' . (isset($data["email"]) ? $data["email"] : "") . '" data-allow-null="0" data-validation-message="Invalid email." data-validation-type="email" placeHolder="Please write your email here..." required>
		</div>
		<div class="phone">
			<label>Phone:</label>
			<input type="text" name="msg[phone]" value="' . (isset($data["phone"]) ? $data["phone"] : "") . '" data-validation-message="Invalid phone." data-validation-type="phone" placeholder="Please write your phone here...">
		</div>
		<div class="message">
			<label>Message: <span class="mandatory">*</span></label>
			<textarea name="msg[message]" data-allow-null="0" data-validation-message="Message cannot be undefined and must have at least 20 words." data-min-words="20" placeHolder="Please write here your question or message..." rows="5" required>' . (isset($data["message"]) ? $data["message"] : "") . '</textarea>
		</div>
		<div class="attachments">
			<label>Attachments: <span class="icon add" onClick="addAttachment(this)">Add</span></label>
			<ul>
				<li class="empty">Without attachments...</li>
			</ul>
		</div>
		<div class="buttons">
			<input type="submit" value="Send">
		</div>
	</form>
	
	<div class="icon loading"></div>
</div>';
?>
