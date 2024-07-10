<?php
interface ICMSProgramInstallationHandler {

	public static function getProgramSettingsHtml();
	public function getStepHtml($step, $extra_settings = null, $post_data = null);
	public function installStep($step, $extra_settings = null, $post_data = null);
	public function validate();
	public function install($user_settings = false);
	public function uninstall();
}
?>
