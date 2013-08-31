recipe = angular.module('recipe', []).
        config(['$routeProvider', function($routeProvider) {
        $routeProvider.
                when('/', {templateUrl: 'client/list.html', controller: ListCtrl}).
                when('/search/', {templateUrl: 'client/list.html', controller: ListCtrl}).
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
recipe.directive('tagInput', function() {
    return {
        restrict: 'A',
        link: function(scope, element, attrs) {
            scope.inputWidth = 20;
            // Watch for changes in text field
            scope.$watch(attrs.ngModel, function(value) {
//                if (value != undefined) {
//                    var tempEl = $('<span>' + value + '</span>').appendTo('body');
//                    scope.inputWidth = tempEl.width() + 5;
//                    tempEl.remove();
//                }
            });
            element.bind('keydown', function(e) {
                if (e.which == 9) {
                    e.preventDefault();
                }

                if (e.which == 8 && scope.$apply(attrs.ngModel).length == 0) {
                    scope.$apply(attrs.deleteTag)();
                }
                scope.$apply();
            });
            element.bind('keyup', function(e) {
                var key = e.which;
                // Tab or Enter pressed 
                if (key == 9 || key == 13) {
                    e.preventDefault();
                    scope.$apply(attrs.newTag)(scope.$apply(attrs.ngModel));
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

function PageCtrl($scope, $http) {
    $scope.loggedIn = false;
    $scope.login = {};
    $('#loginAlert').html('');
    // Test if logged in
    $http({method: 'POST',
        url: 'serverside/ajaxResponder.php',
        data: 'Cmd=amLoggedIn',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}
    }).
            success(function(data, status, headers, config) {
        $scope.loggedIn = data.loggedIn;
    }).
            error(function(data, status, headers, config) {
        console.log("Error: " + data);
    });
    $scope.signin = function() {
        $("#loginBox").removeClass("hide");
    };
    $scope.signinSubmit = function() {
        $('#loginAlert').html('');
        $http({method: 'POST',
            url: 'serverside/ajaxResponder.php',
            data: 'Cmd=Login&user=' + $scope.username + '&pass=' + $scope.password,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).
                success(function(data, status, headers, config) {
            if (data.loggedIn == true) {
                $scope.loggedIn = true;
                $scope.login = {};
                $("#loginBox").addClass("hide");
                $('#loginAlert').html('');
            } else {
                $('#loginAlert').html('<div class="alert alert-danger"><a class="close" data-dismiss="alert">&times;</a><span>' + data.Message + '</span></div>');
            }
        }).
                error(function(data, status, headers, config) {
            $("#loginError");
        });
    };
    $scope.signinCancel = function() {
        $("#loginBox").addClass("hide");
        $scope.login = {};
    };
    $scope.signout = function() {
        $http({method: 'POST',
            url: 'serverside/ajaxResponder.php',
            data: 'Cmd=Logout',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        }).
                success(function(data, status, headers, config) {
            if (data.loggedIn == false) {
                $scope.loggedIn = false;
            }
        }).
                error(function(data, status, headers, config) {
            alert("Error: " + data);
        });
    };
}

function ListCtrl($scope, $http) {
    $scope.recipes = [];
    $http({method: 'POST',
        url: 'serverside/ajaxResponder.php',
        data: 'Cmd=search',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}}
    ).
            success(function(data, status, headers, config) {
// this callback will be called asynchronously
// when the response is available
        $scope.recipes = data.Data.Recipes;
    }).
            error(function(data, status, headers, config) {
// called asynchronously if an error occurs
// or server returns response with an error status.
        alert("Error: " + data);
    });
}

function ViewCtrl($scope, $http, $routeParams, $location) {
    $scope.recipeId = $routeParams.recipeId;
    $scope.recipe = {};
    $scope.recipe.Visibility = "public";
    $http({method: 'POST',
        url: 'serverside/ajaxResponder.php',
        data: 'Cmd=view&ID=' + $scope.recipeId,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}}
    ).
            success(function(data, status, headers, config) {
// this callback will be called asynchronously
// when the response is available
        if (!data.Error) {
            $scope.recipe = data.Recipe;
            $scope.recipe.Visibility = $scope.recipe.Visibility.split(" ").join("");
            $scope.recipe.Title = $scope.recipe.Title.split("\n").join("<br/>");
            $scope.recipe.Description = $scope.recipe.Description.split("\n");
            $scope.recipe.Ingredients = $scope.recipe.Ingredients.split("\n");
            $scope.recipe.Method = $scope.recipe.Method.split("\n");
            $scope.recipe.Notes = $scope.recipe.Notes.split("\n");
        } else {
            $("#myModal .modal-title").html("Error");
            $("#myModal div.modal-body").html(data.Message);
            $('#myModal').on('hide.bs.modal', function() {
                $location.path("/");
            });
            $("#myModal").modal();
        }
    }).
            error(function(data, status, headers, config) {
// called asynchronously if an error occurs
// or server returns response with an error status.
        alert("Error: " + data);
    });
}

function AddCtrl($scope, $http) {
    $scope.recipe = {};
}

function EditCtrl($scope, $http, $routeParams, $location) {
    $scope.recipeId = $routeParams.recipeId;
    $scope.recipe = {};
    $scope.tagText = "";
    $scope.addTag = function(tag) {
// check for empty string
        if (tag.length == 0) {
            return;
        }
// add our string
        $scope.recipe.Tags.push(tag);
        $scope.tagText = "";
    }

    $scope.deleteTag = function(tag) {
        if (tag === undefined) {
            $scope.recipe.Tags.splice($scope.recipe.Tags.length - 1, 1);
        } else {
            $scope.recipe.Tags.splice(tag, 1);
        }
    };
    $http({method: 'POST',
        url: 'serverside/ajaxResponder.php',
        data: 'Cmd=edit&ID=' + $scope.recipeId,
        headers: {'Content-Type': 'application/x-www-form-urlencoded'}}
    ).
            success(function(data, status, headers, config) {
// this callback will be called asynchronously
// when the response is available
        if (!data.Error) {
            $scope.recipe = data.Recipe;
            var tags = $scope.recipe.Tags;
            for (var i = 0; i < tags.length; i++) {
                $scope.recipe.Tags[i] = tags[i].Tag;
            }
        } else {
            $("#myModal .modal-title").html("Error");
            $("#myModal div.modal-body").html(data.Message);
            $('#myModal').on('hide.bs.modal', function() {
                $location.path("/");
            });
            $("#myModal").modal();
        }
    }).
            error(function(data, status, headers, config) {
// called asynchronously if an error occurs
// or server returns response with an error status.
        alert("Error: " + data);
    });
}

function ProfileCtrl($scope, $http) {

}