recipe = angular.module('recipe', []).
        config(['$routeProvider', function($routeProvider) {
        $routeProvider.
                when('/', {templateUrl: 'client/list.html', controller: ListCtrl}).
                when('/search/', {templateUrl: 'client/list.html', controller: ListCtrl}).
                when('/view/:recipeId', {templateUrl: 'client/view.html', controller: ViewCtrl}).
                when('/add/', {templateUrl: 'client/edit.html', controller: AddCtrl}).
                when('/edit/', {templateUrl: 'client/edit.html', controller: EditCtrl}).
                when('/profile/', {templateUrl: 'client/profile.html', controller: ProfileCtrl});
        //otherwise({redirectTo: '/phones'});
    }]).
        run(function($rootScope, $templateCache) {
    $rootScope.$on('$viewContentLoaded', function() {
        $templateCache.removeAll();
    });
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
            $('#myModal').on('hide.bs.modal', function () {
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

}

function EditCtrl($scope, $http) {

}

function ProfileCtrl($scope, $http) {

}