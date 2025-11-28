# My HTML Beautify

> Original Repos:   
> - My HTML Beautify: https://github.com/a19836/myhtmlbeautify/   
> - Bloxtor: https://github.com/a19836/bloxtor/

## Overview

**My HTML Beautify** is a javascript library to beautify HTML code.   

If beautify.js library is present, then it beautifies Javascript and CSS code too.   
If MyCodeBeautifier.js library is present, then it beautifies PHP code too.   

Additionaly it can also be used to manipulate html, getting tag inner and outer html, attributes, tag names and much more...

Check out a live example by opening [index.html](index.html).

---

## Usage

```html
<html>
<head>
	<!-- (optional) Add Code Beautifier -->
	<script language="javascript" type="text/javascript" src="vendor/mycodebeautifier/js/MyCodeBeautifier.js"></script>

	<!-- (optional) Add Html/CSS/JS Beautify code -->
	<script language="javascript" type="text/javascript" src="vendor/jsbeautify/js/lib/beautify.js"></script>
	<script language="javascript" type="text/javascript" src="vendor/jsbeautify/js/lib/beautify-css.js"></script>
	
	<!-- Add MyHtmlBeautify code -->
	<script language="javascript" type="text/javascript" src="MyHtmlBeautify.js"></script>
</head>
<body>
	<script>
		var html = '<!DOCTYPE html><html><head></head><body><!-- &rarr; algo &larr; --><p style="text-align: center;"><span style="color: #bdc3c7;">&rarr; This is a full-featured editor demo. Please explore! &larr;</span></p><script>var x = "abc";</' + 'script><p style="text-align: center;">&nbsp;</p><h2 style="text-align: center;">TinyMCE provides a <span style="text-decoration: underline;">full-featured</span> rich text editing experience, and a featherweight download.</h2><p style="text-align: center;"><strong> <span style="font-size: 14pt;"> <span style="color: #7e8c8d; font-weight: 600;">No matter what you\'re building, TinyMCE has got you covered.</span> </span> </strong></p><p style="text-align: center;">&nbsp;</p><table style="border-collapse: collapse; width: 85%; margin-left: auto; margin-right: auto; border: 0;"><tbody><tr><td style="width: 25%; text-align: center; padding: 7px;"><span style="color: #95a5a6;">50+ Plugins</span></td><td style="width: 25%; text-align: center; padding: 7px;"><span style="color: #95a5a6;">Premium Support</span></td><td style="width: 25%; text-align: center; padding: 7px;"><span style="color: #95a5a6;">Custom Skins</span></td><td style="width: 25%; text-align: center; padding: 7px;"><span style="color: #95a5a6;">Full API Access</span></td></tr></tbody></table><br/><br/><br/><br/><style>.a {width:100%;height:80px;color:red;background:#ff0000;}</style><script>function foo() {return 123;} foo();</' + 'script>test</body></html>';
		
		//pretty html
		var new_html = MyHtmlBeautify.beautify(html);
		
		//other usages:
		var tag_html = null;
		var char, odq = false, osq = false;
		
		for (var i = 0, t = html.length; i < t; i++) {
			char = html[i];
			
			if (char == '"' && !osq && !this.isCharEscaped(html, i))
				odq = !odq;
			else if (char == "'" && !odq && !this.isCharEscaped(html, i))
				osq = !osq;
			else if (char == "<") {
				if (MyHtmlBeautify.isComment(html, i)) { //parse comment if exists
					tag_html = MyHtmlBeautify.getComment(html, i);
				}
				else if (MyHtmlBeautify.isTagHtml(html, i)) {
					tag_html = MyHtmlBeautify.getTagHtml(html, i);
					
					var tag_name = MyHtmlBeautify.getTagName(tag_html[0], 0);
					var tag_attributes_str = MyHtmlBeautify.getAttributesContent(tag_html[0], tag_name.length + 1, '&gt;');
					var tag_attributes_obj = MyHtmlBeautify.getAttributes(tag_html[0], tag_name.length + 1, '&gt;');
					var tag_content = MyHtmlBeautify.getTagContent(tag_html[0], 0, tag_name);
					var tag_code = MyHtmlBeautify.getNonParseInnerTagsNodeContent(html, i, tag_name);
					
					//...
				}
				else
					tag_html = MyHtmlBeautify.getTextContent(html, i);
				
				if (tag_html)
					i = tag_html[1];
				
				//...
				
				console.log(tag_html[0]);
			}
		}
	</script>
</body>
</html>
```

## Other calls

Set some default settings:
```
MyHtmlBeautify.alert_errors = false; //If true alert errors. Default is false.
MyHtmlBeautify.single_html_tags = ["meta", "base", "basefont", "input", "img", "link", "br", "wbr", "hr", "frame", "area", "source", "track", "circle", "col", "embed", "param"]; //single html tags
```

Beautify an html:
```
var options = {
	indent_size: 4, //default value is 1
	indent_char: ' ', //default is \t
};
var new_html = MyHtmlBeautify.beautify(html, options); //options are optional
```


