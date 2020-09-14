import Vue from 'vue'
import PonygonApp from '~Pages/PonygonApp'
import store from './Store/index'

export default new Vue({
	store,

	el: '#app',

	components: {
		PonygonApp,
	},
})
