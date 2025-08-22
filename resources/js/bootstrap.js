import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

window.toastr = toastr;


import Toastify from 'toastify-js';
import 'toastify-js/src/toastify.css';

window.Toastify = Toastify;
