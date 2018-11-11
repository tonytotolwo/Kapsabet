<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>


<div class="envato-elements__welcome">
	<div class="envato-elements__welcome-inner">
		<form action="<?php echo esc_url( admin_url( 'admin.php?action=envato_elements_registration' ) ); ?>" method="POST">
			<?php wp_nonce_field( 'envato_elements_signup' ); ?>
			<?php
			if ( ! empty( $_GET['registration'] ) ) {
				echo '<div class="envato-elements-notice envato-elements-notice--signup">';
				switch ( $_GET['registration'] ) {
					case 'reset':
						echo '<p>' . esc_html__( 'Successfully reset, please register again below.', 'envato-elements' ) . ' </p>';
						break;
					case 'success':
						echo '<p>' . esc_html__( 'Successfully registered', 'envato-elements' ) . ' </p>';
						break;
					case 'error':
						$error_message = get_transient( 'envato-elements-license-message-error' );
						if ( $error_message ) {
							echo '<p>' . esc_html( $error_message ) . ' </p>';
						} else {
							echo '<p>' . esc_html__( 'There was an error with the request, please try again.', 'envato-elements' ) . ' </p>';
						}
						break;
					case 'terms':
						echo '<p>' . esc_html__( 'Please agree to the Terms & Conditions in order to continue.', 'envato-elements' ) . ' </p>';
						break;
					case 'failure':
						echo '<p>' . esc_html__( 'Activation failed, please ensure a valid email address is entered.', 'envato-elements' ) . ' </p>';
						break;
				}
				echo '</div>';
			}
			?>
			<img src="<?php echo esc_url( ENVATO_ELEMENTS_URI . 'assets/images/welcome-2.svg' ); ?>" alt="Welcome"
				width="200"/>
			<p>Welcome. Thanks for trying our new plugin,</p>
			<h2>Envato Elements - Template Kits (Beta).</h2>

			<p>Enter your email address &amp; accept our terms to continue</p>
			<div>
				<?php
				$current_user = wp_get_current_user();
				?>
				<input type="email" name="email_address" value="<?php echo esc_attr( $current_user->user_email ); ?>"
					placeholder="you@example.com">
			</div>
			<div class="envato-elements__welcome-checkboxes">
				<label>
					<input type="checkbox" name="condition_terms" value="1" required>
					<span>
			I agree to the
			<a href="https://wp.envatoextensions.com/terms-conditions/" target="_blank"
				data-nav-type="terms-modal" class="envato-elements--action">Envato Elements - Template Kits BETA Terms</a>
		  </span>
				</label>
				<label>
					<input type="checkbox" name="condition_emails" value="1">
					<span>
			Yes, I want to receive marketing emails about the Envato Elements for WordPress Plugin. I understand my email activity will be tracked &amp; I can unsubscribe at any time.
		  </span>
				</label>
			</div>
			<div>
				<input type="submit" name="submit" id="submit" class="button-primary"
					value="<?php esc_attr_e( 'Continue', 'envato-elements' ); ?>"/>
			</div>
		</form>
	</div>
</div>
