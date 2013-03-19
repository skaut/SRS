/**
 * Created with JetBrains PhpStorm.
 * User: Michal
 * Date: 28.1.13
 * Time: 10:03
 * Author: Michal Májský
 */
var myApp = angular.module('dialog', ['ui']);
function CalendarCtrl($scope, $http) {

    $scope.eventChanged = 0;

    //$scope.events = [];

    $http.post("./get", {})
        .success(function(data, status, headers, config) {
            $scope.events = data;
            console.log($scope.events);
        }).error(function(data, status, headers, config) {
            $scope.status = status;
        });

    $scope.addChild = function() {
        $scope.events.push({
            title: 'Click for Google ' + $scope.events.length,
            start: new Date(2013, 1, 28),
            end: new Date(2013, 1, 29)
           // url: 'http://google.com/'
        });
        $scope.events.dirty = true;

        $scope.eventChanged = $scope.eventChanged + 1;
    }

    $scope.updateEvent = function(event) {
        event.title = prompt('?');
        console.log($scope.events);
        return event;
        //$scope.devCalendar.update();
    }

    $scope.remove = function(index) {
        $scope.events.splice(index,1);
        $scope.events.dirty = true;
        $scope.eventChanged = $scope.eventChanged + 1;
    }

}

myApp.directive('devCalendar',['ui.config', '$parse', function (uiConfig,$parse) {
    uiConfig.devCalendar = uiConfig.devCalendar || {};
    //returns the calendar
    return function(scope, elm, $attrs) {
            var ngModel = $parse($attrs.ngModel);
            var editEvents = [];
            //update the calendar with the correct options
            function update() {
                //IF the calendar has options added then render them.
                var expression,
                    options = {
                        header: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'month,agendaWeek,agendaDay'
                        },
                        selectable: true,
                        selectHelper: true,
                        editable: true,
                        select: function(start, end, allDay) {

                                elm.fullCalendar('renderEvent',
                                    {
                                        title: 'Nepřiřazeno',
                                        start: start,
                                        end: end,
                                        allDay: allDay
                                    },
                                    true // make the event "stick"
                                );

                            elm.fullCalendar('unselect');
                        },



                        eventMouseover: function(event, jsEvent, view) {
                            if (view.name !== 'agendaDay') {
                                $(jsEvent.target).attr('title', event.title);
                            }
                        },

                        eventClick: function(event, element) {

                            event = scope.updateEvent(event);
                            elm.fullCalendar('updateEvent', event);
                            //update();
                            //$('#calendar').fullCalendar('updateEvent', event);

                        },

                        // Calling the events from the scope through the ng-model binding attribute.
                        events: ngModel(scope)
                    };

                if ($attrs.devCalendar) {
                    expression = scope.$eval($attrs.devCalendar);
                } else {
                    expression = {};
                }
                //Set the options from the directive's configuration
                angular.extend(options, uiConfig.devCalendar, expression);
                elm.html('').fullCalendar(options);
            }
            update();
            /*
             *
             *    This is where I get confused. Not sure why you can only watch events.length to update the scope accordingly. If events is watched The console blows to shreds and nothing happens.
             *
             *
             */
            scope.$watch( 'events.length', function( newVal, oldVal )
            {
                console.log('zmena');
                //console.log( 'model changed:', newVal, oldVal );
                update();
            }, true );

        }
    //};
}]);



//Include angular-ui dependency in resources on the side and as 'ui'
myApp.directive('inplace', function() {
    return {
        restrict: 'E',
        transclude: true,
        scope: {
            model: '=model',
            removeFn: '=onDelete',
            events: '=changed'
        },
        template: '<span>'+
            '<span class="c1" ng-hide="editorEnabled"'+
            'ng-click="enableEditor();">{{model}}</span>' +
            '<input ng-show="editorEnabled" ng-model="editModel"' +
            'ng-required ui-keypress="{13: \'unEdit()\'}"'+
            'ui-event="{\'blur\': \'unEdit()\'}"/>' +
            '</span>',
        // The linking function will add behavior to the template
        link: function(scope, element, attrs, $parent, $timeout) {
            scope.editorEnabled= false;

            scope.unEdit = function() {
                scope.model = angular.copy(scope.editModel);
                scope.editorEnabled= false;
            };

            scope.enableEditor= function() {
                scope.editModel = angular.copy(scope.model);
                scope.editorEnabled = true;
            };
        }
    }
});

