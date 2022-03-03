'use strict';

import Vue from 'vue';
import Calendar from './Calendar.vue';
import store from './store'

if(document.getElementById("calendar")) {
    new Vue({
        el: '#calendar',
        store,
        render: h => h(Calendar),
    });
}