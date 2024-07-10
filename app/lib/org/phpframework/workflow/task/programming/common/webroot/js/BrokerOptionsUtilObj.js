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

var BrokerOptionsUtilObj = {
	
	//ui_settings is optional
	initFields : function(broker_div, brokers_options, method_obj, ui_settings) {
		ui_settings = typeof ui_settings != "undefined" && ui_settings && $.isPlainObject(ui_settings) ? ui_settings : {};
		
		var other_broker_variable_label = ui_settings.hasOwnProperty("other_broker_variable_label") && ui_settings["other_broker_variable_label"] ? ui_settings["other_broker_variable_label"] : "From some other broker variable";
		var broker_prefix_label = ui_settings.hasOwnProperty("broker_prefix_label") && ui_settings["broker_prefix_label"] ? ui_settings["broker_prefix_label"] : "From broker: ";
		var html_options = '<option value="">' + broker_prefix_label + '</option>';
		
		if (brokers_options) {
			var existent_broker = typeof method_obj != "undefined" && method_obj != null ? ("" + method_obj).replace(/['"]+/g, "") : "";
			existent_broker = existent_broker.trim().substr(0, 1) == '$' ? existent_broker.trim().substr(1) : existent_broker;
			
			for (var broker_name in brokers_options) {
				var broker = "" + brokers_options[broker_name];
				broker = broker.trim().substr(0, 1) == '$' ? broker.trim().substr(1) : broker;
				
				html_options += '<option value="' + broker.replace(/"/g, "&quot;") + '"' + (existent_broker == broker.replace(/['"]+/g, "") ? ' selected' : '') + '>' + broker_prefix_label + broker_name + '</option>';
			}
		}
		broker_div.find("select").html(html_options);
		
		if (method_obj) {
			method_obj = "" + method_obj + "";
			method_obj = method_obj.trim().substr(0, 1) == '$' ? method_obj.trim().substr(1) : method_obj;
			broker_div.find("input").val(method_obj);
			broker_div.find("input").attr("default_variable", method_obj);
		}	
		else {
			var default_broker = this.getDefaultBroker(brokers_options);
			default_broker = default_broker ? default_broker : "";
			default_broker = default_broker.trim().substr(0, 1) == '$' ? default_broker.trim().substr(1) : default_broker;
			broker_div.find("select").val(default_broker);
			broker_div.find("input").val(default_broker);
		}
	},
	
	getDefaultBroker : function(brokers_options) {
		if (brokers_options) {
			for (var broker_name in brokers_options) {
				return brokers_options[broker_name];
			}
		}
		return null;
	},
	
	onBrokerChange : function(elm) {
		elm = $(elm);
		var input = elm.parent().find("input");
		
		var broker = elm.val();
		/*if (!broker) {
			broker = input.attr("default_variable");
			broker = broker ? broker : "";
		}*/
		
		input.val(broker);
	},
	
	chooseCreatedBrokerVariable : function(elm) {
		ProgrammingTaskUtil.onProgrammingTaskChooseCreatedVariable(elm);
		
		$(elm).parent().children("select").val("");
	},
};
