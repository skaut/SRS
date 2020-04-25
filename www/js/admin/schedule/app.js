var apiPath = basePath + '/api/schedule/';

    /**
     * Pridani programu pretazenim ze seznamu bloku.
     *
     * @param event
     */
    $scope.addEvent = function (event) {
        $scope.event = event;

        var programSaveDTO = {
            block_id: event.block.id,
            start: event.start.format()
        };
        var json = encodeURIComponent(JSON.stringify(programSaveDTO));

        $scope.loading++;
        $http.post(apiPath + 'saveprogram?data=' + json)
            .then(function (response) {
                if (response.data.status == 'success') {
                    $scope.event.id = response.data.program.id;
                    $scope.event.block.programs_count++;
                }
                else {
                    $('#calendar').fullCalendar('removeEvents', [$scope.event._id]);
                }
                $scope.flashMessage(response.data.message, response.data.status);
            }, function (response) {
                $('#calendar').fullCalendar('removeEvents', [$scope.event._id]);
                $scope.flashMessage('Program se nepodařilo uložit.', 'danger');
            }).finally(function () {
            $scope.loading--;
        });
    };


    /**
     * Presunuti programu na jiny cas.
     *
     * @param event
     * @param revertFunc
     */
    $scope.moveEvent = function (event, revertFunc) {
        $scope.event = event;

        event.start.stripZone();

        var programSaveDTO = {
            id: event.id,
            block_id: event.block.id,
            room_id: event.room ? event.room.id : null,
            start: event.start.format()
        };
        var json = encodeURIComponent(JSON.stringify(programSaveDTO));

        $scope.loading++;
        $http.post(apiPath + 'saveprogram?data=' + json)
            .then(function (response) {
                if (response.data.status == 'success') {
                }
                else {
                    revertFunc()
                }
                $scope.flashMessage(response.data.message, response.data.status);
            }, function (response) {
                $scope.flashMessage('Program se nepodařilo uložit.', 'danger');
            }).finally(function () {
            $scope.loading--;
        });
    };


    /**
     * Uprava programu - zmena mistnosti.
     *
     * @param event
     */
    $scope.updateEvent = function (event) {
        $('#program-modal').modal('hide');

        $scope.event = event;

        event.start.stripZone();

        var programSaveDTO = {
            id: event.id,
            block_id: event.block.id,
            room_id: event.room ? event.room.id : null,
            start: event.start.format()
        };
        var json = encodeURIComponent(JSON.stringify(programSaveDTO));

        $scope.loading++;
        $http.post(apiPath + 'saveprogram?data=' + json)
            .then(function (response) {
                if (response.data.status == 'success') {
                    setTitle($scope.event);
                    $('#calendar').fullCalendar('rerenderEvents');
                }
                else {
                    $scope.event.room = $scope.event._room;
                }
                $scope.flashMessage(response.data.message, response.data.status);
            }, function (response) {
                $scope.flashMessage('Program se nepodařilo uložit.', 'danger');
            }).finally(function () {
            $scope.loading--;
        });
    };


    /**
     * Odstraneni programu.
     *
     * @param event
     */
    $scope.removeEvent = function (event) {
        $('#program-modal').modal('hide');

        $scope.event = event;

        $scope.loading++;
        $http.post(apiPath + 'removeprogram/' + event.id)
            .then(function (response) {
                if (response.data.status == 'success') {
                    $scope.event.block.programs_count--;
                    $('#calendar').fullCalendar('removeEvents', [$scope.event._id]);
                }
                $scope.flashMessage(response.data.message, response.data.status);
            }, function (response) {
                $scope.flashMessage('Program se nepodařilo odstranit.', 'danger');
            }).finally(function () {
            $scope.loading--;
        });
    };


    $scope.flashMessage = function (text, type) {
        $scope.message.text = text;
        $scope.message.type = type ? type : 'info';

        $('.notifications .alert').show().animate({
            opacity: 1.0
        }, ALERT_DURATION).slideUp(1000);
    };


    $scope.uiConfig = {
        calendar: {
            lang: 'cs',
            timezone: false,
            defaultView: 'seminar',
            aspectRatio: 1.6,
            header: false,
            eventDurationEditable: false,
            views: {
                seminar: {
                    type: 'agenda',
                    buttonText: 'Seminář',
                    allDaySlot: false,
                    duration: {days: 7},
                    slotDuration: '00:15:00',
                    slotLabelInterval: '01:00:00',
                    snapDuration: '00:05:00'
                }
            },
            eventTextColor: '#fff',
            eventClick: function (event, element) {
                if ($scope.loading == 0 && $scope.config.allowed_modify_schedule) {
                    $scope.event = event;
                    $scope.event._room = $scope.event.room;
                    $('#program-modal').modal('show');
                }
            },
            eventDrop: function (event, delta, revertFunc) {
                if ($scope.loading == 0)
                    $scope.moveEvent(event, revertFunc);
                else
                    revertFunc();
            },
            eventReceive: function (event) {
                if ($scope.loading == 0)
                    $scope.addEvent(event);
                else
                    $('#calendar').fullCalendar('removeEvents', [event._id]);
            }
        }
    };

    $scope.eventSources = [$scope.events];
});
