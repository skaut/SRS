'use strict';

import {createApp} from 'vue';
import Calendar from './Calendar.vue';
import store from './store'

if(document.getElementById("calendar")) {
    const calendarApp = createApp({
        el: '#calendar',
        store,
        render: h => h(Calendar),
    });
}