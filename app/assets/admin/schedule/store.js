'use strict';

import Vue from 'vue';
import Vuex from 'vuex';
import axios from 'axios';
import VueAxios from 'vue-axios'

const COLOR_VOLUNTARY = '#0077F7';
const COLOR_MANDATORY = '#D53343';
const COLOR_AUTO_REGISTERED = '#F7BB07';

Vue.use(Vuex);
Vue.use(VueAxios, axios);
Vue.axios.defaults.baseURL = window.location.origin + '/api/schedule/';

export default new Vuex.Store({
    state: {
        config: {
            seminar_from_date: '2000-01-01',
            seminar_to_date: '2000-01-01',
            allowed_modify_schedule: false
        },
        blocks: [],
        blocksMap: [],
        resources: [],
        resourcesMap: [],
        events: [],
        eventsMap: [],
        message: null,
        loading: 0
    },
    getters: {
        getBlockById: state => id => {
            return state.blocksMap[id];
        },
        getResourceById: state => id => {
            return state.resourcesMap[id];
        }
    },
    mutations: {
        setConfig(state, config) {
            state.config = config;
        },
        setBlocks(state, blocks) {
            state.blocks = blocks;
        },
        setBlocksMap(state, blocksMap) {
            state.blocksMap = blocksMap;
        },
        incrementProgramsCount(state, blockId) {
            state.blocksMap[blockId].programsCount++;
        },
        decrementProgramsCount(state, blockId) {
            state.blocksMap[blockId].programsCount--;
        },
        setResources(state, resources) {
            state.resources = resources;
        },
        setResourcesMap(state, resourcesMap) {
            state.resourcesMap = resourcesMap;
        },
        setEvents(state, events) {
            state.events = events;
        },
        setEventsMap(state, eventsMap) {
            state.eventsMap = eventsMap;
        },
        setEventTime(state, info) {
            const event = state.eventsMap[info.eventId];
            if (event) {
                event.start = info.start;
                event.end = info.end;
            }
        },
        setEventResource(state, info) {
            const event = state.eventsMap[info.eventId];
            if (event) {
                event.resourceId = info.resourceId;
            }
        },
        setMessage(state, message) {
            state.message = message;
        },
        incrementLoading(state) {
            state.loading++;
        },
        decrementLoading(state) {
            state.loading--;
        }
    },
    actions: {
        /**
         * Načte nastavení kalendáře, bloky, místnosti a programy.
         */
        loadData({commit}) {
            commit('incrementLoading');
            Vue.axios.get('get-calendar-config')
                .then(response => {
                    const config = JSON.parse(response.data);
                    commit('setConfig', config);
                }).catch(error => {
                    handleError(commit, error);
                }).finally(() => {
                    commit('decrementLoading');
                });

            commit('incrementLoading');
            axios.all([
                Vue.axios.get('get-blocks'),
                Vue.axios.get('get-rooms'),
                Vue.axios.get('get-programs-admin')
            ]).then(axios.spread((blocksResponse, roomsResponse, programsResponse) => {
                const blocks = Array.prototype.slice.call(JSON.parse(blocksResponse.data))
                    .map(function(block) {
                        return {
                            id: String(block.id),
                            name: block.name,
                            category: block.category,
                            lectors: block.lectors,
                            lectorsNames: block.lectors_names,
                            capacity: block.capacity,
                            duration: block.duration,
                            mandatory: block.mandatory,
                            autoRegistered: block.auto_registered,
                            programsCount: block.programs_count,
                            color: block.mandatory ? (block.auto_registered ? COLOR_AUTO_REGISTERED : COLOR_MANDATORY) : COLOR_VOLUNTARY
                        }
                    });
                commit('setBlocks', blocks);

                const blocksMap = blocks
                    .reduce(function (map, obj) {
                        map[obj.id] = obj;
                        return map;
                        }, {});
                commit('setBlocksMap', blocksMap);

                const resources = Array.prototype.slice.call(JSON.parse(roomsResponse.data))
                    .map(function(room) {
                        return {
                            id: String(room.id),
                            title: room.name + (room.capacity ? (' (' + room.capacity + ')') : ''),
                            extendedProps: {
                                capacity: room.capacity
                            }
                        }
                    });
                resources.push({
                    id: '0',
                    title: 'Nepřiřazená',
                    extendedProps: {
                        capacity: null
                    }
                });
                commit('setResources', resources);

                const resourcesMap = resources
                     .reduce(function (map, obj) {
                         map[obj.id] = obj;
                         return map;
                         }, {});
                commit('setResourcesMap', resourcesMap);

                const events = Array.prototype.slice.call(JSON.parse(programsResponse.data))
                    .map(function(program) {
                        const block = blocksMap[program.block_id];
                        return {
                            id: String(program.id),
                            resourceId: String(program.room_id || 0),
                            title: block.name,
                            start: program.start,
                            end: program.end,
                            color: block.color,
                            extendedProps: {
                                block: block
                            }
                        }
                    });
                commit('setEvents', events);

                const eventsMap = events
                    .reduce(function (map, obj) {
                        map[obj.id] = obj;
                        return map;
                    }, {});
                commit('setEventsMap', eventsMap);
            })).catch(error => {
                handleError(commit, error);
            }).finally(() => {
                commit('decrementLoading');
            });
        },

        /**
         * Zpracuje přidání programu.
         */
        addProgram({commit}, info) {
            commit('incrementLoading');
            const data = {
                block_id: info.event.extendedProps.block.id,
                room_id: info.event.resourceId && info.event.resourceId !== '0' ? info.event.resourceId : null,
                start: info.event.start.toISOString()
            };
            Vue.axios.put('save-program', data)
                .then(response => {
                    const responseObject = JSON.parse(response.data);
                    info.event.setProp('id', responseObject.program.id);
                    info.event.setExtendedProp('id', responseObject.program.id); // todo: odstranit workaround po oprave bugu (https://github.com/fullcalendar/fullcalendar/issues/4730)
                    commit('incrementProgramsCount', info.event.extendedProps.block.id);
                    commit('setMessage', {type: responseObject.status, text: responseObject.message});
                }).catch(error => {
                    info.event.remove();
                    handleError(commit, error);
                }).finally(() => {
                    commit('decrementLoading');
                });
        },

        /**
         * Zpracuje přesunutí programu.
         */
        updateProgram({commit}, info) {
            commit('incrementLoading');
            const data = {
                id: info.event.id !== '' ? info.event.id : info.event.extendedProps.id, // todo: odstranit workaround po oprave bugu (https://github.com/fullcalendar/fullcalendar/issues/4730)
                block_id: info.event.extendedProps.block.id,
                room_id: info.event.getResources()[0].id !== '0' ? info.event.getResources()[0].id : null,
                start: info.event.start.toISOString()
            };
            Vue.axios.put('save-program', data)
                .then(response => {
                    const responseObject = JSON.parse(response.data);
                    commit('setEventTime', {eventId: info.event.id, start: responseObject.program.start, end: responseObject.program.end});
                    commit('setEventResource', {eventId: info.event.id, resourceId: String(responseObject.program.room_id || 0)});
                    commit('setMessage', {type: responseObject.status, text: responseObject.message});
                })
                .catch(error => {
                    info.revert();
                    handleError(commit, error);
                }).finally(() => {
                    commit('decrementLoading');
                });
        },

        /**
         * Zpracuje změnu místnosti z modal okna.
         */
        updateProgramRoom({commit}, info) {
            commit('incrementLoading');
            const data = {
                id: info.event.id !== '' ? info.event.id : info.event.extendedProps.id, // todo: odstranit workaround po oprave bugu (https://github.com/fullcalendar/fullcalendar/issues/4730)
                block_id: info.event.extendedProps.block.id,
                room_id: info.resourceId !== '0' ? info.resourceId : null,
                start: info.event.start.toISOString()
            };
            Vue.axios.put('save-program', data)
                .then(response => {
                    const responseObject = JSON.parse(response.data);
                    info.event.setResources([String(responseObject.program.room_id || 0)]);
                    commit('setEventResource', {eventId: info.event.id, resourceId: String(responseObject.program.room_id || 0)});
                    commit('setMessage', {type: responseObject.status, text: responseObject.message});
                }).catch(error => {
                    handleError(commit, error);
                }).finally(() => {
                    commit('decrementLoading');
                });
        },

        /**
         * Zpracuje odstranění programu z modal okna.
         */
        removeProgram({commit, state}, info) {
            commit('incrementLoading');
            Vue.axios.delete('remove-program/' + (info.event.id !== '' ? info.event.id : info.event.extendedProps.id)) // todo: odstranit workaround po oprave bugu (https://github.com/fullcalendar/fullcalendar/issues/4730)
                .then(response => {
                    const responseObject = JSON.parse(response.data);
                    info.event.remove();
                    const filteredEvents = state.events.filter(event => event.id !== info.event.id);
                    commit('setEvents', filteredEvents);
                    commit('decrementProgramsCount', info.event.extendedProps.block.id);
                    commit('setMessage', {type: responseObject.status, text: responseObject.message});
                }).catch(error => {
                    handleError(commit, error);
                }).finally(() => {
                    commit('decrementLoading');
                });
        }
    }
});

function handleError(commit, error) {
    if (error.response && error.response.data) {
        const responseObject = JSON.parse(error.response.data);
        commit('setMessage', {type: responseObject.status, text: responseObject.message});
    } else {
        commit('setMessage', {type: 'danger', text: 'Neznámá chyba.'});
    }
}