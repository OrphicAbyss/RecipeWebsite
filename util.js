function logObjectString(obj, recurse, level){
	var output = "";
	var indent = "\n";
	
	for (var i=0; i<level; i++){
		indent = indent + "  ";
	}
	
	for (var field in obj){
		if (typeof(obj[field]) == "function"){
			output = output + indent + "function " + field + "()";
		} else if (typeof(obj[field]) == "object" && recurse){
			output = output + indent + "object " + field + " :" + logObjectString(obj[field],true,level+1);
		} else {
			output = output + indent + field + " : " + obj[field];
		}
	}
	
	return output;
}

/**
 * Log all the properties of an object to the console for debugging
 */
function logObject(obj){
	var data = "Data: ";
	
	data = data + logObjectString(obj, true, 0);
	
	console.log(data);
}

/**
 * Log all the properties of an object to the console for debugging
 */
function logObject(obj, recurse){
	var data = "Data: ";
	
	data = data + logObjectString(obj, recurse, 0);
	
	console.log(data);
}
