# My Code Beautifier

> Original Repos:   
> - My Code Beautifier: https://github.com/a19836/mycodebeautifier/   
> - Bloxtor: https://github.com/a19836/bloxtor/

## Overview

**My Code Beautifier** is a javascript library to beautify PHP code, but it also works in other languages.

---

## Usage

```html
<html>
<head>
	<script src="js/MyCodeBeautifier.js"></script>
</head>
<body>
	<textarea class="textarea1" rows=20 style="width:100%;"></textarea>
	<textarea class="textarea2" rows=20 style="width:100%;"></textarea>
	<button onClick="beautify()">Beautify</button>
	
	<script>
		function beautify() {
			var code = $(".textarea1").val();
			var new_code = MyCodeBeautifier.prettyPrint(code);
			document.querySelector(".textarea2").value = new_code;
		}
	</script>
</body>
</html>
```

