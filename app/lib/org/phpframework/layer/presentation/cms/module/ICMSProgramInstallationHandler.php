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

interface ICMSProgramInstallationHandler {

	public static function getProgramSettingsHtml();
	public function getStepHtml($step, $extra_settings = null, $post_data = null);
	public function installStep($step, $extra_settings = null, $post_data = null);
	public function validate();
	public function install($user_settings = false);
	public function uninstall();
}
?>
