import "bootstrap";
import './sass/index.scss';

// Pages components
import HomeComponents from "./components/home.html";
import GetStartedComponents from "./components/get-started.html";

// Errors components
import NotFoundComponents from "./components/errors/not-found.html";

let ContentProvided = null;
let RequestUri      = null;
const Routes        = {
    "/": HomeComponents,
    "/get-started": GetStartedComponents
};

let RequestBackRequest = false;
const Routing = (HistoryUpdate = false) => {

    ContentProvided.innerHTML = Routes[RequestUri.split("#")[0]] ?? NotFoundComponents;
    (HistoryUpdate? history.pushState({}, null, trim(window.location.origin, "/") + RequestUri): null);

    let anchor  = (window.location.hash !== ""? document.getElementById(trim(window.location.hash, "#")): null);
    anchor      = (anchor !== null? anchor.offsetTop: null);
    if (anchor !== null) {
        window.scrollTo(0, anchor);
    }

    let links = document.querySelectorAll('[href]');
    links.forEach(link => {
        if (link.target === "") {
            link.addEventListener("click", (e) => {
                e.preventDefault();
                RequestBackRequest  = false;
                RequestUri          = link.href.replace(trim(window.location.origin, "/"), "") ?? null;
                Routing(true);
            });
        }
    });

    let anchors = document.querySelectorAll('[data-link]');
    anchors.forEach((anchor) => {
        if (anchor.tagName === "I") {
            anchor.addEventListener("click", (e) => {
                e.preventDefault();
                let text = window.location.href.replace(window.location.hash, "") + "#" + anchor.dataset.link;
                window.scrollTo(0, document.getElementById(anchor.dataset.link).offsetTop);
                navigator.clipboard.writeText(text);
            });
        }
    });
};

ready(() => {
    ContentProvided = document.getElementsByTagName("main")[0];
    RequestUri      = "/" + trim(window.location.pathname, "/");
    Routing();
    window.addEventListener("popstate", function (e) {
        RequestUri = "/" + trim(window.location.pathname, "/");
        Routing();
    });
});

/**
 * Check if document is loaded
 * 
 * @param {*} fn 
 */
function ready(fn) {
    if (document.readyState != 'loading'){
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}

/**
 * Trim string
 * 
 * @param {*} s string
 * @param {*} c character
 * @returns 
 */
function trim(s, c) {
    if (c === "]") c = "\\]";
    if (c === "^") c = "\\^";
    if (c === "\\") c = "\\\\";
    return s.replace(new RegExp(
        "^[" + c + "]+|[" + c + "]+$", "g"
    ), "");
}