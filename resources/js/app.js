import "./bootstrap";
import "../css/style.css";

import Toastify from "toastify-js";
import "toastify-js/src/toastify.css";

// expose ke window agar bisa dipanggil dari Blade
window.Toastify = Toastify;
