<html>
<head>
	<title> 500 Server Error</title>
</head>
<body style="text-align:center; background-color:#F7F7F7; ">
<h2>Sorry... Server Error!</h2>
<h2>Server Error - The server detected a syntax error in the client's request...</h2>
<?php
$ip = getenv ("REMOTE_ADDR");

$requri = getenv ("REQUEST_URI");
$servname = getenv ("SERVER_NAME");
$combine = "IP: <b>" . $ip . "</b> tried to load <b>http://" . $servname . $requri . "</b>";

$httpref = getenv ("HTTP_REFERER");
$httpagent = getenv ("HTTP_USER_AGENT");

$today = date("D M j Y g:i:s a T");

//$note = "Yes you have been bagged and tagged for a making an illegal move" ;

$message = "($today) \n
<br><br>
$combine, with the following navigator:<br> \n
User Agent = $httpagent \n<br> \n
Requested File = $requested_file \n
<h2> $note </h2>\n";

echo $message;

$EVC->setTemplate("empty");
?>
</body>
</html>
