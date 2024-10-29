(function() {
    var urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('rec_click')) {
        // Get the base URL (without the query string)
        var url = new URL(window.location.href).origin + new URL(window.location.href).pathname;
        
        // Update the browser's history without reloading the page
        window.history.replaceState({}, document.title, url);
    }
})();