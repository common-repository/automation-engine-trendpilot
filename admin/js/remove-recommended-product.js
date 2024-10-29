document.addEventListener('DOMContentLoaded', function () {
    var removeButtons = document.querySelectorAll('.remove-product');

    removeButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var productId = this.getAttribute('data-product-id');
            var nonce = trendpilotSettings.removeProductNonce;
            var ajaxUrl = trendpilotSettings.ajaxUrl;

            // Using jQuery for AJAX request
            jQuery.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'aetp_remove_recommended_product',
                    product_id: productId,
                    nonce: nonce
                },
                success: function (response) {
                    if (response.success) {
                        button.parentElement.remove();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function (xhr, status, error) {
                    alert('Failed to execute the request.');
                }
            });
        });
    });
});
