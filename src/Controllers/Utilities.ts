class Utilities {
        
     public ready(fn: () => void): void {
        if (document.readyState != 'loading'){
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
     }

     public trim(s: String, c: String): String {
        if (c === "]") c = "\\]";
        if (c === "^") c = "\\^";
        if (c === "\\") c = "\\\\";
        return s.replace(new RegExp(
            "^[" + c + "]+|[" + c + "]+$", "g"
        ), "");
    }
}

export default Utilities;