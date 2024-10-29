<?php
// Load WordPress environment
require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );

// Get the post ID from the URL (passed via iframe)
$post_id = intval( $_GET['post_id'] );

if ( ! $post_id ) {
	wp_die( 'Invalid post ID' );
}

// Get the display type
$display_type = sanitize_text_field( get_post_meta( $post_id, 'display_type', true ) );

// Enqueue appropriate CSS based on display type
if ( $display_type === 'full_width_design_1' ) {
	wp_enqueue_style( 'full-width-design-1-css', esc_url( AETRENDPILOT_PLUGIN_URL . '/public/css/product-displays/full-width-design-1.css' ) );
} elseif ( $display_type === 'full_width_design_2' ) {
	wp_enqueue_style( 'full-width-design-2-css', esc_url( AETRENDPILOT_PLUGIN_URL . '/public/css/product-displays/full-width-design-2.css' ) );
} elseif ( $display_type === 'full_width_design_3' ) {
	wp_enqueue_style( 'full-width-design-3-css', esc_url( AETRENDPILOT_PLUGIN_URL . '/public/css/product-displays/full-width-design-3.css' ) );
}

// Output the shortcode in the preview
$shortcode = '[tp_product_display id="' . $post_id . '"]';
?>

<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Shortcode Preview</title>
	<?php
	// Load the enqueued styles for the specific design
	wp_head();
	?>
</head>

<body>
	<?php echo do_shortcode( $shortcode ); ?>
	<?php wp_footer(); ?>
</body>

</html>