(function($){
	//function set dialog window
	var dialogWindow = undefined;
	
	$.dialog = {
			buttonTypes: {
				NONE: 0,
				OK: 1,
				OK_CANCEL: 2
			},
			buttonClick: function(){
				var cmd = $(this).attr("data-cmd");
				var func = $.dialog.buttonCmds[cmd];
				
				if (func != undefined){
					func();
				}
				
				$.closeDialog();
			},
			buttonCmds: {}
		};
	
	$.setDialogWindow = function(window){
		dialogWindow = window;
		
		dialogWindow.dialog({ 
        	//height: 350,
			//width: 350,
			width: 'auto',
			resizable: false,
			modal: true,
			position: 'center',
			autoOpen: false,
			hide: "fadeOut",
			show: "fadeIn"
        });
	};
	
	//$.setDialogButtons = function(buttons){
		
	//};
	
	$.setDialogButtons = function(type, cmds){
		if (cmds == undefined)
			cmds = {};
		$.dialog.buttonCmds = cmds;
		
		switch (type){
		case $.dialog.buttonTypes.NONE:
			//nothing to add
			$("#dialogButtons").empty();
			break;
		case $.dialog.buttonTypes.OK:
			$("#dialogButtons").empty()
								.append('<button style="float: right" type="button" data-cmd="OK">OK</button>')
								.find("button").click($.dialog.buttonClick);
			
			break;
		case $.dialog.buttonTypes.OK_CANCEL:
			$("#dialogButtons").empty()
								.append('<button type="button" data-cmd="OK">OK</button><button style="float: right" type="button" data-cmd="CANCEL">Cancel</button>')
								.find("button").click($.dialog.buttonClick);;
			break;
		}
	};
	
	//function to show dialog   
	$.showDialog = function(id, title) {
		console.log("Dialog ID: " + id);
		var dialogDiv = undefined;
		dialogWindow.children().each(function (key, value){
			var divObj = $(value);
			var childId = divObj.attr("id");
			if (childId == id){
				dialogDiv = divObj;
				divObj.show();
			} else if (childId != "dialogButtons") {
				divObj.hide();
			}
		});
		
		if (title == undefined && dialogDiv != undefined){
			title = dialogDiv.attr('data-title');
		}
		
		dialogWindow.dialog("option", "title", title);
	    //if the contents have been hidden with css, you need this
		dialogWindow.show();
		// link the surrounding area to close the dialog
		$("div.ui-widget-overlay").live("click",$.closeDialog);
	    //open the dialog
		dialogWindow.dialog("open");
	};
	
	//function to close dialog, probably called by a button in the dialog
	$.closeDialog = function() {
		dialogWindow.dialog("close");
		//$.setDialogButtons($.dialog.buttonTypes.NONE);
	};
})(jQuery);