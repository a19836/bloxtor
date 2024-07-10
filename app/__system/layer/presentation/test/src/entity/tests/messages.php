<?php
//http://jplpinto.localhost/__system/test/tests/messages

//MessageHandler test
$MessageHandler = $PHPFrameWork->getObject("MessageHandler");

$MessageHandler->reportMessage("TEST", "En/EMPTY_EmaIL");
$MessageHandler->reportMessage("TEST2", "UNDEFINED_ID");
$MessageHandler->reportMessage("tESt.sub_Test.messAGes_4.xml", "JUST ONE");

//$messages = $MessageHandler->getMessages("TEST", array("type" => "error"));
//$messages = $MessageHandler->getMessages("TEST");
//$messages = $MessageHandler->getMessages("TEST2");
//$messages = $MessageHandler->getReportedMessages(array("type" => "error"));
$messages = $MessageHandler->getReportedMessages();

//print_r($messages);
echo "<hr/>SHOW MESSAGES<br/><br/>";
foreach($messages as $key => $Message) {
	echo $Message->getId() . " ===> " . $Message->getMessage() . "<br/>";
	//print_r($Message->getAttributes());echo "<br/><br/>";
}
echo "<br><br>";
?>
