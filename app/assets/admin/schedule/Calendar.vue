<template>
    <div class='calendar-app'>
        <div class="notifications">
            <div class="spinner pull-left" v-show="loading > 0">
                <span class="fa fa-spinner fa-pulse"></span>
            </div>

            <div v-if="message" class="alert pull-left ml-2" :class="'alert-' + message.type">
                {{ message.text }}
            </div>
        </div>

        <div id="program-modal" class="modal fade">
            <div class="modal-dialog">
                <div class="modal-content" v-if="selectedEvent">
                    <div class="modal-header">
                        <h5 class="modal-title">Nastavení programu</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group row">
                            <div class="col-3">Název</div>
                            <div class="col-9">
                                <a :href="'../blocks/detail/' + selectedEvent.event.extendedProps.block.id" target="_blank">
                                    {{ selectedEvent.event.extendedProps.block.name }}
                                </a>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-3">Kategorie</div>
                            <div class="col-9">{{ selectedEvent.event.extendedProps.block.category }}</div>
                        </div>
                        <div class="form-group row">
                            <div class="col-3">Lektoři</div>
                            <div class="col-9">{{ selectedEvent.event.extendedProps.block.lectorsNames }}</div>
                        </div>

                        <form class="form-horizontal">
                            <div class="form-group row mb-1">
                                <div class="col-3 col-form-label">
                                    <label for="select-room">Místnost</label>
                                </div>
                                <div class="col-9">
                                    <select id="select-room" class="form-control" v-model="selectedEvent.resource">
                                        <option v-for="resource in resources" :value="resource.id">{{ resource.title }}</option>
                                    </select>
                                    <span v-if="(getResourceById(selectedEvent.resourceId).extendedProps.capacity || Number.MAX_VALUE) < (selectedEvent.event.extendedProps.block.capacity || 0)" class="text-warning">
                                        <span class="fa fa-exclamation-triangle"></span>
                                        Kapacita místnosti je menší než kapacita bloku.
                                    </span>
                                </div>
                            </div>
                        </form>

                        <div class="form-group row">
                            <div class="col-3">Kapacita</div>
                            <div class="col-9">{{ selectedEvent.event.extendedProps.block.capacity }}</div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button @click="handleEventUpdateRoom()" class="btn btn-primary pull-left">Uložit</button>
                        <button @click="handleEventRemove()" class="btn btn-danger pull-right">Odstranit</button>
                    </div>
                </div>
            </div>
        </div>

        <FullCalendar class='demo-app-calendar'
                      ref="fullCalendar"
                      theme-system="bootstrap"
                      locale="cs"
                      timeZone="none"
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
                      @eventDrop="handleEventDrop"
                      @eventClick="handleEventClick"/>
    </div>
</template>

<script>
    'use strict';

    import FullCalendar from '@fullcalendar/vue'
    import timeGridPlugin from '@fullcalendar/timegrid'
    import resourceTimelinePlugin from '@fullcalendar/resource-timeline'
    import interactionPlugin from '@fullcalendar/interaction'
    import bootstrapPlugin from '@fullcalendar/bootstrap'
    import { mapState, mapGetters, mapActions } from 'vuex'

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
                defaultView: localStorage.getItem("fcDefaultView") || "timeGridSeminar",
                selectedEvent: null
            }
        },
        computed: {
            ...mapState(['events', 'resources', 'config', 'loading']),
            ...mapGetters(['getResourceById']),
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
                    },
                };
            }
        },
        methods: {
            ...mapActions(['loadData', 'addProgram', 'updateProgram', 'updateProgramRoom', 'removeProgram']),
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
                this.addProgram(info);
            },
            handleEventDrop(info) {
                this.updateProgram(info);
            },
            handleEventClick(info) {
                this.selectedEvent = {
                    event: info.event,
                    resourceId: info.event.getResources()[0].id
                };
                $('#program-modal').modal('show');
            },
            handleEventUpdateRoom() {
                $('#program-modal').modal('hide');
                this.updateProgramRoom(this.selectedEvent);
            },
            handleEventRemove() {
                $('#program-modal').modal('hide');
                this.removeProgram(this.selectedEvent);
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