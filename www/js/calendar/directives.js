/**
 * Created with JetBrains PhpStorm.
 * User: Michal
 * Date: 3.2.13
 * Time: 15:55
 * To change this template use File | Settings | File Templates.
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