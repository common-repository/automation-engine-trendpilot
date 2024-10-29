<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


$theme_color = isset( $theme_color ) && ! empty( $theme_color ) ? esc_attr( $theme_color ) : '#000000';
$text_color = isset( $text_color ) && ! empty( $text_color ) ? esc_attr( $text_color ) : '#ffffff';
$cta_color = isset( $cta_color ) && ! empty( $cta_color ) ? esc_attr( $cta_color ) : '#ffffff';
$cta_text_color = isset( $cta_text_color ) && ! empty( $cta_text_color ) ? esc_attr( $cta_text_color ) : '#000000';
$cta_text = isset( $cta_text ) && ! empty( $cta_text ) ? esc_html( $cta_text ) : 'Shop Now';
$show_badge = get_post_meta( $post_id, 'show_badge', true );

// Set the global product object
global $product;
$product = wc_get_product( $product_id );

$badge_html = '';
if ( $show_badge == '1' && $product && class_exists( 'AETrendpilot\TrendpilotBadge' ) ) {
	$badge_class = new \AETrendpilot\TrendpilotBadge();
	ob_start();
	$badge_class->ae_display_product_badge( true ); // Pass 'true' to bypass 'product badge active' option check
	$badge_html = ob_get_clean();
}
?>

<div class="trendpilot-pd-full-width-1">
	<div class="trendpilot-product-container" style="background-color: <?php echo esc_attr( $theme_color ); ?>;">
		<section class="trendpilot-product-left">
			<div class="trendpilot-product-content">
				<h2 class="trendpilot-product-heading" style="color: <?php echo esc_attr( $text_color ); ?>;">
					<?php echo esc_html( $design_heading ); ?>
				</h2>
				<h2 class="trendpilot-product-title" style="color: <?php echo esc_html( $text_color ); ?>;">
					<?php echo esc_html( $product_title ); ?>
				</h2>
				<a href="<?php echo esc_url( $product_url ); ?>" class="trendpilot-cta-link">
					<button class="trendpilot-cta-button"
						style="color: <?php echo esc_attr( $cta_text_color ); ?>; background-color: <?php echo esc_attr( $cta_color ); ?>;">
						<?php echo esc_html( $cta_text ); ?>
					</button>
				</a>
			</div>
			<div class="trendpilot-blur-right" style="background-color: <?php echo esc_attr( $theme_color ); ?>;"></div>
		</section>
		<section class="trendpilot-product-right">
			<div class="trendpilot-background-image"
				style="background-image: url('<?php echo esc_url( $product_image_url ); ?>');"></div>
			<div class="trendpilot-overlay"></div>
			<picture class="trendpilot-product-picture">
				<a href="<?php echo esc_url( $product_url ); ?>" class="trendpilot-cta-link">
					<div class="trendpilot-product-image-container">
						<?php echo $badge_html; ?>
						<img class="trendpilot-product-image" src="<?php echo esc_url( $product_image_url ); ?>"
							alt="Product Image">
					</div>
				</a>
			</picture>
		</section>
	</div>
</div>