<?php
// Connecting constants and autoloader
require_once __DIR__. '/src/init.php';

// Create formatting options
$Options = new \Phpcf\Options();

// Optional settings (there are default values ​​for all)
$Options->setTabSequence("\t"); // Your 3-4 spaces or Tab
//$Options->setMaxLineLength(130); // 120 by default
//$Options->setCustomStyle(__DIR__ . '/styles/default/'); // path to the directory with your styles
//$Options->toggleCyrillicFilter(false); // toggle the filter of Cyrillic characters
//$Options->usePure(true); // force use of the version without extension

$Formatter = new \Phpcf\Formatter($Options);

// Format the file
#$Formatter->formatFile('file.php'); // whole file
#$Formatter->formatFile('file.php:1-40,65'); // range of lines

// Format string
$code = '<?php phpinfo()';
$code = '<?php $block_id = $EVC->getCMSLayer()->getCMSBlockLayer()->getBlockIdFromFilePath(__FILE__);
//must be the same than this file name.
$block_settings[$block_id] = array("maximum_login_attempts_to_block_user" => 5, "show_captcha" => 1, "maximum_login_attempts_to_show_captcha" => 3, "redirect_page_url" => "{$project_url_prefix}/", "welcoming_message" => "Welcome user: \'#username#\'!", "register_page_url" => "{$project_url_prefix}auth/register", "forgot_credentials_page_url" => "{$project_url_prefix}auth/forgot_credentials", "single_sign_on_page_url" => "{$project_url_prefix}auth/single_sign_on", "style_type" => "template", "block_class" => "login", "css" => ".login .buttons {\n    margin-bottom:10px;\n}\n.login .register {\n    float:left;\n}\n.login .forgot_credentials {
    float:right;
}\n.login .single_sign_on {\n    margin-left:auto;\n\tmargin-right:auto;\n\ttext-align:center;\n}", "js" => "", "show_username" => 1, "username_default_value" => "", "fields" => array("username" => array("field" => array("disable_field_group" => "", "class" => "username", "label" => array("type" => "label", "value" => "Usernaeesme: ", "class" => "", "title" => "", "previous_html" => "", "next_html" => ""), "input" => array("type" => "text", "class" => "", "place_holder" => "", "href" => "", "target" => "", "src" => "", "title" => "", "previous_html" => "", "next_html" => "", "confirmation" => "", "confirmation_message" => "", "allow_null" => "", "allow_javascript" => "", "validation_label" => "", "validation_message" => "Username cannot be undefined.", "validation_type" => "", "validation_regex" => "", "min_length" => "", "max_length" => "", "min_value" => "", "max_value" => "", "min_words" => "", "max_words" => ""))), "password" => array("field" => array("disable_field_group" => "", "class" => "password", "label" => array("type" => "label", "value" => "Password: ", "class" => "", "title" => "", "previous_html" => "", "next_html" => ""), "input" => array("type" => "password", "class" => "", "place_holder" => "", "href" => "", "target" => "", "src" => "", "title" => "", "previous_html" => "", "next_html" => "", "confirmation" => "", "confirmation_message" => "", "allow_null" => "", "allow_javascript" => "", "validation_label" => "", "validation_message" => "Password cannot be undefined.", "validation_type" => "", "validation_regex" => "", "min_length" => "", "max_length" => "", "min_value" => "", "max_value" => "", "min_words" => "", "max_words" => "")))), "show_password" => 1, "password_default_value" => "", "register_attribute_label" => "Register?", "forgot_credentials_attribute_label" => "Forgot Password?", "single_sign_on_attribute_label" => "Single Sign On?", "do_not_encrypt_password" => 1, "user_environments" => array(""));
$EVC->getCMSLayer()->getCMSBlockLayer()->createBlock("user/login", $block_id, $block_settings[$block_id]);';
$Result = $Formatter->format($code); // whole line with code
#$Result = $Formatter->format($code, [1, 2, 10]); // line numbers for formatting

// All of the above formatting functions return a \Phpcf\FormattingResult object

echo $Result->getContent(); // string with formatted code
echo "\n wasFormatted:".$Result->wasFormatted(); // bool, whether the code was changed
echo "\n getIssues:".print_r($Result->getIssues(), 1); // array, textual description of code formatting problems
echo "\n getError:".$Result->getError(); // \ Exception | null error while formatting the code

