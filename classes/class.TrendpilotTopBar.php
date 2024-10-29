<?php

namespace AETrendpilot;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class TrendpilotTopBar {

	public function registerHooks() {
		// Use array($this, 'methodName') to reference the method within the class
		add_action( 'wp_head', array( $this, 'add_notification_bar' ) );
	}

	public function add_notification_bar() {
		$topBarActive = get_option( 'aetp_top_bar_active', 0 );
		if ( $topBarActive != 1 ) {
			return;
		}
		$message = get_option( 'aetp_top_bar_message', 'Default message' );
		$backgroundColor = get_option( 'aetp_top_bar_background_color', '#012C6D' );
		$textColor = get_option( 'aetp_top_bar_text_color', '#FFFFFF' );

		?>
		<div id="ae-notification-bar"
			style="background-color: <?php echo esc_attr( $backgroundColor ); ?>; color: <?php echo esc_attr( $textColor ); ?>; width: 100%; text-align: center; padding: 10px 0; position: relative; top: 0; z-index: 9999;">
			<?php echo esc_html( $message ); ?>
		</div>

		<?php
	}
}