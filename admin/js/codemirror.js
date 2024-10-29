jQuery(function($) {
    if (typeof codemirrorSettings !== 'undefined') {
        wp.codeEditor.initialize("codemirrorhtml", codemirrorSettings);
    }
});
