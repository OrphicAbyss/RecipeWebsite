recipe = angular.module('recipe', []).
        config(['$routeProvider', function($routeProvider) {
        $routeProvider.
                when('/search/', {templateUrl: 'client/list.html', controller: ListCtrl}).
                when('/add/', {templateUrl: 'client/edit.html', controller: ListCtrl}).
                when('/edit/', {templateUrl: 'client/edit.html', controller: ListCtrl}).
                when('/profile/', {templateUrl: 'client/profile.html', controller: ProfileCtrl});
        //when('/phones/:phoneId', {templateUrl: 'partials/phone-detail.html', controller: PhoneDetailCtrl}).
        //otherwise({redirectTo: '/phones'});
    }]).
        run(function($rootScope, $templateCache) {
    $rootScope.$on('$viewContentLoaded', function() {
        $templateCache.removeAll();
    });
});

function ListCtrl($scope, $http) {
    $scope.recipes = [];
    data = $.param({cmd: "search"});
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

function ProfileCtrl($scope, $http) {
    
}