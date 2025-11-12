/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 *
 * Image Slider - jQuery Image Slider
 * @version: 1.0 - (2016/02/13)
 * @requires jQuery 
 * @author Joao Pinto
**/
var MyImgSlider = new createMyImageSlider();

function createMyImageSlider() {
	var me = this;
	
	me.interval_ttl = 5000;
	me.image_slider_class = "image_slider";
	me.navigation_class = "navigation";
	me.controls_class = "controls";
	me.control_class = "control";
	me.slides_class = "slides";
	me.slide_class = "slide";
	me.prev_class = "prev";
	me.next_class = "next";
	me.on_image_change = null;
	
	var length = null;
	var close_navigation_timeout = null;
	var slider_interval = null;
	var image_slider = null;
	var navigation = null;
	var controls = null;
	var slides = null;
	var prev = null;
	var next = null;
	
	me.init = function() {
		image_slider = $("." + me.image_slider_class);
		slides = image_slider.find("." + me.slides_class + " ." + me.slide_class);
		length = slides.length;
		
		var html = "";
		for (var i = 0; i < length; i++) {
			html += '<span class="' + me.control_class + (i == 0 ? ' selected' : '') + '">Image ' + (i + 1) + '</span>';
		}
		html = '<div class="' + me.navigation_class + '">' +
			'<span class="' + me.prev_class + '">prev</span>' +
			'<span class="' + me.next_class + '">next</span>' +
		'</div>' +
		'<div class="' + me.controls_class + '">' + html + '</div>';
		
		image_slider.append(html);
		
		navigation = image_slider.children("." + me.navigation_class);
		controls = image_slider.children("." + me.controls_class).children("." + me.control_class);
		prev = navigation.children("." + me.prev_class);
		next = navigation.children("." + me.next_class);
		
		image_slider.mouseover(function() {
			if (close_navigation_timeout) {
				clearTimeout(close_navigation_timeout);
			}
		
			me.stopAutoSlider();
		
			navigation.fadeIn("fast");
		});

		image_slider.mouseout(function() {
			close_navigation_timeout = setTimeout(function() {
				navigation.fadeOut("fast");
			}, 500);
		
			me.startAutoSlider();
		});
	
		controls.on('click', function() {
			me.stopAutoSlider();
			
			var idx = $(this).index();
			me.changeToImage(idx);
		
			me.startAutoSlider();
		});
	
		prev.on('click', function() {
			me.stopAutoSlider();
			
			var idx = me.getPreviousImageIndex();
			me.changeToImage(idx);
		
			me.startAutoSlider();
		});
	
		next.on('click', function() {
			me.stopAutoSlider();
			
			var idx = me.getNextImageIndex();
			me.changeToImage(idx);
		
			me.startAutoSlider();
		});
	
		me.startAutoSlider();
	}
	
	me.getPreviousImageIndex = function() {
		var idx = slides.filter(".opaque").first().index();
		return idx == 0 ? length - 1 : idx - 1;
	}
	
	me.getNextImageIndex = function() {
		var idx = slides.filter(".opaque").first().index();
		return idx == length - 1 ? 0 : idx + 1;
	}
	
	me.changeToImage = function(idx) {
		slides.removeClass("opaque");
		slides.eq(idx).addClass("opaque");

		controls.removeClass("selected");
		controls.eq(idx).addClass("selected");
		
		if (typeof on_image_change == "function")
			on_image_change(idx, slides.eq(idx), controls.eq(idx));
	}
	
	me.startAutoSlider = function() {
		me.stopAutoSlider();
		
		slider_interval = setInterval(function() {
			var idx = me.getNextImageIndex();
			me.changeToImage(idx);
		}, me.interval_ttl);
	}
	
	me.stopAutoSlider = function() {
		if (slider_interval) {
			clearTimeout(slider_interval);
		}
	}
};
