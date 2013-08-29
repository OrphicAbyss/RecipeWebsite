$.fn.serializeObject = function()
{
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};

var tableGen = {
    headerRow: function (item){
        if ($.isArray(item)){
            var cells = "";
            for (var i in item){
                cells += tableGen.headerCell(item[i]);
            }
            return tableGen.headerRow(cells);
        } else {
            return "<tr class='header-row'>" + item + "</tr>";
        }
    },
    headerCell: function (item){
        return "<td class='header-cell'>" + item + "</td>";
    },
    dataRow: function(attr, sClass, item){
        if ($.isArray(item)){
            return tableGen.dataRow(attr, sClass, item.join(""));
        } else {
            return "<tr " + attr + " class='data-row" + sClass + "'>" + item + "</tr>";
        }
    },
    dataCell: function(item){
        if ($.isArray(item)){
            return tableGen.dataCell(item.join(", "));
        } else {
            return "<td class='data-cell'>" + item + "</td>";
        }
    }
};

/**
 * Build a header row of a html table.
 * 
 * If an object is passed in:
 * 		They key's of the object are used as the heading text.
 * If an array is passed in:
 * 		The values of the objects in the arrays are used.
 * 
 * @param rowData the row of data to display
 * @return A string representing the html for the table
 */
function buildHeader(rowData){
    var cells = [];
    for (var key in rowData){
        if (key != "DataCmd" && key != "DataId" && key != "DataHash" && key != "DataParam")
            cells.push("number" == typeof(key) ? rowData[key] : key);
    }   
	return tableGen.headerRow(cells);
}

function findAndDel(data, colName){
    var value = data[colName];
    delete data[colName];
    return (value == undefined) ? "" : value;
}

function propValStr(property, value){
    if (value == "")
        return "";
    else
        return property + '="' + value + '" ';
}

/**
 * Build a body row of a html table.
 * 
 * @param rowData The data to display
 * @param dataColumns An array of columns to display (optional)
 * @param altClass The class to give alternate rows
 * @return The html created for the row
 */
function buildRow(rowData, dataColumns, altClass){
	var htmlOutput = "";
	var rowDataAttrs = "";
	// Test for drill down data
	rowDataAttrs += propValStr("data-cmd", findAndDel(rowData, "DataCmd"));
	rowDataAttrs += propValStr("data-id", findAndDel(rowData, "DataId"));
	rowDataAttrs += propValStr("data-hash", findAndDel(rowData, "DataHash"));
	rowDataAttrs += propValStr("data-param", findAndDel(rowData, "DataParam"));
	
	var outputData = "";
	if (dataColumns == null){
		for (var key in rowData){
			outputData += tableGen.dataCell(rowData[key]);
		}
	} else {
		for (var colKey in dataColumns){
			outputData += tableGen.dataCell(rowData[dataColumns[colKey]]);
		}
	}

	htmlOutput = tableGen.dataRow(rowDataAttrs, (altClass == true ? ' alt' : ''), outputData);	
	return htmlOutput;
}

/**
 * Build a html table for a list
 * @param json
 * @return The html created for the list
 */
function buildList(json){
	var html = '<table><thead>' + buildHeader(json[0]) + '</thead><tbody>';
	
	for (var i = 0; i < json.length; i++){
		html += buildRow(json[i], null, (i % 2 == 1));
	}
	html += '</tbody></table>';
	
	return html;
}

/**
 * Build a html table for an item
 * @param json
 * @return The html created for the item
 */
function buildItem(json){
   	// Test for drill down data
	var drillDownCmds = findAndDel(json,"DataCmd");
	var drillDownId = findAndDel(json,"DataId");
    
	var html = "<div class='table-item'>";
	html += "<h1 style='float: left'>" + findAndDel(json,'Title') + "</h1>";
	html += "<img src='icons/" + json['Visibility'].split(' ').join('').toLowerCase() + ".png' alt='" + json['Visibility'] + "' title='" + json['Visibility'] + "' style='padding-left: 0.5em'/>";
    delete(json['Visibility']);
	html += "<div style='float: right'><div style='text-align: right; margin-bottom: 1em;'><h2 style='margin: 0px'>Tags</h2>";
    for (var tagKey in json['Tags']){
        html += "<div class='link' data-cmd='Cmd=search&Tags=" + json['Tags'][tagKey]['Tag'] + "'>" + json['Tags'][tagKey]['Tag'] + "</div>";
    }
    delete(json['Tags']);
	html += "</div>";
    for (var imageKey in json['Images']){
        html += "<img style='width: 200px; margin-bottom: 1em; float: right; clear: right; border: 1px solid black' src='images/" + json['Images'][imageKey]['FilenameServer'] + "'/>";
    }
    delete(json['Images']);
	html += "</div>";
	html += "<div style='clear: left'></div>"
    for (var partKey in json){
        var value = json[partKey];
        if ("string" == typeof(value))
            value = value.split("\n").join("<br/>");

        if (partKey != "Author"){
        	if (value != "")
				html += "<div><h2 class='header-cell'>" + partKey + "</h2><div class='data-cell'>" + value + "&nbsp;</div></div>";
		} else {
			html += "<div><h2 class='header-cell'>" + partKey + "</h2><div class='data-cell'>" + value['Name'] + "&nbsp;</div></div>";
		}
    }
	html += "</div>";
	
	if ((drillDownCmds != undefined && drillDownId != undefined) && drillDownCmds.length > 0){
        html += "<div style='padding-top: 2em'><h2 class='header-cell'>Actions</h2><div class='data-cell'>";
        for (var drillDownKey in drillDownCmds){
            html += '<div class="link" data-cmd="Cmd=' + drillDownCmds[drillDownKey]['cmd'] + '&ID=' + drillDownId + '">'+ drillDownCmds[drillDownKey]['text'] + '</div>';
        }
        html += "</div></div>"
	}
	html += '<div style="clear: right"></div>'
	return html;
}

/**
 * Create HTML to list recipes based on an array of recipes from a search
 */
function buildRecipeList(json){
	var html = "";
	var count = 0;
	
	html += "<thead>";
	html += tableGen.headerRow(["Title", "Description", "Author", "Tags"]);
	html += "</thead><tbody>";
    for (var key in json){
        value = json[key];
        
        var output = [];
        output.push(tableGen.dataCell("<a href='#view/ID=" + value['ID'] + "'>" + value['Title'] + "</a>"));
        output.push(tableGen.dataCell(value['Description']));
        output.push(tableGen.dataCell(value['Author'] != undefined ? value['Author']['Name'] : ''));
        var tagList = [];
        var tags = value['Tags']
        for (var tagKey in tags){
            tagList.push(tags[tagKey]['Tag']);
        }
        output.push(tableGen.dataCell(tagList));
        var attrs = "data-cmd='view' data-id='" + value['ID'] + "'";
        var sClass = (count++ % 2 == 1 ? ' alt' : '');
        html += tableGen.dataRow(attrs, sClass, output);		
    }
	html += "</tbody>";
	
	return html;
}

/**
 * Creaet HTML to list the images of the selected recipe for managing
 */
function buildImageList(json){
	var html = "";
	var count = 0;
    
	html += "<thead>";
	html += tableGen.headerRow(["Images","Details"]);
	html += "</thead><tbody>";
	for (var imageKey in json){
		var image = json[imageKey];
		var rowHtml = "";
		rowHtml += tableGen.dataCell("<img style='border: 1px solid black' src='images/" + image['FilenameServer'] + "'/>");
		rowHtml += tableGen.dataCell("<label>Filename</label>&nbsp;" + image['Filename'] + 
									 "<label>Discription</label>&nbsp;" + image['Description'] +
									 "<label>Actions</label><div class='link' data-cmd='Cmd=deleteImage&ID=" + image['ID'] + "'>Delete Image</div>");
		
		html += tableGen.dataRow("", count++ % 2 == 1 ? " alt" : "", rowHtml);
	}
	html += "</tbody>";
    
	return html;
}

jQuery.expr[':'].icontains = function(a, i, m) { 
  return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0; 
};

/**
 * Event handler for searching for recipes
 * 
 * Searches the text in each row for a match and hides any row that doesn't have
 * any matching text.
 * 
 * Also removes all the 'alt' classes from the html elements and reapplies them
 * based on the now visible rows so that they are still altinating
 * 
 * @param data The parent html element which contains the rows to search for
 * @param searchFor The text string to search for
 */
function onchangeSearch(data, searchFor){
    var children = $(data).find("tbody").children();
    var showAll = searchFor == "" ? true : false;
    var visibleCount = 0;
	
    $.each(children, function(key, value){
		var jqValue = $(value);
		jqValue.removeClass("alt");
		if (!showAll){
			var found = jqValue.find("*:icontains('" + searchFor + "')");
			if (found.length > 0){
				jqValue.show();
				if (visibleCount++ % 2 == 1)
					jqValue.addClass("alt");
			} else {
				jqValue.hide();
			}
		} else {
			jqValue.show();
			if (visibleCount++ % 2 == 1)
				jqValue.addClass("alt");
		}
	});
}

/**
 * Build a html form to edit an item
 * 
 * @param container
 * @param json
 */
function buildForm(container, json){
	$.each(json, function(key, value){
		if (key == "Tags"){
			var tagValue = convertTagObjectsToStrArray(value).join(",");
			container.find("input[name='"+ key + "']").val(tagValue);
			container.find("textarea[name='"+ key + "']").val(tagValue);
		} else {
			container.find("input[name='"+ key + "']").val(value);
			container.find("textarea[name='"+ key + "']").val(value);
		}
	});
}

/**
 * Unselect the current tab and hide the current page, after fading
 * select the new page and fade in the new page
 *
 * @param pageId The id of the page
 * @param updateContentsFunc The function to run to update the contents of a page
 */
function selectPage(pageId, updateContentsFunc){
	if (pageId == "") {
		pageId = "#ajax";
	}
	
	if ($("pageId").hasClass("page")){
		// Unselect the currently selected tab
		$("div.menu div.sel").removeClass("sel");
		var button = $("pageId").attr("data-button-id");
		if (button != undefined || button != ""){
			$("#" + button).addClass("sel");
		}
	}
	
	var callback = function(){
		console.log("Replacing page...\n");
		// Call the update contents function
		if (updateContentsFunc != undefined)
			updateContentsFunc();
		
		if (pageId == "#edit" || pageId == "#add"){
			doAjaxJson("Cmd=GetTags",function(json){setupTags("edit",handleJson(json));});
		}
		
		// Fade in the new page
		$(pageId).fadeIn();
	}
	
	// Fade out the current page content
	var curPage = $(".page:visible");
	if (curPage == []){
		console.log("Calling callback direct...\n");
		callback();
	} else {
		console.log("Calling callback through fadeOut...\n");
		curPage.fadeOut(callback);
	}
}

/**
 * Add the functions to show and hide the loading dialog
 */
(function($){
	// Immediately show a throbber
	$.waitShow = function() {
		$("#wait").removeClass("hide");
		return $;
	};
	
	// Hide all throbbers
	$.waitHide = function() {
		$("#wait").addClass("hide");
		return $;
	};
})(jQuery);

function convertTagObjectsToStrArray(tags){
	//convert tag objects into array of strings
	var tagArray = [];
	$.each(tags,function (key,value){
		tagArray.push(value['Tag']);
	});
	return tagArray;
}

/**
 * Setup the tags for recipes editing and adding
 * 
 * @param pageId The ID of the page containing the form for adding/editing
 * @param tags The array of tags to use
 */
function setupTags(pageId, tags){
	// Remove old tags stuff if it's there
	$("DIV#" + pageId + " input#Tags ~ DIV.superblyTagfieldDiv").remove();
	// Get the populated values
	var data = $("DIV#" + pageId + " input#Tags").val();
	if (data != "")
		data = data.split(",");
	else
		data = [];

	var tagArray = convertTagObjectsToStrArray(tags);

	// Setup tags field
    $("DIV#" + pageId + " input#Tags").superblyTagField({
        allowNewTags: true,
        showTagsNumber: 10,
        preset: data,
        tags: tagArray
    });
}

function menuTriggerFunc(){
	var id = $(this).attr("id");
	
	// Unselect the currently selected tab
	$("div.menu div.sel").removeClass("sel");
	$(this).addClass("sel");
	
	var dataCmd = $(this).attr("data-cmd");
	if (dataCmd != undefined && dataCmd != ""){
		loadPageAjax("Cmd=" + dataCmd);
		return;
	}
	
	// Handle the logout button 
	if (id == "butLogin"){
		var box = $("#loginBox");
		box.toggleClass("hide");
	} else {
		// Grab the page we want to show
		var tabId = "#" + id;
		var pageId = "#" + $(tabId).attr("data-content-id");

		if (pageId == "#add"){
			doAjaxJson("Cmd=GetTags",function(json){setupTags("add",handleJson(json));});
		} else if (pageId == "#find"){
			doAjaxJson("Cmd=GetTags",function(json){setupTags("find",handleJson(json));});
		} else if (pageId == "#admin"){
			loadPageAjax("Cmd=admin");
			return;
		}

		if (pageId == "#home"){
			window.updateHash("#/");
		} else {
			window.updateHash(pageId + "/");
		}
		
		// Fade out the current page content
		$(".page:visible").fadeOut(callback=function(){
			var form = $(pageId).find("form")[0];
			if (form != undefined)
				form.reset();
			// Select the new tab
			$(tabId).addClass("sel");
			// Fade in the new page
			$(pageId).fadeIn();
		});
	}
}

function handleClick(e){
	var dataCmd = $(this).attr("data-cmd");
	var dataParam = $(this).attr("data-param");
	var dataId = $(this).attr("data-id");
	var dataHash = $(this).attr("data-hash");

	dataParam = (dataParam == undefined ? "" : dataParam);
	if (dataParam.trim() != ""){
		dataParam = "&" + dataParam;
	}

	if (dataId != undefined){
		loadPageAjax("Cmd=" + dataCmd + "&ID=" + dataId + dataParam);
	} else if (dataHash != undefined) {
		loadPageAjax("Cmd=" + dataCmd + "&Hash=" + dataHash + dataParam);
	} else if (dataCmd != undefined) {
		loadPageAjax("Cmd=" + dataCmd + dataParam);
	}
}

function handleJson(json){
	var displayType = json['Display'];
	var displayData = json['Data'];
	var displayURL = json['URL'];
	var displayCallback = json['Callback'];
	
	if (displayURL != ""){
		window.updateHash(displayURL);
	}
	
	switch (displayType){
		case 'ERROR':
			var errMsg = displayData['Message'];
			//An error occured so show the error dialog
			$("#error div").empty().append(errMsg);
			$.showDialog('error','Error');
			break;
		case 'REFRESH':
			//Refresh the page (after login or logout)
			window.location.reload();
			break;
		case 'MESSAGE':
			//popup dialog
			var msg = displayData['Message'];
			if ("string" == typeof(msg)) msg = msg.split("\n").join("<br/>");
			$("#dialogBlank").empty().append(msg);
			$.setDialogButtons($.dialog.buttonTypes.OK);
			$.showDialog('dialogBlank','Message');
			break;
		case 'DIALOG':
			//popup dialog
			var dlgMsg = displayData['Message'];
			if ("string" == typeof(msg)) dlgMsg = dlgMsg.split("\n").join("<br/>");
			$("#dialogBlank").empty().append(dlgMsg);
			$.setDialogButtons($.dialog.buttonTypes.OK_CANCEL,{OK: function (){loadPageAjax(displayData["OkCmd"]);}}); 
			$.showDialog('dialogBlank','Message');
			
			break;
		case 'CALLBACK':
			//Make a callback to the ajax controller with the given command string
			selectPage("", function(){
				loadPageAjax(displayData);
			});
			break;
		case 'LIST-RECIPE':
			selectPage("#list-recipe", function(){
				var container = $("#recipe-list");
				container.empty();
				container.append(buildRecipeList(displayData['Recipes']));
				
                var inputRadio = $("#list-recipe input[type='radio']");
                $.each(inputRadio, function (key, value){
                    if (value.value == displayData['Type']){
                        value.checked = true;
                    }
                });
                
				var rows = container.find('tr.data-row');
				// Add highlight on hovering in list tables
				rows.mouseover(function (){$(this).addClass("over");});
				rows.mouseout(function (){$(this).removeClass("over");});
				// Add click event handling
				rows.click(handleClick);
				
				
				onchangeSearch(container,$("#input-search").text());
			});
			break;
		case 'LIST':
			selectPage("#list", function(){
				var container = $("#list");
				container.empty();
				container.append(buildList(displayData));
				
				var rows = container.find('tr.data-row');
				// Add highlight on hovering in list tables
				rows.mouseover(function (){$(this).addClass("over");});
				rows.mouseout(function (){$(this).removeClass("over");});
				// Add click event handling
				rows.click(handleClick);
			});
			break;
		case 'ITEM':
			selectPage("#item", function(){
				var container = $("#item");
				container.empty();
				container.append(buildItem(displayData));

				// Add a form handler to the new content
				container.find("form").submit(handleForm);
				container.linkify();
				container.find("img").tooltip({showURL: false, track: true});
				container.find("img").click(function(){
					$("#dialogBlank").empty();
					var img = $(this).clone();
					img.attr("style","");
					img.appendTo("#dialogBlank");
					$.showDialog('dialogBlank',displayData['Title']);
				});
				container.find("div[data-cmd^='Cmd=']").click(function(){
					loadPageAjax($(this).attr("data-cmd"));
				});
			});
			break;
		case 'EDIT':
			selectPage("#edit", function () {
				buildForm($("#edit"),displayData);
			});
			break;
		case 'IMAGE':
			selectPage("#image", function() {
				var image = $("#image");
				image.find("input[id='id']").val(displayData['DataId']);
				image.find("span[id='Title']").text(displayData['Title']);
				
				var container = $("#image-list");
				container.empty();
				container.append(buildImageList(displayData['Images']));
				
				container.find("div[data-cmd^='Cmd=']").click(function(){
					loadPageAjax($(this).attr("data-cmd"));
				});
			});
			break;
		case 'TEXT':
			var container = $("#" + displayData['ID']);
			container.empty();
			container.append(displayData['Message']);
			break;
		case 'DATA':
			return displayData;
		case 'NOTHING':
			//do nothing :)
			break;
		default:
			alert("Unknown response type returned from ajax call: " + displayType);
			break;
	}
	
	if (displayCallback != undefined && displayCallback != ""){
		//Make a callback to the ajax controller with the given command string
		selectPage("", function(){
			loadPageAjax(displayCallback);
		});
	}
	
	return {};
}

/**
 * Make an ajax call
 * 
 * @param data The data to send (serialised form or similar)
 * @param successFunc The function to call if the call is successful
 * @param errorFunc (optional) The function to call if the call fails (caused by the server not responding or badly formatted JSON response).
 */
function doAjaxJson(data, successFunc, errorFunc){
	$.waitShow();
	
	if (errorFunc == undefined){
		errorFunc = function(error) {alert("Error loading ajax return object:\n\n" + error.responseText);};
	}
	
	$.ajax({
		type: 'POST',
		url: "serverside/ajaxResponder.php",
		data: data,
		dataType: "json",
		success: [successFunc, $.waitHide],
		error: [errorFunc, $.waitHide]
	});				
}

function loadPageAjax(cmd){
	doAjaxJson(cmd, function(json){handleJson(json);});
}

/**
 * handleForm
 * 
 * Called when a submit button on a form is pressed
 */
var handleForm = function (){
	var form = $(this);
	
	if (form.attr("name") == "login"){
		window.setTimeout(function() {
			loadPageAjax(form.serialize());
		}, 200);
		//If we are the login page we need to do a fake submit to save password
		return true;
	}
		
	loadPageAjax(form.serialize());
	return false;	
}