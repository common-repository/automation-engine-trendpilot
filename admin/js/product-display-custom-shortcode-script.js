jQuery(document).ready(function($) {

    if (typeof productDisplayData !== 'undefined') {

        // Wait for the DOM to be fully loaded and parsed
        $(window).on('load', function() {
            // Now try to select the elements
            var imageElement = document.querySelector('.trendpilot-product-image');
            var titleElement = document.querySelector('.trendpilot-product-title');
            var descriptionElement = document.querySelector('.trendpilot-product-description');
            var ctaLinkElements = document.querySelectorAll('.trendpilot-cta-link');
            var designHeadingElement = document.querySelector('.trendpilot-design-heading');
            var backgroundImageElement = document.querySelector('.trendpilot-design-background-img');

            // Apply product data to the elements
            if (imageElement) {
                imageElement.src = productDisplayData.image_url;
            }
            if (titleElement) {
                titleElement.textContent = productDisplayData.title;
            }
            if (descriptionElement) {
                descriptionElement.innerHTML = productDisplayData.description;
            }
            if (designHeadingElement) {
                designHeadingElement.textContent = productDisplayData.design_heading;
            }
            if (backgroundImageElement && productDisplayData.background_image) {
                backgroundImageElement.src = productDisplayData.background_image;
            }
            ctaLinkElements.forEach(function(element) {
                element.href = productDisplayData.product_url;
                var button = element.querySelector('.trendpilot-cta-button');
                if (button) {
                    button.textContent = productDisplayData.cta_text;
                }
            });
        });
    }
});