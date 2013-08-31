<?php
session_start();

//session variable loggedin contains the logged in state, if its not set default it to 0 meaning not logged in
if (!isset($_SESSION['loggedin'])) {
    $_SESSION['loggedin'] = false;
    $_SESSION['userid'] = "";
    $_SESSION['username'] = "";
    $_SESSION['isadmin'] = false;
}
?><!DOCTYPE html>
<html data-ng-app="recipe">
    <head>
        <title>Recipe Notebook</title>
        <!-- Load jQuery Library -->
        <!--script type="text/javascript" src="jquery/jquery-1.6.1.js"></script-->
        <!--<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>-->
        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>

        <!-- Load jQuery Plugins -->
        <script type="text/javascript" src="jquery/jquery.linkify-1.0-min.js"></script>
        <link rel="stylesheet" href="superbly-tagfield/superbly-tagfield.css" />
        <script type="text/javascript" src="superbly-tagfield/superbly-tagfield.min.js"></script>

        <!-- Load stacktrack js library -->
        <script type="text/javascript" src="stacktrace-0.3.js"></script>

        <!-- Load ajaxFileUpload library -->
        <script type="text/javascript" src="ajaxfileupload.js"></script>
        <!-- Load tooltip js library -->
        <script type="text/javascript" src="jquery-tooltip/jquery.tooltip.min.js"></script>
        <link rel="stylesheet" href="jquery-tooltip/jquery.tooltip.css" type="text/css"></link>
        <!--         Load recipe js library 
                <script type="text/javascript" src="recipe.js"></script>
                 Load debug util library 
                <script type="text/javascript" src="util.js"></script>-->

        <!-- Piwik -->
        <script type="text/javascript" src="../piwik/piwik.js"></script>
        <script type="text/javascript">
            var pkBaseURL = (("https:" == document.location.protocol) ? "https://www.gluonporridge.net/piwik/" : "http://www.gluonporridge.net/piwik/");
            try {
                var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 3);
                piwikTracker.trackPageView();
                piwikTracker.enableLinkTracking();
            } catch (err) {
            }
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
            $(document).ready(function() {
                window.onhashchange = function() {
//                    if (!window.ignoreHashChange) {
//                        handleUrlParams();
//                        if (urlPage == "") {
//                            urlPage = "home";
//                        }
//                        var pageObj = $("div.navbar button[data-content-id='" + urlPage + "']");
//                        if (pageObj.length > 0) {
//                            pageObj.trigger("click");
//                        } else {
//                            loadPageAjax("Cmd=" + urlPage + "&" + urlParamsFull);
//                        }
//                    }
//                    window.ignoreHashChange = false;

//                    //track in piwik
//                    piwikTracker.setCustomUrl(window.location.href);
//                    piwikTracker.setDocumentTitle(document.title);
//                    piwikTracker.trackPageView();
                };
            }
        </script>

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
        <!-- Optional theme -->
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-theme.min.css">
        <!-- Latest compiled and minified JavaScript -->
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.0.7/angular.min.js"></script>
        <script src="client/controller.js"></script>
    </head>
    <body data-ng-controller="PageCtrl">
        <div class="navbar">
            <div class="container">
                <div class="navbar-nav">
                    <a class="navbar-brand">Gastronomy Taxonomy</a>
                </div>
                <ul class="nav navbar-nav navbar-right">
                    <li><a href="#search">Find a recipe</a></li>
                    <li data-ng-show="loggedIn"><a href="#add">Add a recipe</a></li>
                    <li data-ng-show="loggedIn"><a href="#profile">Profile</a></li>
                    <?php if ($_SESSION['isadmin'] == true) { ?>
                        <li data-ng-show="loggedIn"><a href="#admin">Admin</a></li>
                    <?php } ?>
                    <li data-ng-show="loggedIn"><a data-ng-click="signout()">Sign out</a></li>
                    <li data-ng-show="!loggedIn"><a data-ng-click="signin()">Sign in</a></li>
                </ul>
            </div>
        </div>
        <div class="container">
            <div id="loginBox" class="loginBox hide">
                <div class="well">
                    <div id="loginAlert"></div>
                    <form name="login" target="autocomp" method="post" action="login.html">
                        <label for="user">Username:</label>
                        <input type="text" id="user" name="user" class="form-control" data-ng-model="username"/>
                        <label for="pass">Password:</label>
                        <input type="password" id="pass" name="pass" class="form-control" data-ng-model="password"/>
                        <button type="button" class="btn btn-primary" data-ng-click="signinSubmit()">Sign in</button>
                        <button type="button" class="btn btn-default" data-ng-click="signinCancel()">Cancel</button>
                        <a data-dialog="frmReg" style="font-size: small">Not registered?</a>
                        <a data-dialog="frmReset" style="font-size: small">Forgotten Password?</a>
                    </form>
                    <div id="response" style="color: red"></div>
                    <iframe name="autocomp" style="display: none"></iframe>
                </div>
            </div>
        </div>
        <div>
            <!-- Modal -->
            <div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                            <h4 class="modal-title"></h4>
                        </div>
                        <div class="modal-body">
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <div data-ng-view>
                <p>Broken view controller</p>
            </div>
        </div>
        <div class="container">
            <div class="content">
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
                <div class="page hide" id="list">
                </div>
                <div class="page hide" id="item" style="margin-left: 30px; border-left: 1px dashed red; padding-left: 30px; padding-right: 30px;">
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