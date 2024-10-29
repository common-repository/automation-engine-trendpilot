jQuery(document).ready(function($) {
    if (typeof upsellProductData !== 'undefined') {
        var shouldShowPopup = localStorage.getItem('showUpsellPopup');
        if (shouldShowPopup === 'true') {
            var upsellContent;

            var noThanks = upsellProductData.cartUrlRaw;

            if(upsellProductData.redirect_to_cart_after_add === 'no'){
                noThanks = upsellProductData.current_product_url;
            }  

            if (upsellProductData.isVariable) {
                // Variable product: Show 'View Product' button
                upsellContent = '<div class="ae-upsell-card">' +
                                '<h2 class="upsell-heading">Why not upgrade your order?</h2>' +
                                '<hr class="ae-separator">' +
                                '<div class="ae-upsell-container">' +
                                '<div class="ae-upsell-left"><img src="' + upsellProductData.imageUrl + '" alt="' + upsellProductData.name + '"></div>' +
                                '<div class="ae-upsell-right">' +
                                '<h2>' + upsellProductData.name + '</h2>' +
                                '<p class="upsell-price">' + upsellProductData.price + '</p>' +
                                '<a href="' + upsellProductData.cartUrl + '" id="upsellAddToCart" class="yes-please-button button wp-element-button">View Product</a>' +
                                '<a href="' + noThanks + '" class="no-thanks-btn">No Thanks</a>' +
                                '</div></div></div>';
            } else {
                // Non-variable product: Show 'Add to Cart' button
                upsellContent = '<div class="ae-upsell-card">' +
                                '<h2 class="upsell-heading">Why not upgrade your order?</h2>' +
                                '<hr class="ae-separator">' +
                                '<div class="ae-upsell-container">' +
                                '<div class="ae-upsell-left"><img src="' + upsellProductData.imageUrl + '" alt="' + upsellProductData.name + '"></div>' +
                                '<div class="ae-upsell-right">' +
                                '<h2>' + upsellProductData.name + '</h2>' +
                                '<p class="upsell-price">' + upsellProductData.price + '</p>' +
                                '<a href="' + upsellProductData.cartUrl + '" id="upsellAddToCart" class="yes-please-button button wp-element-button">Add to Cart</a>' +
                                '<a href="' + noThanks + '" class="no-thanks-btn">No Thanks</a>' +
                                '</div></div></div>';
            }

            // Populate the modal with the content and show it
            $('#upsellModal .aetp-modal-content').html(upsellContent);
            $('#upsellModal').show();

            // Remove the flag from local storage to prevent showing the popup again
            localStorage.removeItem('showUpsellPopup');
        }
    }

    // Event handler for 'Add to Cart' form submission
    $(document).on('submit', 'form.cart', function(event) {
        localStorage.setItem('showUpsellPopup', 'true');
    });

    // Close button functionality for the modal
    $('#upsellModal').on('click', '.modal-close', function() {
        $('#upsellModal').hide();
    });
}); 
