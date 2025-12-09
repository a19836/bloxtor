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

//load external lib dynamically bc is a LGPL licence, which means our framework must work without this library also. This means if the user doesn't have this library installed or if he removes it, our code still needs to work.
if (file_exists( get_lib("lib.vendor.phpmailer.vendor.autoload") ))
	include_once get_lib("lib.vendor.phpmailer.vendor.autoload");

class SmtpEmail {
	private $smtp_host;
	private $smtp_port;
	private $smtp_user;
	private $smtp_pass;
	private $smtp_secure;
	private $smtp_encoding;
	
	private $PHPMailer = null;
	private $phpmailer_class_exists = false;
	
	public function __construct($smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtp_secure, $smtp_encoding = 'utf-8') {
		$this->smtp_host = $smtp_host;
		$this->smtp_port = $smtp_port;
		$this->smtp_user = $smtp_user;
		$this->smtp_pass = $smtp_pass;
		$this->smtp_secure = $smtp_secure;//ssl, tls or null
		$this->smtp_encoding = $smtp_encoding;
		
		$this->phpmailer_class_exists = class_exists("PHPMailer") || class_exists("PHPMailer\PHPMailer\PHPMailer");
	}
	
	public function setDebug($debug, $output = 'echo') {
		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$this->PHPMailer->SMTPDebug = $debug;
		
		//Ask for HTML-friendly debug output
		$this->PHPMailer->Debugoutput = $output;
	}
	
	public function send($from_email, $from_name, $reply_to_email, $reply_to_name, $to_email, $to_name, $subject, $message, $attachments = null) {
		if (!$this->phpmailer_class_exists)
			return false;
		
		$this->prepareEmailAndName($from_email, $from_name);
		$this->prepareEmailAndName($reply_to_email, $reply_to_name);
		
		$offset = 0;
		$to_emails = array();
		
		do {
			$te = $to_email;
			$tn = $to_name;
			$offset = $this->prepareEmailAndName($te, $tn, $offset);
			
			if ($offset !== false) {
				$offset = strpos($to_email, ",", $offset);
				$offset = $offset !== false ? $offset + 1 : false;
			}
			
			if (self::getEmail($te))
				$to_emails[] = array($te, $tn);
		}
		while ($offset !== false && $offset < strlen($to_email));
		
		if (empty($to_emails) || !self::getEmail($from_email)) {
			$this->PHPMailer->ErrorInfo  = "Invalid email format";
			return false;
		}
		
		if (!self::getEmail($reply_to_email))
			$reply_to_email = $from_email;
		
		//Create a new PHPMailer instance
		$this->PHPMailer = class_exists("PHPMailer") ? new PHPMailer : new PHPMailer\PHPMailer\PHPMailer;
		//Tell PHPMailer to use SMTP
		$this->PHPMailer->isSMTP();
		//Set encoding
		$this->PHPMailer->CharSet = $this->smtp_encoding;
		//Set the hostname of the mail server
		$this->PHPMailer->Host = $this->smtp_host;//"mail.example.com";
		//Set the SMTP port number - likely to be 25, 465 or 587
		$this->PHPMailer->Port = $this->smtp_port;//25;
		//Set the prefix to the server
		//$mail->set('SMTPSecure', 'tls');
		$this->PHPMailer->SMTPSecure = $this->smtp_secure ? $this->smtp_secure : null;
		//Whether to use SMTP authentication
		$this->PHPMailer->SMTPAuth = $this->smtp_user ? true : false;
		//Username to use for SMTP authentication
		$this->PHPMailer->Username = $this->smtp_user;//"yourname@example.com";
		//Password to use for SMTP authentication
		$this->PHPMailer->Password = $this->smtp_pass;//"yourpassword";
		//Set who the message is to be sent from
		$this->PHPMailer->setFrom($from_email, $from_name);
		//Set an alternative reply-to address
		$this->PHPMailer->addReplyTo($reply_to_email, $reply_to_name);
		
		//Set who the message is to be sent to
		foreach ($to_emails as $to_email)
			$this->PHPMailer->addAddress($to_email[0], $to_email[1]);
		
		//Set the subject line
		$this->PHPMailer->Subject = $subject;
		
		//Read an HTML message body from an external file, convert referenced images to embedded,
		//convert HTML into a basic plain-text alternative body
		//$this->PHPMailer->msgHTML(file_get_contents('contents.html'), dirname(__FILE__));
		
		$m = preg_replace("/<br\s*\/?>/", "", $message); //only allow br tags
		$is_html = $m != strip_tags($m);
		
		if ($is_html) {
			$this->PHPMailer->ContentType = 'text/html';
			
			$message = trim(str_replace(array("\n", "\r"), "", $message));
			$this->PHPMailer->msgHTML( nl2br($message) );
			
			//Set Body directly instead of calling msgHTML
			//$this->PHPMailer->Body = $message;
			//$this->PHPMailer->AltBody = strip_tags($message);//'This is a plain-text message body';
		}
		else {
			$this->PHPMailer->msgHTML( nl2br($message) );
			//Set Body directly instead of calling msgHTML
			//$this->PHPMailer->Body = $message;
			//Replace the plain text body with one created manually
			$this->PHPMailer->AltBody = strip_tags($message);//'This is a plain-text message body';
		}
		
		//Attach files
		if ($attachments) {
			$attachments = is_array($attachments) ? $attachments : array($attachments);
			
			for ($i = 0, $t = count($attachments); $i < $t; $i++) {
				$attachment = $attachments[$i];
				$attachment_path = $attachment;
				$attachment_name = "";
				
				if (is_array($attachment)) {
					$attachment_path = isset($attachment["path"]) ? $attachment["path"] : null;
					$attachment_name = isset($attachment["name"]) ? $attachment["name"] : null;
				}
				
				if ($attachment_path && file_exists($attachment_path))
					$this->PHPMailer->addAttachment($attachment_path, $attachment_name);
			}
		}
		
		//Attach an image file
		//$this->PHPMailer->addAttachment('images/phpmailer_mini.png', 'PHP Mailer Mini logo');

		//send the message, check for errors
		return $this->PHPMailer->send() ? true : false;
	}
	
	public function getPHPMailer() { return $this->PHPMailer; } //be careful using this function, bc the $this->PHPMailer can be null if the $this->phpmailer_class_exists is false.
	
	public function getErrorInfo() { 
		return $this->phpmailer_class_exists ? $this->PHPMailer->ErrorInfo : "PHPMailer library is not loaded, wasn't installed or doesn't exists!";
	}
	
	public static function getEmail($text) {
		$regex = '/^(?:([^<>]+)\s*<)?([a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,})>?$/i';
		
		return is_string($text) && preg_match($regex, $text, $m) ? $m[2] : false;
	}

	private function prepareEmailAndName(&$email, &$name, $offset = 0) {
		$sp = strpos($email, "<", $offset);
			
		if ($sp !== false) {
			$ep = strpos($email, ">", $sp);
			$ep = $ep === false ? strlen($email) : $ep;
			
			$name = trim($name);
			$name = empty($name) ? trim(substr($email, $offset, $sp - $offset)) : $name;
			$email = trim(substr($email, $sp + 1, $ep - $sp - 1));
			
			$offset = $ep;
		}
		else
			$offset = strlen($email);
		
		return $offset;
	}
}
?>
