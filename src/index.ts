import RouterObj from "./Controllers/Router";
import Utilities from "./Controllers/Utilities";

import "bootstrap";
import "./Sass/index.scss";

let $ = new Utilities;

$.ready(() => {
    let Router = new RouterObj(document.getElementById("data-content"));
    Router.NewRouting();
});