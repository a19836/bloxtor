# JQuery My Image Slider

## Overview

**JQuery My Image Slider** is a lightweight JavaScript library to show images in a slider.

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

