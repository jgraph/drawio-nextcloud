import { registerWidget } from '@nextcloud/vue/dist/Components/NcRichText.js'
import Vue from 'vue'
import DrawioReferenceWidget from './components/DrawioReferenceWidget.vue'

registerWidget('drawio_diagram', (el, { richObjectType, richObject, accessible }) => {
    const Widget = Vue.extend(DrawioReferenceWidget)
    new Widget({
        propsData: {
            richObjectType,
            richObject,
            accessible,
        },
    }).$mount(el)
})
