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

//Leave this code here, because is adding the TRIM function to the IE browsers. Otherwise the browser gives errors.
if(typeof String.prototype.trim !== 'function') {
  String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, ''); 
  }
}

var MyCodeBeautifier = {
	//Note: This doesn't format switch/case/default statements
	//TODO: fix switch formatting
	
	prettyPrint : function(code) {
		/* #ADD_SECURITY_CODE_HERE# */  //Important: do not remove this comment because it will be used by the other/scripts/obfuscate_js_files.php to only allow this code in the allowed domains.
		
		var prefix = "";
		var open_php_tag = true;
		var open_single_quotes = false;
		var open_double_quotes = false;
		var open_single_comments = false;
		var open_multiple_comments = false;
		var paranteses_count = 0;
		var line, j, char;
		
		//fix the issue where code: <?$x or <?php$x. Transform it to: <? $x or <?php $x
		var m = null;
		var regex = /<\?(php)?\$/g;
		while ((m = regex.exec(code))) {
			var index = m.index;
			
			code = code.substr(0, index + m[0].length - 1) + " " + code.substr(index + m[0].length - 1);
		}
		
		var new_code = prefix;
		var lines = code.replace(/\r/g, "").split("\n");
		for (var i = 0; i < lines.length; i++) {
			line = lines[i];
		
			if (!open_single_quotes && !open_double_quotes && !open_single_comments && !open_multiple_comments)
				line = line.trim();
			
			line += "\n";
		
			for (j = 0; j < line.length; j++) {
				char = line[j];
				
				if (char == ">" && line[j - 1] == "?" && open_php_tag && !open_single_quotes && !open_double_quotes && !open_single_comments && !open_multiple_comments)
					open_php_tag = false;
				else if (char == "<" && line[j + 1] == "?" && !open_php_tag && !open_single_quotes && !open_double_quotes && !open_single_comments && !open_multiple_comments)
					open_php_tag = true;
				
				if (open_php_tag) {
					if (char == '"' && !open_single_quotes && !open_single_comments && !open_multiple_comments && !this.isCharEscaped(line, j)) {
						open_double_quotes = !open_double_quotes;
						new_code += char;
					}
					else if (char == "'" && !open_double_quotes && !open_single_comments && !open_multiple_comments && !this.isCharEscaped(line, j)) {
						open_single_quotes = !open_single_quotes;
						new_code += char;
					}
					else if (char == "\n" && open_single_comments) {
						open_single_comments = false;
						new_code += char + prefix;
					}
					else if (open_multiple_comments && char == "*" && line[j + 1] == "/") {
						open_multiple_comments = false;
						j++;
						new_code += "*/";
					}
					else if (char == "/" && !open_single_quotes && !open_double_quotes && !open_single_comments && !open_multiple_comments && (line[j + 1] == "*" || line[j + 1] == "/")) {
						if (line[j + 1] == "*") {
							open_multiple_comments = true;
							j++;
							new_code += "/*";
						}
						else {
							open_single_comments = true;
							j++;
							new_code += "//";
						}
					}
					else if (!open_single_quotes && !open_double_quotes && !open_single_comments && !open_multiple_comments) {
						if (char == "{") {
							prefix += "\t";
						
							if (j > 0 && line[j - 1] != " ")
								new_code += " ";
						
							new_code += char;
					
							if (line[j + 1] != "\n")
								new_code += "\n" + prefix;
						}
						else if (char == "}") {
							prefix = prefix ? prefix.substr(1) : "";
							
							if (j > 0 && line[j - 1] != "\n")
								new_code += "\n" + prefix + char;
							else if (new_code.match(/<\?(php)?\s*$/)) //otherwise we will get "<?}" which gives a php error. We must have "<? }" or "<?php }"
								new_code += (new_code.match(/\s+$/) ? "" : " ") + char; //only add space if there isn't a space char yet.
							else
								new_code = new_code.substr(0, new_code.length - 1) + char;//remove the last \t
							
							if (line[j + 1] != "\n")
								new_code += "\n" + prefix;
						}
						else if (char == "\n") {
							if (paranteses_count == 0) { //Removes all previous lines that are empty, excluding the first empty line, bc it only allows 2 empty lines.
								if (new_code.match(/\r?\n\r?\s*\r?\n\r?\s*$/)) 
									new_code = new_code.replace(/\r?\n\r?\s*$/, "");
							}
							else if (new_code.match(/\r?\n\r?\s*$/)) //Removes all previous lines that are empty, including the first empty line, bc it only allows 1 empty line.
								new_code = new_code.replace(/\r?\n\r?\s*$/, "");
							
							new_code += char + prefix;
						}
						else if (char == "(") {
							paranteses_count++;
					
							prefix += "\t";
							new_code += char;
						}
						else if (char == ")") {
							paranteses_count = paranteses_count > 1 ? paranteses_count - 1 : 0;
					
							prefix = prefix ? prefix.substr(1) : "";
					
							if (line[j - 1] == "\n" || j == 0)
								new_code = new_code.substr(0, new_code.length - 1) + char;//remove the last \t
							else
								new_code += char;
						}
						else if (char == ";" && paranteses_count == 0) {
							new_code += char;
					
							if (line[j + 1] != "\n")
								new_code += "\n" + prefix;
						}
						else {
							var next_char = line[j + 1];
							var previous_char = line[j - 1];
					
							if (char == "," && next_char != " ")
								new_code += char + " ";
							else if (char == "&" && next_char == "&") {
								new_code += (previous_char != " " ? " " : "") + "&&" + (line[j + 2] != " " ? " " : "");
								j++;
							}
							else if (char == "|" && next_char == "|") {
								new_code += (previous_char != " " ? " " : "") + "||" + (line[j + 2] != " " ? " " : "");
								j++;
							}
							else if (char == "=" || char == ">" || char == "<" || char == "!") {
								if (char == ">" && previous_char == "-")
									new_code += char;
								else if (char == "=" && (previous_char == "-" || previous_char == "+"))
									new_code += char;
								else if (char == ">" && previous_char == "=") 
									new_code += char + (next_char != " " ? " " : "");
								else if (char == "=" && next_char == ">")
									new_code += (previous_char != " " ? " " : "") + char;
								else if (char == "<" && next_char == "?")
									new_code += char;
								else if (char == ">" && previous_char == "?")
									new_code += char;
								else if (char == ">")
									new_code += (previous_char != " " ? " " : "") + char + (next_char != " " && next_char != "=" ? " " : "");
								else if (char == "<")
									new_code += (previous_char != " " ? " " : "") + char + (next_char != " " && next_char != "=" ? " " : "");
								else if (char == "!" && next_char == "=")
									new_code += (previous_char != " " ? " " : "") + char;
								else if (char == "=")
									new_code += (previous_char != " " && previous_char != "=" && previous_char != "!" && previous_char != "<" && previous_char != ">" && previous_char != "." && previous_char != "?" ? " " : "") + char + (next_char != " " && next_char != "=" ? " " : "");
									//previous_char != "?", because of "<?=", this is: "<?= $x ?>"
								else
									new_code += char;
							}
							else if (char == " " && new_code.substr(new_code.length - 1) == "\n")	//This is for the cases "$x=1; $y=2;" which will convert the code to: "$x=1;\n $y=2;". Note that the space before $y should not be there! The right indentation is: "$x=1;\n$y=2;"
								new_code += "";//ignore space
							else	
								new_code += char;
						}
					}
					else
						new_code += char;
				}
				else
					new_code += char;
			}
		}
		
		/*
		* Fix the issue where the php code has multiple lines but the first line is together with the <?php tag, this is: <?php $x = 2;
		* Basically: 
		* 	if we have 
		* 		<? $x = 2; $t = 3; ?>
		* 	then we will have:
		* 		<? $x = 2; 
		*		$t = 3; 
		*		?>
		* 	So we added the code bellow, so we can have:
		* 		<? 
		* 		$x = 2; 
		*		$t = 3; 
		*		?>
		*
		*	If we have only 1 line, like: <? $x = 2; ?>, then the code will not change!
		*/
		var first_line = new_code.split("\n");
		first_line = first_line[0];
		
		if (first_line.match(/<\?(php)? [^\n]/) && !first_line.match(/\?>/)) {
			var m = first_line.match(/<\?(php)? /);
			var pos = m[0].length;
			
			new_code = new_code.substr(0, pos) + "\n" + new_code.substr(pos);
		}
		
		//return indented code
		return new_code;
	},
	
	isCharEscaped : function (str, idx) {
		var escaped = false;
		
		for (var i = idx - 1; i >= 0; i--) {
			if (str[i] == "\\")
				escaped = !escaped;
			else
				break;
		}
		
		return escaped;
	},
};
