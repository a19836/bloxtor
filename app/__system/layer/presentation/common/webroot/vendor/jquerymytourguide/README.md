# JQuery My Tour Guide

> Original Repos:   
> - JQuery My Tour Guide: https://github.com/a19836/jquerymytourguide/   
> - Bloxtor: https://github.com/a19836/bloxtor/

## Overview

**JQuery My My Tour Guide** is a lightweight JavaScript library that extends the functionality of the [LikaloLLC Tourguide](https://github.com/LikaloLLC/tourguide.js?tab=readme-ov-file) library.
It provides an easy way to create interactive guided tours for your web applications.

Check out a live example by opening [index.html](index.html).

Requirements:
- jquery library

Additional details about **LikaloLLC Tourguide** can be found [here](lib/tourguide/README.md).

---

## Examples

- [Simple Tour Guide Example](./index.html)
- [Tour Guide with steps defined in HTML attributes (`data-tour`)](./lib/tourguide/test/index_with_html_attribute.html)
- [Tour Guide with steps defined in JavaScript](./lib/tourguide/test/index_with_js_steps.html)
- [Tour Guide with steps defined on the server side (JSON)](./lib/tourguide/test/index_with_remote_steps.html)
- [Example with images (JSFiddle)](https://jsfiddle.net/eugenetrue/q465gb7L/)

---

## Usage

```html
<html>
<head>
	<!-- Add jquery lib -->
	<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
	
	<!-- Add jquery tourguide lib -->
	<script language="javascript" type="text/javascript" src="lib/tourguide/tourguide.js"></script>
	
	<!-- Add jquerymytourguide lib -->
	<script language="javascript" type="text/javascript" src="js/MyTourGuide.js"></script>
</head>
<body>
	<h1>More examples <a href="lib/tourguide/index.html" target="eg">here</a></h1>
	<ul>
		<li>
			<button class="btn">Button TEST 1</button>
			<button class="btn">Button TEST 2</button>
			<button class="btn2" style="display:none">Button TEST 3</button>
		</li>
		<li>
			<div class="msg">Some msg</div>
		</li>
	</ul>
	<div>
		<button onClick="MyTourGuide.restart()">Restart tour again</button>
	</div>
	
	<script>
	var steps = [
		{
			"selector":".btn, .btn2",
			"title":"Tooltip Tour Guide",
			"content":"Create a new project by clicking in the blue button",
		},
		{
			"selector":".msg",
			"title":"Tooltip Tour Guide",
			"content":"then click the created project to select it..."
		}
	];
	
	var options = {
		steps: steps,
		onStart: func1, //callback called when tourguide starts: func1(options);
		onStep: func2, //callback called when changing to another tourguide step: func2(current_step, type);
		onStop: func3, //callback called when tourguide stops: func3(options);
		onComplete: func4, //callback called when tourguide completes: func4();
	};
	MyTourGuide.init(options);
	MyTourGuide.start(); //(optional) to start on page load
	</script>
</body>
</html>
```

## Other calls

Creates a second tourguide variable:
```
var MyTourGuide2 = new MyTourGuideClass();
```

Gets the tourguide internal library from jquery:
```
var tg = MyTourGuide.tourguide;
```

Gets the current active options when the tourguide was initialized:
```
var options = MyTourGuide.options
```

Starts tourguide:
```
MyTourGuide.start(step_index); //step_index is optional.
```

Stops tourguide:
```
MyTourGuide.stop();
```

Completes tourguide:
```
MyTourGuide.complete();
```

Restarts tourguide:
```
MyTourGuide.restart();
```

Checks if tourguide is active:
```
MyTourGuide.isActive();
```

Executes onStart callback:
```
MyTourGuide.onStart(options);
```

Executes onStep callback:
```
MyTourGuide.onStep(current_step, type);
```

Executes onStop callback:
```
MyTourGuide.onStop(options);
```

Executes onComplete callback:
```
MyTourGuide.onComplete();
```

Executes the steps callback:
```
MyTourGuide.prepareSteps(steps);
```

Prepares tourguide with handlers and options. This is already called in the init method:
```
MyTourGuide.prepareTourguide();
```

Prepare a step:
```
MyTourGuide.prepareStep(current_step, type);
```

Prepare a step arrow:
```
MyTourGuide.prepareStepArrow(current_step);
```

Get the step arrow position:
```
var position = MyTourGuide.getStepArrowPosition(current_step);
```

Get the tourguide style:
```
var style = MyTourGuide.getStyle();

```

