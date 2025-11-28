# My Auto Complete

> Original Repos:   
> - My Auto Complete: https://github.com/a19836/myautocomplete/   
> - Bloxtor: https://github.com/a19836/bloxtor/

## Overview

**My Auto Complete** is a lightweight JavaScript library that transforms a text box into a smart autocomplete field, displaying matching suggestions in a dropdown list to make user selection faster and easier as you type.

Check out a live example by opening [index.html](index.html).

---

## Usage

```html
<html>
<head>
	<link rel="stylesheet" href="css/style.css">
	<script src="js/MyAutoComplete.js"></script>
</head>
<body>
	<!--Make sure the form has the autocomplete function switched off:-->
	<form autocomplete="off" action="?">
		<input id="myInput" type="text" name="myCountry" placeholder="Country">
		<input type="submit">
	</form>
	
	<script>
		var countries = ["Afghanistan","Albania","Algeria","Andorra","Angola","Anguilla","Antigua &amp; Barbuda","Argentina","Armenia","Aruba","Australia","Austria","Azerbaijan","Bahamas","Bahrain","Bangladesh","Barbados","Belarus","Belgium","Belize","Benin","Bermuda","Bhutan","Bolivia","Bosnia &amp; Herzegovina","Botswana","Brazil","British Virgin Islands","Brunei","Bulgaria","Burkina Faso","Burundi","Cambodia","Cameroon","Canada","Cape Verde","Cayman Islands","Central Arfrican Republic","Chad","Chile","China","Colombia","Congo","Cook Islands","Costa Rica","Cote D Ivoire","Croatia","Cuba","Curacao","Cyprus","Czech Republic","Denmark","Djibouti","Dominica","Dominican Republic","Ecuador","Egypt","El Salvador","Equatorial Guinea","Eritrea","Estonia","Ethiopia","Falkland Islands","Faroe Islands","Fiji","Finland","France","French Polynesia","French West Indies","Gabon","Gambia","Georgia","Germany","Ghana","Gibraltar","Greece","Greenland","Grenada","Guam","Guatemala","Guernsey","Guinea","Guinea Bissau","Guyana","Haiti","Honduras","Hong Kong","Hungary","Iceland","India","Indonesia","Iran","Iraq","Ireland","Isle of Man","Israel","Italy","Jamaica","Japan","Jersey","Jordan","Kazakhstan","Kenya","Kiribati","Kosovo","Kuwait","Kyrgyzstan","Laos","Latvia","Lebanon","Lesotho","Liberia","Libya","Liechtenstein","Lithuania","Luxembourg","Macau","Macedonia","Madagascar","Malawi","Malaysia","Maldives","Mali","Malta","Marshall Islands","Mauritania","Mauritius","Mexico","Micronesia","Moldova","Monaco","Mongolia","Montenegro","Montserrat","Morocco","Mozambique","Myanmar","Namibia","Nauro","Nepal","Netherlands","Netherlands Antilles","New Caledonia","New Zealand","Nicaragua","Niger","Nigeria","North Korea","Norway","Oman","Pakistan","Palau","Palestine","Panama","Papua New Guinea","Paraguay","Peru","Philippines","Poland","Portugal","Puerto Rico","Qatar","Reunion","Romania","Russia","Rwanda","Saint Pierre &amp; Miquelon","Samoa","San Marino","Sao Tome and Principe","Saudi Arabia","Senegal","Serbia","Seychelles","Sierra Leone","Singapore","Slovakia","Slovenia","Solomon Islands","Somalia","South Africa","South Korea","South Sudan","Spain","Sri Lanka","St Kitts &amp; Nevis","St Lucia","St Vincent","Sudan","Suriname","Swaziland","Sweden","Switzerland","Syria","Taiwan","Tajikistan","Tanzania","Thailand","Timor L'Este","Togo","Tonga","Trinidad &amp; Tobago","Tunisia","Turkey","Turkmenistan","Turks &amp; Caicos","Tuvalu","Uganda","Ukraine","United Arab Emirates","United Kingdom","United States of America","Uruguay","Uzbekistan","Vanuatu","Vatican City","Venezuela","Vietnam","Virgin Islands (US)","Yemen","Zambia","Zimbabwe"];
		
		MyAutoComplete.init(document.getElementById("myInput"), countries);
	</script>
</body>
</html>
```

## Other calls

Create an auto complete field:
```
var input_elm = document.getElementById("myInput"); //get a html element
var list_of_available_values = ["a", "b", "..."];
var options = {
	get: function(input_elm) { //(optional) this callback function is triggered when the user enters something into the input field. Thus, when the user types something, a list of available values ​​is displayed based on what they typed. Basically, the system filters the list of all available values ​​according to the user's input. This function allows changing that value to another, filtering the list of available values ​​by that new value.
		console.log(input_elm);
		
		return input_elm.value; //must return the input value. Note that this is already the default behaviour.
	},
	set: function(input_elm, selected_value) { //(optional) callback called when one value from the available list gets selected by the user
		console.log(input_elm);
		console.log(selected_value);
		
		input_elm.value = selected_value; //must set the value to input. Note that this is already the default behaviour.
	}
};

MyAutoComplete.init(input_elm, list_of_available_values, options);
```
