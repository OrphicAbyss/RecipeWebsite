recipe = angular.module('recipe', []).
        config(['$routeProvider', function($routeProvider) {
        $routeProvider.
                when('/', {templateUrl: 'client/list.html', controller: ListCtrl}).
                when('/search/', {templateUrl: 'client/list.html', controller: ListCtrl, reloadOnSearch: true}).
                when('/view/:recipeId', {templateUrl: 'client/view.html', controller: ViewCtrl}).
                when('/add/', {templateUrl: 'client/edit.html', controller: AddCtrl}).
                when('/edit/:recipeId', {templateUrl: 'client/edit.html', controller: EditCtrl}).
                when('/profile/', {templateUrl: 'client/profile.html', controller: ProfileCtrl});
        //otherwise({redirectTo: '/phones'});
    }]).
        run(function($rootScope, $templateCache) {
    $rootScope.$on('$viewContentLoaded', function() {
        $templateCache.removeAll();
    });
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
function PageCtrl($scope, $recipeServer) {
    $scope.loggedIn = false;
    $scope.user = {};
    $('#loginAlert').html('');
    // Test if logged in
    $recipeServer.amLoggedIn(function(loggedIn) {
        $scope.loggedIn = loggedIn;
    });
    $scope.signin = function() {
        $("#loginBox").removeClass("hide");
    };
    $scope.signinSubmit = function() {
        if ($scope.user.username === undefined)
            $scope.user.username = $('#user').val();
        if ($scope.user.password === undefined)
            $scope.user.password = $('#pass').val();
        $('#loginAlert').html('');
        $recipeServer.login($scope.user.username, $scope.user.password, function(data) {
            if (data.loggedIn == true) {
                $scope.loggedIn = true;
                $("#loginBox").addClass("hide");
                $('#loginAlert').html('');
            } else {
                $('#loginAlert').html('<div class="alert alert-danger"><a class="close" data-dismiss="alert">&times;</a><span>' + data.message + '</span></div>');
            }
        });
    };
    $scope.signinCancel = function() {
        $("#loginBox").addClass("hide");
        $scope.user = {};
    };
    $scope.signout = function() {
        $recipeServer.logout(function(data) {
            $scope.loggedIn = data;
        });
    };
}

function ListCtrl($scope, $recipeServer, $routeParams, $location) {
    $scope.search = $routeParams.search;
    $("#nav-bar-menu li").removeClass("active");
    $("#nav-search").addClass("active");
    $scope.recipes = [];
    $recipeServer.list(function(data) {
        $scope.recipes = data.recipes;
    });
    $scope.tagSearch = function(tag) {
        $location.search("search=" + tag);
    };
    $scope.clearSearch = function(tag) {
        $location.search("");
        $scope.search = "";
    };
}

function ViewCtrl($scope, $recipeServer, $routeParams, $location) {
    $("#nav-bar-menu li").removeClass("active");
    $scope.recipeId = $routeParams.recipeId;
    $scope.recipe = {};
    $scope.recipe.Visibility = "public";
    $recipeServer.view($scope.recipeId,
            function(recipe) {
                $scope.recipe = recipe;
                $scope.recipe.Visibility = $scope.recipe.Visibility.split(" ").join("");
                $scope.recipe.Title = $scope.recipe.Title.split("\n").join("<br/>");
                $scope.recipe.Description = $scope.recipe.Description.split("\n");
                $scope.recipe.Ingredients = $scope.recipe.Ingredients.split("\n");
                $scope.recipe.Method = $scope.recipe.Method.split("\n");
                $scope.recipe.Notes = $scope.recipe.Notes.split("\n");
                // TODO: check for controls
            },
            function(message) {
                $("#myModal .modal-title").html("Error");
                $("#myModal div.modal-body").html(message);
                $('#myModal').on('hide.bs.modal', function() {
                    $location.path("/");
                });
                $("#myModal").modal();
            });
    $scope.tagSearch = function(tag) {
        $location.path("/search/").search("search=" + tag);
    };
    $scope.edit = function() {
        $location.path("/edit/" + $scope.recipeId);
    };
}

function AddCtrl($scope, $recipeServer, $location) {
    $("#nav-bar-menu li").removeClass("active");
    $("#nav-add").addClass("active");
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
        $recipeServer.save($scope.recipe, function (data){
            $location.path("/view/" + data['recipeId']);
        });
    };
    $scope.cancel = function() {
//TODO: check form for changes and ask before continuing.

        $location.path("/search/");
    };
}

function EditCtrl($scope, $recipeServer, $routeParams, $location) {
    $("#nav-bar-menu li").removeClass("active");
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
                $("#myModal .modal-title").html("Error");
                $("#myModal div.modal-body").html(message);
                $('#myModal').on('hide.bs.modal', function() {
                    $location.path("/");
                });
                $("#myModal").modal();
            });
    $scope.save = function() {

    };
    $scope.cancel = function() {
//TODO: check form for changes and ask before continuing.

        $location.path("/view/" + $scope.recipeId);
    };
}

function ProfileCtrl($scope, $recipeServer) {
    $("#nav-bar-menu li").removeClass("active");
    $("#nav-profile").addClass("active");
    $scope.profile = {};
    $scope.save = function() {
        $("#msgHolder").html("");
        var password = $scope.profile.password;
        var newPassword = $scope.profile.newPassword;
        var confNewPassword = $scope.profile.confNewPassword;
        $recipeServer.updatePassword(password, newPassword, confNewPassword,
                function(message) {
                    $("#msgHolder").html('<div class="alert alert-success"><a class="close" data-dismiss="alert">&times;</a><span>' + message + '</span></div>');
                },
                function(message) {
                    $("#msgHolder").html('<div class="alert alert-danger"><a class="close" data-dismiss="alert">&times;</a><span>' + message + '</span></div>');
                });
    };
}