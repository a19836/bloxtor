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

function CreateWidgetBootstrapContainerClassObj(ui_creator, menu_widget, widget_tag, cols) {
	var me = this;
	
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
		//setTimeout(function() { //must be in settimeout bc we are overwriting the widget.
			var html = me.getContainerHtml(cols);
			var new_widget = $(html);
			widget.after(new_widget);
			
			ui_creator.convertHtmlElementToWidget(new_widget);
			ui_creator.replaceWidgetWithWidget(widget, new_widget);
			
			widget[0] = new_widget[0];
		//}, 100);
	};
	
	me.getContainerHtml = function(cols) {
		var html = '<div class="row">';
		
		if ($.isArray(cols))
			for (var i = 0, t = cols.length; i < t; i++)
				html += '<div class="col-' + cols[i] + '">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</div>';
		
		html += '</div>';
		
		return html;
	};
}
