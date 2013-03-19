/**
 * Created with JetBrains PhpStorm.
 * User: Michal
 * Date: 18.2.13
 * Time: 20:33
 * Author: Michal Májský
 */

function userCtrl($scope, $http) {
    var api_path = basePath + '/admin/evidence/';
    $http.post(api_path+"getattendees", {})
        .success(function(data, status, headers, config) {
//            angular.forEach(data, function(user, key) {
//            user.url.replace('\\', '');
//            });
            $scope.users = data;
        }).error(function(data, status, headers, config) {
            $scope.status = status;
        });
}