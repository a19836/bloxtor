<?php
/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

class SendEmail {
	private $mail_from;
	private $mail_boundary;
	private $charset;
	
	public function __construct($mail_from, $mail_boundary = false, $encoding = "UTF8") {
		$mf = "";
		$parts = explode(",", $mail_from);
		foreach ($parts as $part)
			$mf .= ($mf ? ", " : "") . (strpos($mail_from, "<") !== false ? $part : "$part <$part>");
		
		$this->mail_from = $mf;
		$this->mail_boundary = $mail_boundary ? $mail_boundary : "NextPart00A_000_1951044531D";
		
		$ue = strtoupper($encoding);
		
		if ($ue == "UTF8" || $ue == "UTF-8")
			$this->charset = array("utf-8", "8");
		else
			$this->charset = array("iso-8859-1", "7");
	}
	
	public function send($mail_to, $subject, $message, $extra_headers = false) {
		$html_cont = $message;
		$text_cont = str_replace(array("<br>", "<br >", "<br/>", "<br />"), "\n", $message);
		$text_cont = strip_tags($text_cont);
	
		$headers = $this->getMultiPartHeader($this->mail_from, $subject, $extra_headers);
		$content = $this->getMultiPartContent($text_cont, $html_cont);
	
		$status = mail($mail_to, $subject, $content, $headers);
		return $status;
	}

	public function getMultiPartContent($text, $html) {
		$charset_0 = isset($this->charset[0]) ? $this->charset[0] : null;
		$charset_1 = isset($this->charset[1]) ? $this->charset[1] : null;
		
		return '------='.$this->mail_boundary.'
Content-Type: text/plain;
	charset="'.$charset_0.'"
Content-Transfer-Encoding: '.$charset_1.'bit

'.$text.'

------='.$this->mail_boundary.'
Content-Type: text/html;
	charset="'.$charset_0.'"
Content-Transfer-Encoding: '.$charset_1.'bit

'.$html.'

------='.$this->mail_boundary.'--';
	}

	public function getMultiPartHeader($mail_from, $subject, $extra_headers = false) {
		$charset_1 = isset($this->charset[1]) ? $this->charset[1] : null;
		
		$headers = 'From: '.$mail_from.'
Subject: '.$subject.'
Content-Transfer-Encoding: '.$charset_1.'bit
MIME-Version: 1.0
Content-Type: multipart/alternative; boundary="----='.$this->mail_boundary.'"';
		
		$return_path_index = false;
		$reply_to_index = false;
		
		if(is_array($extra_headers)) {
			$keys = array_keys($extra_headers);
			$t = count($keys);
			for($i = 0; $i < $t; $i++) {
				$key = $keys[$i];
				$headers .= "\n{$key}: " . $extra_headers[$key];
			
				if(strtolower($key) == "return-path") {
					$return_path_index = $i;
				}
				else if(strtolower($key) == "reply-to") {
					$reply_to_index = $i;
				}
			}
		}
		
		if(!$return_path_index || !$reply_to_index) {
			$return_email = $return_path_index !== false ? $extra_headers[ $keys[$return_path_index] ] : (
				$reply_to_index !== false ? $extra_headers[ $keys[$reply_to_index] ] : false
			);
			
			$headers .= $return_path_index === false ? "\nReturn-Path: ".($return_email ? $return_email : $mail_from) : "";
			$headers .= $reply_to_index === false ? "\nReply-To: ".($return_email ? $return_email : $mail_from) : "";
		}
		
		return $headers;
	}
}
?>
