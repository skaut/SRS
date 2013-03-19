/**
 * Created with JetBrains PhpStorm.
 * User: Michal
 * Date: 3.2.13
 * Time: 15:55
 * Author: Michal Májský
 */

app.directive("externalBlock", function ($parse) {
    return {
        restrict: 'A',

        link: function (scope, element, attrs) {
            attrs.$observe('externalBlock', function(id) {
                prepareExternalBlock(scope.options[id], element);
            });

        }
    };

});