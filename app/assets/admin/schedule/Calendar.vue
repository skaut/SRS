<template>
    <div class='calendar-app'>
        <span class="fa fa-spinner fa-pulse" v-show="loading > 0"></span>
        <FullCalendar class='demo-app-calendar'
                      ref="fullCalendar"
                      theme-system="bootstrap"
                      locale="cs"
                      aspect-ratio="1.6"
                      scheduler-license-key="GPL-My-Project-Is-Open-Source"
                      :plugins="calendarPlugins"
                      :views="calendarViews"
                      :default-view="defaultView"
                      :event-render="handleEventRender"
                      :view-skeleton-render="handleChangeView"
                      :header="{left: 'timeGridSeminar,resourceTimelineSeminar', center: '', right: ''}"
                      :events="events"
                      :resources="resources"
                      :droppable="config.allowed_modify_schedule"
                      :event-start-editable="config.allowed_modify_schedule"
                      :event-resource-editable="config.allowed_modify_schedule"
                      :event-overlap="handleEventOverlap"
                      @eventReceive="handleEventReceive"
                      @eventDrop="handleEventDrop"/>
    </div>
</template>

<script>
    'use strict';

    import FullCalendar from '@fullcalendar/vue'
    import timeGridPlugin from '@fullcalendar/timegrid'
    import resourceTimelinePlugin from '@fullcalendar/resource-timeline'
    import interactionPlugin from '@fullcalendar/interaction'
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
                    interactionPlugin,
                    bootstrapPlugin
                ],
                defaultView: localStorage.getItem("fcDefaultView") || "timeGridSeminar"
            }
        },
        computed: {
            ...mapState(['events', 'resources', 'config', 'loading']),
            calendarViews: function views() {
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
                        visibleRange: {
                            start: this.config.seminar_from_date,
                            end: this.config.seminar_to_date
                        },
                        buttonText: "Na šířku",
                        snapDuration: {minutes: 5},
                        slotDuration: {minutes: 15},
                        slotLabelInterval: {hours: 1},
                        resourceLabelText: "Místnosti"
                    }
                };
            }
        },
        methods: {
            ...mapActions(['loadData', 'addProgram', 'updateProgram', 'removeProgram']),
            handleChangeView(info) {
                localStorage.setItem("fcDefaultView", info.view.type);
            },
            handleEventRender(info) {
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
            },
            handleEventOverlap(stillEvent, movingEvent) {
                return !stillEvent.extendedProps.block.autoRegistered && !movingEvent.extendedProps.block.autoRegistered;
            },
            handleEventReceive(info) {
                this.addProgram(info.event);
                info.event.extendedProps.block.programsCount++;
            },
            handleEventDrop(eventDropInfo) {
                if (eventDropInfo.newResource) {
                    // eventDropInfo.event.title = "afsafsdf";
                    // eventDropInfo.event.extendedProps.room = eventDropInfo.newResource.extendedProps.room;
                }
            },
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