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

        <!-- Load ajaxFileUpload library -->
<!--        <script type="text/javascript" src="ajaxfileupload.js"></script>-->

        <!-- Piwik -->
        <script type="text/javascript" src="//gluonporridge.net/piwik/piwik.js"></script>
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

            html {
                height: 100%;
            }

            body {
                position: relative;
                min-height: 100%;
            }

            table.table tr:last-child td:first-child {
                -moz-border-radius-bottomleft:10px;
                -webkit-border-bottom-left-radius:10px;
                border-bottom-left-radius:10px
            }

            table.table tr:last-child td:last-child {
                -moz-border-radius-bottomright:10px;
                -webkit-border-bottom-right-radius:10px;
                border-bottom-right-radius:10px
            }

            .main-div {
                padding-bottom: 120px;
            }

            .footer {
                position: absolute;
                bottom: 0px;
                width: 100%;
                border-top: 1px solid #ccc;
                background-color: #eee;
                color: #555;
                padding-top: 15px;
            }

            @media only screen and (max-width: 800px) {
                #list-table td:nth-child(3),
                #list-table th:nth-child(3) {
                    display: none;
                }
            }

            .animate-enter {
                -webkit-animation: enter_sequence 0.5s linear; /* Safari/Chrome */
                -moz-animation: enter_sequence 0.5s linear; /* Firefox */
                -o-animation: enter_sequence 0.5s linear; /* Opera */
                animation: enter_sequence 0.5s linear; /* IE10+ and Future Browsers */
            }
            
            @-webkit-keyframes enter_sequence {
                from { opacity:0; }
                to { opacity:1; }
            }
            
            @-moz-keyframes enter_sequence {
                from { opacity:0; }
                to { opacity:1; }
            }
            
            @-o-keyframes enter_sequence {
                from { opacity:0; }
                to { opacity:1; }
            }
            
            @keyframes enter_sequence {
                from { opacity:0; }
                to { opacity:1; }
            }
            
            .animate-exit {
                -webkit-animation: exit_sequence 0.5s linear; /* Safari/Chrome */
                -moz-animation: exit_sequence 0.5s linear; /* Firefox */
                -o-animation: exit_sequence 0.5s linear; /* Opera */
                animation: exit_sequence 0.5s linear; /* IE10+ and Future Browsers */
            }
            
            @-webkit-keyframes exit_sequence {
                from { opacity:1; }
                to { opacity:0; }
            }
            
            @-moz-keyframes exit_sequence {
                from { opacity:1; }
                to { opacity:0; }
            }
            
            @-o-keyframes exit_sequence {
                from { opacity:1; }
                to { opacity:0; }
            }
            
            @keyframes exit_sequence {
                from { opacity:1; }
                to { opacity:0; }
            }
        </style>

        <!-- Latest compiled and minified CSS -->
        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
        <!-- Optional theme -->
        <!--<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-theme.min.css">-->
        <!-- Latest compiled and minified JavaScript -->
        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.js"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.1.5/angular.js"></script>
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.js"></script>
        <script src="client/controller.js"></script>
        
        <script type="text/javascript">
            $(document).ready(function() {
                window.onhashchange = function() {
                    //track in piwik
                    piwikTracker.discardHashTag(false);
                    piwikTracker.setCustomUrl(window.location.href);
                    piwikTracker.setDocumentTitle(document.title);
                    piwikTracker.trackPageView();
                };
            });
        </script>
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
                        <li data-ng-show="loggedIn"><a href="" data-ng-click="signout()">Sign out</a></li>
                        <li data-ng-show="!loggedIn"><a href="" data-ng-click="signin()">Sign in</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="container">
            <div ng-include="" id="box"></div>
            <div id="loginBox" class="modal fade">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-ng-click="signinCancel()">&times;</button>
                            <h4 class="modal-title">Sign in</h4>
                        </div>
                        <form name="login">
                            <div class="modal-body">
                                <div id="loginAlert"></div>

                                <label for="user">Username:</label>
                                <input type="text" id="user" name="user" class="form-control" data-ng-model="user.username"/>
                                <label for="pass">Password:</label>
                                <input type="password" id="pass" name="pass" class="form-control" data-ng-model="user.password"/>
                            </div>
                            <div class="modal-footer">
                                <div class="pull-left">
                                    <button type="button" class="btn btn-sm" data-ng-click="registerClick()">Not registered?</button>
                                    <button type="button" class="btn btn-sm" data-ng-click="resetClick()">Forgotten Password?</button>
                                </div>
                                <button type="submit" class="btn btn-primary" data-ng-click="signinSubmit()">Sign in</button>
                                <button type="button" class="btn btn-default" data-ng-click="signinCancel()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div id="registerBox" class="modal fade">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-ng-click="registerCancel()">&times;</button>
                            <h4 class="modal-title">Register Account</h4>
                        </div>
                        <form name="register">
                            <div class="modal-body">
                                <div id="registerAlert"></div>
                                <label for="Name">Username:</label>
                                <input type="text" id="user" name="user" class="form-control" data-ng-model="user.username"/>
                                <label for="Pass">Password:</label>
                                <input type="password" id="pass" name="pass" class="form-control" data-ng-model="user.password"/>
                                <label for="Email">Email:</label>
                                <input type="email" id="Email" name="email" class="form-control" data-ng-model="user.email"/>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" data-ng-click="registerSubmit()">Register</button>
                                <button type="button" class="btn btn-default" data-ng-click="registerCancel()">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div id="resetBox" class="modal fade">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-ng-click="resetCancel()">&times;</button>
                            <h4 class="modal-title">Reset Password</h4>
                        </div>
                        <form name="reset">
                            <div class="modal-body">
                                <div id="resetAlert"></div>

                                <label for="Email">Registered Email:</label>
                                <input type="email" id="Email" name="email" class="form-control" data-ng-model="user.email"/>

                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" data-ng-click="resetSubmit()">Reset Password</button>
                                <button type="button" class="btn btn-default" data-ng-click="resetCancel()">Cancel</button>
                            </div>
                        </form>
                    </div>
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
        <div class="container main-div">
            <div data-ng-view data-ng-animate="{enter: 'animate-enter', leave: 'animate-exit'}">
                <p>Broken view controller</p>
            </div>
        </div>
        <div class="footer">
            <div class="container">
                <p><b>Gluon Recipe</b></p>
                <p>Client side built using <a href="http://angularjs.org/">AngularJS</a>, <a href="http://getbootstrap.com/">Bootstrap</a> and <a href="http://jquery.com/">JQuery</a>.</p>
                <p>Backend build using <a href="http://www.mysql.com/">MySQL</a> and <a href="http://php.net/">PHP</a>.</p>
            </div>
        </div>
    </body>
</html>