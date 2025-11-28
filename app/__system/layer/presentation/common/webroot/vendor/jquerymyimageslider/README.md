# JQuery My Image Slider

> Original Repos:   
> - JQuery My Image Slider: https://github.com/a19836/jquerymyimageslider/   
> - Bloxtor: https://github.com/a19836/bloxtor/

## Overview

**JQuery My Image Slider** is a lightweight JavaScript library to show images in a slider.

Check out a live example by opening [index.html](index.html).

Requirements:
- jquery library

---

## Usage

```html
<html lang="en">
<head>
	<!-- Add jquery lib -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.8/jquery.min.js"></script>
	
	<!-- Add imageslider lib -->
	<link type="text/css" rel="stylesheet" href="css/style.css" />
	<script src="js/script.js"></script>
</head>
<body>
	<div class="image_slider">
		<ul class="slides">
			<li class="slide opaque">
				<img src="https://bloxtor.com/img/bg/home/section_4_11.png" />
				<span class="image_title">
					Our software work in Cell-phones, Tablets, Smart-TVs, regular computers...
				</span>
			</li>
			<li class="slide">
				<img src="https://bloxtor.com/img/bg/home/section_4_21.png" />
				<span class="image_title">
					With our CMS you can create secured, consolidated and scalable SAAS...
				</span>
			</li>
			<li class="slide">
				<img src="https://bloxtor.com/img/bg/home/section_4_22.png" />
				<span class="image_title">
					Easy, user-friendly and reliable...
				</span>
			</li>
			<li class="slide">
				<img src="https://bloxtor.com/img/bg/home/section_4_23.png" />
			</li>
		</ul>
	</div>
	<script>
		MyImgSlider.init();
	</script>
</body>
</html>
```

## Other calls

Create new image slider handler:
```
var MyImgSlider2 = new createMyImageSlider();
```

Change default settings, if apply:
```
MyImgSlider2.interval_ttl = 5000;
MyImgSlider2.image_slider_class = "image_slider";
MyImgSlider2.navigation_class = "navigation";
MyImgSlider2.controls_class = "controls";
MyImgSlider2.control_class = "control";
MyImgSlider2.slides_class = "slides";
MyImgSlider2.slide_class = "slide";
MyImgSlider2.prev_class = "prev";
MyImgSlider2.next_class = "next";
MyImgSlider2.on_image_change = function(selected_slide_index, selected_slide_elm, selected_control_elm) { //callback called everytime an image is slided.
	console.log(selected_slide_index);
	console.log(selected_slide_elm);
	console.log(selected_control_elm);
}
```

Initialize the slider:
```
MyImgSlider.init();
```

Get previous image index:
```
var index = MyImgSlider.getPreviousImageIndex();
```

Get next image index:
```
var index = MyImgSlider.getNextImageIndex();
```

Change display image:
```
MyImgSlider.changeToImage(idx);
```

Start auto-slider:
```
MyImgSlider.startAutoSlider();
```

Stop auto-slider:
```
MyImgSlider.stopAutoSlider();
```

