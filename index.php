<?php
	session_start();
	
	//session variable loggedin contains the logged in state, if its not set default it to 0 meaning not logged in
	if (!isset($_SESSION['loggedin'])){
		$_SESSION['loggedin'] = false;
		$_SESSION['userid']   = "";
		$_SESSION['username'] = "";
		$_SESSION['isadmin']  = false;
	}
?><!DOCTYPE html>
<html>
	<head>
		<title>Recipe Notebook</title>
		<!-- Load jQuery Library -->
		<!--script type="text/javascript" src="jquery/jquery-1.6.1.js"></script-->
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
		
		<!-- Load jQuery Plugins -->
		<script type="text/javascript" src="jquery/jquery.linkify-1.0-min.js"></script>
		<link rel="stylesheet" href="superbly-tagfield/superbly-tagfield.css" />
		<script type="text/javascript" src="superbly-tagfield/superbly-tagfield.min.js"></script>
		
		<!-- Load jQuery UI -->
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/jquery-ui.min.js"></script>
		<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/themes/base/jquery-ui.css" type="text/css" media="all" />
		
		<!-- Load stacktrack js library -->
		<script type="text/javascript" src="stacktrace-0.3.js"></script>
		
		<!-- Load ajaxFileUpload library -->
		<script type="text/javascript" src="ajaxfileupload.js"></script>
		<!-- Load tooltip js library -->
		<script type="text/javascript" src="jquery-tooltip/jquery.tooltip.min.js"></script>
		<link rel="stylesheet" href="jquery-tooltip/jquery.tooltip.css" type="text/css"></script>
		<!-- Load dialog js library -->
		<script type="text/javascript" src="dialog.js"></script>
		<!-- Load recipe js library -->
		<script type="text/javascript" src="recipe.js"></script>
		<!-- Load debug util library -->
		<script type="text/javascript" src="util.js"></script>
		
		<!-- Piwik -->
		<script type="text/javascript" src="../piwik/piwik.js"></script>
		<script type="text/javascript">
			var pkBaseURL = (("https:" == document.location.protocol) ? "https://www.gluonporridge.net/piwik/" : "http://www.gluonporridge.net/piwik/");
			try {
				var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 3);
				piwikTracker.trackPageView();
				piwikTracker.enableLinkTracking();
			} catch( err ) {}
		</script>
		<!-- End Piwik Tracking Code -->
		
		<!-- Import fonts -->
		<link href='http://fonts.googleapis.com/css?family=PT+Serif+Caption' rel='stylesheet' type='text/css'>
		<link href='http://fonts.googleapis.com/css?family=Quattrocento+Sans' rel='stylesheet' type='text/css'>
		<!-- Import our css -->
		<style type="text/css">
			@import "recipe.css";
		</style>
		<script type="text/javascript">	
			var urlPage = "";
			var urlParamsFull = "";
			var urlParams = {};
			function handleUrlParams() {
				urlPage = "";
				urlParamsFull = "";
				urlParams = {};
			    var e;
			    var a = /\+/g;  // Regex for replacing addition symbol with a space
			    var r = /([^&=]+)=?([^&]*)/g;
			    var d = function (s) { return decodeURIComponent(s.replace(a, " ")); };
			    var q = window.location.hash.substring(1);
                
                if (q.indexOf("/") != -1){
                    urlPage = q.substring(0,q.indexOf("/"));
                    urlParamsFull = q.substring(q.indexOf("/") + 1); 
                    q = urlParamsFull; 
                }
	
			    while (null != (e = r.exec(q))){
			       urlParams[d(e[1])] = d(e[2]);
                }
			}
			handleUrlParams();
			
			$(document).ready(function() {
				// Setup the dialog window
				$.setDialogWindow($("#dialog"));
				// Event listener for the forms
				$("form").submit(handleForm);
				// Handle events from a tag buttons
				//$("a[data-dialog!='']").click(function (){
		            //$.showDialog($(this).attr("data-dialog"));
	            //});

				window.ignoreHashChange = false;
				
				window.updateHash = function(newHash){
					if (newHash.substring(0,1) != "#"){
						newHash = "#" + newHash;
					}
					if (window.location.hash != newHash){
						window.ignoreHashChange = true;
						window.location.hash = newHash;
					}
				};
				
				window.onhashchange = function(){
					if (!window.ignoreHashChange){
						handleUrlParams();
						if (urlPage == ""){
							urlPage = "home";
						}
						var pageObj = $("div.menu div[data-content-id='" + urlPage + "']");
						if (pageObj.length > 0){
							pageObj.trigger("click");
						} else {
							loadPageAjax("Cmd=" + urlPage + "&" + urlParamsFull);
						}
					}
					window.ignoreHashChange = false;

					//track in piwik
					piwikTracker.setCustomUrl(window.location.href);
					piwikTracker.setDocumentTitle(document.title);
					piwikTracker.trackPageView();
				};
				
				
				/* MENU BAR EVENTS */
				var menu = $("div.menu div"); 			
				menu.bind('click',menuTriggerFunc);
				menu.mouseover(function (){ $(this).addClass("over"); });
				menu.mouseout(function (){ $(this).removeClass("over"); });

                var searchInputs = $("input[name='search-type']");
                searchInputs.click(function (){
                    loadPageAjax("Cmd=search&" + $(this).val() + "=true");
                });

				if ("confirm" in urlParams){
					window.location.hash = "#";
					loadPageAjax("Cmd=Confirm&confirm=" + urlParams["confirm"] + "&username=" + urlParams["username"]);
					handleUrlParams();
				}

				if (urlPage != ""){
					var pageObj = $("div.menu div[data-content-id='" + urlPage + "']");
					if (pageObj.length > 0){
						pageObj.trigger("click");
					} else {
						loadPageAjax("Cmd=" + urlPage + "&" + urlParamsFull);
					}
				} else {
					loadPageAjax("Cmd=search");
				}
			});
		</script>
	</head>
	<body>
		<div class="container">
			<div class="header">
				<div class="menu">
<!--<div id="butHome" data-content-id="home" class="sel">Home</div>-->
<?php if ($_SESSION['loggedin'] == true){ ?>
					<div id="butFind" data-content-id="search" data-cmd="search">Find a recipe</div>
					<div id="butAdd" data-content-id="add">Add a recipe</div>
					<div id="butProfile" data-content-id="profile">Profile</div>
<?php 	if ($_SESSION['isadmin'] == true){ ?>
					<div id="butAdmin" data-cmd="admin">Admin</div>
<?php 	} ?>
					<div id="butLogout" data-cmd="Logout">Logout</div>
<?php } else { ?>
					<div id="butLogin">Login</div>
<?php }?>
				</div>
				<div class="maxwidth">
					<div id="loginBox" class="loginBox hide">
						<form name="login" target="autocomp" method="post" action="login.html">
							<label for="user">Username:</label>
							<input type="text" id="user" name="user" />
							<label for="pass">Password:</label>
							<input type="password" id="pass" name="pass" /><br />
							<input type="hidden" id="Cmd" name="Cmd" value="Login" /> 
							<input id="btnLogin" value="Login" type="submit" />
							<a data-dialog="frmReg" style="font-size: small">Not registered?</a>
							<a data-dialog="frmReset" style="font-size: small">Forgotten Password?</a>
						</form>
						<div id="response" style="color: red"></div>
						<iframe name="autocomp" style="display: none"></iframe>
					</div>
				</div>
				<div class="title">
					<h1>Gastronomy Taxonomy</h1>
				</div>
			</div>
			<div class="content">
				<div class="page" id="home"><!-- data-button-id="butHome"> -->
				</div>
				<div class="page hide" id="find" data-button-id="butFind">
					<form>
						<input type="hidden" name="Cmd" value="search" />
						<label>List all public recipes</label>
						<input type="hidden" name="Public" value="1" />
						<input type="submit" id="find" value="find"/>
					</form>
					<form>
						<input type="hidden" name="Cmd" value="search" />
						<label>List all my recipes</label>
						<input type="submit" id="find" value="find"/>
					</form>
					<form>
						<input type="hidden" name="Cmd" value="search" />
						<label>Search by tags:</label>
					 	<input type="text" id="Tags" name="Tags" />
						<input type="submit" value="find"/>
					</form>
				</div>
				<div class="page hide" id="list-recipe" data-button-id="butFind">
					<div style="display: inline">
						<label style="display: inline" for="input-public">Public:</label>
						<input type="radio" id="input-public" name="search-type" value="Public">
<?php if ($_SESSION['loggedin'] == true){ ?>
						<label style="display: inline" for="input-private">Private:</label>
						<input type="radio" id="input-private" name="search-type" value="Private">
<?php } ?>
					</div>
					<div style='float: right'>
						<label style='display: inline' for='input-search'>Search:</label>
						<input type='text' id='input-search' name='input-search' onkeyup='javascript:onChangeSearch("#recipe-list", this.value);'>
					</div>
					<table id="recipe-list" style="clear: right; width: 100%;">
					</table>
				</div>
				<div class="page hide" id="list">
				</div>
				<div class="page hide" id="item" style="margin-left: 30px; border-left: 1px dashed red; padding-left: 30px; padding-right: 30px;">
				</div>
				<div class="page hide" id="add" data-button-id="add">
					<form>
						<label for="Title">Title</label>
						<textarea id="Title" name="Title" rows=1></textarea><br />
						<label for="Description">Description (optional)</label>
						<textarea id="Description" name="Description" rows=10></textarea><br />
						<label for="Ingredients">Ingredients</label>
						<textarea id="Ingredients" name="Ingredients" rows=10></textarea><br />
						<label for="Method">Method</label>
						<textarea id="Method" name="Method" rows=10></textarea><br />
						<label for="Notes">Notes (optional)</label>
						<textarea id="Notes" name="Notes" rows=10></textarea><br />
						<label for="Source">Source (optional)</label>
					 	<textarea id="Source" name="Source" rows = 1></textarea><br />
					 	<label for="Tags">Tags</label>
					 	<input type="text" id="Tags" name="Tags" /><br/>
					 	<label for="Visibility">Visibility</label>
					 	<select id="Visibility" name="Visibility">
							<option value="0" selected>Private</option>
							<option value="1">Link Share</option>
							<option value="2">Public</option>					 	
					 	</select><br/>
					 	<input type="hidden" id="Cmd" name="Cmd" value="SaveRecipe"/>
						<input name="btnSave" value="Save" type="submit"/>
					</form>
				</div>
				<div class="page hide" id="edit">
					<form>
						<input type="hidden" name="ID" />
						<input type="hidden" name="Version" />
						<label for="Title">Title</label>
						<textarea id="Title" name="Title" rows=1></textarea><br/>
						<label for="Description">Description (optional)</label>
						<textarea id="Description" name="Description" rows=10></textarea><br/>
						<label for="Ingredients">Ingredients</label>
						<textarea id="Ingredients" name="Ingredients" rows=10></textarea><br/>
						<label for="Method">Method</label>
						<textarea id="Method" name="Method" rows=10></textarea><br/>
						<label for="Notes">Notes (optional)</label>
						<textarea id="Notes" name="Notes" rows=10></textarea><br />
						<label for="Source">Source (optional)</label>
					 	<textarea id="Source" name="Source" rows = 1></textarea><br/>
					 	<label for="Tags">Tags</label>
					 	<input type="text" id="Tags" name="Tags" /><br/>
					 	<label for="Visibility">Visibility</label>
					 	<select id="Visibility" name="Visibility">
							<option value="0" selected>Private</option>
							<option value="1">Link Share</option>
							<option value="2">Public</option>					 	
					 	</select><br/>
					 	<input type="hidden" id="Cmd" name="Cmd" value="SaveRecipe"/>
						<input name="btnSave" value="Save" type="submit"/>
					</form>
				</div>
				<div class="page hide" id="image">
					<form name="form" action="" method="POST" enctype="multipart/form-data">
						<h1>Manage images for recipe: <span id="Title"></span></h1>
                        <table id="image-list" width="100%">
                        </table>
						<h1>Upload new image</h1>
						<input id="id" name="id" type="hidden" value=""/>
						<input id="Cmd" name="Cmd" type="hidden" value="imageUpload"/>
						<label for="imgDescription">Image Description</label>
						<input id="imgGescription" name="description" type="text" maxlength="255"/><br/>
						<label for="fileToUpload">Image File</label>
						<input id="fileToUpload" name="fileToUpload" type="file" size="45"/><br/>
						<button class="button" id="buttonUpload" onclick="javascript: return ajaxFileUpload($('#image form'));">Upload</button>
					</form>
				</div>
				<div class="page hide" id="profile" data-button-id="profile">
					<form>
						<label for="pass">Current Password</label>
						<input type="password" id="pass" name="pass" /><br />
						<label for="newPass">New Password</label>
						<input type="password" id="newPass" name="newPass" /><br />
						<label for="confNewPass">Confirm New Password</label>
						<input type="password" id="confNewPass" name="confNewPass" /><br />
					 	<input type="hidden" id="Cmd" name="Cmd" value="UpdatePassword"/>
						<input name="btnSave" value="Save" type="submit"/>
					</form>
				</div>
				<div class="page hide" id="admin">
				</div>
				<div class="page hide" id="ajax">
				</div>
				<div class="hide" id="dialog">
					<div id="frmReg" data-title="Register Account">
						<form name="frmReg">
							<label for="Name">Username:</label>
							<input type="text" id="Name" name="user" />
							<label for="Pass">Password:</label>
							<input type="password" id="Pass" name="pass" />
							<label for="Email">Email:</label>
							<input type="text" id="Email" name="Email" />
							<input type="hidden" id="Cmd" name="Cmd" value="Register"/>
							<input name="btnRegister" style="float: right" value="Register" type="submit"/>
							<div class="clear"></div>
						</form>
					</div>
					<div id="frmReset" data-title="Reset Password">
						<form name="frmReset">
							<label for="Email">Registered Email:</label>
							<input type="text" id="Email" name="Email" />
							<input type="hidden" id="Cmd" name="Cmd" value="ResetPass"/>
							<input name="btnRegister" style="float: right" value="Reset Password" type="submit"/>
							<div class="clear"></div>
							<div id="responseReset" style="color: red"></div>
						</form>
					</div>
					<div id="error" data-title="Error">
						<span class="ui-icon ui-icon-alert" style="float: left"></span>
						<div></div>
					</div>
					<div id="dialogBlank"></div>
					<div id="dialogButtons"></div>
				</div>
				<div id="wait" class="wait hide"><p><img src="images/ajax-loader.gif" />&nbsp;&nbsp;Loading...</p></div>
			</div>
		</div>
	</body>
</html>