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

function CreateWidgetBootstrapClassObj(ui_creator, menu_widget, widget_tag) {
	var me = this;
	me.available_items = [];
	me.widget = null;
	
	me.extend = function(obj) {
		for (var x in this)
			obj[x] = this[x];
	};
	
	me.init = function() {
		menu_widget.attr({
			"data-on-drag-stop-func": ui_creator.obj_var_name + ".menu_widgets_objs['" + widget_tag + "'].onDropMenuWidget"
		});
	};
	
	me.onDropMenuWidget = function(menu_widget, widget, event, ui_obj) {
		me.showPopup(widget, "Buttons", me.available_items);
	};
	
	me.showPopup = function(widget, title, available_items) {
		me.widget = widget;
		
		//remove previous popup
		var ui = ui_creator.getUI();
		ui.children(".layout-ui-editor-bootstrap-widgets-popup:not(." + widget_tag + ")").remove();
		
		var popup_content = ui.children(".layout-ui-editor-bootstrap-widgets-popup." + widget_tag);
		
		if (popup_content[0] && ui_creator.popup_elm.is(popup_content))
			ui_creator.showPopup();
		else {
			ui_creator.destroyPopup();
			
			//create popup
			var handler = function(elm, html) {
				me.updateWidgetHtml(me.widget, html);
				me.widget = null;
				
				ui_creator.hidePopup();
			};
			popup_content = me.getPopupContent(title, available_items, handler);
			popup_content.addClass(widget_tag);
			ui.append(popup_content);
			
			ui_creator.initPopup({
				elementToShow: popup_content,
				parentElement: document,
				onClose: function() {
					if (me.widget && me.widget[0] && me.widget[0].parentNode) //check if widget really exists, bc this function is called on hide and if the user selects a bootstrap widget, then the handler will replace this widget, whcih means its parentNode will not exists, bc the widget was removed before.
						ui_creator.deleteTemplateWidget(me.widget);
					
					me.widget = null;
				},
			});
			ui_creator.showPopup();
		}
	};
	
	me.getPopupContent = function(title, available_items, handler) {
		var has_multiple_versions = $.isPlainObject(available_items);
		
		//prepare popup html
		var html = '<div class="layout-ui-editor-bootstrap-widgets-popup' + (has_multiple_versions ? ' multiple_versions' : '') + '">'
				+ '<div class="title">Bootstrap - ' + title + '</div>'
				+ '<div class="content">'
					+ '<div class="info">Choose one of the following widgets:</div>';
		
		//prepare bootstrap versions, if apply
		if (has_multiple_versions) {
			html += '<ul class="tabs tabs_transparent">';
			var versions_content = '';
			
			for (var version in available_items) {
				var v_id = ("" + version).replace(/\./g, "_");
				
				html += '<li><a href="#items-' + v_id + '">Bootstrap v' + version + '</a></li>';
				versions_content += '<ul id="items-' + v_id + '"></ul>';
			}
			
			html += '</ul>' + versions_content;
		}
		else
			html += '<ul id="items-default"></ul>';
		
		html += '</div>'
			+ '<div class="buttons">'
				+ '<button>Add Selected Widget</button>'
			+ '</div>'
		+ '</div>';
		
		var popup_content = $(html);
		var content = popup_content.children(".content");
		
		popup_content.find(" > .buttons > button").on("click", function(e) {
			var input = popup_content.find("ul li input:checked").first();
			var widget_html = input.data("widget_html");
			
			if (widget_html)
				handler(this, widget_html);
			else
				ui_creator.showError("Please select one of the listed widgets");
		});
		
		if (has_multiple_versions) {
			content.tabs();
			
			for (var version in available_items)  {
				var v_id = ("" + version).replace(/\./g, "_");
				
				me.preparePopupContentItems(content.children("#items-" + v_id), available_items[version]);
			}
		}
		else
			me.preparePopupContentItems(content.children("#items-default"), available_items);
		
		//selects first widget
		content.find("ul li input").first().prop("checked", true).attr("checked", "checked").parent().closest("li").addClass("selected");
		
		return popup_content;
	};
	
	me.preparePopupContentItems = function(ul, items) {
		var is_empty = !$.isArray(items) || items.length == 0;
		
		if (!is_empty) {
			for (var i = 0, t = items.length; i < t; i++) {
				var item = me.getPopupContentItem(items[i]);
				
				if (i % 2)
					item.addClass("highlight");
				
				ul.append(item);
			}
		}
		else
			ul.append('<li class="items-empty">There are no elements to show...</li>');
	};
	
	me.getPopupContentItem = function(data) {
		var html = '<li' + (data["class"] ? ' class="' + data["class"] + '"' : '') + (data["title"] ? ' title="' + data["title"] + '"' : '') + '>'
				+ '<input type="radio" name="widget" />'
				+ '<img src="' + data["image"] + '"/>'
				+ '<span class="name">' + data["name"] + '</span>'
			+ '</li>';
		var item = $(html);
		
		item.on("click", function(e) {
			var li = $(this);
			li.parent().children("li.selected").removeClass("selected");
			li.children("input").trigger("click");
			li.addClass("selected");
		});
		
		item.children("input").data("widget_html", data["html"]).on("click", function(e) {
			e.stopPropagation && e.stopPropagation();
			
			var li = $(this).parent().closest("li");
			li.parent().children("li.selected").removeClass("selected");
			li.addClass("selected");
		});
		
		return item;
	};
	
	me.updateWidgetHtml = function(widget, html) {
		var new_widget = $(html);
		widget.after(new_widget);
		
		ui_creator.convertHtmlElementToWidget(new_widget);
		ui_creator.replaceWidgetWithWidget(widget, new_widget);
	};
}
