<template>
    <div class='calendar-app'>
        <div id="program-modal" class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content" v-if="selectedEventInfo">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ selectedEventInfo.event.extendedProps.block.name }}</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <ul class="list-unstyled">
                            <li ng-if="event.block.category">
                                <strong>Kategorie: </strong>
                                {{ selectedEventInfo.event.extendedProps.block.category }}
                            </li>
                            <li ng-if="event.room">
                                <strong>Místnost: </strong>
                                {{ selectedEventInfo.event.getResources().length > 0 ? selectedEventInfo.event.getResources()[0].title : ''}}
                            </li>
                            <li>
                                <strong>Obsazenost: </strong>
                                {{ selectedEventInfo.event.extendedProps.block.capacity
                                ? selectedEventInfo.event.extendedProps.attendeesCount + '/' + selectedEventInfo.event.extendedProps.block.capacity
                                : selectedEventInfo.event.extendedProps.attendeesCount
                                }}
                            </li>
                        </ul>

                        <div ng-if="event.block.lectors">
                            <h6>Lektoři</h6>
                            <div v-for="lector in selectedEventInfo.event.extendedProps.block.lectors">
                                <b>{{ lector.name }}</b>
                                <p>
                                    <img :src="basePath + '/files/user_photos/' + lector.photo" v-if="lector.photo" class="pull-left mr-3 mb-3"/>
                                    {{ lector.about }}
                                </p>
                                <div class="clearfix"></div>
                            </div>
                        </div>

                        <h6>Popis</h6>
                        <p>{{ selectedEventInfo.event.extendedProps.block.perex }}</p>
                        <div v-html="selectedEventInfo.event.extendedProps.block.description"></div>
                    </div>

                    <div class="modal-footer" v-show="userAllowedRegisterPrograms">
                        <button @click="handleAttendEvent()" v-show="!selectedEventInfo.event.extendedProps.userAttends"
                                :disabled="selectedEventInfo.event.extendedProps.blocked || selectedEventInfo.event.extendedProps.occupied || !selectedEventInfo.event.extendedProps.paid"
                                class="btn btn-sm btn-success pull-left">
                            Přihlásit se na program
                        </button>
                        <button @click="handleUnattendEvent()" v-show="selectedEventInfo.event.extendedProps.userAttends"
                                :disabled="selectedEventInfo.event.extendedProps.block.autoRegistered"
                                class="btn btn-sm btn-danger pull-left">
                            Odhlásit se z programu
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="help-modal" class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Nápověda</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <p>Kliknutím na program se zobrazí možnost přihlášení/odhlášení a další informace o programu.</p>

                        <h6>Typy programů</h6>
                        <ul class="list-group text-white">
                            <li class="list-group-item bg-success">Programy, které máte přihlášené.</li>
                            <li class="list-group-item bg-primary">Programy, na které se lze přihlásit.</li>
                            <li class="list-group-item bg-danger">Programy, které jsou povinné.</li>
                            <li class="list-group-item bg-secondary">Programy, na které se nelze přihlásit (máte přihlášený stejný program, je obsazený nebo jej nemáte zaplacený).</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-warning alert-forever" v-show="notRegisteredMandatoryPrograms > 0">
            Máte nepřihlášené povinné programy (v kalendáři červeně). Prosíme, zapište si je.
        </div>

        <div class="row">
            <div class="col-auto mr-auto">
                <div class="btn-group">
                    <button @click="handleChangeView('timeGridSeminar')" class="btn btn-sm btn-secondary" :class="{ active: defaultView === 'timeGridSeminar' }">
                        Na výšku
                    </button>
                    <button @click="handleChangeView('resourceTimelineSeminar')" class="btn btn-sm btn-secondary" :class="{ active: defaultView === 'resourceTimelineSeminar' }">
                        Na šířku
                    </button>
                </div>
                <div class="btn-group" v-show="defaultView === 'resourceTimelineSeminar'">
                    <button @click="handlePrev()" id="btnPrev" class="btn btn-sm btn-secondary">
                        <span class="fc-icon fc-icon-chevron-left"></span>
                    </button>
                    <button id="btnTitle" class="btn btn-sm btn-secondary" disabled></button>
                    <button @click="handleNext()" id="btnNext" class="btn btn-sm btn-secondary">
                        <span class="fc-icon fc-icon-chevron-right"></span>
                    </button>
                </div>
                <button data-toggle="modal" data-target="#help-modal" class="btn btn-sm btn-secondary">
                    <i class="fa fa-question-circle"></i> Nápověda
                </button>
            </div>
            <div class="col-auto">
                <div class="spinner float-left" v-show="loading > 0">
                    <span class="fa fa-spinner fa-pulse fa-2x" style=""></span>
                </div>
                <div class="notifications float-left ml-2 mt-2 mt-sm-0">
                    <div v-if="message" class="alert" :class="'alert-' + message.type" style="padding: 4px 8px; margin: 0">
                        {{ message.text }}
                    </div>
                </div>
            </div>
        </div>

        <FullCalendar id="calendar"
                      style="visibility: hidden"
                      ref="fullCalendar"
                      theme-system="bootstrap"
                      locale="cs"
                      timeZone="none"
                      aspect-ratio="1.85"
                      header="false"
                      scheduler-license-key="GPL-My-Project-Is-Open-Source"
                      :plugins="calendarPlugins"
                      :views="calendarViews"
                      :default-view="defaultView"
                      :valid-range="validRange"
                      :min-time="minTime"
                      :max-time="maxTime"
                      :event-render="eventRender"
                      :view-skeleton-render="viewSkeletonRender"
                      :dates-render="datesRender"
                      :events="events"
                      :resources="resources"
                      @eventClick="handleEventClick"/>
    </div>
</template>

<script>
    'use strict';

    import FullCalendar from '@fullcalendar/vue'
    import timeGridPlugin from '@fullcalendar/timegrid'
    import resourceTimelinePlugin from '@fullcalendar/resource-timeline'
    import bootstrapPlugin from '@fullcalendar/bootstrap'
    import { mapState, mapActions } from 'vuex'

    export default {
        components: {
            FullCalendar
        },
        data: function () {
            return {
                calendarPlugins: [
                    timeGridPlugin,
                    resourceTimelinePlugin,
                    bootstrapPlugin
                ],
                defaultView: localStorage.getItem("fcDefaultView") || "timeGridSeminar",
                selectedEventInfo: null,
                userAllowedRegisterPrograms: userAllowedRegisterPrograms
            }
        },
        computed: {
            ...mapState(['events', 'resources', 'config', 'loading', 'message', 'notRegisteredMandatoryPrograms']),
            calendarViews() {
                return {
                    timeGridSeminar: {
                        type: 'timeGrid',
                        visibleRange: {
                            start: this.config.seminar_from_date,
                            end: this.config.seminar_to_date
                        },
                        buttonText: "Na výšku",
                        allDaySlot: false,
                        snapDuration: {minutes: 5},
                        slotDuration: {minutes: 15},
                        slotLabelInterval: {hours: 1}
                    },
                    resourceTimelineSeminar: {
                        type: 'resourceTimeline',
                        buttonText: "Na šířku",
                        snapDuration: {minutes: 5},
                        slotDuration: {minutes: 15},
                        slotLabelInterval: {hours: 1},
                        resourceLabelText: "Místnosti"
                    },
                };
            },
            validRange() {
                return {
                    start: this.config.seminar_from_date,
                    end: this.config.seminar_to_date
                }
            },
            minTime() {
                return {
                    minutes: this.config.min_time * 60
                };
            },
            maxTime() {
                return {
                    minutes: this.config.max_time * 60
                };
            }
        },
        watch: {
            message: function () {
                $('.notifications').show().animate({
                    opacity: 1.0
                }, ALERT_DURATION).slideUp(1000);
            },
            config: function () {
                $('#calendar').css('visibility', 'visible');
            }
        },
        methods: {
            ...mapActions(['loadData', 'attendProgram', 'unattendProgram']),

            /**
             * Vykreslí název místnosti v timeGridSeminar zobrazení.
             */
            eventRender(info) {
                if (info.view.type === 'timeGridSeminar') {
                    const eventResources = info.event.getResources();
                    if (eventResources.length > 0 && eventResources[0].id > 0) {
                        const titleEl = info.el.getElementsByClassName('fc-title')[0];
                        const roomEl = document.createElement('div');
                        roomEl.setAttribute('class', 'fc-room');
                        roomEl.innerText = eventResources[0].title;
                        titleEl.parentNode.insertBefore(roomEl, titleEl.nextSibling);
                    }
                }
                if (info.event.extendedProps.block.perex) {
                    $(info.el).tooltip({
                        title: info.event.extendedProps.block.perex,
                        placement: 'bottom',
                        trigger: 'hover',
                        container: 'body'
                    });
                }
            },

            /**
             * Zpracuje přepnutí view.
             */
            handleChangeView(view) {
                this.defaultView = view;
                this.$refs.fullCalendar.getApi().changeView(view);
            },

            /**
             * Uloží zvolené view.
             */
            viewSkeletonRender(info) {
                localStorage.setItem("fcDefaultView", info.view.type);
            },

            /**
             * Zpracuje přechod na přechozí den.
             */
            handlePrev(view) {
                this.$refs.fullCalendar.getApi().prev();
            },

            /**
             * Zpracuje přechod na následující den.
             */
            handleNext(view) {
                this.$refs.fullCalendar.getApi().next();
            },

            /**
             * Překreslí tlačítka pro volbu dne.
             */
            datesRender(info) {
                $("#btnPrev").prop("disabled", info.view.currentStart.toISOString().split('T')[0] <= this.validRange.start);
                $("#btnNext").prop("disabled", info.view.currentEnd.toISOString().split('T')[0] >= this.validRange.end);
                $("#btnTitle").html(info.view.title);
            },

            /**
             * Zpracuje kliknutí na událost (otevře modal okno).
             */
            handleEventClick(info) {
                this.selectedEventInfo = {
                    event: info.event,
                    resourceId: info.event.getResources()[0].id
                };
                $('#program-modal').modal('show');
            },

            /**
             * Zpracuje přihlášení na program.
             */
            handleAttendEvent() {
                $('#program-modal').modal('hide');
                $('.fc-event').tooltip('dispose');
                this.attendProgram(this.selectedEventInfo);
            },

            /**
             * Zpracuje odhlášení z programu.
             */
            handleUnattendEvent() {
                $('#program-modal').modal('hide');
                $('.fc-event').tooltip('dispose');
                this.unattendProgram(this.selectedEventInfo);
            }
        },
        created: function () {
            this.loadData();
        }
    }
</script>

<style lang='scss'>
    @import '~@fullcalendar/core/main.css';
    @import '~@fullcalendar/daygrid/main.css';
    @import '~@fullcalendar/timegrid/main.css';
    @import '~@fullcalendar/timeline/main.css';
    @import '~@fullcalendar/resource-timeline/main.css';
    @import '~@fullcalendar/bootstrap/main.css';

    .fc-today {
        background-color: inherit !important;
    }
    .fc-title {
        font-weight: bold;
    }
    .fc-room {
        padding: 0 1px;
    }
</style>