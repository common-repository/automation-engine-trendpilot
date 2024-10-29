jQuery(document).ready(function ($) {
    // Design attributes functionality
    var designAttributesBox = $('#product_display_design_attributes_meta_box');
    designAttributesBox.show(); // Always show the Design Attributes box

    var frame;
    $('#upload_background_image_button').on('click', function (event) {
        event.preventDefault();
        if (frame) {
            frame.open();
            return;
        }
        frame = wp.media({
            title: 'Select or Upload Background Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });
        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $('#background_image_1').val(attachment.id);
            $('#background_image_url').html(attachment.url);
        });
        frame.open();
    });

    // Initialize Spectrum color pickers with default values
    $('.color-picker').spectrum({
        showInput: true,
        preferredFormat: "hex",
        allowEmpty: true,
        change: function (color) {
            $(this).val(color ? color.toHexString() : '');
        }
    });

    // Shortcode copying functionality
    function copyShortcode(input) {
        input.select();
        document.execCommand('copy');
        var message = input.nextElementSibling;
        message.style.display = 'inline';
        setTimeout(function () {
            message.style.display = 'none';
        }, 2000);
    }

    // Attach the copyShortcode function to inputs with the class 'shortcode-copy-input'
    document.querySelectorAll('.shortcode-copy-input').forEach(function (input) {
        input.addEventListener('click', function () {
            copyShortcode(this);
        });
    });

    // Product search functionality
    var products = window.productDisplaySettings.products || [];
    $("#product_search").autocomplete({
        source: products,
        select: function (event, ui) {
            $("#product_id").val(ui.item.id);
        }
    });

    // Display type toggle functionality
    function toggleHtmlMetaBox() {

        var displayType = $('#display_type').val();
        var htmlMetaBox = $('#product_display_html_meta_box');
        if (displayType === 'custom') {
            htmlMetaBox.show();
        } else {
            htmlMetaBox.hide();
        }
    }

    // Initialize the display type on load
    toggleHtmlMetaBox(); // Ensure correct state on page load

    // Attach event listener to the display type dropdown
    $('#display_type').on('change', toggleHtmlMetaBox);

    // CodeMirror editor initialization for the HTML meta box
    if ($('#html').length) {
        var editor = CodeMirror.fromTextArea(document.getElementById("html"), {
            mode: "htmlmixed",
            theme: "default",
            lineNumbers: true,
            lineWrapping: true,
            matchBrackets: true,
            autoCloseTags: true,
            extraKeys: {
                "Ctrl-Space": "autocomplete"
            }
        });

        editor.on("change", function (cm) {
            cm.save(); // Ensure that changes are saved to the textarea
        });
    }

    
});
