<?php
include_once get_lib("org.phpframework.util.web.SendEmail");
include_once get_lib("org.phpframework.util.web.SmtpEmail");

class SendEmailHandler {
	
	public static function sendEmail($settings) {
		$mail_boundary = isset($settings["mail_boundary"]) ? $settings["mail_boundary"] : null;
		$encoding = !empty($settings["encoding"]) ? $settings["encoding"] : 'UTF8';
		
		$from_email = isset($settings["from_email"]) ? $settings["from_email"] : null;
		$reply_email = isset($settings["reply_email"]) ? $settings["reply_email"] : null;
		$to_email = isset($settings["to_email"]) ? $settings["to_email"] : null;
		$subject = isset($settings["subject"]) ? $settings["subject"] : null;
		$message = isset($settings["message"]) ? $settings["message"] : null;
		$extra_headers = isset($settings["extra_headers"]) ? $settings["extra_headers"] : null;
		
		if ($reply_email && (!$extra_headers || stripos($extra_headers, "Reply-To") === false))
			$extra_headers = trim($extra_headers) . "\r\nReply-To: $reply_email";
		
		$SendEmail = new SendEmail($from_email, $mail_boundary, $encoding);
		$status = $SendEmail->send($to_email, $subject, $message, $extra_headers);
		
		return $status;
	}
	
	public static function sendSMTPEmail($settings) {
		$smtp_host = isset($settings["smtp_host"]) ? $settings["smtp_host"] : null;
		$smtp_port = isset($settings["smtp_port"]) ? $settings["smtp_port"] : null;
		$smtp_user = isset($settings["smtp_user"]) ? $settings["smtp_user"] : null;
		$smtp_pass = isset($settings["smtp_pass"]) ? $settings["smtp_pass"] : null;
		$smtp_secure = isset($settings["smtp_secure"]) ? $settings["smtp_secure"] : null;
		$smtp_encoding = !empty($settings["smtp_encoding"]) ? $settings["smtp_encoding"] : 'utf-8';
		
		$from_email = isset($settings["from_email"]) ? $settings["from_email"] : null;
		$reply_email = isset($settings["reply_email"]) ? $settings["reply_email"] : null;
		$to_email = isset($settings["to_email"]) ? $settings["to_email"] : null;
		$subject = isset($settings["subject"]) ? $settings["subject"] : null;
		$message = isset($settings["message"]) ? $settings["message"] : null;
		$debug = !empty($settings["debug"]) ? $settings["debug"] : 0;
		
		$SmtpEmail = new SmtpEmail($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtp_secure, $smtp_encoding);
		$SmtpEmail->setDebug($debug);
		$status = $SmtpEmail->send($from_email, null, $reply_email, null, $to_email, null, $subject, $message);
		
		return $status;
	}
}
?>
