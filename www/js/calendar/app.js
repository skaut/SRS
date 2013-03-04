/**
 * Created with JetBrains PhpStorm.
 * User: Michal
 * Date: 3.2.13
 * Time: 15:51
 * To change this template use File | Settings | File Templates.
 */
var app = angular.module("calendar", []);

app.config(function ($httpProvider) {
    $httpProvider.responseInterceptors.push('myHttpInterceptor');
    var spinnerFunction = function (data, headersGetter) {
        // todo start the spinner here
        $('.ajax-loader').fadeIn();
        return data;
    };
    $httpProvider.defaults.transformRequest.push(spinnerFunction);
})
// register the interceptor as a service, intercepts ALL angular ajax http calls
    .factory('myHttpInterceptor', function ($q, $window) {
        return function (promise) {
            return promise.then(function (response) {
                // do something on success
                // todo hide the spinner
                $('.ajax-loader').fadeOut();
                return response;

            }, function (response) {
                // do something on error
                // todo hide the spinner
                $('.ajax-loader').fadeOut();
                return $q.reject(response);
            });
        };
    });

app.filter('showUnassigned', function() {
    return function(items, apply) {
        if (apply) {
        var filtered = [];
        angular.forEach(items, function(item) {
            if(item.program_count == 0) {
                filtered.push(item);
            }
        });
        return filtered;
        }
        return items;
    };
});