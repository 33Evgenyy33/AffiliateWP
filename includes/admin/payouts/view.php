<?php
/**
 * 'View Payout' admin template
 *
 * @package    AffiliateWP\Admin\Payouts
 * @copyright  Copyright (c) 2014, Pippin Williamson
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      1.9
 */

$payout = affwp_get_payout( absint( $_GET['payout_id'] ) );
?>

<div class="wrap">

	<h2><?php printf( __( 'Payout: #%d', 'affiliate-wp' ), $payout->ID ); ?></h2>

	<?php
	/**
	 * Fires at the top of the 'View Payout' page, just inside the opening div.
	 *
	 * @since 1.9
	 *
	 * @param \AffWP\Affiliate\Payout $payout Payout object.
	 */
	do_action( 'affwp_edit_payout_top', $payout );
	?>

	<table id="affwp_payout" class="form-table">

		<tr class="form-row">

			<th scope="row">
				<?php _e( 'Affiliate', 'affiliate-wp' ); ?>
			</th>

			<td>
				<?php
				$url = add_query_arg( array(
					'page'         => 'affiliate-wp-affiliates',
					'action'       => 'view_affiliate',
					'affiliate_id' => $payout->affiliate_id
				), admin_url( 'admin.php' ) );

				$name      = affiliate_wp()->affiliates->get_affiliate_name( $payout->affiliate_id );
				$affiliate = affwp_get_affiliate( $payout->affiliate_id );

				printf( '<a href="%1$s">%2$s</a> (ID: %3$s)',
					esc_url( $url ),
					esc_html( $name ),
					esc_html( $affiliate->ID )
				);
				?>
			</td>

		</tr>

		<tr class="form-row">

			<th scope="row">
				<?php _e( 'Referrals', 'affiliate-wp' ); ?>
			</th>

			<td>
				<?php
				$referrals = affiliate_wp()->affiliates->payouts->get_referral_ids( $payout );
				$links     = array();
				$base      = admin_url( 'admin.php?page=affiliate-wp-referrals&action=edit_referral&referral_id=' );

				foreach ( $referrals as $referral_id ) {
					$links[] = sprintf( '<a href="%1$s">%2$s</a>',
						esc_url( $base . $referral_id ),
						esc_html( $referral_id )
					);
				}

				echo implode( ', ', $links );
				?>
			</td>

		</tr>

		<tr class="form-row">

			<th scope="row">
				<?php _e( 'Amount', 'affiliate-wp' ); ?>
			</th>

			<td>
				<?php echo affwp_currency_filter( affwp_format_amount( $payout->amount ) ); ?>
			</td>

		</tr>

		<tr class="form-row">

			<th scope="row">
				<?php _e( 'Payout Method', 'affiliate-wp' ); ?>
			</th>

			<td>
				<?php echo empty( $payout->payout_method ) ? __( '(none)', 'affiliate-wp' ) : esc_html( $payout->payout_method ); ?>
			</td>

		</tr>

		<tr class="form-row">

			<th scope="row">
				<?php _e( 'Status', 'affiliate-wp' ); ?>
			</th>

			<td>
				<?php
				printf( '<span class="affwp-status %1$s"><i></i>%2$s</span>',
					esc_attr( $payout->status ),
					affwp_get_payout_status_label( $payout )
				);
				?>
			</td>

		</tr>

		<tr class="form-row">

			<th scope="row">
				<?php _e( 'Payout Date', 'affiliate-wp' ); ?>
			</th>

			<td>
				<?php echo date_i18n( get_option( 'date_format' ), strtotime( $payout->date ) ); ?>
			</td>

		</tr>

		<?php
		/**
		 * Fires at the end of the 'View Payout' page, just inside the closing table tag.
		 *
		 * @since 1.9
		 *
		 * @param \AffWP\Affiliate\Payout $payout Payout object.
		 */
		do_action( 'affwp_edit_payout_end', $payout );
		?>

	</table>

	<?php
	/**
	 * Fires at the end of the 'View Payout' page, just inside the closing div.
	 *
	 * @since 1.9
	 *
	 * @param \AffWP\Affiliate\Payout $payout Payout object.
	 */
	do_action( 'affwp_edit_payout_bottom', $affiliate );
	?>

</div>