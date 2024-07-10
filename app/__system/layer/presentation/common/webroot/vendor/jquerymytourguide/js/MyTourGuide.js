/*
 * Copyright (c) 2007 PHPMyFrameWork - Joao Pinto
 * AUTHOR: Joao Paulo Lopes Pinto -- http://jplpinto.com
 * 
 * The use of this code must be allowed first by the creator Joao Pinto, since this is a private and proprietary code.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS 
 * OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY 
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR 
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL 
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, 
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER 
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT 
 * OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE. IN NO EVENT SHALL 
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN 
 * AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE 
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

var MyTourGuide = new MyTourGuideClass();

function MyTourGuideClass() {
	var me = this;
	
	me.tourguide = null;
	me.options = null;
	
	me.init = function (opts) {
		opts = $.isPlainObject(opts) ? opts : {};
		me.options = opts;
		
		//prepare options
		var opts_start = opts.onStart;
		var on_start = typeof opts_start == "function" ? function(options) {
			var status = me.onStart(options);
			
			if (status !== false)
				opts_start(options);
		} : me.onStart;
		
		var opts_step = opts.onStep;
		var on_step = typeof opts_step == "function" ? function(current_step, type) {
			var status = me.onStep(current_step, type);
			
			if (status !== false)
				opts_step(current_step, type);
		} : me.onStep;
		
		var opts_stop = opts.onStop;
		var on_stop = typeof opts_stop == "function" ? function(options) {
			var status = me.onStop(options);
			
			if (status !== false)
				opts_stop(options);
		} : me.onStop;
		
		var opts_complete = opts.onComplete;
		var on_complete = typeof opts_complete == "function" ? function() {
			var status = me.onComplete();
			
			if (status !== false)
				opts_complete();
			
			return status; //if is false the system doesn't close the tour.
		} : me.onComplete;
		
		me.options.steps = me.prepareSteps(opts ? opts.steps : null);
		me.options.onStart = on_start;
		me.options.onStep = on_step;
		me.options.onStop = on_stop;
		me.options.onComplete = on_complete;
		
		//console.log(me.options);
		return me.prepareTourguide();
	};
	
	//handlers
	me.start = function(step_index) {
		me.tourguide && me.tourguide.start(step_index);
	};
	me.stop = function() {
		me.tourguide && me.tourguide.stop();
	};
	me.complete = function() {
		me.tourguide && me.tourguide.complete();
	};
	me.restart = function() {
		me.stop();
		me.start();
	};
	me.isActive = function() {
		return me.tourguide && me.tourguide._active;
	};
	
	//callbacks
	me.onStart = function(options) {
		//console.log("onStart");
		//console.log(options);
		
		var current_index = me.tourguide._current ? me.tourguide._current : 0;
		var current_step = me.tourguide._steps[current_index];
		me.prepareStep(current_step);
		
		return true;
	};
	
	me.onStep = function(current_step, type) {
		me.prepareStep(current_step, type);
	};
	
	me.onStop = function(options) {
		//console.log("onStop");
		//console.log(options);
		
		//empty - currently, do nothing
	};
	
	me.onComplete = function() {
		//console.log("onComplete");
		
		//empty - currently, do nothing
	};
	
	//utils
	me.prepareTourguide = function() {
		//stop previous tourguide if started before
		if (me.isActive())
			me.stop();
		
		//init me.tourguide
		if (me.options.steps.length > 0) {
			me.tourguide = new Tourguide(me.options);
			
			//append new style to me.tourguide._shadowRoot
			var shadow_root = $(me.tourguide._shadowRoot);
			
			if (shadow_root.children(".mytourguide_style").length == 0) {
				var css = me.getStyle() + (me.options.css ? me.options.css : "");
				var style = '<style class="mytourguide_style">'.concat(css, "</style>");
				shadow_root.append(style);
			}
			
			//overwrite the complete handler
			me.tourguide.complete = function() {
				if (me.isActive()) {
					var status = typeof me.options.onBeforeComplete != "function" || me.options.onBeforeComplete();
					
					if (status !== false) {
						me.tourguide.stop();
						me.tourguide._options.onComplete();
					}
				}
			};
			
			//console.log(me.tourguide);
			//console.log(me.tourguide._shadowRoot);
			//console.log($(me.tourguide._shadowRoot).children("style"));
			
			return true;
		}
		
		return false;
	};
	
	me.prepareSteps = function(steps) {
		if (!$.isArray(steps))
			steps = [];
		else
			for (var i = 0, t = steps.length; i < t; i++) {
				var step = steps[i];
				
				if (step.selector) {
					var exists = $(step.selector).length > 0;
					//console.log(step.selector+":"+exists);
					
					if (!exists && !step.do_not_remove_on_page_load) {
						steps.splice(i, 1);
						i--;
						t--;
					}
					else if (("" + step.selector).indexOf(",") != -1) {
						var parts = ("" + step.selector).split(",");
						steps[i].selector = parts[0];
						parts.shift();
						steps[i].step_selector = parts.join(",");
					}
				}
			}
		
		return steps;
	};
	
	me.prepareStep = function(current_step, type) {
		if (current_step) {
			var current_step_index = current_step.index;
			var current_step_options = me.tourguide._options.steps[current_step_index];
			var node = current_step.highlight && current_step.highlight.nodes ? current_step.highlight.nodes[0] : null;
			var previous_step_index = current_step_index - 1;
			var previous_step_options = previous_step_index >= 0 ? me.tourguide._options.steps[previous_step_index] : null;
			var next_step_index = current_step_index + 1;
			var next_step_options = next_step_index < me.tourguide._options.steps.length ? me.tourguide._options.steps[next_step_index] : null;
			
			me.prepareStepArrow(current_step);
			
			if (current_step_options) {
				if (typeof current_step_options.onStepStart == "function")
					if (current_step_options.onStepStart(current_step) === false)
						return false;
				
				if (!node && current_step_options.step_selector) {
					var parts = current_step_options.step_selector.split(",");
					
					for (var i = 0, t = parts.length; i < t; i++) {
						var step_selector = parts[i];
						node = $(step_selector)[0];
						//console.log(node);
						
						if (node) {
							parts[i] = current_step_options.selector;
							me.options.steps[current_step_index].step_selector = parts.join(",");
							me.options.steps[current_step_index].selector = step_selector;
							
							if (me.prepareTourguide(current_step_index))
								me.start(current_step_index);
							
							return false;
						}
					}
				}
				
				if (!node && current_step_options.selector && !current_step_options.skip_if_not_highlight) {
					var elm = $(current_step_options.selector);
					node = elm[0];
					
					if (node && !$(node).data("tourguide_refreshed")) {
						$(node).data("tourguide_refreshed", 1);
						
						if (me.prepareTourguide(current_step_index))
							me.start(current_step_index);
						
						return false;
					}
				}
				
				if (!node) { //set bullet point to inactive
					$(current_step.container.nodes[0].parentNode).children(".guided-tour-step").each(function(idx, step_elm) { //Do not use .parent() bc it doesn't work
						var bullet_point = $(step_elm).find(" > .guided-tour-step-tooltip > .guided-tour-step-tooltip-inner > .guided-tour-step-footer > .guided-tour-step-bullets > ul > li")[current_step_index];
						
						if (bullet_point)
							$(bullet_point).addClass("inactive");
					});
				}
				
				if (!node && current_step_options.selector && current_step_options.hasOwnProperty("step_id")) {
					if (type == "next" || type == "previous") {
						if (previous_step_options && current_step_options.step_id == previous_step_options.step_id) {
							if (type == "next") {
								if (current_step.last)
									me.tourguide.stop();
								else
									me.tourguide.next();
							}
							else if (type == "previous")
								me.tourguide.previous();
						}
						else if (next_step_options && current_step_options.step_id == next_step_options.step_id)
							me.tourguide.next();
					}
					else if (!type) { //it means the user clicked at the bullet points
						if (previous_step_options && current_step_options.step_id == previous_step_options.step_id)
							me.tourguide.previous();
						else if (next_step_options && current_step_options.step_id == next_step_options.step_id)
							me.tourguide.next();
					}
				}
				
				if (!node && (current_step_options.skip_if_not_exists || current_step_options.skip_if_not_highlight)) {
					if (type == "next") {
						if (current_step.last)
							me.tourguide.stop();
						else
							me.tourguide.next();
					}
					else if (type == "previous") {
						if (previous_step_options)
							me.tourguide.go(previous_step_index); //Do not use previous bc it enter in a infinit loop, and I don't know why...
						else if (next_step_options)
							me.tourguide.next();
					}
					else if (!type) {
						if (previous_step_options)
							me.tourguide.go(previous_step_index); //Do not use previous bc it enter in a infinit loop, and I don't know why...
						else if (next_step_options)
							me.tourguide.next();
					}
				}
				
				if (typeof current_step_options.onStepEnd == "function")
					if (current_step_options.onStepEnd(current_step) === false)
						return false;
			}
		}
		
		return true;
	};
	
	me.prepareStepArrow = function(current_step) {
		var arrow = current_step.arrow && current_step.arrow.nodes ? current_step.arrow.nodes[0] : null;
			
		if (arrow) {
			setTimeout(function() {
				var position = me.getStepArrowPosition(current_step);
				//console.log(current_step);
				//console.log(position);
				
				if (position.indexOf("top") != -1)
					$(arrow).addClass("title-color");
				else
					$(arrow).removeClass("title-color");
			}, 500); //cannot be 300 bc the arrow didn't get positioning yet.
		}
	};
	
	me.getStepArrowPosition = function(current_step) {
		var position = "";
		var arrow = current_step.arrow && current_step.arrow.nodes ? current_step.arrow.nodes[0] : null;
		
		if (arrow) {
			var top = parseInt(arrow.style.top);
			var bottom = parseInt(arrow.style.bottom);
			var left = parseInt(arrow.style.left);
			var right = parseInt(arrow.style.right);
			
			var vertical_position = top && top < 0 ? "top" : (bottom && bottom < 0 ? "bottom" : null);
			var horizontal_position = left && left < 0 ? "left" : (right && right < 0 ? "right" : null);
			
			if (!vertical_position && horizontal_position && top > 0 && top < 20)
				vertical_position = "top";
			
			position += vertical_position ? vertical_position : "";
			position += (position && horizontal_position ? "-" : "") + (horizontal_position ? horizontal_position : "");
		}
		
		return position;
	};
	
	me.getStyle = function() {
		return ':host {'
				+ '--tourguide-step-title-background-color: #0d6efd;'
				+ '--tourguide-step-title-color: #fff;'
				+ '--tourguide-step-button-close-color: #fff;'
				+ '--tourguide-bullet-inactive-color:#eee;'
			+ '}'
			+ '.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content {'
				+ 'overflow:auto;'
				+ 'max-height:calc(100vh - 200px);'
				+ 'max-width:calc(100vh - 50px);'
			+ '}'
			+ '.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-content-wrapper {'
				+ 'margin:1.5em 2em;'
				+ 'font-size:12px;'
			+ '}'
			+ '.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-title {'
				+ 'font-size:inherit;'
				+ 'margin:-1.5em -2em 1.5em;'
				+ 'padding:10px;'
				+ 'background:var(--tourguide-step-title-background-color);'
				+ 'color:var(--tourguide-step-title-color);'
				+ 'border-top-left-radius:5px;'
				+ 'border-top-right-radius:5px;'
			+ '}'
			+ '.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-bullets {'
				+ 'margin-top:-1em;'
			+ '}'
			+ '.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-bullets ul {'
				+ 'margin-bottom:.5em;'
			+ '}'
			+ '.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-bullets ul li.inactive {'
				+ 'display:none;'
				//+ 'background-color:var(--tourguide-bullet-inactive-color);'
			+ '}'
			+ '.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-button-close {'
				+ 'top:5px;'
				+ 'right:5px;'
			+ '}'
			+ '.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-button-prev,'
			+ '  .guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-button-next,'
			+ '  .guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-button-complete {'
				+ 'width:1.5em;'
				+ 'height:1.5em;'
				+ 'margin-top:0;'
			+ '}'
			+ '.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-button-prev .guided-tour-icon,'
			+ '  .guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-button-next .guided-tour-icon,'
			+ '  .guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-step-button-complete .guided-tour-icon {'
				+ 'width:1.5em;'
				+ 'height:1.5em;'
			+ '}'
			+ '.guided-tour-step.active .guided-tour-step-tooltip .guided-tour-step-tooltip-inner .guided-tour-arrow.title-color {'
				+ 'background:var(--tourguide-step-title-background-color);'
			+ '}';
			
	};
};
