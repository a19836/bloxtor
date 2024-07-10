var curColourIndex = typeof curColourIndex != "undefined" && curColourIndex >= 0 ? curColourIndex: 1;
var maxColourIndex = typeof maxColourIndex != "undefined" && maxColourIndex >= 0 ? maxColourIndex: 24;

if(typeof nextColor !== 'function') {
	function nextColor() {
		var R, G, B;
		R = parseInt(128 + Math.sin((curColourIndex * 3 + 0) * 1.3) * 128);
		G = parseInt(128 + Math.sin((curColourIndex * 3 + 1) * 1.3) * 128);
		B = parseInt(128 + Math.sin((curColourIndex * 3 + 2) * 1.3) * 128);
	
		curColourIndex = curColourIndex + 1;
	
		if (curColourIndex > maxColourIndex) {
			curColourIndex = 1;
		}
	
		return "rgb(" + R + "," + G + "," + B + ")";
	}
}

if(typeof randomColor !== 'function') {
	function randomColor() {
		var R, G, B;
	
		var max = 255;
		var min = 0;
	
		R = Math.floor(Math.random() * (max - min)) + min;
		G = Math.floor(Math.random() * (max - min)) + min;
		B = Math.floor(Math.random() * (max - min)) + min;
	
		return "rgb(" + R + "," + G + "," + B + ")";
	}
}

if(typeof getContrastYIQ !== 'function') {
	//http://24ways.org/2010/calculating-color-contrast/
	//alert( getContrastYIQ("#EF4444") ); //white
	function getContrastYIQ(hexcolor){
		hexcolor = hexcolor.substr(0, 1) == "#" ? hexcolor.substr(1) : hexcolor;
		
		var r = parseInt(hexcolor.substr(0, 2), 16);
		var g = parseInt(hexcolor.substr(2, 2), 16);
		var b = parseInt(hexcolor.substr(4, 2), 16);
		
		var yiq = ((r*299)+(g*587)+(b*114))/1000;
		
		return (yiq >= 128) ? 'black' : 'white';
	}
}

if(typeof getContrast50 !== 'function') {
	//http://24ways.org/2010/calculating-color-contrast/
	//alert( getContrast50("#EF4444") ); //black
	function getContrast50(hexcolor){
	    	hexcolor = hexcolor.substr(0, 1) == "#" ? hexcolor.substr(1) : hexcolor;
		
		return (parseInt(hexcolor, 16) > 0xffffff/2) ? 'black' : 'white';
	}
}

if(typeof hexToRgb !== 'function') {
	//console.log( hexToRgb("#0033ff") ); // {r: 0, g: 51, b: 255};
	//console.log( hexToRgb("#03f") ); // {r: 0, g: 51, b: 255};
	//console.log( hexToRgb("#03faa") ); // {r: 0, g: 51, b: 255, a: 0.667};
	//console.log( hexToRgb("#ff0000aa") ); // {r: 255, g: 0, b: 0, a: 0.667};
	function hexToRgb(hex) {
	    	if (hex.substr(0, 1) != "#")
	    		hex = "#" + hex;
	    	
	    	//Expand shorthand form (e.g. "#03F") to full form (e.g. "#0033FF")
		if (hex.length == 4) {
			var shorthandRegex = /^#([a-f\d])([a-f\d])([a-f\d])$/i;
			
			hex = hex.replace(shorthandRegex, function(m, r, g, b) {
				return "#" + r + r + g + g + b + b;
		    	});
	    	}
	    	
	    	//Expand shorthand form (e.g. "#03Faa") to full form (e.g. "#0033FFaa")
		if (hex.length == 6) {
			var shorthandRegex = /^#([a-f\d])([a-f\d])([a-f\d])([a-f\d])([a-f\d])$/i;
			
			hex = hex.replace(shorthandRegex, function(m, r, g, b, z, x) {
				return "#" + r + r + g + g + b + b + z + x;
		    	});
		}
		
		//in case opacity exists: #0033FFaa
		if (hex.length == 9) {
			var result = /^#([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
			var opacity = "0x" + result[4];
			opacity = +(opacity / 255).toFixed(3);
			
			return result ? {
				r: parseInt(result[1], 16),
				g: parseInt(result[2], 16),
				b: parseInt(result[3], 16),
				a: opacity
		    	} : null;
		}
		
		//in case of #0033FF
	    	var result = /^#([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
		
	    	return result ? {
			r: parseInt(result[1], 16),
			g: parseInt(result[2], 16),
			b: parseInt(result[3], 16)
	    	} : null;
	}
}

if(typeof rgbToHex !== 'function') {
	function componentToHex(c) {
		var hex = parseInt(c).toString(16);
		return hex.length == 1 ? "0" + hex : hex;
	}

	//console.log("rgbToHex(0, 51, 255) => " + rgbToHex(0, 51, 255) ); // #0033ff
	//console.log("rgbToHex(0, 51, 255, .5) => " + rgbToHex(0, 51, 255, .5) ); // #0033ff80
	//console.log("rgbToHex(0, 51, 255, 40%) => " + rgbToHex(0, 51, 255, "40%") ); // #0033ff66
	function rgbToHex(r, g, b, a) {
		var opacity = "";
		
		if (typeof a != undefined && a != null) {
			if (("" + a).indexOf("%") != -1) //in case the a is a percentage
     			a = a.substr(0, a.length - 1) / 100;
			
			//convert a value to letters
			opacity = Math.round(a * 255).toString(16);
			
			if (opacity.length == 1)
	    			opacity = "0" + opacity;
		}
		
		return "#" + componentToHex(r) + componentToHex(g) + componentToHex(b) + opacity;
	}
}

if(typeof colorRgbToHex !== 'function') {
	//alert( colorRgbToHex("rgb(0, 51, 255)") ); // #0033ff
	function colorRgbToHex(color) {
		var m = color.match(/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/i);
		
		if (!m)
			m = color.match(/^rgba\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*(,\s*([0-9]+(\.[0-9]*)?|\.[0-9]+)\s*)?\)$/i);
		
		if (m)
			return rgbToHex(m[1], m[2], m[3], m[5]);
	}
}

if(typeof colorHexToRgb !== 'function') {
	//alert( colorHexToRgb("#0033ff") ); // rgb(0, 51, 255)
	function colorHexToRgb(color) {
		var rgb = hexToRgb(color);
		
		if (rgb) {
			if (rgb.length == 4)
				return "rgba(" + rgb[0] + ", " + rgb[1] + ", " + rgb[2] + ", " + rgb[3] + ")";
			
			return "rgb(" + rgb[0] + ", " + rgb[1] + ", " + rgb[2] + ")";
		}
	}
}


//Leave this code here, because is adding the TRIM function to the IE browsers. Otherwise the browser gives errors.
if(typeof String.prototype.trim !== 'function') {
  String.prototype.trim = function() {
    return this.replace(/^\s+|\s+$/g, ''); 
  }
}

//Leave this code here, because is adding the hashCode function to all browsers.
if(typeof String.prototype.hashCode !== 'function') {
	String.prototype.hashCode = function(){
		var hash = 0;
		if (this.length == 0) return hash;
		for (i = 0; i < this.length; i++) {
			char = this.charCodeAt(i);
			hash = ((hash<<5)-hash)+char;
			hash = hash & hash; // Convert to 32bit integer
		}
		return hash;
	}
}

