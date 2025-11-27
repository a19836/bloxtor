# JQuery My Tree

> Original Repos:   
> - JQuery My My Tree: https://github.com/a19836/jquerymytree/   
> - Bloxtor: https://github.com/a19836/bloxtor/

## Overview

**JQuery My Tree** is a lightweight JavaScript library that transforms <ul> and <li> elements into an intuitive, user-friendly tree view, listing these nodes into a collapsible and hierarchical tree structure, ideal for file system navigation.

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
	
	<!-- Add jquerymytree lib -->
	<link rel="stylesheet" href="css/style.min.css" type="text/css" charset="utf-8" />
	<script language="javascript" type="text/javascript" src="js/mytree.js"></script>
</head>
<body>
	<script>
	$(function () {
		var mytree = new MyTree();
		mytree.init("file_tree");
	});
	</script>
	
	<div id="file_tree" class="hidden">
		<ul>
			<li class="jstree-open">
				<label>Folder A</label>
				<ul>
					<li><a onClick="window.open('//bloxtor.com')">Link</a></li>
				</ul>
			</li>
			<li>
				<label>Folder B</label>
				<ul>
					<li class="jstree-open">
						Sub-Folder C
						<ul>
							<li data-jstree='{"icon":"jstree-file"}'>
								<label>test</label>
								<ul>
									<li data-jstree='{"icon":"jstree-folder"}'>subtest</li>
								</ul>
							</li>
						</ul>
					</li>
					<li data-jstree='{"icon":"jstree-file"}'>file.xml</li>
				</ul>
			</li>
			<li data-jstree='{"icon":"jstree-folder"}'>Fake Folder</li>
			<li>Through Ajax on open
				<ul url="http://some_url_with_json_response/to/be/called/on/open/or/click"></ul>
			</li>
		</ul>
	</div>
</body>
</html>
```

## Other calls

Create new tree object:
```
var mytree = new MyTree({
	multiple_selection : false, //allow multiple selection
	toggle_selection : false, //click one to select, click again to deselect
	toggle_children_on_click : true, //toggle children on click the element, besides clicking the arrow icon
	ajax_callback_before : func1, //on ajax request, after getting json response from server this gets called as second callback, before the initChilds. This function is responsible to parse the json_obj and convert it into html nodes inside of the ul node: func1(ul, json_obj, mytree);
	ajax_callback_after : func2, //on ajax request, after getting json response from server this gets called as third callback, after the initChilds: func2(ul, json_obj, mytree);
	ajax_callback_error : func3, //on ajax request error callback: func3(ul, jqXHR, textStatus, errorThrown, mytree) 
});
```

Get html element initialized as tree:
```
var elm = mytree.tree_elm;
```

Converts a html element into a tree based in its id:
```
mytree.init(tree_elm_id);
```

Converts a html element into a tree:
```
mytree.initNodeChilds(node);
```

Selects a node based in its id:
```
mytree.selectNode(node_id);
```

Checks if a node is selected based in its id:
```
var selected = mytree.isNodeSelected(node_id);
```

Deselects all nodes:
```
mytree.deselectAll();
```

Gets all selected nodes:
```
var nodes = mytree.getSelectedNodes();
```

Refreshes children based in a node selector: 
```
mytree.refreshNodesChilds(selector);
```

Refreshes children based in a node id:
```
mytree.refreshNodeChildsByNodeId(id);
```

Refreshes children based in a node element:
```
mytree.refreshNodeChilds(node, {
	ajax_callback_first : func1, //on ajax request, after getting json response from server this gets called as first callback, before the initChilds. This function is responsible to parse the json_obj and convert it into html nodes inside of the ul node: func1(ul, json_obj);
	ajax_callback_last : func2, //on ajax request, after getting json response from server this gets called as fourth callback, after the initChilds: func2(ul, json_obj, mytree);
	ajax_callback_error : func3, //on ajax request error callback: func3(ul, jqXHR, textStatus, errorThrown, mytree)
});
```
