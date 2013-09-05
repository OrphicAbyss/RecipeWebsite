recipe = angular.module('recipe', []).
        config(['$routeProvider', function($routeProvider) {
        $routeProvider.
                when('/', {templateUrl: 'client/list.html', controller: ListCtrl}).
                when('/search/', {templateUrl: 'client/list.html', controller: ListCtrl, reloadOnSearch: false}).
                when('/view/:recipeId', {templateUrl: 'client/view.html', controller: ViewCtrl}).
                when('/add/', {templateUrl: 'client/edit.html', controller: AddCtrl}).
                when('/edit/:recipeId', {templateUrl: 'client/edit.html', controller: EditCtrl}).
                when('/profile/', {templateUrl: 'client/profile.html', controller: ProfileCtrl}).
                when('/confirm/', {templateUrl: 'client/list.html', controller: ConfirmCtrl}).
        otherwise({redirectTo: '/'});
    }]).
        run(function($rootScope, $templateCache) {
    $rootScope.$on('$viewContentLoaded', function() {
        $templateCache.removeAll();
    });
});

recipe.factory('$dialog', function() {
    return {
        open: function(title, message) {
            angular.element("#myModal .modal-title").html(title);
            angular.element("#myModal div.modal-body").html(message);
//            angular.element('#myModal').on('hide.bs.modal', function() {
//                $route.reload();
//            });
            angular.element("#myModal").modal();
        }
    };
});

recipe.factory('$recipeServer', function($http) {
    var callServer = function(data, successCallback) {
        $http({method: 'POST',
            url: 'serverside/jsonResponder.php',
            data: data,
            headers: {'Content-Type': 'application/json'}
        }).success(function(data, status, headers, config) {
            successCallback(data);
        }).error(function(data, status, headers, config) {
            console.log("Error: " + data);
        });
    };
    return {
        amLoggedIn: function(callback) {
            callServer({cmd: 'amLoggedIn'}, function(data) {
                callback(data.loggedIn);
            });
        },
        login: function(username, password, callback) {
            callServer({cmd: 'login', username: username, password: password}, callback);
        },
        reset: function(email, callback) {
            callServer({cmd: 'reset', email: email}, callback);
        },
        register: function(username, password, email, callback) {
            callServer({cmd: 'register', username: username, password: password, email: email}, callback);
        },
        logout: function(callback) {
            callServer({cmd: 'logout'}, function(data) {
                callback(data.loggedIn);
            });
        },
        updatePassword: function(password, newPassword, confNewPassword, successCallback, errorCallback) {
            callServer({cmd: 'updatePassword', password: password, newPassword: newPassword, confNewPassword: confNewPassword}, function(data) {
                if (data.error) {
                    errorCallback(data.message);
                } else {
                    successCallback(data.message);
                }
            });
        },
        view: function(recipeId, successCallback, errorCallback) {
            callServer({cmd: 'view', id: recipeId}, function(data) {
                if (data.error) {
                    errorCallback(data.message);
                } else {
                    successCallback(data.recipe);
                }
            });
        },
        edit: function(recipeId, successCallback, errorCallback) {
            callServer({cmd: 'edit', id: recipeId}, function(data) {
                if (data.error) {
                    errorCallback(data.message);
                } else {
                    successCallback(data.recipe);
                }
            });
        },
        save: function(data, successCallback) {
            callServer({cmd: 'save', recipe: data}, function(data) {
                successCallback(data);
            });
        },
        list: function(successCallback) {
            callServer({cmd: 'list'}, function(data) {
                successCallback(data);
            });
        }
    };
});

recipe.directive('tagInput', function() {
    return {
        restrict: 'A',
        scope: {
            tagList: '=tagInput'
        },
        link: function(scope, element, attrs) {
            scope.inputValue = "";
            scope.inputWidth = 20;
            // Watch for changes in text field
            scope.$watch(attrs.ngModel, function(value) {
                scope.inputValue = value;
            });
            element.bind('keydown', function(e) {
                if (e.which == 8 && scope.inputValue.length == 0) {
                    // delete previous tag
                    scope.tagList.splice(scope.tagList.length - 1, 1);
                }
                scope.$apply();
            });
            element.bind('keyup', function(e) {
                var key = e.which;
                // Tab or Enter pressed 
                if (key == 13) {
                    e.preventDefault();
                    var tag = scope.inputValue;
                    // check for empty string
                    if (tag.length != 0) {
                        // add our string
                        scope.tagList.push(tag);
                        scope.$apply(attrs.ngModel + "=''");
                    }
                }
                scope.$apply();
            });
            element.bind('focus', function(e) {
                element.parent().parent().parent().addClass("focus");
            });
            element.bind('blur', function(e) {
                element.parent().parent().parent().removeClass("focus");
            });
        }
    };
});

function PageCtrl($scope, $route, $recipeServer, $dialog, $templateCache) {
    $scope.loggedIn = false;
    $scope.user = {};
    angular.element('#loginAlert').html('');
    // Test if logged in
    $recipeServer.amLoggedIn(function(loggedIn) {
        $scope.loggedIn = loggedIn;
    });

    $scope.signin = function() {
        angular.element("#loginBox").modal();
    };

    $scope.signout = function() {
        $recipeServer.logout(function(data) {
            $scope.loggedIn = data;
            $route.reload();
        });
    };

    $scope.signinSubmit = function() {
        if ($scope.user.username === undefined)
            $scope.user.username = angular.element('#user').val();
        if ($scope.user.password === undefined)
            $scope.user.password = angular.element('#pass').val();

        angular.element('#loginAlert').html('');

        if ($scope.user.username == undefined ||
                $scope.user.username == "" ||
                $scope.user.password == undefined ||
                $scope.user.password == "") {
            angular.element('#loginAlert').html('<div class="alert alert-danger"><a class="close" data-dismiss="alert">&times;</a><span>Enter both a username and password.</span></div>');
        } else {
            $recipeServer.login($scope.user.username, $scope.user.password, function(data) {
                if (data.loggedIn == true) {
                    $scope.loggedIn = true;
                    angular.element("#loginBox").modal('hide');
                    $route.reload();
                } else {
                    angular.element('#loginAlert').html('<div class="alert alert-danger"><a class="close" data-dismiss="alert">&times;</a><span>' + data.message + '</span></div>');
                }
            });
        }
    };

    $scope.signinCancel = function() {
        angular.element("#loginBox").modal('hide');
    };

    $scope.registerClick = function() {
        angular.element('#loginBox').on('hidden.bs.modal', function(e) {
            angular.element("#registerBox").modal();
            angular.element('#loginBox').off('hidden.bs.modal');
        });
        angular.element("#loginBox").modal('hide');
    };

    $scope.registerSubmit = function() {
        if ($scope.user.username === undefined)
            $scope.user.username = angular.element('#user').val();
        if ($scope.user.password === undefined)
            $scope.user.password = angular.element('#pass').val();

        angular.element('#registerAlert').html('');

        if ($scope.user.username == undefined ||
                $scope.user.username == "" ||
                $scope.user.password == undefined ||
                $scope.user.password == "") {
            angular.element('#registerAlert').html('<div class="alert alert-danger"><a class="close" data-dismiss="alert">&times;</a><span>Enter both a username and password.</span></div>');
        } else {
            $recipeServer.register($scope.user.username, $scope.user.password, $scope.user.email, function(data) {
                if (data.error == false) {
                    angular.element('#registerBox').on('hidden.bs.modal', function(e) {
                        $dialog.open("Registerd", data.message);
                    });
                    angular.element("#registerBox").modal("hide");

                } else {
                    angular.element('#registerAlert').html('<div class="alert alert-danger"><a class="close" data-dismiss="alert">&times;</a><span>' + data.message + '</span></div>');
                }
            });
        }
    };

    $scope.registerCancel = function() {
        angular.element("#registerBox").modal("hide");
    };

    $scope.resetClick = function() {
        angular.element('#loginBox').on('hidden.bs.modal', function(e) {
            angular.element("#resetBox").modal();
            angular.element('#loginBox').off('hidden.bs.modal');
        });
        angular.element("#loginBox").modal("hide");

    };

    $scope.resetSubmit = function() {
        angular.element('#resetAlert').html('');
        $recipeServer.reset($scope.user.email, function(data) {
            if (data.error == false) {
                angular.element('#resetBox').on('hidden.bs.modal', function(e) {
                    $dialog.open("Password reset", data.message);
                    angular.element('#resetBox').off('hidden.bs.modal');
                });
                angular.element("#resetBox").modal("hide");
                //$route.reload();
            } else {
                angular.element('#resetAlert').html('<div class="alert alert-danger"><a class="close" data-dismiss="alert">&times;</a><span>' + data.message + '</span></div>');
            }
        });
    };

    $scope.resetCancel = function() {
        angular.element("#resetBox").modal("hide");
    };
}

function ConfirmCtrl($scope, $route) {
    alert("confirm");
}

function ListCtrl($scope, $recipeServer, $routeParams, $location) {
    angular.element("#nav-bar-menu li").removeClass("active");
    angular.element("#nav-search").addClass("active");

    $scope.search = $routeParams.search;
    $scope.recipes = [];

    $recipeServer.list(function(data) {
        $scope.recipes = data.recipes;
    });

    $scope.tagSearch = function(tag) {
        $location.search("search=" + tag);
        $scope.search = tag;
    };

    $scope.clearSearch = function(tag) {
        $location.search("");
        $scope.search = "";
    };
}

function ViewCtrl($scope, $recipeServer, $routeParams, $location) {
    angular.element("#nav-bar-menu li").removeClass("active");
    $scope.recipeId = $routeParams.recipeId;
    $scope.recipe = {};
    $scope.recipe.Visibility = "public";
    $recipeServer.view($scope.recipeId,
            function(recipe) {
                $scope.recipe = recipe;
                $scope.recipe.Visibility = $scope.recipe.Visibility.split(" ").join("");
                $scope.recipe.Description = $scope.recipe.Description.split("\n");
                $scope.recipe.Ingredients = $scope.recipe.Ingredients.split("\n");
                $scope.recipe.Method = $scope.recipe.Method.split("\n");
                $scope.recipe.Notes = $scope.recipe.Notes.split("\n");
                // TODO: check for controls
            },
            function(message) {
                $dialog.open("Error", message);
//                angular.element('#myModal').on('hide.bs.modal', function() {
//                    $location.path("/");
//                });
            });
    $scope.tagSearch = function(tag) {
        $location.path("/search/").search("search=" + tag);
    };
    $scope.edit = function() {
        $location.path("/edit/" + $scope.recipeId);
    };
}

function AddCtrl($scope, $recipeServer, $location) {
    angular.element("#nav-bar-menu li").removeClass("active");
    angular.element("#nav-add").addClass("active");
    $scope.recipe = {Tags: [], Visibility: 0};
    // TODO: Move this into the directive part for the input if possible
    $scope.deleteTag = function(tag) {
        if (tag === undefined) {
            $scope.recipe.Tags.splice($scope.recipe.Tags.length - 1, 1);
        } else {
            $scope.recipe.Tags.splice(tag, 1);
        }
    };
    $scope.save = function() {
        $recipeServer.save($scope.recipe, function(data) {
            $location.path("/view/" + data['recipeId']);
        });
    };
    $scope.cancel = function() {
//TODO: check form for changes and ask before continuing.
        $location.path("/search/");
    };
}

function EditCtrl($scope, $recipeServer, $routeParams, $location) {
    angular.element("#nav-bar-menu li").removeClass("active");
    $scope.recipeId = $routeParams.recipeId;
    $scope.recipe = {};
    // TODO: Move this into the directive part for the input if possible
    $scope.deleteTag = function(tag) {
        if (tag === undefined) {
            $scope.recipe.Tags.splice($scope.recipe.Tags.length - 1, 1);
        } else {
            $scope.recipe.Tags.splice(tag, 1);
        }
    };
    $recipeServer.edit($scope.recipeId,
            function(recipe) {
                $scope.recipe = recipe;
            },
            function(message) {
                $dialog.open("Error", message);
//                angular.element('#myModal').on('hide.bs.modal', function() {
//                    $location.path("/");
//                });
            });
    $scope.save = function() {
        $scope.ID = $scope.recipeID;
        $recipeServer.save($scope.recipe, function(data) {
            $location.path("/view/" + $scope.recipeId);
        });
    };
    $scope.cancel = function() {
//TODO: check form for changes and ask before continuing.
        $location.path("/view/" + $scope.recipeId);
    };
}

function ProfileCtrl($scope, $recipeServer) {
    angular.element("#nav-bar-menu li").removeClass("active");
    angular.element("#nav-profile").addClass("active");
    $scope.profile = {};
    $scope.save = function() {
        angular.element("#msgHolder").html("");
        var password = $scope.profile.password;
        var newPassword = $scope.profile.newPassword;
        var confNewPassword = $scope.profile.confNewPassword;
        $recipeServer.updatePassword(password, newPassword, confNewPassword,
                function(message) {
                    angular.element("#msgHolder").html('<div class="alert alert-success"><a class="close" data-dismiss="alert">&times;</a><span>' + message + '</span></div>');
                },
                function(message) {
                    angular.element("#msgHolder").html('<div class="alert alert-danger"><a class="close" data-dismiss="alert">&times;</a><span>' + message + '</span></div>');
                });
    };
}