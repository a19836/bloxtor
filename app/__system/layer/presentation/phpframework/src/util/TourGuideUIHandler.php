<?php
include_once get_lib("org.phpframework.util.HashCode");
include_once get_lib("org.phpframework.util.web.CookieHandler");
include_once $EVC->getUtilPath("VideoTutorialHandler");

class TourGuideUIHandler {
	
	public static function getHtml($entity_code, $project_url_prefix, $project_common_url_prefix, $online_tutorials_url_prefix, $options = null) {
		$restart_allow = !$options || !array_key_exists("restart_allow", $options) || $options["restart_allow"];
		$css = $options ? $options["css"] : "";
		
		$tourguide_id = self::getPageTourGuideId($entity_code);
		$tour_guide_options = self::getPageTourGuideOptions($entity_code, $project_url_prefix, $project_common_url_prefix, $online_tutorials_url_prefix, $tutorials_exist);
		
		if ($tour_guide_options) {
			$css = "
:host {
	--tourguide-font-family:var(--main-font-family);
	--tourguide-bg-color:#2C2D34;
	--tourguide-bg-color:var(--link-color);
	--tourguide-step-title-background-color:var(--tourguide-bg-color);
	--tourguide-step-title-color:#DFE1ED;
	--tourguide-accent-color:var(--tourguide-bg-color);
	--tourguide-focus-color:var(--tourguide-bg-color);
	--tourguide-bullet-current-color:var(--tourguide-bg-color);
	--tourguide-step-button-next-color:var(--tourguide-bg-color);
	--tourguide-step-button-complete-color:var(--tourguide-bg-color);
	--tourguide-bullet-visited-color:#83889E;
}
.guided-tour-step.active .guided-tour-step-tooltip {
	border-radius:5px;
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner {
	/* padding-bottom:3.5em; *//* with guided-tour-step-actions shown */
	padding-bottom:1em; /* with guided-tour-step-actions hidden */
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content::-webkit-scrollbar {
	width:10px;
	height:10px;
	background:transparent;
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content::-webkit-scrollbar-track {
	background:transparent;
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content::-webkit-scrollbar-thumb {
	background:var(--main-scrollbar-thumb-bg);
	background-clip:padding-box;
	border:2px solid transparent;
	border-radius:9999px;
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-button-close {
	top:0.5em;
	right:0.5em;
	width:1.5em;
	height:1.5em;
	color:var(--tourguide-step-title-color);
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-button-close:hover {
	outline:none;
	color:var(--tourguide-step-button-close-color);
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content-wrapper {
	font-family:var(--main-font-family);
	font-size:9pt;
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-title {
	border-top-left-radius:0.2rem;
	border-top-right-radius:0.2rem;
	color:var(--tourguide-step-title-color);
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-bullets {
	margin-top:-.5em;
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-button-prev, 
  .guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-button-next, 
  .guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-button-complete {
	margin-top:.5em;
}

/* ACTIONS  */

.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-actions {
	column-gap:2em;
	justify-content:space-between;
	position:absolute;
	right:1.5em;
	left:1.5em;
	bottom:1.5em;
	
	display:none;
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-actions .button.complete_tour {
	margin:0 auto;
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-actions .button.complete_tour[disabled] {
	opacity:.5;
	cursor:not-allowed;
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-actions .button.secondary {
	padding: 0.5em 1.5em;
	background:var(--tourguide-bullet-visited-color);
	color:#fff;
	border-radius:4px;
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-actions .button.secondary:not([disabled]):hover, 
  .guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-actions .button.secondary:not([disabled]):focus {
	outline-color:#83889E;
	filter:brightness(120%);
}
.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-actions .button.secondary[disabled]:hover, 
  .guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-actions .button.secondary[disabled]:focus {
	outline-color:transparent;
}

/* TUTORIALS */

.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials a {
	text-decoration:none !important;
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials a:hover,
  .guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials a:focus {
	outline:none;
	text-decoration:underline !important;
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul {
	padding-left:0;
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li {
	list-style:none;
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li > .tutorial_header {
	padding:3px 0;
	cursor:pointer;
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul > li ul > li.open > .tutorial_header {
	padding:3px 0 0 0;
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li > .tutorial_header:hover {
	color:var(--icon-folder-color);
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li > .tutorial_header > .tutorial_description {
	opacity:.8;
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li:not(.open) > .tutorial_header > .tutorial_description {
	display:none;
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li > .tutorial_header > .tutorial_description img {
	max-width:100%;
	margin:10px auto;
	display:block;
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li > .tutorial_header > .tutorial_description a {
	color:var(--link-color);
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li > .tutorial_header > .tutorial_title {
	padding-left:20px;
	font-weight:500;
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li > .tutorial_header > .tutorial_title .icon {
	width:16px;
	display:inline-block;
	white-space:nowrap;
	overflow:hidden;
	cursor:pointer;
	position:relative;
	vertical-align:middle;
	
	margin-left:-20px;
	margin-right:5px;
	opacity:1;
	color:var(--icon-active-color);/*#4070FF*/
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li > .tutorial_header > .tutorial_title .icon::before {
	text-rendering:auto;
	-webkit-font-smoothing:antialiased;
	font:var(--fa-font-solid);
	margin-right:16px;
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li.open.with_sub_tutorials > .tutorial_header > .tutorial_title .icon:not(.dropdown_arrow) {
	opacity:.5;
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li.with_sub_tutorials > .tutorial_header > .tutorial_title .icon.dropdown_arrow {
	width:10px;
	margin:auto 0 auto 10px;
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li > .tutorial_header > .tutorial_title .icon.video::before {
	content: \"\\\\f03d\";
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li > .tutorial_header > .tutorial_title .icon.dropdown_arrow::before {
	content: \"\\\\f107\";
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li.open.with_sub_tutorials > .tutorial_header > .tutorial_title .icon.dropdown_arrow::before {
	content:\"\\\\f106\";
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li > ul {
	margin:0 0 0 10px;
}
.guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials > ul li:not(.open) > ul {
	display:none;
}
" . $css;
			
			$html = '
<style>
	.tourguide_hidden_html_node {
		display:none !important;
	}
	
	.tourguide_restart_btn {
		position:fixed;
		bottom:10px;
		right:10px;
		font-size:30px;
		border-radius: 50%;
		z-index:9999; /* according with our tests the bigger z-index in all pages is this one */
	}
	.tourguide_restart_btn:not(.completed):not(:hover) {
		animation: tourguide_restart_btn_glow 1s infinite alternate;
	}
	.tourguide_restart_btn:not(.ui-draggable-dragging):hover:after {
		margin-top:-30px;
		padding: 5px;
		position: absolute;
		right: 30px;
		display: block;
		font:var(--main-font-family);
		font-size: 8pt;
		border-radius: var(--info-label-tooltip-border-radius);
		border: 1px solid var(--info-label-tooltip-border);
		background: var(--info-label-tooltip-bg);
		color: var(--info-label-tooltip-color);
		white-space:nowrap;
	}
	.tourguide_restart_btn:not(.completed):not(.ui-draggable-dragging):hover:after {
		content:"Incomplete Tour: There are still some tutorial videos to watch.";
	}
	.tourguide_restart_btn.completed:not(.ui-draggable-dragging):hover:after {
		content:"Tour is complete, but you can restart it if you wish.";
	}
	.tourguide_restart_btn .icon {
		width:auto;
		border-radius:100%;
		opacity:1;
		transition:opacity .5s ease-in-out;
		color:var(--link-color);
		background-color:white;
	}
	.tourguide_restart_btn .icon:hover {
		opacity:.8;
	}
	.tourguide_restart_btn .icon::before {
		margin:0;
	}
	@keyframes tourguide_restart_btn_glow {
		from {
			box-shadow: 0 0 30px -5px var(--link-color);
		}
		to {
			box-shadow: 0 0 30px 5px var(--link-color);
		}
	}
	
	/* MODAL VIDEO */

	.show_tour_guide_tutorial_video_popup {
		width:600px;
		height:auto !important;
		/*max-height:100vh;
		overflow-x: visible;
		overflow-y:auto;*/
		box-sizing:border-box;
		display:none;
	}
	.show_tour_guide_tutorial_video_popup .content {
		padding:20px;
	}
	.show_tour_guide_tutorial_video_popup .video {
		margin-bottom:20px;
		text-align:center;
		background:#000;
	}
	.show_tour_guide_tutorial_video_popup .video iframe {
		max-width:100%;
		background-color:#000;
		background-repeat:no-repeat;
		background-size:contain;
		background-position:center;
	}
	.show_tour_guide_tutorial_video_popup .details:after {
		content:\"\";
		height:1px;
		display:block;
		float:none;
		clear:both;
	}
	.show_tour_guide_tutorial_video_popup .details .image {
		width:100px;
		max-height:100px;
		margin-right:10px;
		margin-bottom:10px;
		border:1px solid #ddd;
		border-radius:3px;
		float:left;
	}
	.show_tour_guide_tutorial_video_popup .details .description {
		text-align:left;
	}
	.show_tour_guide_tutorial_video_popup .details .description img {
		max-width:100%;
		margin:10px auto;
		display:block;
	}
	.show_tour_guide_tutorial_video_popup .details .description a {
		color:var(--link-color);
	}
</style>
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymytourguide/lib/tourguide/tourguide.js"></script>
<script language="javascript" type="text/javascript" src="' . $project_common_url_prefix . 'vendor/jquerymytourguide/js/MyTourGuide.js"></script>
<script>
	function toggleTourGuideTutorialSubTutorials(elm) {
		$(elm).parent().closest("li").toggleClass("open");
		
		MyTourGuide.tourguide.currentstep.position();
	}
	function openTourGuideTutorialVideoPopup(elm) {
		elm = $(elm);
		var popup = $(".show_tour_guide_tutorial_video_popup");
		
		if (!popup[0]) {
			var html = \'<div class="myfancypopup with_title show_tour_guide_tutorial_video_popup">\'
						+ \'<div class="title"></div>\'
						+ \'<div class="content">\'
							+ \'<div class="video">\'
								+ \'<iframe width="560" height="315" title="" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>\'
							+ \'</div>\'
							+ \'<div class="details">\'
								+ \'<img class="image" alt="Card image cap" onError="$(this).hide()">\'
								+ \'<div class="description"></div>\'
							+ \'</div>\'
						+ \'</div>\'
					+ \'</div>\';
			popup = $(html);
			$(document.body).append(popup);
		}
		
		//console.log(MyTourGuide.tourguide._containerElement);
		//console.log(MyTourGuide.tourguide._containerElement.style.zIndex);
		popup.css("z-index", parseInt(MyTourGuide.tourguide._containerElement.style.zIndex) + 1);
		
		var video_url = elm.attr("video_url");
		var image_url = elm.attr("image_url");
		var p = elm.parent().closest("li");
		var title = p.find(".tutorial_title").text();
		var description = p.find(".tutorial_description").html();
		
		p.addClass("video_watched");
		
		if (description)
			popup.find(".description").show();
		else
			popup.find(".description").hide();
		
		popup.find(".title").html(title);
		popup.find(".description").html(description);
		popup.find(".image").show().attr("src", image_url);
		popup.find("iframe").attr("src", video_url).attr("title", title).css("background-image", "url(" + image_url + ")");
		
		MyFancyPopup.init({
			elementToShow: popup,
			parentElement: document,
			onOpen: function() {
				//set z-index again, bc when we open the popup it will change the z-index assynchronously.
				var z_index = parseInt(MyTourGuide.tourguide._containerElement.style.zIndex) + 1;
				
				if (parseInt(popup.css("z-index")) < z_index) {
					popup.css("z-index", z_index);
					
					setTimeout(function() {
						popup.css("z-index", z_index);
						
						setTimeout(function() {
							popup.css("z-index", z_index);
							
							setTimeout(function() {
								popup.css("z-index", z_index);
							}, 500);
						}, 500);
					}, 500);
				}
			},
			onClose: function() {
				popup.find("iframe").removeAttr("src"); //remove src attribute so the video stops playing, in case is playing already...
			},
		});
		
		MyFancyPopup.showPopup();
	}
	
	$(function () {
		if (typeof MyTourGuide != "undefined" && typeof MyTourGuide.init == "function") {
			var tourguide_id = "' . $tourguide_id . '";
			var tourguide_cookie = MyJSLib.CookieHandler.getCookie("tourguide");
			var is_tourguide_done = tourguide_cookie ? ("" + tourguide_cookie).indexOf("|" + tourguide_id + "|") !== -1 : false;
			
			var tutorials_exist = ' . ($tutorials_exist ? "true" : "false") . ';
			var tourguide_tutorials_cookie = MyJSLib.CookieHandler.getCookie("tourguidetutorials");
			var are_tourguide_tutorials_done = !tutorials_exist || (
				tourguide_tutorials_cookie ? ("" + tourguide_tutorials_cookie).indexOf("|" + tourguide_id + "|") !== -1 : false
			);
			
			var restart_btn = null;
			' . ($restart_allow ? '
				//add help short action to re-start tour again at the bottom of screen
				restart_btn = $(\'<div class="tourguide_restart_btn"><span class="icon question"></span></div>\');
				$(document.body).append(restart_btn);
				
				restart_btn.on("click", function() {
					if (!this.click_event_disabled && !MyTourGuide.tourguide._active)
						MyTourGuide.restart();
				});
				restart_btn.draggable({
					start: function() {
						this.click_event_disabled = true;
					},
					stop: function() {
						setTimeout(function() {
							restart_btn[0].click_event_disabled = false;
						}, 300);
					}
				});
				
				if (!tutorials_exist || are_tourguide_tutorials_done)
					restart_btn.addClass("completed");
			' : '') . '
			
			var on_start_func = function () {
				//console.log("on_start_func");
				if (restart_btn)
					restart_btn.hide();
			};
			var on_stop_func = function () {
				//console.log("on_stop_func");
				var is_close = false;
				
				if (window.event && window.event instanceof KeyboardEvent && window.event.keyCode == 27) //27: Escape
					is_close = true;
				else if (MyTourGuide.tourguide.currentstep && MyTourGuide.tourguide.currentstep.close_event)
					is_close = true;
				
				if (is_close) {
					//set cookie so next time does not load this anymore
					var tourguide_cookie = MyJSLib.CookieHandler.getCookie("tourguide"); //must get cookie again, bc if this tour is open in an iframe and the parent window changes the cookie, we must get it again, otherwise we will loose the changes done from the parent window.
					tourguide_cookie = (tourguide_cookie ? tourguide_cookie : "|") + tourguide_id + "|";
					MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie("tourguide", tourguide_cookie);
				}
				
				if (restart_btn) {
					restart_btn.show();
					
					var move = MyTourGuide.tourguide.currentstep && MyTourGuide.tourguide.currentstep.tooltip && MyTourGuide.tourguide.currentstep.tooltip.first();
					var o = restart_btn.offset();
					
					if (move) {
						if (MyTourGuide.tourguide.currentstep.target) {
							var target = $(MyTourGuide.tourguide.currentstep.target);
							var to = target.offset();
							var left = to.left + parseInt(target.width() / 2);
							var top = to.top + parseInt(target.height() / 2);
							
							restart_btn.css({left: left + "px", top: top + "px", right: "auto", bottom: "auto"});
						}
						else {
							var tooltip = MyTourGuide.tourguide.currentstep.tooltip.first();
							restart_btn.css({left: tooltip.style.left, top: tooltip.style.top, right: "auto", bottom: "auto"});
						}
						
						restart_btn.animate(o, 500, "swing", function() {
							restart_btn.css({left: "", top: "", right: "", bottom: ""});
						});
					}
				}
			};
			var on_before_complete_func = function () {
				//console.log("on_before_complete_func");
				
				var status = true;
				
				//check if exists any videos and if it does, check if user already watch all videos
				if (tutorials_exist && !are_tourguide_tutorials_done) {
					var tutorials = MyTourGuide.tourguide._shadowRoot.querySelector(".guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials");
					var all_videos_watched = tutorials && $(tutorials).find("li").length > 0 && $(tutorials).find("li.with_video:not(.video_watched)").length == 0; //check if not empty, bc if we click in the "Disable Tour" button, the tutorials will be empty
					
					//set cookie so next time does not show confirmation message anymore
					if (all_videos_watched) {
						tourguide_tutorials_cookie = (tourguide_tutorials_cookie ? tourguide_tutorials_cookie : "|") + tourguide_id + "|";
						MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie("tourguidetutorials", tourguide_tutorials_cookie);
						
						if (restart_btn)
							restart_btn.addClass("completed");
					}
					else { //show message warning user that he did not watch all videos
						//status = confirm("You have not watched the videos on the last slide of this Tour! We strongly recommend watching them all to better understand how to work with the framework. Are you sure you want to close this Tour?"); //disable prompt bc it is annoying
					}
				}
				
				return status;
			};
			var on_complete_func = function () {
				//console.log("on_complete_func");
				
				//set cookie so next time does not load this anymore
				var tourguide_cookie = MyJSLib.CookieHandler.getCookie("tourguide"); //must get cookie again, bc if this tour is open in an iframe and the parent window changes the cookie, we must get it again, otherwise we will loose the changes done from the parent window.
				tourguide_cookie = (tourguide_cookie ? tourguide_cookie : "|") + tourguide_id + "|";
				MyJSLib.CookieHandler.setCurrentDomainEternalRootSafeCookie("tourguide", tourguide_cookie);
			};
			var dont_show_anymore_func = function (event, action, context) {
				/*console.log(event);
				console.log(action);
				console.log(context);
				console.log(tourguide_id);*/
				
				MyTourGuide.complete();
			};
			
			var options = ' . json_encode($tour_guide_options) . ';
			options = $.isPlainObject(options) ? options : {};
			options.css = (options.css ? options.css : "") + \'' . str_replace("\n", "", $css) . '\';
			
			//convert steps callbacks into functions
			if ($.isArray(options.steps))
				for (var i = 0, t = options.steps.length; i < t; i++) {
					var step = options.steps[i];
					
					if (step.onStepStart && typeof step.onStepStart == "string")
						eval("options.steps[i].onStepStart = " + step.onStepStart + ";");
					
					if (step.onStepEnd && typeof step.onStepEnd == "string")
						eval("options.steps[i].onStepEnd = " + step.onStepEnd + ";");
				}
			
			if (options.onStep && typeof options.onStep == "string")
				eval("options.onStep = " + options.onStep + ";");
			
			if (options.onStart && typeof options.onStart == "string") {
				eval("var options_on_start = " + options.onStart + ";");
				
				options.onStart = function() {
					if (typeof options_on_start == "function")
						options_on_start();
					
					on_start_func();
				};
			}
			else
				options.onStart = on_start_func;
			
			if (options.onStop && typeof options.onStop == "string") {
				eval("var options_on_stop = " + options.onStop + ";");
				
				options.onStop = function() {
					if (typeof options_on_stop == "function")
						options_on_stop();
					
					on_stop_func();
				};
			}
			else
				options.onStop = on_stop_func;
			
			if (options.onBeforeComplete && typeof options.onBeforeComplete == "string") {
				eval("var options_on_before_complete = " + options.onBeforeComplete + ";");
				
				options.onBeforeComplete = function() {
					var status = true;
					
					if (typeof options_on_before_complete == "function")
						status = options_on_before_complete();
					
					if (status !== false)
						status = on_before_complete_func();
					
					return status;
				};
			}
			else
				options.onBeforeComplete = on_before_complete_func;
			
			if (options.onComplete && typeof options.onComplete == "string") {
				eval("var options_on_complete = " + options.onComplete + ";");
				
				options.onComplete = function() {
					if (typeof options_on_complete == "function")
						options_on_complete();
					
					on_complete_func();
				};
			}
			else
				options.onComplete = on_complete_func;
			
			options["actionHandlers"] = [
				new Tourguide.ActionHandler("dontShowTourAnymore", dont_show_anymore_func)
			];
			
			//console.log(options);
			if (MyTourGuide.init(options)) {
				//start tour if not yet done
				if (!is_tourguide_done)
					MyTourGuide.start(0);
			}
			else if (restart_btn)
				restart_btn.remove();
		}
	});
</script>';
			
			return $html;
		}
	}
	
	public static function getPageTourGuideId($entity_code) {
		switch($entity_code) {
			case "admin/index":
				$edit_entity_type = !empty($_GET["admin_type"]) ? $_GET["admin_type"] : (!empty($_COOKIE["admin_type"]) ? $_COOKIE["admin_type"] : "");
				$tourguide_id = HashCode::getHashCode("$entity_code?$edit_entity_type");
				break;
			
			default:
				$tourguide_id = HashCode::getHashCode($entity_code);
		}
		
		return $tourguide_id;
	}
	
	/*
	$steps[] = array(
		"selector" => ".choose_available_project .new_project button",
		//"image" => "https://cdn.docsie.io/workspace_x276s7jrJhnLKVh3n/doc_eIWkWMNchrMYpUSGw/file_zL84IvnQjMxj3wAvR/6f7ee74d-48c0-e460-4e51-b0fae3a839a3castlebytheriver2.png",
		//"video" => "http://techslides.com/demos/sample-videos/small.webm",
		//"width" => "320px",
		"title" => "Create a project",
		"placement" => "top-end",
		"content" => "Create a new project by clicking in the blue button",
		//"overlay" => true,
		//"layout" => "horizontal",
		//"navigation" => true,
		"actions" => array(
			array(
				"id" => "got_it",
				"label" => "Got it",
				"action" => "stop"
			),
			array(
				"id" => "learn_more",
				"label" => "Show me",
				"action" => "next",
				"primary" => true
			),
			array(
				"label" => "Click",
				"action" => "custom"
			)
		)
	);
	$steps[] = array(
		"selector" => ".choose_available_project .new_first_project button",
		"title" => $main_title,
		"content" => "Create a new project by clicking in the blue button",
		"step_id" => 1,
	);
	$steps[] = array(
		"selector" => ".choose_available_project .new_project button",
		"title" => "Tooltip Tour Guide EXISTS",
		"content" => "Create a new project by clicking in the blue button",
		"step_id" => 1,
	);
	*/
	public static function getPageTourGuideOptions($entity_code, $project_url_prefix, $project_common_url_prefix, $online_tutorials_url_prefix, &$tutorials_exist = false) {
		//echo "entity_code:$entity_code";die();
		$options = array(
			"steps" => array(),
		);
		$admin_type = !empty($_COOKIE["admin_type"]) ? $_COOKIE["admin_type"] : "";
		
		switch($entity_code) {
			case "admin/index":
				$admin_type = !empty($_GET["admin_type"]) ? $_GET["admin_type"] : $admin_type;
				
				//avoids the left and right keyboard keys to work from the inner iframe. With this function the arrows only work in the main window.
				$prepare_inner_tourguides_func = 'function(current_step) {
					var target = current_step.target;
					var iframe = document.querySelector("#right_panel iframe");
					
					if (target && iframe) {
						$(iframe).load(function() {
							iframe.blur();
							target.focus();
						});
						
						iframe.blur();
						target.focus();
						
						setTimeout(function() {
							iframe.blur();
							target.focus();
							
							setTimeout(function() {
								iframe.blur();
								target.focus();
								
								setTimeout(function() {
									iframe.blur();
									target.focus();
								}, 1000);
							}, 1000);
						}, 1000);
					}
				}';
				
				if ($admin_type == "advanced") {
					$options["steps"][] = array(
						"selector" => "#top_panel",
						"content" => "The <strong>Advanced Workspace</strong> comprises a '<strong>Top bar</strong>', a '<strong>Navigator</strong>', and a '<strong>Content Panel</strong>' displaying content corresponding to selections made in the Top bar or Navigator.",
						"onStepEnd" => $prepare_inner_tourguides_func
					);
					$options["steps"][] = array(
						"selector" => "#top_panel > .center",
						"content" => "The '<strong>Top bar</strong>' displays available projects in the center, enabling you to select a project to work on and access its dashboard and pages below.",
					);
					$options["steps"][] = array(
						"selector" => "#top_panel > .right",
						"content" => "On the right side, you'll find various buttons and actions.",
					);
					$options["steps"][] = array(
						"selector" => "#top_panel > .right > .flush_cache",
						"content" => "From this icon, you can clear your cache. This is very useful when something isn't working but appears to be correct. Just flush the cache and try again.",
					);
					$options["steps"][] = array(
						"selector" => "#top_panel > .right > .tools",
						"content" => "From this icon, you can access various actions and functions, including the option to switch to another workspace, manage users and modules, deploy projects, and more.",
					);
					$options["steps"][] = array(
						"selector" => "#top_panel > .right > .sub_menu_user",
						"content" => "In this submenu, explore advanced features such as Tutorials and a Debug console.<br/>Furthermore, you can provide feedback or report bugs or send us questions via the 'Feedback' menu, or rearrange panels by flipping the <strong>Navigator</strong> to the other side.",
					);
					$options["steps"][] = array(
						"selector" => "#left_panel",
						"content" => "In the '<strong>Navigator</strong>', you can access the files and components for your projects/applications.<br/>Each application is built on a <strong>Multi-Layer Structure</strong>, primarily consisting of <strong>Interface</strong>, <strong>Business Logic</strong>, and <strong>Database layers</strong>.",
					);
					$options["steps"][] = array(
						"selector" => "#left_panel.left_panel_with_tabs .tabs .tab_main_node.tab_main_node_presentation_layers",
						"content" => "Here, you can browse through the pages within your projects and other interface/presentation components.",
						"do_not_remove_on_page_load" => true,
						"skip_if_not_exists" => true,
					);
					$options["steps"][] = array(
						"selector" => "#left_panel.left_panel_with_tabs .tabs .tab_main_node.tab_main_node_business_logic_layers",
						"content" => "Here, you can access and view the Business Logic services.",
						"do_not_remove_on_page_load" => true,
						"skip_if_not_exists" => true,
					);
					$options["steps"][] = array(
						"selector" => "#left_panel.left_panel_with_tabs .tabs .tab_main_node.tab_main_node_db_layers",
						"content" => "Here, you can access and edit the Database tables.",
						"do_not_remove_on_page_load" => true,
						"skip_if_not_exists" => true,
					);
					$options["steps"][] = array(
						"selector" => "#right_panel",
						"content" => "The '<strong>Content Panel</strong>' displays content corresponding to your selections in the Top Bar or Navigator.<br/><p style=\"text-align:center; font-weight:bold;\">Enjoy...</p>",
					);
					
				}
				else if ($admin_type == "simple") {
					$options["steps"][] = array(
						"selector" => "#top_panel",
						"content" => "The <strong>Simple Workspace</strong> comprises a '<strong>Top bar</strong>' and a '<strong>Content Panel</strong>' displaying content corresponding to selections made in the Top bar.",
						"onStepEnd" => $prepare_inner_tourguides_func
					);
					$options["steps"][] = array(
						"selector" => "#top_panel > .center",
						"content" => "The '<strong>Top bar</strong>' displays available projects in the center, enabling you to select a project to work on and access its dashboard and pages below.",
					);
					$options["steps"][] = array(
						"selector" => "#top_panel > .right",
						"content" => "On the right side, you'll find various buttons and actions.",
					);
					$options["steps"][] = array(
						"selector" => "#top_panel > .right > .flush_cache",
						"content" => "From this icon, you can clear your cache. This is very useful when something isn't working but appears to be correct. Just flush the cache and try again.",
					);
					$options["steps"][] = array(
						"selector" => "#top_panel > .right > .tools",
						"content" => "From this icon, you can access various actions and features, including the option to switch to another workspace, manage users and modules, deploy projects, and more.",
					);
					$options["steps"][] = array(
						"selector" => "#top_panel > .right > .sub_menu_user",
						"content" => "In this submenu, discover advanced features like Tutorials and a Debug console. Additionally, you can provide feedback or report bugs or send us questions through the 'Feedback' menu.",
					);
					$options["steps"][] = array(
						"selector" => "#right_panel",
						"content" => "The '<strong>Content Panel</strong>' displays content corresponding to your selections in the Top Bar.<br/><p style=\"text-align:center; font-weight:bold;\">Enjoy...</p>",
					);
				}
				
				break;
			
			case "admin/admin_home":
				$hide_popup_func = 'function(current_step) {
					$("body > .myfancypopup.edit_project_details_popup").data("tourguide_hide", "1").css("visibility", "hidden");
				}';
				$show_popup_func = 'function(current_step) {
					var popup = $("body > .myfancypopup.edit_project_details_popup");
					
					if (popup.data("tourguide_hide"))
						popup.data("tourguide_hide", null).css("visibility", "");
				}';
				
				$options["steps"][] = array(
					"selector" => ".admin_panel > ul > li a[href='#projs']",
					"content" => "Panel with your projects.",
					"onStepEnd" => $hide_popup_func
				);
				$options["steps"][] = array(
					"selector" => ".admin_panel > ul > li a[href='#tutorials']",
					"content" => "Panel with video tutorials to learn how to work with the framework.",
					"onStepEnd" => $hide_popup_func
				);
				$options["steps"][] = array(
					"selector" => ".admin_panel > ul > li:nth-child(3) > a",
					"content" => "Panel with the framework documentation where you can learn more about it and its structure and architecture.",
					"onStepEnd" => $hide_popup_func
				);
				$options["steps"][] = array(
					"selector" => ".choose_available_project .new_first_project button, .choose_available_project .new_project button",
					"content" => "Click the blue button to create a new project. A popup will appear for you to enter the project name, then follow the instructions in the popup.",
					"onStepEnd" => $hide_popup_func
				);
				$options["steps"][] = array(
					"selector" => ".choose_available_project > .group",
					"content" => "The created projects will appear here.<br/>Click on a project to select it and go to the its dashboard...",
					"onStepEnd" => $hide_popup_func
				);
				
				$options["onStop"] = $show_popup_func;
				break;
			
			case "admin/admin_home_project":
				$hide_popup_func = 'function(current_step) {
					$(".status_message").data("tourguide_hide", "1").hide();
				}';
				$show_popup_func = 'function(current_step) {
					var popup = $(".status_message");
					
					if (popup.data("tourguide_hide"))
						popup.data("tourguide_hide", null).show();
				}';
				
				$options["steps"][] = array(
					"selector" => ".admin_panel .project .project_title .sub_menu",
					"content" => "Click on these icon to open the sub-menu to edit this project details or to preview it as an end-user."
				);
				$options["steps"][] = array(
					"selector" => ".status_message .create_new_page_message button",
					"content" => "Click on this button to add a new page.",
					"skip_if_not_exists" => true,
					"onStepStart" => $show_popup_func
				);
				$options["steps"][] = array(
					"selector" => ".admin_panel .project_files .pages button",
					"content" => "Click on this button to add a new page.",
					"onStepEnd" => $hide_popup_func
				);
				$options["steps"][] = array(
					"selector" => ".admin_panel .project_files .pages .mytree > li > ul > li.empty_files button",
					"content" => "Or click on this button to add also new page.",
					"skip_if_not_exists" => true,
					"do_not_remove_on_page_load" => true,
					"onStepEnd" => $hide_popup_func,
					"step_id" => 1
				);
				$options["steps"][] = array(
					"selector" => ".admin_panel .project_files .pages .mytree",
					"content" => "The created pages will appear in this section.<br/>Click on a page to open the page editor and design your interface.",
					"onStepEnd" => $hide_popup_func,
					"step_id" => 1
				);
				$options["steps"][] = array(
					"content" => "To create a new project/app or view the pages of another project/app, click on the home icon in the top bar or [here]({$project_url_prefix}admin/admin_home) to be redirected to the framework dashboard.",
				);
				
				$options["onStop"] = $show_popup_func;
				break;
			
			case "admin/choose_available_tool":
				$options["steps"][] = array(
					"selector" => ".choose_available_tool .switch_admin_ui",
					"content" => "Click this icon to switch between workspaces based on your technical skills.",
				);
				$options["steps"][] = array(
					"selector" => ".choose_available_tool .switch_project",
					"content" => "Click this icon to select a project to work on.",
				);
				$options["steps"][] = array(
					"selector" => ".choose_available_tool .manage_projects",
					"content" => "Click this icon to manage project details, such as defining the main project, configuring project variables, and adjusting other settings.",
				);
				$options["steps"][] = array(
					"selector" => ".choose_available_tool .manage_layers",
					"content" => "Click this icon to define the Layers structure for your projects. Specify your micro-services or policy structure, determine how each layer connects to one another (either natively or through a network protocol), and set access permissions for each layer, among other settings.",
				);
				$options["steps"][] = array(
					"selector" => ".choose_available_tool .manage_modules",
					"content" => "Click this icon to install new modules from our store or manage the installed ones.",
				);
				$options["steps"][] = array(
					"selector" => ".choose_available_tool .manage_users",
					"content" => "Click this icon to manage user permissions, databases accessed by each project, or the reference of other components by each project.",
				);
				$options["steps"][] = array(
					"selector" => ".choose_available_tool .manage_test_units",
					"content" => "Click this icon to create test units that can be used to deploy your projects.",
				);
				$options["steps"][] = array(
					"selector" => ".choose_available_tool .manage_deployments",
					"content" => "Click this icon to deploy your projects to another servers.",
				);
				$options["steps"][] = array(
					"selector" => ".choose_available_tool .install_program",
					"content" => "Click this icon to install a program from our store into your project.",
				);
				$options["steps"][] = array(
					"selector" => ".choose_available_tool .flush_cache",
					"content" => "Click this icon to delete the framework cache. The framework generates caches from various settings, and occasionally manual deletion is required to properly preview your project.",
				);
				break;
			
			case "presentation/edit_entity":
				$edit_entity_type = !empty($_GET["edit_entity_type"]) ? $_GET["edit_entity_type"] : (!empty($_COOKIE["edit_entity_type"]) ? $_COOKIE["edit_entity_type"] : "");
				$edit_entity_type = !empty($edit_entity_type) ? strtolower($edit_entity_type) : "simple";
				
				if ($edit_entity_type == "simple") {
					$hide_popup_func = 'function(current_step) {
						$(".myfancypopup.choose_available_template_popup:visible, .popup_overlay:visible").data("tourguide_hide", "1").addClass("tourguide_hidden_html_node");
					}';
					$show_popup_func = 'function(current_step_or_options) { 
						var popup = $(".myfancypopup.choose_available_template_popup, .popup_overlay");
						
						if (popup.data("tourguide_hide"))
							popup.data("tourguide_hide", null).removeClass("tourguide_hidden_html_node");
					}';
					
					$options["steps"][] = array(
						"content" => "Welcome to the page editor. Here, you can design your page by dragging and dropping components into the canvas regions. You can start designing your page from scratch or choose a pre-defined layout. Please follow this guide to learn more about the process.",
						"onStepEnd" => $hide_popup_func,
					);
					$options["steps"][] = array(
						"selector" => ".top_bar",
						"content" => "The top bar displays the current page name on the left side, while featuring various buttons and actions on the right side.",
						"onStepEnd" => $hide_popup_func,
					);
					$options["steps"][] = array(
						"selector" => ".top_bar li.sub_menu",
						"content" => "Within this submenu, you'll find more advanced actions, including the ability to activate the auto-save feature, which is currently disabled. Furthermore, you can rearrange panels below by moving the <strong>Canvas</strong> area to the other side.",
						"onStepEnd" => $hide_popup_func,
					);
					$options["steps"][] = array(
						"selector" => ".code_layout_ui_editor > .layout-ui-editor > .template-widgets",
						"content" => "This is the <strong>Canvas</strong> area where you can view the selected template and position visual components (HTML elements - <strong>Widgets</strong>) to design your page and visualize its appearance.<br/>Just drag and drop Widgets into the selected template <strong>Regions</strong> and see how easy it is to implement your vision.<br/>By clicking on the Widgets set here, a properties panel will open, allowing you to edit the widget properties.",
						"onStepEnd" => $hide_popup_func,
					);
					$options["steps"][] = array(
						"selector" => ".code_layout_ui_editor > .layout-ui-editor > .menu-widgets",
						"content" => "This is the <strong>Widgets</strong> Panel where you can drag and drop elements into the Canvas area.",
						"onStepEnd" => $hide_popup_func,
					);
					$options["steps"][] = array(
						"selector" => ".code_layout_ui_editor > .layout-ui-editor > .options > .options-left",
						"content" => "This panel includes buttons to switch between different side panels.",
						"onStepEnd" => $hide_popup_func,
					);
					$options["steps"][] = array(
						"selector" => ".code_layout_ui_editor > .layout-ui-editor > .options > .options-left > .option.show-widgets",
						"content" => "This button indicates the currently selected side panel, displaying HTML elements (Widgets) available for drag-and-drop onto the Canvas area.",
						"onStepEnd" => $hide_popup_func,
					);
					$options["steps"][] = array(
						"selector" => ".code_layout_ui_editor > .layout-ui-editor > .options > .options-left > .option.show-layers",
						"content" => "This button shows a side panel with the HTML elements' structure in the Canvas area, in a Tree layout.<br/>Keep in mind, some HTML elements may be hidden in the Canvas area and can only be accessed through the Layers panel.",
						"onStepEnd" => $hide_popup_func,
					);
					$options["steps"][] = array(
						"selector" => ".code_layout_ui_editor > .layout-ui-editor > .options > .options-left > .option.show-right-container-dbs",
						"content" => "This button showcases the Database Tables, allowing you to edit and drag them onto your Canvas area to incorporate dynamic data into your HTML.",
						"do_not_remove_on_page_load" => true,
						"skip_if_not_exists" => true,
						"onStepStart" => 'function(current_step) {
							if ($(".code_layout_ui_editor > .layout-ui-editor.with_right_container_dbs").length == 0) {
								MyTourGuide.options.steps.splice(current_step.index, 1);
								
								if (MyTourGuide.prepareTourguide())
									MyTourGuide.start(current_step.index);
								
								return false;
							}
						}',
						"onStepEnd" => $hide_popup_func,
					);
					$options["steps"][] = array(
						"selector" => ".code_layout_ui_editor > .layout-ui-editor > .options > .options-left > .option.show-layout-options",
						"content" => "This button reveals various settings to toggle the visibility of specific HTML elements (Widgets) in the Canvas area, such as 'script', 'style' or other elements.",
						"onStepEnd" => $hide_popup_func,
					);
					$options["steps"][] = array(
						"selector" => ".entity_obj > .regions_blocks_includes_settings > .settings_header",
						"content" => "At the bottom, you'll find the '<strong>Main Settings</strong>' panel, housing settings for this page.<br/>Here, you can modify the HTML for the chosen template, add new CSS or JavaScript files, and edit or create resources.",
						"onStepEnd" => $hide_popup_func,
						"placement" => "bottom-end",
					);
					$options["steps"][] = array(
						"selector" => ".code_layout_ui_editor > .layout-ui-editor > .options > .options-left > .option.choose-template",
						"content" => "Last but not least, by clicking on this button, a popup will display the available templates where you can switch to a different layout.",
						"do_not_remove_on_page_load" => true,
						"onStepEnd" => $hide_popup_func,
					);
					$options["steps"][] = array(
						"selector" => ".myfancypopup.choose_available_template_popup .html_editor",
						"content" => "Within this popup, you can select how you want to design your page.<br/>Choose this option if you want to design it from scratch, designing all HTML elements.</p>",
						"do_not_remove_on_page_load" => true,
						"skip_if_not_exists" => true,
						"onStepEnd" => $show_popup_func,
					);
					$options["steps"][] = array(
						"selector" => ".myfancypopup.choose_available_template_popup .template_editor",
						"content" => "Or you can select a template to start creating your page, which is the recommended and easiest option.<br/><p style=\"text-align:center; font-weight:bold;\">Enjoy...</p>",
						"do_not_remove_on_page_load" => true,
						"skip_if_not_exists" => true,
						"onStepEnd" => $show_popup_func,
					);
					$options["steps"][] = array(
						"content" => "To create a new page or view the pages of this project/app, click on the '**Pages**' menu in the top bar, or click on the home icon on the right or [here]({$project_url_prefix}admin/admin_home) to be redirected to the framework dashboard and then choose the corresponding project/app where you want to create the new page.",
					);
					
					$options["onStop"] = $show_popup_func;
				}
				else {
					//TODO: add tourguide for the edit_entity_advanced
				}
				break;
			
			case "businesslogic/edit_method":
			case "businesslogic/edit_function":
			case "admin/edit_file_class_method":
			case "admin/edit_file_function":
				$editor_name = $entity_code == "businesslogic/edit_method" || $entity_code == "admin/edit_file_class_method" ? "method" : "function";
				
				$options["steps"][] = array(
					"selector" => "#main_column",
					"content" => "The <strong>" . ucfirst($editor_name) . " Editor</strong> lets you draw workflow diagrams or directly edit the code.<br/>Changes to the diagram generate new code, and changes to the code create a new diagram.<br/>This editor comprises a '<strong>Top bar</strong>', a '<strong>Tasks List</strong>', a '<strong>Canvas Area</strong>' displaying workflow diagram corresponding to the code of the current $editor_name, and the '<strong>Main Settings</strong>' panel.",
				);
				$options["steps"][] = array(
					"selector" => ".top_bar",
					"content" => "The '<strong>Top bar</strong>' displays information about the $editor_name you are editing and 2 tabs on the right side.",
				);
				$options["steps"][] = array(
					"selector" => ".top_bar > header > .title > .name",
					"content" => "This is where you can edit the name of your $editor_name.",
				);
				$options["steps"][] = array(
					"selector" => ".with_top_bar_tab > .tabs",
					"content" => "In the top-right corner of the '<strong>Top bar</strong>', you'll find 2 tabs to switch between the workflow diagram and the code editor. Both tabs are synchronized, so changes made in one tab are instantly reflected in the other.",
					//"placement" => "top-end",
				);
				$options["steps"][] = array(
					"selector" => "#ui > .taskflowchart > .tasks_menu",
					"content" => "This panel is the '<strong>Tasks List</strong>' panel, containing available tasks that you can drag and drop into your '<strong>Canvas Area</strong>'. Each task has its own logic and properties that you can configure through no-code settings.",
				);
				$options["steps"][] = array(
					"selector" => "#ui > .taskflowchart > .tasks_flow",
					"content" => "This panel is the '<strong>Canvas Area</strong>', where you can draw your workflow logic diagram and drop tasks from the '<strong>Tasks List</strong>' panel.",
				);
				$options["steps"][] = array(
					"selector" => "#settings",
					"content" => "At the bottom of the editor, you can find the '<strong>Main Settings</strong>' panel containing the settings for your $editor_name, allowing you to configure its arguments and annotations with specific validations, among other options.",
					//"placement" => "bottom",
				);
				break;
			
			case "dataaccess/edit_query":
				$options["steps"][] = array(
					"selector" => "#main_column",
					"content" => "The <strong>Query Editor</strong> allows you to write SQL code directly or create it visually, using a tables diagram or no-code configurations.<br/>This editor includes a '<strong>Top bar</strong>', '<strong>No-code Configurations</strong>', and the '<strong>Main Settings</strong>' panel. For 'SELECT' statements, it also displays a '<strong>Tables Diagram</strong>'.",
				);
				$options["steps"][] = array(
					"selector" => ".top_bar",
					"content" => "The '<strong>Top bar</strong>' displays information about the query you are editing.",
				);
				$options["steps"][] = array(
					"selector" => ".edit_single_query .relationship .rel_type select",
					"content" => "This is where you can edit the type of your query.",
				);
				$options["steps"][] = array(
					"selector" => ".edit_single_query .relationship .rel_name input",
					"content" => "This is where you can edit the name of your query.",
				);
				$options["steps"][] = array(
					"selector" => ".edit_single_query .relationship .query .sql_text_area",
					"content" => "This is the SQL code area where you can write your statements directly.",
				);
				$options["steps"][] = array(
					"selector" => ".edit_single_query .relationship.query_select .query .query_select .query_ui",
					"content" => "This is the Tables diagram canvas where you can draw tables and connect them, automatically generating your SQL code.",
					"do_not_remove_on_page_load" => true,
					"skip_if_not_exists" => true,
				);
				$options["steps"][] = array(
					"selector" => ".edit_single_query .relationship.query_select .query .query_select .query_settings, .edit_single_query .relationship.query_insert .query_insert_update_delete, .edit_single_query .relationship.query_update .query_insert_update_delete, .edit_single_query .relationship.query_delete .query_insert_update_delete",
					"content" => "In the '<strong>No-code Configurations</strong>' section, you can configure your query using no-code methods by adding and customizing query components.",
					"do_not_remove_on_page_load" => true,
					"skip_if_not_exists" => true,
				);
				$options["steps"][] = array(
					"selector" => ".settings",
					"content" => "At the bottom of the editor, you can find the '<strong>Main Settings</strong>' panel containing the settings for your query, allowing you to configure parameters and result map and class.",
				);
				break;
		}
		
		//prepare video tutorials
		$tutorials = VideoTutorialHandler::getSimpleTutorials($project_url_prefix, $online_tutorials_url_prefix);
		$filtered_tutorials = VideoTutorialHandler::filterTutorials($tutorials, $entity_code, $admin_type);
		
		if ($filtered_tutorials) {
			$tutorials_exist = true;
			$tutorials_html = self::getPageTourGuideTutorialsHtml($filtered_tutorials);
			
			$options["steps"][] = array(
				"selector" => "body",
				"content" => 'To complete your tour please watch all the following videos: <div class="tutorials"></div>',
				"onStepEnd" => 'function(current_step) { 
					var div = MyTourGuide.tourguide._shadowRoot.querySelector(".guided-tour-step .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content .tutorials");
					
					if (div)
						div.innerHTML = \'' . addcslashes(str_replace("\n", "", $tutorials_html), "\\'") . ' To watch more videos please go to <a href="' . $online_tutorials_url_prefix . 'video/simple" target="blank">Online Tutorials</a>\';
				}',
			);
		}
		
		if ($options["steps"])
			for ($i = 0, $t = count($options["steps"]); $i < $t; $i++) {
				$step = $options["steps"][$i];
				
				if (!isset($step["title"]))
					$step["title"] = "Tooltip Tour Guide";
				
				if ($i + 1 < $t) //in all slides except the last
					$step["actions"] = array(
						array( //show disable button as a secondary button and in the last slide show it as primary button
							//"id" => "disable_tour",
							"label" => "Disable Tour",
							"action" => "dontShowTourAnymore",
							"class" => "secondary",
							"title" => "Do NOT show this Tour anymore.",
						),
						array( //show skip button, except at the last step
							//"id" => "skip_tour",
							"label" => "Skip Tour",
							"action" => "stop",
							"primary" => true,
							"title" => "Skip now this Tour and show it next time.",
						)
					);
				else //in the last slide
					$step["actions"] = array(
						array( //show disable button as a primary button
							//"id" => "complete_and_disable_tour",
							"label" => "Complete Tour and disable it for the next time",
							"action" => "dontShowTourAnymore",
							"class" => "secondary complete_tour",
							"title" => "End Tour and do NOT show it anymore.",
						),
					);
				
				$options["steps"][$i] = $step;
			}
		
		return $options;
	}
	
	public static function getPageTourGuideTutorialsHtml($tutorials) {
		$html = '';
		
		if ($tutorials)
			foreach ($tutorials as $id => $tutorial) {
				if ($tutorial["video"] || $tutorial["items"]) {
					$attrs = '';
					$collapse_icon = '';
					//$tutorial["image"] = "http://jplpinto.localhost/__system/img/logo_full_white.svg";
					
					if ($tutorial["items"]) {
						$attrs = 'onClick="toggleTourGuideTutorialSubTutorials(this)"';
						$collapse_icon = '<span class="icon dropdown_arrow"></span>';
					}
					else
						$attrs = 'onClick="openTourGuideTutorialVideoPopup(this)" video_url="' . $tutorial["video"] . '" image_url="' . $tutorial["image"] . '"';
					
					$html .= '<li class="' . ($tutorial["items"] ? 'with_sub_tutorials' : 'with_video') . '">
							<div class="tutorial_header" ' . $attrs . '>
								<div class="tutorial_title"' . ($tutorial["description"] ? ' title="' . str_replace('"', '&quot;', strip_tags($tutorial["description"])) . '"' : '') . '><span class="icon video"></span>' . $tutorial["title"] . $collapse_icon . '</div>
								' . ($tutorial["description"] ? '<div class="tutorial_description">' . $tutorial["description"] . '</div>' : '') . '
							</div>';
					
					if ($tutorial["items"])
						$html .= self::getPageTourGuideTutorialsHtml($tutorial["items"]);
					
					$html .= '</li>';
				}
			}
		
		if ($html)
			$html = "<ul>$html</ul>";
		
		return $html;
	}
}
?>
