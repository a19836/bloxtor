# JQuery My Context Menu

> Original Repos:   
> - JQuery My Context Menu: https://github.com/a19836/jquerymycontextmenu/   
> - Bloxtor: https://github.com/a19836/bloxtor/

## Overview

**JQuery My Context Menu** is a lightweight JavaScript library for displaying user-defined context menus on right-click.

Requirements:
- jquery library

---

## Usage

```html
<html>
<head>
	<!-- Add jquery lib -->
	<script language="javascript" type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.1/jquery.min.js"></script>
	
	<!-- Add MyContextMenu lib -->
	<link rel="stylesheet" type="text/css" href="css/style.css" />
	<script type="text/javascript" src="js/jquery.mycontextmenu.js"></script>
</head>
<body>
	<p>Right Click on the "Menu 1" link and image</p>
	<p><a class="mylinks" href="#">Menu 1</a></p>
	<p>Menu with image <img src="https://www.google.com/images/branding/googlelogo/1x/googlelogo_color_272x92dp.png" /></p>
	
	<!--HTML for Context Menu 1-->
	<ul id="contextmenu1" class="mycontextmenu">
		<li><a href="#">Item 1a</a></li>
		<li><a href="#">Item 2a</a></li>
		<li><a href="#">Item Folder 3a</a>
			<ul>
			<li><a href="#">Sub Item 3.1a</a></li>
			<li><a href="#">Sub Item 3.2a</a></li>
			<li><a href="#">Sub Item 3.3a</a></li>
			<li><a href="#">Sub Item 3.4a</a></li>
			</ul>
		</li>
		<li><a href="#">Item 4a</a></li>
		<li><a href="#">Item Folder 5a</a>
			<ul>
			<li><a href="#">Sub Item 5.1a</a></li>
			<li><a href="#">Item Folder 5.2a</a>
				<ul>
				<li><a href="#">Sub Item 5.2.1a</a></li>
				<li><a href="#">Sub Item 5.2.2a</a></li>
				<li><a href="#">Sub Item 5.2.3a</a></li>
				<li><a href="#">Sub Item 5.2.4a</a></li>
				</ul>
			</li>
			</ul>
		</li>
		<li><a href="#">Item 6a</a></li>
	</ul>
	
	<!--HTML for Context Menu 2-->
	<ul id="contextmenu2" class="mycontextmenu">
		<li><a href="#">Item Image 1a</a></li>
		<li><a href="#">Item Image 2a</a></li>
		<li><a href="#">Item Image 1a</a></li>
		<li><a href="#">Item Image 2a</a></li>
	</ul>
	
	<script type="text/javascript">
		//apply context menu to links with class="mylinks"
		$('a.mylinks').addcontextmenu('contextmenu1');

		//apply context menu to all images on the page
		$('img').addcontextmenu('contextmenu2');
	</script>
</body>
</html>
```

## Other calls

Create new MyContextMenuClass object:
```
var MyContextMenu2 = new MyContextMenuClass();
```

Sets a contextmenu to a node based in a html element:
```
$('#selector').addcontextmenu('id for contextmenu node');
```

Sets a contextmenu to a node based in a html element:
```
MyContextMenu.addContextMenu(elm, context_menu_id, options);
```

Get right arrow html corresponding to menu that contains sub-menus:
```
MyContextMenu.getRightArrowHtml();
```

Set the html for the arrow displayed when a menu contains sub-menus:
```
MyContextMenu.setRightArrowHtml(html);
```

Get context menu offset:
```
MyContextMenu.getContextMenuOffset()
```

Set context menu offset:
```
MyContextMenu.setContextMenuOffset(offset);
```

Get build context menu ids:
:
```
MyContextMenu.getBuildContextMenuIds();
```

Set build context menu ids:
```
MyContextMenu.setBuildContextMenuIds(ids);
```

Get event when a menu item is selected:
```
MyContextMenu.getSelectedEvent();
```

Set the event of a menu item selection:
```
MyContextMenu.setSelectedEvent(event);
```

Get event target when a menu item is selected:
```
MyContextMenu.getSelectedEventTarget();
```

Get the FF shadows offset. The "FF shadows offset" is a fixed pixel offset used to compensate for Browser’s drop-shadow rendering, which affects how much space a context menu needs before hitting the right or bottom edges of the window.
```
MyContextMenu.getFFShadowsOffset();
```

Set the FF shadows offset:
```
MyContextMenu.setFFShadowsOffset(offset);
```

Get debug status:
```
MyContextMenu.getDebugStatus();
```

Enable debug status:
```
MyContextMenu.enableDebugStatus();
```

Disable debug status:
```
MyContextMenu.disableDebugStatus();
```

Get flag status if click event should be added to document:
```
MyContextMenu.getAddDocumentClickEventStatus();
```

Enable flag to create event on document click:
```
MyContextMenu.enableAddDocumentClickEventStatus();
```

Disable flag to create event on document click:
```
MyContextMenu.disableAddDocumentClickEventStatus();
```

Get if a specific contextmenu was already set for a html element:
```
MyContextMenu.me.isContextMenuSet(elm, context_menu_id);
```

Set a context menu to a html element:
```
MyContextMenu.initContextMenu(elm, context_menu_elm, {
	callback: function() { ... }, //optional: callback to be call before the contextmenu gets set. If this callback is present, it must return true, otherwise the contextmenu won't be set for the html element.
	ignore_tap_hold: true, //optional: if true sets the taphold event
});
```

Show a context menu and set the click event to a specific event:
```
MyContextMenu.showContextMenu(context_menu_elm, ev); //ev is optional
```

Hide a context menu:
```
MyContextMenu.hideContextMenu(context_menu_elm);
```

Hide a context menus, except for the ignore_context_menu_id:
```
MyContextMenu.hideAllContextMenu(ignore_context_menu_id); //ignore_context_menu_id is optional and can be an id or a html element
```

Update the position of a contextmenu:
```
MyContextMenu.updateContextMenuPosition(ul, ev); //ev is the mouse event
```

Converts a html element into a context menu with the corresponding handlers:
```
MyContextMenu.buildContextMenu(menu_elm);
```

