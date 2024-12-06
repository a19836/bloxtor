<?php
include_once get_lib("org.phpframework.util.web.MyCurl");

$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");

$change_other_settings_url = $project_url_prefix . "user/change_other_settings";

$notifications = array();

if (!$openai_encryption_key)
	$notifications[] = array(
		"class" => "",
		"icon" => "info",
		"title" => "Artificial Intelligence is disabled!",
		"description" => 'To enable it, please add your OpenAI Key in the <a href="javascript:void(0)" onClick="goTo(this, \'url\', event)" url="' . $change_other_settings_url . '">Manage Permissions/Users</a> panel.',
	);

if (!empty($get_store_notifications_url)) {
	$curl_data = array(
		"url" => $get_store_notifications_url
	);
	$response = MyCurl::getUrlContents($curl_data, "content_json");
	
	if ($response && is_array($response))
		$notifications = array_merge($notifications, $response);
}

echo json_encode($notifications);
die();
?>
