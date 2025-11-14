# JQuery My Fancy Light Box

## Overview

**JQuery My Fancy Light Box** is a lightweight JavaScript library to display popups.

Requirements:
- jquery library

---

## Usage

```html
<html>
<head>
	<!-- Add jquery lib -->
	<script language="javascript" type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
	
	<!-- Add Fancy LighBox lib -->
	<link rel="stylesheet" href="css/style.css" type="text/css" charset="utf-8" media="screen, projection" />
	<script language="javascript" type="text/javascript" src="js/jquery.myfancybox.js"></script>
	
	<style>
	a {margin-bottom:10px; display:block;}
	.myfancypopup.yellow { background:yellow; }
	</style>
	<script>
	var MyFancyPopup2 = null;
	
	function showPopup1() {
		MyFancyPopup.init({
			elementToShow: $('#popup1')
		});
		MyFancyPopup.showPopup({
			not_draggable: true
		});
	}
	function showPopup2() {
		MyFancyPopup.init({
			elementToShow: $('#popup2')
		});
		MyFancyPopup.showPopup();
	}
	function showPopup3() {
		if (!MyFancyPopup2) {
			MyFancyPopup2 = new MyFancyPopupClass();
			MyFancyPopup2.init({
				elementToShow: $('#popup3'),
				parentElement: $('section').first(),
				type: "iframe",
				url: "iframe.html",
				refreshIframeOnOpen: true,
				popupClass: "yellow",
				onIframeOnLoad: function() {
					MyFancyPopup.hideLoading();
				}
			});
		}
		
		MyFancyPopup2.showPopup();
	}
	</script>
</head>	
<body>
	<div id="popup1" class="myfancypopup">
		<div class="title">NOT Draggable Popup</div>
		<p class="content">CONTENT HERE</p>
	</div>
	
	<div id="popup2" class="myfancypopup">
		<h1>Draggable Popup with inline iframe</h1>
		<iframe src="iframe.html"></iframe>
	</div>
	
	<section class="some_node">
		<div id="popup3" class="myfancypopup">
			<h1>Popup with auto iframe</h1>
		</div>
	</section>
	
	<a href="#" onClick="showPopup1();">CLICK HERE TO SEE POPUP 1</a>
	<a href="#" onClick="showPopup2();">CLICK HERE TO SEE POPUP 2</a>
	<a href="#" onClick="showPopup3();">CLICK HERE TO SEE POPUP 3</a>
</body>
</html>
```

## Other calls

Create new MyFancyPopup object:
```
var MyFancyPopup2 = new MyFancyPopupClass();
```

Initialize MyFancyPopup based in a html element:
```
MyFancyPopup.init({
	elementToShow: $('#some_selector'), //html node that should be converted in a popup
	parentElement: document, //node where the overlay and loading will be appended
	onOpen: function() { ... }, //callback on popup open
	onClose: function() { ... }, //callback on popup close
	beforeClose: function() { ... }, //callback on before popup close
	popupClass: "some_class", //this call will be used in the elementToShow, loading and overlay nodes.
});
```

Initialize MyFancyPopup based in a html element with a dynamic iframe:
```
MyFancyPopup.init({
	elementToShow: $('#some_selector'), //html node that should be converted in a popup
	parentElement: $('section').first(), //node where the overlay and loading will be appended
	type: "iframe",
	url: "iframe.html", //url for the iframe
	refreshIframeOnOpen: true, //if true, every time the popup open, it will refresh the iframe url
	onIframeUnLoad: function() { ... }, //callback on iframe unload
	onIframeBeforeUnLoad: function() { ... }, //callback on before iframe unload
	onIframeOnLoad: function() { ... }, //callback on iframe load
	onOpen: function() { ... }, //callback on popup open
	onClose: function() { ... }, //callback on popup close
	beforeClose: function() { ... }, //callback on before popup close
	popupClass: "some_class", //this call will be used in the elementToShow, loading and overlay nodes.
});
```

Get initial settings when calling the init method:
```
MyFancyPopup.settings;
```

Show/open popup:
```
var opts = {
	not_draggable: true //optional: if true, popup is not draggable. 
}
MyFancyPopup.showPopup(opts); //opts are optional
```

Reshow popup by hidding and showing it again:
```
var opts = {
	not_draggable: true //optional: if true, popup is not draggable. 
}
MyFancyPopup.reshowPopup(opts); //opts are optional
```


Hide popup:
```
MyFancyPopup.hidePopup();
```

Update popup position and overlay:
```
MyFancyPopup.updatePopup();
```

Destroy popup and corresponding handlers:
```
MyFancyPopup.destroyPopup();
```

Checks if popup is opened:
```
var shown = MyFancyPopup.isPopupOpened();
```

Center popup:
```
MyFancyPopup.centerPopupHtmlElm(elm, parent); //elm and parent are optional
```

Get popup element corresponding to elementToShow:
```
var popup = self.getPopup();
```

Reset the Z-index of the pop-up window by moving it to the front:
```
MyFancyPopup.reinitZIndex();
```

Set an option:
```
MyFancyPopup.setOption(option_name, option_value)
```

Get an option:
```
var option = MyFancyPopup.getOption = function(option_name)
```

Get an element offset (top and left positions):
```
var offset = MyFancyPopup.getOffset(elm);
```

Get overlay element:
```
var overlay = MyFancyPopup.getOverlay();
```

Show overlay:
```
MyFancyPopup.showOverlay();
```

hide overlay:
```
MyFancyPopup.hideOverlay();
```

Resize overlay and fits it to the screen or parent element depending of its css:
```
MyFancyPopup.resizeOverlay();
```

Get loading icon node:
```
var loading = MyFancyPopup.getLoading();
```

Show loading icon:
```
MyFancyPopup.showLoading();
```

Hide loading icon:
```
MyFancyPopup.hideLoading();
```

Get popup close button:
```
var btn = MyFancyPopup.getPopupCloseButton();
```

Creates a close button if not exists and set the correspondent handler to close popup:
```
MyFancyPopup.prepareElementCloseButton();
```

Creates iframe node if not exists and set the correspondent handlers and classes:
```
MyFancyPopup.prepareElementIfIframe();
```

