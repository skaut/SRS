'use strict';

import {createApp} from 'vue';
import Calendar from './Calendar.vue';
import BlocksList from './BlocksList.vue';
import store from './store'

if(document.getElementById("calendar")) {
    const calendarApp = createApp({
        el: '#calendar',
        store,
        render: h => h(Calendar),
    });
}

if(document.getElementById("blocks-list")) {
    const blocksListApp = createApp({
        el: '#blocks-list',
        store,
        render: h => h(BlocksList),
    });
}