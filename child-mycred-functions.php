<?php
if(!defined('error_reporting')) { define('error_reporting', '0'); }
ini_set( 'display_errors', error_reporting );
if(error_reporting == '1') { error_reporting( E_ALL ); }
if(isdolcetheme !== 1) { die(); }

/**
 * Render Shortcode Form
 * Returns an advanced version allowing for further customizations.
 * @since 0.1
 * @version 1.6
 */


public function child_render_shortcode_form( $atts, $content = '' ) {

	// Make sure the add-on has been setup
	if ( ! isset( $this->core->buy_creds ) ) {
		if ( mycred_is_admin() )
			return '<p style="color:red;"><a href="' . $this->get_settings_url( 'buycred_module' ) . '">This Add-on needs to setup before you can use this shortcode.</a></p>';
		else
			return '';
	}

	extract( shortcode_atts( array(
		'button'  => __( 'Buy Now', 'mycred' ),
		'gateway' => '',
		'ctype'   => MYCRED_DEFAULT_TYPE_KEY,
		'amount'  => '',
		'gift_to' => '',
		'gift_by' => __( 'Username', 'mycred' ),
		'inline'  => 0
	), $atts ) );

	// If we are not logged in
	if ( $this->current_user_id == 0 ) return $content;

	// Get gateways
	$installed = $this->get();

	// Catch errors
	if ( empty( $installed ) ) return 'No gateways installed.';
	elseif ( ! empty( $gateway ) && ! array_key_exists( $gateway, $installed ) ) return 'Gateway does not exist.';
	elseif ( empty( $this->active ) ) return 'No active gateways found.';
	elseif ( ! empty( $gateway ) && ! $this->is_active( $gateway ) ) return 'The selected gateway is not active.';

	// Make sure we are trying to sell a point type that is allowed to be purchased
	if ( ! in_array( $ctype, $this->core->buy_creds['types'] ) )
		$ctype = $this->core->buy_creds['types'][0];

	if ( $ctype == $this->core->cred_id )
		$mycred = $this->core;
	else
		$mycred = mycred( $ctype );

	global $post;

	// Prep
	$buyer_id     = $this->current_user_id;
	$recipient_id = $buyer_id;
	$classes      = array( 'myCRED-buy-form' );

	if ( $this->core->buy_creds['gifting']['authors'] && $gift_to == 'author' )
		$recipient_id = $post->post_author;

	if ( $this->core->buy_creds['gifting']['members'] && absint( $gift_to ) !== 0 )
		$recipient_id = absint( $gift_to );

	$button_label = $mycred->template_tags_general( $button );

	if ( ! empty( $gateway ) ) {
		$gateway_name = explode( ' ', $installed[ $gateway ]['title'] );
		$button_label = str_replace( '%gateway%', $gateway_name[0], $button_label );
		$classes[]    = $gateway_name[0];
	}

	$amounts = array();
	$minimum = $this->core->number( $this->core->buy_creds['minimum'] );
	if ( ! empty( $amount ) ) {
		$_amounts = explode( ',', $amount );
		foreach ( $_amounts as $_amount ) {
			$_amount = $mycred->number( $_amount );
			if ( $_amount === $mycred->zero() ) continue;
				$amounts[] = $_amount;
		}
	}

	ob_start();

	?>
	<div class="row">
		<div class="col-xs-12">
			<form method="post" class="form<?php if ( $inline == 1 ) echo '-inline'; ?> <?php echo implode( ' ', $classes ); ?>" action="">
				<input type="hidden" name="token" value="<?php echo wp_create_nonce( 'mycred-buy-creds' ); ?>" />
				<input type="hidden" name="transaction_id" value="<?php echo strtoupper( wp_generate_password( 6, false, false ) ); ?>" />
				<input type="hidden" name="ctype" value="<?php echo $ctype; ?>" />

				<div class="form-group">
					<label><?php echo $mycred->plural(); ?></label>

					<?php // No amount given - user must nominate the amount ?>
					<?php if ( count( $amounts ) == 0 ) : ?>

					<input type="text" name="amount" class="form-control" placeholder="<?php echo $mycred->format_creds( $minimum ); ?>" min="<?php echo $minimum; ?>" value="" />

					<?php // Amount given ?>
					<?php else : ?>
						<?php // One amount - this is the amount a user must buy ?>
						<?php if ( count( $amount ) > 1 ) { ?>

					<p class="form-control-static"><?php echo $mycred->format_creds( $amounts[0] ); ?></p>
					<input type="hidden" name="amount" value="<?php echo $amounts[0]; ?>" />

						<?php // Multiple items - user selects the amount from a dropdown menu ?>
						<?php } else { ?>

					<select name="amount" class="form-control">
							<?php foreach ( $amounts as $amount )
								echo '<option value="' . $amount . '">' . $mycred->format_creds( $amount ) . '</option>';
							?>
					</select>

						<?php } ?>
					<?php endif; ?>
					
					<?php if ( $gift_to != '' ) : ?>
					<div class="form-group">
						<label for="gift_to"><?php _e( 'Recipient', 'mycred' ); ?></label>
						<?php
						// Post author - show the authors name
						if ( $this->core->buy_creds['gifting']['authors'] && $gift_to == 'author' ) {
							$author = get_userdata( $recipient_id );
						?>
						<p class="form-control-static"><?php echo $author->display_name; ?></p>
						<input type="hidden" name="gift_to" value="<?php echo $recipient_id; ?>" />
						<?php
						}
						// Specific User - show the members name
						elseif ( $this->core->buy_creds['gifting']['members'] && absint( $gift_to ) !== 0 ) {
							$member = get_userdata( $recipient_id );
						?>
						<p class="form-control-static"><?php echo $member->display_name; ?></p>
						<input type="hidden" name="gift_to" value="<?php echo $recipient_id; ?>" />
						<?php
						}
						// Users need to nominate recipient
						else {
						?>
						<input type="text" class="form-control pick-user" name="gift_to" placeholder="<?php echo $gift_by; ?>" value="" />
						<?php
						}
						?>
					</div>
					<?php endif; ?>
					<?php if ( empty( $gateway ) && count( $installed ) > 1 ) : ?>
					<div class="form-group">
						<label for="gateway"><?php _e( 'Pay Using', 'mycred' ); ?></label>
						<select name="mycred_buy" class="form-control">
						<?php
						foreach ( $installed as $gateway_id => $info ) {
							if ( $this->is_active( $gateway_id ) )
							echo '<option value="' . $gateway_id . '">' . $info['title'] . '</option>';
							}
						?>
						</select>
					</div>
					<?php else : ?>
					<input type="hidden" name="mycred_buy" value="<?php echo $gateway; ?>" />
					<?php endif; ?>
					<div class="form-group">
						<input type="submit" class="btn btn-primary btn-block btn-lg" value="<?php echo $button_label; ?>" />
					</div>
				</div>
			</form>
		</div>
	</div>
	<?php
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}
