# JQuery My Status Message

> Original Repos:   
> - JQuery My Status Message: https://github.com/a19836/jquerymystatusmessage/   
> - Bloxtor: https://github.com/a19836/bloxtor/

## Overview

**JQuery My Status Message** is a lightweight JavaScript library for displaying info and error messages elegantly.

Check out a live example by opening [index.html](index.html).

Requirements:
- jquery library

---

## Usage

```html
<html>
<head>
	<!-- Add jquery lib -->
	<script language="javascript" type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
	
	<!-- Add statusmessage lib -->
	<link rel="stylesheet" href="css/style.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="js/statusmessage.js"></script>
	
	<style>
		body {
			font-family:arial;
		}
		.top_messages {
			position:fixed;
			top:0;
			left:0;
			right:0;
			bottom:auto;
			padding-top:0;
		}
	</style>
</head>
<body>
	<h1>Simple examples:</h1>
	<div>
		<button onClick="showMessage()">Show bottom message with default timeout (5s)</button>
		<br/>
		<br/>
		<button onClick="showError()">Show bottom error with default timeout (5s)</button>
		<br/>
		<br/>
		<button onClick="showBottomMessageWithTimeout()">Show bottom message with timeout 2s</button>
		<br/>
		<br/>
		<button onClick="showTopErrorWithTimeout()">Show top error with timeout 2s</button>
	</div>
	
	<script>
	function showMessage() {
		StatusMessageHandler.showMessage("Info Message");
	}
	function showError() {
		StatusMessageHandler.showError("Error Message");
	}
	function showBottomMessageWithTimeout() {
		StatusMessageHandler.showMessage("Bottom Message with timeout", "", "", 2000);
	}
	function showTopErrorWithTimeout() {
		StatusMessageHandler.showError("Bottom Message with timeout", "", "top_messages", 2000);
	}
	</script>
</body>
</html>
```

## Other calls

Get the main html element with the "status_message" class or other defined:
```
var elm = StatusMessageHandler.getMessageHtmlObj(message_html_obj_class); //optional: message_html_obj_class
```

Show a message text and add message_class to the the main html element and the message_html_obj_class to the children node showing the text. If this function is called multiple times, then the messages will be appended:
```
var elm = StatusMessageHandler.showMessage(message, message_class, message_html_obj_class, timeout); //optional: message_class, message_html_obj_class, timeout
```

Show an error text and add message_class to the the main html element and the message_html_obj_class to the children node showing the text. If this function is called multiple times, then the messages will be appended:
```
var elm = StatusMessageHandler.showError(message, message_class, message_html_obj_class, timeout); //optional: message_class, message_html_obj_class, timeout
```

Get the node showing the text with the "message_class" class or other defined:
```
var elm = StatusMessageHandler.getMessageElement(message, message_class, message_html_obj_class); //optional: message_html_obj_class
```

Remove a node message:
```
StatusMessageHandler.removeMessage(elm);
```

Remove the latest shown node message, with the type "info" or "error" and message_html_obj_class class:
```
StatusMessageHandler.removeLastShownMessage(type, message_html_obj_class); //optional: message_html_obj_class
```

Remove all shown nodes messages based in the message_html_obj_class class:
```
StatusMessageHandler.removeMessages(type, message_html_obj_class); //optional: message_html_obj_class
```

Get some elements of the StatusMessageHandler:
```
var message_html_obj = StatusMessageHandler.message_html_obj; //get current shown message html element
var other_message_html_objs = StatusMessageHandler.other_message_html_objs; //get the list of html elements created to show messages
```

