<?php
$UserAuthenticationHandler->checkPresentationFileAuthentication($entity_path, "access");
UserAuthenticationHandler::checkUsersMaxNum($UserAuthenticationHandler);

if (!is_dir(TEST_UNIT_PATH))
	mkdir(TEST_UNIT_PATH, 0755, true);
?>
