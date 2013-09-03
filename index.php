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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Recipe Notebook</title>
        <!-- Load jQuery Library -->
        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js"></script>

        <!-- Load stacktrack js library -->
        <script type="text/javascript" src="stacktrace-0.3.js"></script>

        <!-- Load ajaxFileUpload library -->
        <script type="text/javascript" src="ajaxfileupload.js"></script>

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

        <!-- Import our css -->
        <style type="text/css">
            /*@import "recipe.css";*/

            .tag-input {
                margin-top: -1px;
                width: 100%;
                border: none;
                outline: none;
                color: #555;
            }

            .tag {
                -webkit-box-shadow: inset 0 1px 0 rgba(255,255,255,0.15),0 1px 1px rgba(0,0,0,0.075);
                box-shadow: inset 0 1px 0 rgba(255,255,255,0.15),0 1px 1px rgba(0,0,0,0.075);
                border: 1px solid #ccc;
                background-color: #eee;
                border-radius: 4px;
                padding-left: 6px;
                padding-right: 6px;
            }

            .tag-control {
                height: auto;
            }

            .tag-control.focus {
                border-color: #66afe9;
                outline: 0;
                -webkit-box-shadow: inset 0 1px 1px rgba(0,0,0,0.075),0 0 8px rgba(102,175,233,0.6);
                box-shadow: inset 0 1px 1px rgba(0,0,0,0.075),0 0 8px rgba(102,175,233,0.6);
            }

            div.no-side-padding {
                padding-left: 0px;
                padding-right: 0px;
            }

            .table {
                border-radius: 4px;
            }

            @media only screen and (max-width: 800px) {
                #list-table td:nth-child(3),
                #list-table th:nth-child(3) {
                    display: none;
                }
            }
        </style>
        <script type="text/javascript">
//            $(document).ready(function() {
//                window.onhashchange = function() {
//                    //track in piwik
//                    piwikTracker.setCustomUrl(window.location.href);
//                    piwikTracker.setDocumentTitle(document.title);
//                    piwikTracker.trackPageView();
//                };
//            }
        </script>

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
        <!-- Optional theme -->
        <!--<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-theme.min.css">-->
        <!-- Latest compiled and minified JavaScript -->
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.0.7/angular.min.js"></script>
        <script src="client/controller.js"></script>
    </head>
    <body data-ng-controller="PageCtrl">
        <div class="navbar navbar-default">
            <div class="container">
                <div class="navbar-header">
                    <button class="navbar-toggle collapsed" type="button" data-toggle="collapse" data-target=".bs-navbar-collapse">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand">Gastronomy Taxonomy</a>
                </div>
                <div class="navbar-collapse bs-navbar-collapse collapse" role="navigation" style="height: 1px;">
                    <ul id="nav-bar-menu" class="nav navbar-nav navbar-right">
                        <li id="nav-search"><a href="#search">Find a recipe</a></li>
                        <li id="nav-add" data-ng-show="loggedIn"><a href="#add">Add a recipe</a></li>
                        <li id="nav-profile" data-ng-show="loggedIn"><a href="#profile">Profile</a></li>
                        <?php if ($_SESSION['isadmin'] == true) { ?>
                            <li id="nav-admin" data-ng-show="loggedIn"><a href="#admin">Admin</a></li>
                        <?php } ?>
                        <li data-ng-show="loggedIn"><a data-ng-click="signout()">Sign out</a></li>
                        <li data-ng-show="!loggedIn"><a data-ng-click="signin()">Sign in</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="container">
            <div id="loginBox" class="well hide">
                <div id="loginAlert"></div>
                <form name="login">
                    <label for="user">Username:</label>
                    <input type="text" id="user" name="user" class="form-control" data-ng-model="user.username"/>
                    <label for="pass">Password:</label>
                    <input type="password" id="pass" name="pass" class="form-control" data-ng-model="user.password"/>
                    <br/>
                    <div class="pull-right">
                        <button type="button" class="btn btn-sm" data-ng-click="register()">Not registered?</button>
                        <button type="button" class="btn btn-sm" data-ng-click="reset()">Forgotten Password?</button>
                    </div>
                    <div class="">
                        <button type="button" class="btn btn-primary" data-ng-click="signinSubmit()">Sign in</button>
                        <button type="button" class="btn btn-default" data-ng-click="signinCancel()">Cancel</button>
                    </div>
                </form>
            </div>
            <div id="registerBox" class="well hide">
                <div id="registerAlert"></div>
                <form name="frmReg">
                    <label for="Name">Username:</label>
                    <input type="text" id="user" name="user" class="form-control" data-ng-model="user.username"/>
                    <label for="Pass">Password:</label>
                    <input type="password" id="pass" name="pass" class="form-control" data-ng-model="user.password"/>
                    <label for="Email">Email:</label>
                    <input type="email" id="Email" name="email" class="form-control" data-ng-model="user.email"/>
                    <br/>
                    <div class="">
                        <button type="button" class="btn btn-primary" type="registerSubmit()">Register</button>
                        <button type="button" class="btn btn-default" data-ng-click="registerCancel()">Cancel</button>
                    </div>
            </div>
            <div id="resetBox" class="well hide">
                <div id="resetAlert"></div>
                <form name="frmReset">
                    <label for="Email">Registered Email:</label>
                    <input type="email" id="Email" name="email" class="form-control" data-ng-model="user.email"/>
                    <br/>
                    <div class="">
                        <button type="button" class="btn btn-primary" type="resetSubmit()">Reset Password</button>
                        <button type="button" class="btn btn-default" data-ng-click="resetCancel()">Cancel</button>
                    </div>
                </form>
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
            </div>
        </div>
    </body>
</html>