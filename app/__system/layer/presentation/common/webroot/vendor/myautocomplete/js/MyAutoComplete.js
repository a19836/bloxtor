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

var MyAutoComplete = {
	document_event_click_init : false,
	inputs : [],
	currentFocus : null,
	
	init : function(input, available_options, opts) {
		/* #ADD_SECURITY_CODE_HERE# */  //Important: do not remove this comment because it will be used by the other/scripts/obfuscate_js_files.php to only allow this code in the allowed domains.
		
		if (!input)
			return false;
		
		//the autocomplete function takes two arguments, the text field element and an array of possible autocompleted values
		var me = this;
		var is_textarea = input.nodeName.toUpperCase() == "TEXTAREA";
		var input_id = input.id ? input.id : parseInt(Math.random() * 10000);
		var autocomplete_elm_id = input_id + "autocomplete-list";
		
		me.inputs.push(input);
		
		//execute a function when someone writes in the text field
		input.addEventListener("input", function(e) {
			//close any already open lists of autocompleted values
			me.deleteAllAutoCompleteElms();
			
			//get input value
			var get_handler = opts ? opts["get"] : null;
			
			if (typeof get_handler != "function")
				get_handler = function(inp) {
					return inp.value;
				};
			
			var val = get_handler(input);
			
			if (!val)
				return false;
			
			me.currentFocus = -1;
			
			//create a DIV element that will contain the items (values)
			var autocomplete_elm = document.createElement("DIV");
			autocomplete_elm.setAttribute("id", autocomplete_elm_id);
			autocomplete_elm.setAttribute("class", "autocomplete-items");
			
			autocomplete_elm.style.top = (input.offsetTop + input.offsetHeight) + "px";
			autocomplete_elm.style.left = input.offsetLeft + "px";
			autocomplete_elm.style.width = input.offsetWidth + "px";
			
			//append the DIV element as a child of the autocomplete container
			input.parentNode.appendChild(autocomplete_elm);
			input.autocomplete_elm = autocomplete_elm;
			
			//for each item in the array...
			if (available_options && available_options.length)
				for (var i = 0, t = available_options.length; i < t; i++) {
					var available_option = "" + available_options[i];
					
					//check if the item starts with the same letters as the text field value
					if (available_option.substr(0, val.length).toUpperCase() == val.toUpperCase()) {
						//create a DIV element for each matching element
						var item_elm = document.createElement("DIV");
						item_elm.innerHTML = "<strong>" + available_option.substr(0, val.length) + "</strong>"; //make the matching letters bold.
						item_elm.innerHTML += available_option.substr(val.length); //add other string part
						item_elm.innerHTML += "<input type='hidden' value='" + available_option + "'>"; //insert a input field that will hold the current array item's value.
						
						//execute a function when someone clicks on the item value (DIV element)
						item_elm.addEventListener("click", function(e) {
							var selected_value = this.getElementsByTagName("input")[0].value;
							
							//insert the value for the autocomplete text field
							var set_handler = opts ? opts["set"] : null;
				
							if (typeof set_handler != "function")
								set_handler = function(inp, new_value) {
									inp.value = new_value;
								};
							
							set_handler(input, selected_value);
							
							//close the list of autocompleted values, (or any other open lists of autocompleted values
							me.deleteAllAutoCompleteElms();
						});
						
						autocomplete_elm.appendChild(item_elm);
					}
				}
		});
		
		//execute a function presses a key on the keyboard
		input.addEventListener("keydown", function(e) {
			var autocomplete_elm = input.autocomplete_elm && input.autocomplete_elm.parentNode ? input.autocomplete_elm : document.getElementById(autocomplete_elm_id);
			var items = null;
			
			if (autocomplete_elm) 
				items = autocomplete_elm.getElementsByTagName("div");
			
			if (e.keyCode == 40) { //down
				if (items && items.length)
					e.preventDefault();
				
				//If the arrow DOWN key is pressed, increase the currentFocus variable
				me.currentFocus++;
				
				//and and make the current item more visible
				me.selectCurrentItemActive(items);
			} 
			else if (e.keyCode == 38) { //up
				if (items && items.length)
					e.preventDefault();
				
				//If the arrow UP key is pressed, decrease the currentFocus variable
				me.currentFocus--;
				
				//and and make the current item more visible
				me.selectCurrentItemActive(items);
			} 
			else if (e.keyCode == 13) { //enter
				if (items && items.length) { //If the ENTER key is pressed, prevent the form from being submitted
					e.preventDefault();
					
					//click on the "active" item
					if (me.currentFocus > -1)
						items[me.currentFocus].click();
				}
			}
			else if (e.key === "Escape") { //escape
				if (items && items.length)
					me.deleteAllAutoCompleteElms();
			}
		});
		
		//execute a function when someone clicks in the document
		if (!this.document_event_click_init) {
			this.document_event_click_init = true;
			
			document.addEventListener("click", function (e) {
				me.deleteAllAutoCompleteElms(e.target);
			});
		}
	},
	
	//set the current focused item as "active"
	selectCurrentItemActive : function(items) {
		if (!items) 
			return false;
		
		//start by removing the "active" class on all items
		this.unselectActiveItems(items);
		
		if (this.currentFocus >= items.length) 
			this.currentFocus = 0;
		
		if (this.currentFocus < 0) 
			this.currentFocus = (items.length - 1);
		
		//add class "autocomplete-active"
		if (items && items.length)
			items[this.currentFocus].classList.add("autocomplete-active");
	},
	
	//remove the "active" class from all autocomplete items
	unselectActiveItems : function(items) {
		for (var i = 0, t = items.length; i < t; i++)
			items[i].classList.remove("autocomplete-active");
	},
	
	//delete all autocomplete lists in the document, except the one passed as an argument
	deleteAllAutoCompleteElms : function(elm) {
		var autocomplete_elms = document.getElementsByClassName("autocomplete-items");
		
		for (var i = 0, t = autocomplete_elms.length; i < t; i++)
			if (elm != autocomplete_elms[i] && this.inputs.indexOf(elm) == -1)
				autocomplete_elms[i].parentNode.removeChild(autocomplete_elms[i]);
	}
}
