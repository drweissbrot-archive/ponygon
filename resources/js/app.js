import axios from 'axios'

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.withCredentials = true

import './Vue'

import '~Echo'
import '~Store/lobby-events'
