/*
 * Copyright (c) 2025 Bloxtor (http://bloxtor.com) and Joao Pinto (http://jplpinto.com)
 * 
 * Multi-licensed: BSD 3-Clause | Apache 2.0 | GNU LGPL v3 | HLNC License (http://bloxtor.com/LICENSE_HLNC.md)
 * Choose one license that best fits your needs.
 */

//IMPORTANT: DO NOT ADD HERE THE ADD_SECURITY_CODE_HERE, BC THIS FILE WILL BE USED IN THE PROJECTS LEVEL TOO AND THE PROJECTS CAN BE EXPORTED TO ANY CLIENT SERVER, WHICH MEANS IT CAN RUN IN ANY DOMAIN.

(function(){
//avoids this file to give an error if called twice.
if (typeof MyJSLib == "function")
	return;

/****************************************************************************************
 *				 START: CORE FUNCTIONS 					*
 ****************************************************************************************/
var MyJSLib = window.MyJSLib = function() {
	return new MyJSLib.fn.init();
};

MyJSLib.fn = MyJSLib.prototype = {
	init: function() {
		return this;
	},
	
	toString: Object.prototype.toString,
	
	isFunction: function(obj) {
		return this.toString.call(obj) === "[object Function]";
	},

	isString: function(obj) {
		return this.toString.call(obj) == "[object String]";
	},
	
	isObject: function(obj) {
		return (typeof obj === "object" && obj !== null && typeof Array.isArray == "function" && !Array.isArray(obj)) || Object.prototype.toString.call(obj) === "[object Object]";
	},
	
	isNumber: function(obj) {
		//return this.toString.call(obj) == "[object Number]";
		return !isNaN( parseFloat(obj) ) && isFinite(obj);
	},
	
	//Simulates the jquery function: $.isNumeric(obj);
	isNumeric: function(obj) {
		return this.isNumber(obj);
	},
	
	//Simulates the jquery function: $.isArray(obj);
	isArray: function(obj) {
		//return this.toString.call(obj) == "[object Array]";
		return (typeof Array.isArray == "function" && Array.isArray(obj)) || Object.prototype.toString.call(obj) === '[object Array]';
	},
	
	//Simulates the jquery function: $.isPlainObject(obj); Copied from jquery 3.6.0
	isPlainObject: function(obj) {
		//return this.isObject(obj);
		
		//defined some jquery global vars
		var getProto = Object.getPrototypeOf;
		var class2type = {};
		var toString = class2type.toString;
		var hasOwn = class2type.hasOwnProperty;
		var fnToString = hasOwn.toString;
		var ObjectFunctionString = fnToString.call( Object );
		
		//code from isPlainObject in jquery 3.6.0
		var proto, Ctor;

		// Detect obvious negatives
		// Use toString instead of jQuery.type to catch host objects
		if ( !obj || toString.call( obj ) !== "[object Object]" ) {
			return false;
		}

		proto = getProto( obj );

		// Objects with no prototype (e.g., `Object.create( null )`) are plain
		if ( !proto ) {
			return true;
		}

		// Objects with prototype are plain iff they were constructed by a global Object function
		Ctor = hasOwn.call( proto, "constructor" ) && proto.constructor;
		return typeof Ctor === "function" && fnToString.call( Ctor ) === ObjectFunctionString;
	},
	
	//Simulates the jquery function: $.isEmptyObject(obj);
	isEmptyObject: function(obj) {
		for (var name in obj)
			return false;
		return true;
	},
	
	isjQuery: function(node) {
		return node && typeof jQuery != "undefined" && node instanceof jQuery;
	},
	
	isMobile: function(ua) {
		ua = ua && typeof ua != "undefined" ? "" + ua : navigator.userAgent;
		
		return /(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(ua) 
    || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(ua.substr(0,4));
	},
	
	each: function(obj, handler) {
		if (this.isArray(obj) || NodeList.prototype.isPrototypeOf(obj) || HTMLCollection.prototype.isPrototypeOf(obj)) {
			for (var i = 0, t = obj.length; i < t; i++)
				if (handler(i, obj[i]) === false)
					break;
			
			return true;
		}
		else if (this.isPlainObject(obj)) {
			for (var k in obj)
				if (handler(k, obj[k]) === false)
					break;
		
			return true;
		}
		
		return false;
	},
	
	getNativeNode: function(node) {
		return this.isjQuery(node) ? node[0] : node;
	},
	
	test: "test works!",
	
	//TODO: core functions
};

MyJSLib.fn.init.prototype = MyJSLib.fn;
/****************************************************************************************
 *				 END: CORE FUNCTIONS 					*
 ****************************************************************************************/


/****************************************************************************************
 *				 START: COOKIE HANDLER 					*
 ****************************************************************************************/
MyJSLib.CookieHandler = MyJSLib.fn.CookieHandler = ({

	/*getCookie: function(name) {
		if (document.cookie.length > 0) {
			c_start = document.cookie.indexOf(name + "=");
			if (c_start != -1) { 
				c_start = c_start + name.length + 1; 
				c_end = document.cookie.indexOf(";", c_start);
				if (c_end == -1) 
					c_end = document.cookie.length;
			
				return decodeURIComponent(document.cookie.substring(c_start, c_end));
			} 
		}
		return "";
	},*/
	getCookie: function(name) {
		name = name + "=";
		var ca = document.cookie.split(';');
		
		for(var i = 0; i < ca.length; i++) {
			var c = ca[i];
			
			while (c.charAt(0) == ' ')
				c = c.substring(1);
		
			if (c.indexOf(name) == 0)
				return decodeURIComponent(c.substring(name.length, c.length));
		}
		
		return "";
	},
	
	setCurrentDomainEternalRootSafeCookie: function(name, value, expire_days, path, domain, secure, httponly) {
		if (!expire_days)
			expire_days = 366 * 10; //10 years
		
		if (!path)
			path = "/";
		
		if (!domain)
			domain = "" + document.location.host; //Setting the domain is very important so the cookies can be set based in the domain. Note that is not possible to set different cookies for the same domain but with different ports. Cookies are only based on hostname.
		
		this.setCookie(name, value, expire_days, path, domain, secure, httponly);
	},
	
	//$.cookie("previousUrl", window.location.href, {path:"/"}); - Do not use jquery
	setCookie: function(name, value, expire_days, path, domain, secure, httponly) {
		document.cookie = this.getConfiguredCookie(name, value, expire_days, path, domain, secure, httponly);
	},
	
	getConfiguredCookie : function(name, value, expire_days, path, domain, secure, httponly) {
		var expires = "";
		
		if (expire_days) {
			var exdate = new Date();
			//exdate.setDate(exdate.getDate() + expire_days);
			//expires = expire_days != null ? ";expires="+ d.toGMTString() : "";
			exdate.setTime(exdate.getTime() + (expire_days * 24 * 60 * 60 * 1000));
			expires = expire_days != null ? "; expires="+ exdate.toUTCString() : "";
		}
		
		//Note that is not possible to set different cookies for the same domain but with different ports. Cookies are only based on hostname.
		//The current cookie specification is RFC 6265: Cookies do not provide isolation by port. If a cookie is readable by a service running on one port, the cookie is also readable by a service running on another port of the same server. If a cookie is writable by a service on one port, the cookie is also writable by a service running on another port of the same server. For this reason, servers SHOULD NOT both run mutually distrusting services on different ports of the same host and use cookies to store security sensitive information.
		//This means that we need to remove the port from the domain, if it exists
		if (domain && typeof domain == "string" && domain.indexOf(":") != -1)
			domain = domain.substr(0, domain.indexOf(":"));
		
		path = path ? "; path=" + path : "";
		domain = domain ? "; domain=" + domain : "";
		secure = secure ? "; secure" : "";
		httponly = httponly ? "; httponly" : "";
		
		return encodeURIComponent(name) + "=" + encodeURIComponent(value) + expires + path + domain + secure + httponly;
	},
	
	eraseCookie: function(name) {
		this.setCookie(name, "", -1);
	},
	
	browseCookiesExists: function() {
		return document.cookie ? true : false;
	},
	
	getCookiesMaximumByteLength : function() {
		return 4092; //all cookies mut be less than 4093 bytes in order to work in all browsers
	},
	
	getAllCookiesByteLength : function() {
		return this.getCookieByteLength(document.cookie);
	},
	
	getCookieByteLength : function(str) {
		// returns the byte length of an utf8 string
		var s = str.length;
		
		for (var i = str.length-1; i >= 0; i--) {
			var code = str.charCodeAt(i);
			
			if (code > 0x7f && code <= 0x7ff) 
				s++;
			else if (code > 0x7ff && code <= 0xffff) 
				s += 2;
			if (code >= 0xDC00 && code <= 0xDFFF) 
				i--; //trail surrogate
		}
		
		return s;
	}
});
/****************************************************************************************
 *				 END: COOKIE HANDLER 					*
 ****************************************************************************************/



/****************************************************************************************
 *				 START: AJAX HANDLER 					*
 ****************************************************************************************/
MyJSLib.AjaxHandler = MyJSLib.fn.AjaxHandler = ({

	requestObject: function() {
		var XMLRequestObject = null; /* XMLHttpRequest Object */
		
		if(window.XMLHttpRequest) { /* Mozilla, Safari,...*/
			XMLRequestObject = new XMLHttpRequest();
			if(XMLRequestObject.overrideMimeType)
				XMLRequestObject.overrideMimeType("text/xml");
		
		} else if(window.ActiveXObject) { /* IE */
			try {
				XMLRequestObject = new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e) {
				try {
					XMLRequestObject = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e) {}
			}
		}
		
		if(!XMLRequestObject)
			alert("Giving up :( Cannot create an XMLHTTP instance");
	
		return XMLRequestObject;
	},
	
	send: function(method, url, options) {
		if(!options) options = {};
		
		method = method ? method.toUpperCase() : false;
		var assync = options && options.assync ? true : false;
		var parameters = options && method == "POST" ? options.parameters : false;
		
		var result_type = options ? options.result_type : false;
		var callback_func = options ? options.callback_func : false;
		var callback_func_args = options ? options.callback_func_args : false;
	
		var requestObj = this.requestObject();
		
		/*
		open(method, url, async, user, psw)
			method: the request type GET or POST
			url: the file location
			async: true (asynchronous) or false (synchronous)
			user: optional user name
			psw: optional password
		*/
		requestObj.open(method, url, assync);
		
		if(method == "POST") {
			requestObj.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
			if(parameters)
				requestObj.setRequestHeader("Content-length", parameters.length);
			requestObj.setRequestHeader("Connection", "close");
		}
		
		requestObj.onreadystatechange = function(){
			if(requestObj.readyState == 4) {
				if(MyJSLib.fn.isFunction(callback_func))
					callback_func( MyJSLib.AjaxHandler.getResult(requestObj, result_type), callback_func_args);
			}
		};
		
		requestObj.send(parameters ? parameters : null);
		
		return !assync ? this.getResult(requestObj, result_type) : true;
	},
	
	get: function(url, options) {
		if(!options) options = {};
		
		options.assync = true;
		url = this.getURLWidthParameters(url, options ? options.parameters : false);
		
		return this.send("GET", url, options);
	},
	
	gets: function(url, options) {
		if(!options) options = {};
		
		options.assync = false;
		url = this.getURLWidthParameters(url, options ? options.parameters : false);
		
		return this.send("GET", url, options);
	},
	
	post: function(url, options) {
		if(!options) options = {};
		
		options.assync = true;
		
		return this.send("POST", url, options);
	},
	
	posts: function(url, options) {
		if(!options) options = {};
		
		options.assync = false;
		
		return this.send("POST", url, options);
	},
	
	getResult: function(requestObj, result_type) {
		if(result_type == 1)
			return requestObj.responseXML;
		else if(result_type == 2) 
			return requestObj.responseText;
		
		return requestObj;
	},
	
	getURLWidthParameters: function(url, parameters) {
		if(parameters) {
			url += url.indexOf("?") > -1 ? "" : "?";
			url += "&" + this.prepareParameters(parameters);
		}
		return url;
	},
	
	prepareParameters: function(parameters) {//TODO: check this function
		try {
			if(MyJSLib.fn.isArray(parameters)) {
				var str = "", item;
				for(var i = 0; i < parameters.length; ++i) {
					item = parameters[i];
					if(str != "")
						str += "&";
					str += encodeURIComponent(item.name) + "=" + encodeURIComponent(item.value);
				}
				return str;
			}
			else if(MyJSLib.fn.isObject(parameters)) {
				var str = "";
				for(name in parameters) {
					if(str != "")
						str += "&";
					str += encodeURIComponent(name) + "=" + encodeURIComponent( parameters[name] );
				}
				return str;
			}
		} catch(e) {
			alert(e && e.message ? e.message : e);
		}
		return parameters;
	}
});
/****************************************************************************************
 *				 END: AJAX HANDLER 					*
 ****************************************************************************************/



/****************************************************************************************
 *				 START: XML HANDLER 					*
 ****************************************************************************************/
MyJSLib.XMLHandler = MyJSLib.fn.XMLHandler = ({
	
	loadXMLFile: function(fname) {
		var xmlDoc;
		
		try {
			if (window.ActiveXObject) { // code for IE
				xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
			}
			else if(document.implementation && document.implementation.createDocument) { // code for Mozilla, Firefox, Opera, etc.
				xmlDoc = document.implementation.createDocument("","",null);
			}
			else {
				alert('Your browser cannot handle this script');
			}
			xmlDoc.async = "false";
			xmlDoc.load(fname);
		}
		catch(e) {
			alert(e && e.message ? e.message : e);
			return;
		}
		return(xmlDoc);
	},
	
	loadXMLString: function(text) {
		var xmlDoc;
		
		try {
			if(window.ActiveXObject) { //Internet Explorer
				xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
				xmlDoc.async = "false";
				xmlDoc.loadXML(text);
			}  
			else { // Firefox, Mozilla, Opera, etc.
				parser = new DOMParser();
				xmlDoc = parser.parseFromString(text, "text/xml");
			}
		}
		catch(e) {
			alert(e && e.message ? e.message : e);
			return;
		}
		return xmlDoc;
	}
});
/****************************************************************************************
 *				 END: XML HANDLER 					*
 ****************************************************************************************/



/****************************************************************************************
 *				 START: XSLT HANDLER 					*
 ****************************************************************************************/
MyJSLib.XSLTHandler = MyJSLib.fn.XSLTHandler = ({
	
	getNodeValue: function(xmldoc, path, index) {
		var values = this.getNodeValues(xmldoc, path);
		return index ? values[index] : values[0];
	},
	
	getNodeValues: function(xmldoc, path) {
		var values = new Array();
		
		var nodes = this.getNodes(xmldoc, path);
		if(nodes) {
			var root = nodes.childNodes ? nodes.childNodes[0] : false;
			if(root && root.childNodes) {
				var node_value;
				for(var i = 0; i < root.childNodes.length; ++i) {
					node_value = root.childNodes[i];
					if(node_value.nodeName == "VALUE")
						values.push(node_value.firstChild ? node_value.firstChild.data : "");
				}
			}
		}
		return values;
	},
	
	getNodes: function(xmldoc, path) {
		if(xmldoc && path) {
			path = this.configurePath(path);
		
			var xslt = '<?xml version="1.0"?>' +
					'<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:myNS="http://devedge.netscape.com/2002/de">' +
					'<xsl:output method="html" />' +
						'<xsl:template match="/">' +
							'<ROOT>' +
								'<xsl:for-each select="' + path + '">' +
									'<VALUE><xsl:value-of select="." /></VALUE>' +
								'</xsl:for-each>' +
							'</ROOT>' +
						'</xsl:template>' +
					'</xsl:stylesheet>';
		
		
			return this.displayResult(xmldoc, xslt);
		}
		else
			return null;
	},
	
	displayResult: function(xml, xslt) {
		var resultDocument;
	
		if(MyJSLib.fn.isString(xml)) xml = MyJSLib.XMLHandler.loadXMLString(xml);
		if(MyJSLib.fn.isString(xslt)) xslt = MyJSLib.XMLHandler.loadXMLString(xslt);
		
		try {
			if(window.ActiveXObject) { // code for IE
				resultDocument = xml.transformNode(xslt);
			}
			else if(document.implementation && document.implementation.createDocument) { // code for Mozilla, Firefox, Opera, etc.
				xsltProcessor = new XSLTProcessor();
				xsltProcessor.importStylesheet(xslt);
				resultDocument = xsltProcessor.transformToFragment(xml, document);
			}
		}
		catch(e) {
			alert(e && e.message ? e.message : e);
			return false;
		}
		
		if(MyJSLib.fn.isString(resultDocument)) {
			resultDocument = MyJSLib.XMLHandler.loadXMLString(resultDocument);
		}
		
		return resultDocument;
	},
	
	configurePath: function(path) {
		return path ? path.replace(/[\/]+/g, "/") : path;
	}
});
/****************************************************************************************
 *				 END: XSLT HANDLER 					*
 ****************************************************************************************/


/****************************************************************************************
 *				 START: FORM HANDLER 					*
 ****************************************************************************************/
MyJSLib.FormHandler = MyJSLib.fn.FormHandler = ({
	
	messages: {
		"empty_form_object": "Empty form object detected!",
		"undefined_field": "Field '#label#' cannot be blank.",
		"invalid_field_type": "Invalid #validation_type# format in '#label#'.",
		"field_min_length": "Length of '#label#' cannot be less than #min_length# characters.",
		"field_max_length": "Length of '#label#' cannot be more than #max_length# characters.",
		"field_min_value": "Value of '#label#' cannot be less than #min_value#.",
		"field_max_value": "Value of '#label#' cannot be great than #max_value#.",
		"mandatory_checkbox": "Please checked the field '#label#'.",
		"field_min_words": "Value of '#label#' need to have more than #min_words# word(s).",
		"field_max_words": "Value of '#label#' need to have less than #max_words# word(s).",
		"confirmation": "Do you want continue?",
		"system_error": "System error. Please contact the system administrator!",
		
		"int": "int",
		"bigint": "bigint",
		"number": "number",
		"double": "double",
		"float": "float",
		"decimal": "decimal",
		"phone": "phone",
		"fax": "fax",
		"email": "email",
		"domain": "domain",
		"date": "date",
		"datetime": "datetime",
		"timestamp": "timestamp",
		"time": "time",
		"ipaddress": "ipaddress",
		"smallint": "smallint",
		"tinyint": "tinyint",
		"filename": "filename",
	},
	
	initForm: function(cform) {
		if (cform) {
			cform = MyJSLib.fn.getNativeNode(cform);
			var btns = cform.querySelectorAll("input[type=submit], button[type=submit]");
			
			MyJSLib.fn.each(btns, function(idx, btn) {
				if (btn.getAttribute("is-data-clicked-inited") != 1) {
					btn.setAttribute("is-data-clicked-inited", 1);
					
					btn.addEventListener("click", function() {
						MyJSLib.fn.each(btns, function(idy, other_btn) {
							if (other_btn && other_btn.parentNode)
								other_btn.removeAttribute("data-clicked");
						});
						
						this.setAttribute("data-clicked", "1");
					});
				}
			});
		}
	},
	
	getFormElements: function(cform) {
		if (cform) {
			if (cform.nodeName.toLowerCase() == "form")
				return cform.elements;
			else
				return cform.querySelectorAll("input, select, textarea, button");
		}
		
		return [];
	},
	
	formCheck: function(cform) {
		if(!cform) {
			alert(MyJSLib.FormHandler.messages["empty_form_object"]);
			return false;
		}
		
		var elements = this.getFormElements(cform);
		var attrs = this.getFormElementsChecks(elements);
		
		//gets the second argument of this function
		var oncheckfunction = arguments.length > 1 && arguments[1] ? arguments[1] : this.onFormCheck;
		
		if(oncheckfunction && MyJSLib.fn.isFunction(oncheckfunction))
			return oncheckfunction(cform, attrs);
		
		return true;
	},
	
	formElementsCheck: function(elements) {
		var attrs = this.getFormElementsChecks(elements);
		
		//gets the second argument of this function
		var oncheckfunction = arguments.length > 1 && arguments[1] ? arguments[1] : this.onFormCheck;
		
		if(oncheckfunction && MyJSLib.fn.isFunction(oncheckfunction))
			return oncheckfunction(null, attrs);
		
		return true;
	},
	
	getFormElementsChecks: function(elements) {
		var confirm_element = false;
		var errors = 0;
		var wrong_elms = new Array();
		var message_exists = false;
		
		var elm, sub_elm, type, name, value, value_aux, checked, sub_checked, validation_label, validation_message, allow_null, allow_javascript, validation_type, validation_regex, validation_func, min_length, max_length, min_value, max_value, mandatory_checkbox, min_words, max_words, error_message, aux;
		
		for(var i = 0, t = elements.length; i < t; i++) {
			elm = elements[i];
			type = elm.type;
			name = elm.name;
			
			if(type == "select")
				value = elm.options[elm.selectedIndex] ? elm.options[elm.selectedIndex].value : false;
			else
				value = elm.value;
			
			if(type == 'submit' && (elm.getAttribute('data-confirmation') == "1" || elm.getAttribute('confirmation') == "1") && elm.getAttribute("data-clicked") == "1") {
				confirm_element = elm;
				break;
			}
			else {
				checked = elm.checked ? elm.checked : elm.getAttribute('checked');
				validation_label = elm.hasAttribute('data-validation-label') ? elm.getAttribute('data-validation-label') : elm.getAttribute('validationlabel');
				validation_message = elm.hasAttribute('data-validation-message') ? elm.getAttribute('data-validation-message') : elm.getAttribute('validationmessage');
				allow_null = elm.hasAttribute('data-allow-null') ? elm.getAttribute('data-allow-null') : elm.getAttribute('allownull');
				allow_javascript = elm.hasAttribute('data-allow-javascript') ? elm.getAttribute('data-allow-javascript') : elm.getAttribute('allowjavascript');
				validation_type = elm.hasAttribute('data-validation-type') ? elm.getAttribute('data-validation-type') : elm.getAttribute('validationtype');
				validation_regex = elm.hasAttribute('data-validation-regex') ? elm.getAttribute('data-validation-regex') : elm.getAttribute('validationregex');
				validation_func = elm.hasAttribute('data-validation-func') ? elm.getAttribute('data-validation-func') : elm.getAttribute('validationfunc');
				min_length = parseInt(elm.getAttribute('minlength'));
				max_length = parseInt(elm.getAttribute('maxlength'));
				min_value = elm.getAttribute('min');
				min_value = min_value && ("" + min_value).indexOf(".") != -1 ? parseFloat(min_value) : parseInt(min_value);
				max_value = elm.getAttribute('max');
				max_value = max_value && ("" + max_value).indexOf(".") != -1 ? parseFloat(max_value) : parseInt(max_value);
				mandatory_checkbox = elm.hasAttribute('data-mandatory-checkbox') ? elm.getAttribute('data-mandatory-checkbox') : elm.getAttribute('mandatorycheckbox');
				min_words = parseInt(elm.hasAttribute('data-min-words') ? elm.getAttribute('data-min-words') : elm.getAttribute('minwords'));
				max_words = parseInt(elm.hasAttribute('data-max-words') ? elm.getAttribute('data-max-words') : elm.getAttribute('maxwords'));
				
				checked = checked && checked != 'false' && checked != '0';
				validation_label = validation_label ? validation_label : (name ? name.replace(/_/gi," ").toUpperCase() : "");
				allow_null = ("" + allow_null).length && (("" + allow_null).toLowerCase() == 'false' || allow_null == '0') ? false : true;
				allow_javascript = ("" + allow_javascript).length && (("" + allow_javascript).toLowerCase() == 'true' || allow_javascript == '1') ? true : false;
				mandatory_checkbox = mandatory_checkbox && mandatory_checkbox != 'false' && mandatory_checkbox != '0';
				
				message_exists = false;
				error_message = "";
				aux = false;
				
				// 0. NULL CONTROL
				if(!allow_null) {
					value_aux = value ? value.toString().replace(/ /g, "") : false;
					
					var is_null = !value_aux || value_aux == '' || ((type == "checkbox" || type == "radio") && !checked);
					
					//check if null applies to multiple radio buttons
					if (is_null && (type == "checkbox" || type == "radio") && !checked) {
						for (var j = 0; j < t; j++) 
							if (j != i) {
								sub_elm = elements[j];
								
								if (sub_elm.type == type && sub_elm.name == name) {
									sub_checked = sub_elm.checked ? sub_elm.checked : sub_elm.getAttribute('checked');
									sub_checked = sub_checked && sub_checked != 'false' && sub_checked != '0';
									
									if (sub_checked) {
										is_null = false;
										break;
									}
								}
							}
					}
					
					if(is_null) {
						if(!message_exists) {
							errors++;
						
							if(validation_message) {
								error_message = validation_message;
								message_exists = true;
							}
							else
								error_message = this.messages["undefined_field"].replace("#label#", validation_label);
							
							wrong_elms.push({"element" : elm, "error" : "allow_null", "message" : error_message});
						}
					}		
				}
				// END OF NULL CONTROL
	
				// 1. JAVASCRIPT CONTROL
				if(!allow_javascript && value.toString().indexOf("<script") != -1) {
					if(!message_exists) {
						errors++;
					
						if(validation_message) {
							error_message = validation_message;
							message_exists = true;
						}
						else
							error_message = this.messages["undefined_field"].replace("#label#", validation_label);
						
						wrong_elms.push({"element" : elm, "error" : "allow_javascript", "message" : error_message});
					}		
				}
				// END OF JAVASCRIPT CONTROL
	
				// 2. TYPE CONTROL
				if((validation_type || validation_regex || validation_func) && value && !this.inputCheck(value, validation_type, validation_regex, validation_func)) {
					if(!message_exists) {
						errors++;
					
						if(validation_message) {
							error_message = validation_message;
							message_exists = true;
						}
						else 
							error_message = this.messages["invalid_field_type"].replace("#label#", validation_label).replace("#validation_type#", this.messages.hasOwnProperty(validation_type) ? this.messages[validation_type] : (validation_type ? validation_type : ""));
						
						wrong_elms.push({"element" : elm, "error" : "validation_type", "message" : error_message});
					}
				}
				// END OF TYPE CONTROL


				// 3. MIN/MAX LENGTH CONTROL
				if(min_length && value && value.length < min_length) {
					if(!message_exists) {
						errors++;
					
						if(validation_message) {
							error_message = validation_message;
							message_exists = true;
						}
						else
							error_message = this.messages["field_min_length"].replace("#label#", validation_label).replace("#min_length#", min_length);
						
						wrong_elms.push({"element" : elm, "error" : "min_length", "message" : error_message});
					}
				}

				if(max_length && value && value.length > max_length) {
					if(!message_exists) {
						errors++;
					
						if(validation_message) {
							error_message = validation_message;
							message_exists = true;
						}
						else
							error_message = this.messages["field_max_length"].replace("#label#", validation_label).replace("#max_length#", max_length);
						
						wrong_elms.push({"element" : elm, "error" : "max_length", "message" : error_message});
					}
				}
				// END OF LENGTH CONTROL
	

				// 4. MIN/MAX VALUE CONTROL FOR NUMBERS
				if(min_value && value && value < min_value) {
					if(!message_exists) {
						errors++;
					
						if(validation_message) {
							error_message = validation_message;
							message_exists = true;
						}
						else 
							error_message = this.messages["field_min_value"].replace("#label#", validation_label).replace("#min_value#", min_value);
						
						wrong_elms.push({"element" : elm, "error" : "min_value", "message" : error_message});
					}
				}

				if(max_value && value && value > max_value) {
					if(!message_exists) {
						errors++;
					
						if(validation_message) {
							error_message = validation_message;
							message_exists = true;
						}
						else
							error_message = this.messages["field_max_value"].replace("#label#", validation_label).replace("#max_value#", max_value);
						
						wrong_elms.push({"element" : elm, "error" : "max_value", "message" : error_message});
					}
				}
				// END OF MIN/MAX VALUE CONTROL


				// 5. CHECK MANDATORY CHECKBOX
				if(mandatory_checkbox && !checked) {
					if(!message_exists) {
						errors++;
					
						if(validation_message) {
							error_message = validation_message;
							message_exists = true;
						}
						else
							error_message = this.messages["mandatory_checkbox"].replace("#label#", validation_label);
						
						wrong_elms.push({"element" : elm, "error" : "mandatory_checkbox", "message" : error_message});
					}
				}
				// END CHECK MANDATORY CHECKBOX
		

				// 6. MIN NUMBER OF WORDS
				if(min_words && min_words >= 0 && value) {
					aux = this.strCountValue(value);
					if(aux < min_words) {
						if(!message_exists) {
							errors++;
						
							if(validation_message) {
								error_message = validation_message;
								message_exists = true;
							}
							else 
								error_message = this.messages["field_min_words"].replace("#label#", validation_label).replace("#min_words#", min_words);
							
							wrong_elms.push({"element" : elm, "error" : "min_words", "message" : error_message});
						}
					}
				}
				// END MIN NUMBER OF WORDS


				// 7. MAX NUMBER OF WORDS
				if(max_words && max_words >= 0 && value) {
					aux = this.strCountValue(value);
					if(aux > max_words) {
						if(!message_exists) {
							errors++;
						
							if(validation_message) {
								error_message = validation_message;
								message_exists = true;
							}
							else
								error_message = this.messages["field_max_words"].replace("#label#", validation_label).replace("#max_words#", max_words);
							
							wrong_elms.push({"element" : elm, "error" : "max_words", "message" : error_message});
						}
					}
				}
				// END MAX NUMBER OF WORDS

			}
		} // END OF THE FOR LOOP
		
		return {"errors" : errors, "confirm_element" : confirm_element, "wrong_elms" : wrong_elms};
	},
	
	onFormCheck: function(cform, attrs) {
		var errors = attrs.errors;
		var confirm_element = attrs.confirm_element;
		
		if (errors == 0) {
			if (cform)
				MyJSLib.FormHandler.checkFormSecurity(cform);
			
			if (confirm_element && (confirm_element.getAttribute('data-confirmation') == "1" || confirm_element.getAttribute('confirmation'))) {
				confirm_element.confirmation = false;
				
				var confirm_message = confirm_element.getAttribute('data-confirmation-message') ? confirm_element.getAttribute('data-confirmation-message') : (
					confirm_element.getAttribute('confirmationmessage') ? confirm_element.getAttribute('confirmationmessage') : MyJSLib.FormHandler.messages["confirmation"]
				);
				
				if (confirm(confirm_message) == true)
					return true;
				else 
					return false;
			}
			else 
				return true;
		}
		else {
			var message = MyJSLib.FormHandler.getFormErrorMessage(attrs);
			
			if (message)
				alert(message);
			
			return false;
		}
		
		return false;
	},
	
	getFormErrorMessage: function(attrs) {
		var errors = attrs.errors;
		var message = "";//"Please check the following input(s): \n";
		
		if (errors > 0) {
			var repeated_messages = new Array();
			var wrong_elms = attrs.wrong_elms;
			var elm;
			
			for (var i = 0, t = wrong_elms.length; i < t; ++i) {
				elm = wrong_elms[i];
				
				if (elm.message && repeated_messages.indexOf(elm.message) == -1) {
					message += /*"- " + */elm.message + "\n";
					
					repeated_messages.push(elm.message);
				}
			}
		}
		
		return message;
	},

	inputCheck: function(input, type, regex, func)  {
		var expression;

		if(type == 'int' || type == 'bigint' || type == 'number')
			expression	= /^-*\d+$/;
		else if(type == 'double' || type == 'float' || type == 'decimal')
			expression	= /(^-*\d+$)|(^-*\d+\.\d+$)|(^\.\d+$)/;
		else if(type == 'phone' || type == 'fax')
			expression	= /(^(\+|)[0-9 ]+$)|(^\+\d+$)|(^\d+$)|(^\+)(\d*\-\d*\-\d*$)|(\d*\-\d*\-\d*$)/;
		else {
			switch(type) {
				case 'email':
					expression	= /^([a-z0-9\+\-\_\.]+)\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,10})(\]?)$/i;
					break;
				case 'domain':
					expression	= /^([a-z0-9-_]+\.)*[a-z0-9][a-z0-9-_]+\.[a-z]{2,}$/i;
					break;
				case 'date':
					expression	= /^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})$/;
					break;
				case 'datetime':
				case 'timestamp':
					expression	= /^([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})(([ T]{1})([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?)?$/;
					break;
				case 'time':
					expression	= /^([0-9]{1,2}):([0-9]{1,2})(:([0-9]{1,2}))?$/;
					break;
				case 'ipaddress':
					expression	= /^((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])\.){3}(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9][0-9]|[0-9])$/;
					break;
				case 'smallint':
					expression	= /^[0-9]{1,5}$/;
					break;
				case 'tinyint':
					expression	= /^[0,1]{1}$/;
					break;
				case 'filename':
					//expression	= /^[a-z0-9\-\_\.]+$/i;
					//expression	= /^([\p{L}\w \-\+\.]+$)/gu; //'\w' means all words with '_' and '/u' means with accents and ç too. Cannot use this bc it does not work in IE.
					expression	= /^([\w\u00C0-\u00D6\u00D8-\u00F6\u00F8-\u024F\u1EBD\u1EBC \-\+\.]+$)/g; //'\w' means all words with '_' and 'u' means with accents and ç too.
					break;
				default:
					expression	= "";
			}
		}
		
		var status = expression == '' || !expression || expression.test(input);
		
		if (status && regex && typeof regex != "undefined") {
			var parts = regex.split("/");
			var pattern, flags = "";
			
			if (parts.length >= 3) {
				pattern = parts[1];
				flags = parts[2];
			}
			else
				pattern = regex;
			
			expression = new RegExp(pattern, flags); 
			status = expression.test(input);
			//console.log(pattern+", "+flags+":"+expression.test(input));
		}
		
		if (status && func) {
			//starts with a letter or underscore and doesn't have invalid chars
			if (typeof func == "string" && !func.match(/[^\w\.]/) && func.match(/^[a-zA-Z_]/)) { 
				try {
					eval('func = ' + func + ';');
				}
				catch(e) {}
			}
			
			if (typeof func == "function")
				status = func(input, type, regex);
		}
		
		return status;
	},

	strCountValue: function(value) {
		var value_splited = value.split(" ");
		var counter = 0;
		for(j = 0; j < value_splited.length; j++)
			if(value_splited[j])
				++counter;
		return counter;
	},

	checkAndSubmitForm: function(form, options) {
		var cform;
		if (MyJSLib.fn.isString(form)) {
			eval("cform = document." + form + ";");
		
			if (!cform)
				cform = document.getElementById(form);
			
			if (!cform && MyJSLib.fn.isjQuery(form))
				cform = form[0];
			else if (!cform && typeof form == "string")
				cform = document.querySelector(form);
			
			if (!cform && typeof $ == "function")
				cform = $(form)[0];
		}
		else
			cform = form;
		
		if (!cform || cform.nodeName.toLowerCase() != "form") {
			var msg = "ERROR: cform is not a form object, so we cannot call the .submit() function or submit it via ajax!";
			alert(msg);
			throw msg;
			
			return false;
		}
		
		var oncheck_func = options && options.oncheck_func ? options.oncheck_func : false;
		
		if(cform && this.formCheck(cform, oncheck_func)) {
			var status = true;
			if(options && MyJSLib.fn.isFunction(options.callfront_func))
				status = options.callfront_func(cform);
			
			if(status) {
				var is_ajax = cform.hasAttribute("data-ajax") ? cform.getAttribute("data-ajax") : cform.getAttribute("ajax");
				
				if(is_ajax) {
					this.submitFormViaAjax(cform, options);
					return false;
				}
				else
					cform.submit();
				
				return true;
			}
		}
		
		return false;
	},

	submitFormViaAjax: function(cform, options) {
		var method = cform.getAttribute("method");
		var query = this.getFormQuery(cform);
		var action = this.getFormAction(cform);
		
		try {
			if(MyJSLib.AjaxHandler) {
				var callback_func = options ? options.callback_func : false;
				var result_type = options ? options.ajax_result_type : false;
				var settings = { callback_func : callback_func, callback_func_args : cform, result_type : result_type, parameters : query };
				var status = method.toLowerCase() == "get" ? MyJSLib.AjaxHandler.get(action, settings) : MyJSLib.AjaxHandler.post(action, settings);
				return status;
			}
		} catch(e) {
			alert(this.messages["system_error"]);
		}
		return false;
	},

	getFormQuery: function(cform) {
		var query = ""; 
		var formElem, name;
		var elements = this.getFormElements(cform);
		
		for (var i = 0; i < elements.length; i++) {
			formElem = elements[i];
			name = formElem.name;
			switch (formElem.type) {
			        case 'text':
			        case 'select':
			        case 'select-one':
			        case 'hidden':
			        case 'submit':
			        case 'password':
			        case 'textarea':
			                query += encodeURIComponent(name) + '=' + encodeURIComponent(formElem.value) + '&';
			        	break;
			        	
			  	case 'select-multiple': 
					for(var j = 0; j < formElem.length; j++) 
						if(formElem[j].selected) 
							query += encodeURIComponent(name) + '=' + encodeURIComponent(formElem[j].value) + '&';
					break;
				
				case 'radio': 
				case 'checkbox': 
					if(formElem.checked)
						query += encodeURIComponent(name) + '=' + encodeURIComponent(formElem.value) + '&';
					break;
			
				default: 
					query += encodeURIComponent(name) + '=' + encodeURIComponent(formElem.value) + '&';
			}	
		}
		return query;
	},
	
	getFormAction: function(cform) {
		var action = cform.getAttribute("action");
		
		if(action == "")
			action = location.search;
		
		return action;
	},
	
	checkFormSecurity: function(cform) {
		if (cform) {
			var security_code = cform.hasAttribute("data-security-code") ? cform.getAttribute("data-security-code") : cform.getAttribute("securitycode");
		
			if(security_code) {
				var security_code_value = MyJSLib.CookieHandler.getCookie(security_code);
			
				var input = document.createElement("input");
				input.setAttribute("type", "hidden");
				input.setAttribute("name", security_code);
				input.setAttribute("value", security_code_value ? security_code_value : "");
			
				cform.appendChild(input);
			}
		}
	}
});
/****************************************************************************************
 *				 END: FORM HANDLER 					*
 ****************************************************************************************/



/****************************************************************************************
 *				 START: LOADING BAR HANDLER 				*
 ****************************************************************************************/
MyJSLib.LoadingBarHandler = MyJSLib.fn.LoadingBarHandler = ({
	loadingBarHtml : '<div id="subloadingbar">Loading ...</div>',
	
	showLoadingBar : function() {
		var loadingbar = document.getElementById('loadingbar');
		if(!loadingbar) {
			this.createLoadingBar();
			loadingbar = document.getElementById('loadingbar');
		}
	
		if(loadingbar)
			loadingbar.style.display = 'block';
		
		return true;
	},

	hideLoadingBar : function() {
		var loadingbar = document.getElementById('loadingbar');
		if(loadingbar)
			loadingbar.style.display = 'none';
		
		return true;
	},

	createLoadingBar : function() {
		var div = document.createElement("div");
		div.setAttribute("id", "loadingbar");
		div.innerHTML = this.loadingBarHtml;
	
		document.body.insertBefore(div, null);
		
		return true;
	}
});
/****************************************************************************************
 *				 END: LOADING BAR HANDLER 				*
 ****************************************************************************************/





/****************************************************************************************
 *				 START: BROWSER HISTORY HANDLER 				*
 ****************************************************************************************/
MyJSLib.BrowserHistoryHandler = MyJSLib.fn.BrowserHistoryHandler = ({
	browser_history_urls_cookie_name : 'browser_history_urls',
	browser_history_index_cookie_name : 'browser_history_index',
	error_message : 'Action not allowed!',
	no_history_class : 'hidden',
	urls_maximum_number : 20,
	
	//This function should be called in every page so the history can work
	init : function(options) {
		//This must be called first than other functions otherwise the initBackHtmlElement and initForwardHtmlElement won't work for the first history.
		var with_hash_tags = options && options.hasOwnProperty("with_hash_tags") ? options["with_hash_tags"] : with_hash_tags;
		this.initBrowserHistory(with_hash_tags);
		
		if (options) {
			if (options.hasOwnProperty("error_message"))
				this.error_message = options["error_message"];
			
			var back_html_element = options.hasOwnProperty("back_html_element") ? options["back_html_element"] : null;
			if (back_html_element)
				this.initBackHtmlElement(back_html_element);
		
			var back_html_elements = options.hasOwnProperty("back_html_elements") ? options["back_html_elements"] : null;
			if (back_html_elements && MyJSLib.fn.isArray(back_html_elements) && !MyJSLib.fn.isEmptyObject(back_html_elements))
				MyJSLib.fn.each(back_html_elements, function(idx, element) {
					this.initBackHtmlElement(element);
				});
				
			var forward_html_element = options.hasOwnProperty("forward_html_element") ? options["forward_html_element"] : null;
			if (forward_html_element)
				this.initForwardHtmlElement(forward_html_element);
		
			var forward_html_elements = options.hasOwnProperty("forward_html_elements") ? options["forward_html_elements"] : null;
			if (forward_html_elements && MyJSLib.fn.isArray(forward_html_elements) && !MyJSLib.fn.isEmptyObject(forward_html_elements))
				MyJSLib.fn.each(forward_html_elements, function(idx, element) {
					this.initForwardHtmlElement(element);
				});
		}
	},
	
	initBackHtmlElement : function(element) {
		if (this.no_history_class && !this.backHistoryExists()) {
			element = MyJSLib.fn.getNativeNode(element);
			
			if (element && !element.classList.contains(this.no_history_class))
				element.classList.add(this.no_history_class);
		}
	},
	
	initForwardHtmlElement : function(element) {
		if (this.no_history_class && !this.forwardHistoryExists()) {
			element = MyJSLib.fn.getNativeNode(element);
			
			if (element && !element.classList.contains(this.no_history_class))
				element.classList.add(this.no_history_class);
		}
	},
	
	//Init history
	initBrowserHistory : function(with_hash_tags) {
		//window.location.href: http://www.w3schools.com:443/jsref/prop_loc_href.asp?asd=asd#asd87
		//window.location.pathname: /jsref/prop_loc_href.asp
		//window.location.host: www.w3schools.com:443
		var url = window.location.href;
		url = url.substr(url.indexOf(window.location.pathname));
		
		if (!with_hash_tags && url.indexOf("#") != -1)
			url = url.substr(0, url.indexOf("#"));
		
		if (url) {
			var value = MyJSLib.CookieHandler.getCookie(this.browser_history_urls_cookie_name);
			var index = MyJSLib.CookieHandler.getCookie(this.browser_history_index_cookie_name);
			//console.log("*******************");
			//console.log(value);
			//console.log(index);
			
			if (value) {
				var urls = value.split("|");
				
				if (urls.length > 0) {
					//check index url
					index = index >= 0 && index < urls.length ? index : urls.length - 1;
					
					//check if url is not repeated
					if (urls[index] != url) {
						//add url to urls
						if (index == (urls.length - 1)) //check if url is last position
							urls.push(url);
						else { //otherwise replace all other urls
							//var aux = urls.slice(0, index + 1);//Note: I tried to use the slice method but it didn't work. Please leave this code here and DO NOT activate the slice function!
							var aux = [];
							for (var i = 0; i < urls.length; i++) 
								if (i <= index)
									aux.push(urls[i]);
							
							aux.push(url);
							urls = aux;
						}
						
						//get urls to discard according with cookies maximum bytes length.
						var max_bytes = MyJSLib.CookieHandler.getCookiesMaximumByteLength();
						var curr_bytes = MyJSLib.CookieHandler.getAllCookiesByteLength() + MyJSLib.CookieHandler.getCookieByteLength("|" + encodeURIComponent(url));
						var urls_to_discard_size = 0;
						
						while (curr_bytes > max_bytes) {
							urls_to_discard_size++;
							urls_to_discard_size = urls_to_discard_size > urls.length ? urls.length : urls_to_discard_size;
							
							curr_bytes = curr_bytes - MyJSLib.CookieHandler.getCookieByteLength( "|" + encodeURIComponent(urls.splice(urls_to_discard_size * -1).join("|")) );
						}
						
						//prepare urls_maximum_number var according with urls_to_discard_size var.
						if (parseInt(this.urls_maximum_number) >= 0) {
							var urls_max_number = this.urls_maximum_number;
							
							if (urls_to_discard_size > 0)
								urls_max_number = urls_to_discard_size >= urls.length ? 0 : urls.length - urls_to_discard_size;
							
							//prepare urls array and discard old urls according with new urls_max_number var.
							if (urls.length > urls_max_number) 
								urls = urls_max_number > 0 ? urls.slice(urls_max_number * -1) : []; //if urls_max_number == 0, it means that the cookie size was already exceeded and we cannot have urls
						}
						else if (urls_to_discard_size > 0) {
							if (urls_to_discard_size >= urls.length)
								urls = []; //it means that the cookie size was already exceeded and we cannot have urls
							else
								urls = urls.slice(urls_to_discard_size * -1);
						}
						
						value = urls.join("|");
						index = urls.length - 1;
					}
				}
				else {//supposedly this case will never happen because the split will always return at least 1 item, but just in case, please leave this code here:
					value = url;
					index = 0;
				}
			}
			else {
				value = url;
				index = 0;
			}
			//console.log(value);
			//console.log(index);
			
			MyJSLib.CookieHandler.setCookie(this.browser_history_urls_cookie_name, value, 0, "/");
			MyJSLib.CookieHandler.setCookie(this.browser_history_index_cookie_name, index, 0, "/");
		}
	},
	
	indexHistoryExists : function(index) {
		var value = MyJSLib.CookieHandler.getCookie(this.browser_history_urls_cookie_name);
		
		if (value) {
			var urls = value.split("|");
			
			if (urls.length > 0) {
				var url = index >= 0 && index < urls.length ? urls[index] : "";
				return url ? true : false;
			}
		}
		
		return false;
	},
	
	backHistoryExists : function() {
		var index = MyJSLib.CookieHandler.getCookie(this.browser_history_index_cookie_name);
		index = (index ? index : 0) - 1;
		
		return this.indexHistoryExists(index);
	},
	
	forwardHistoryExists : function() {
		var index = MyJSLib.CookieHandler.getCookie(this.browser_history_index_cookie_name);
		index = (index ? index : 0) + 1;
		
		return this.indexHistoryExists(index);
	},
	
	go : function(index) {
		if (index >= 0) {
			var value = MyJSLib.CookieHandler.getCookie(this.browser_history_urls_cookie_name);
		
			if (value) {
				var urls = value.split("|");
				var url = index >= 0 && index < urls.length ? urls[index] : "";
			
				if (url) {
					MyJSLib.CookieHandler.setCookie(this.browser_history_index_cookie_name, index, 0, "/");
					document.location = url;
				}
			}
		}
		
		if (!url && this.error_message)
			alert(this.error_message);
	},
	
	goBack : function() {
		var index = MyJSLib.CookieHandler.getCookie(this.browser_history_index_cookie_name);
		index = (index ? index : 0) - 1;
		
		this.go(index);
	},
	
	goForward : function() {
		var index = MyJSLib.CookieHandler.getCookie(this.browser_history_index_cookie_name);
		index = (index ? index : 0) + 1;
		
		this.go(index);
	},
});
/****************************************************************************************
 *				 END: BROWSER HISTORY HANDLER 				*
 ****************************************************************************************/
 
})();
