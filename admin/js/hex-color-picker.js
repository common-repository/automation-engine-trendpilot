// js/hex-color-picker.js
jQuery(document).ready(function ($) {
    $(".hex-color-picker").spectrum({
        preferredFormat: "hex",
        showInput: true,
        showInitial: true,
        allowEmpty: true,
        showPalette: true,
        palette: [
            ["#000", "#444", "#666", "#999", "#ccc", "#eee", "#f3f3f3", "#fff"],
            ["#f00", "#f90", "#ff0", "#0f0", "#0ff", "#00f", "#90f", "#f0f"]
        ]
    });
});
