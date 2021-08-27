import RequestObject from "../Schematics/RequestObject";
import Routes from "../Schematics/Routes";
import Utilities from "./Utilities";

import HomeComponents from "../Components/HomeComponents";
import GetStartedComponents from "../Components/GetStartedComponents";

let $ = new Utilities;

class Router {

    public Request: RequestObject;
    private Routes: Array<Routes> = [];

    private components = {
        "HomeComponents": HomeComponents,
        "GetStartedComponents": GetStartedComponents
    };
    
    constructor(
        private main: any
    ) {
        this.Routes     = this.ParseRoutes();
        this.Request    = this.UpdateRequestLocation();
    }

    /**
     * Routing application
     * 
     * @return void
     */
    public async NewRouting(pattern: string | null = null, req: string | null = null): Promise<void> {
        this.Routes.forEach((Route: Routes, i: number) => {
            if (pattern !== null && Route.pattern === pattern || Route.pattern === "/" + $.trim(this.Request.path, "/")) {
                Route = this.LoadRouteComponents(Route);
                this.main.innerHTML = Route.content;
            }
            (pattern !== null? history.pushState({}, null, req): null);
            this.Request    = this.UpdateRequestLocation();
            this.Routes[i]  = Route;
        });
        return;
    }

    /**
     * Import new component
     * 
     * @param name 
     * @returns 
     */
    private LoadRouteComponents(Route: Routes): Routes {
        if (typeof Route.component === "string") {
            Route.content   = (typeof Route.component === "string"? Route.component: null).replace("Components", "").toLowerCase();
            Route.content   = require("../Components/" + Route.component + "/" + Route.content + ".html").default;
            Route.component = Object.values(this.components)[Object.keys(this.components).findIndex((k) => k === Route.component)]();
        }
        return Route;
    }

    /**
     * Update request location
     * 
     * @return RequestObject
     */
    private UpdateRequestLocation(): RequestObject {
        let Request         = new RequestObject;
        Request.hostname    = window.location.hostname;
        Request.path        = window.location.pathname;
        Request.port        = window.location.port;
        Request.anchor      = window.location.hash;
        return Request;
    }

    /**
     * Parse routes configuration
     * 
     * @return Array<Routes>
     */
    private ParseRoutes(): Array<Routes> {
        let RoutesList = require("../routes.json") ?? [];
        RoutesList.forEach((Route: any, i: number) => {
            let Routed          = new Routes;
            Routed.pattern      = Route.pattern;
            Routed.component    = Route.component;
            RoutesList[i]       = Route;
        });
        return RoutesList;
    }

}

export default Router;