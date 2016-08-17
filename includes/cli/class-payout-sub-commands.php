<?php
namespace AffWP\Affiliate\Payout\CLI;

use \AffWP\CLI\Sub_Commands\Base;
use \WP_CLI\Utils;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Implements basic CRUD CLI sub-commands for payouts.
 *
 * @since 1.9
 *
 * @see \AffWP\CLI\Sub_Commands\Base
 */
class Sub_Commands extends Base {

	/**
	 * Payout display fields.
	 *
	 * @since 1.9
	 * @access protected
	 * @var array
	 */
	protected $obj_fields = array(
		'ID',
		'amount',
		'affiliate_id',
		'affiliate_email',
		'referrals',
		'payout_method',
		'status',
		'date'
	);

	/**
	 * Sets up the fetcher for sanity-checking.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @see \AffWP\Affiliate\Payout\CLI\Fetcher
	 */
	public function __construct() {
		$this->fetcher = new Fetcher();
	}

	/**
	 * Retrieves a payout object or field(s) by ID.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The payout ID to retrieve.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole payout object, returns the value of a single field.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific fields. Defaults to all fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv, yaml. Default: table
	 *
	 * ## EXAMPLES
	 *
	 *     # save the payout field value to a file
	 *     wp payout get 12 --field=amount > amounts.txt
	 */
	public function get( $args, $assoc_args ) {
		parent::get( $args, $assoc_args );
	}

	/**
	 * Adds a payout.
	 *
	 * ## OPTIONS
	 *
	 * [--start_date=<date>]
	 * : Starting date to pay out referrals for. Can be used without --date_end to pay out referrals
	 * on or after this date.
	 *
	 * [--end_date=<date>]
	 * : Starting date to pay out referrals for. Can be used without --date_start to pay out referrals
	 * on or before this date.
	 *
	 * [--min_earnings=<amount>]
	 * : Minimum total earnings required to generate a payout for an affiliate. Compared as greater than or equal to.
	 *
	 * If omitted, minimum earnings required will be 0.
	 *
	 * [--payout_method=<method>]
	 * : Payout method. Default 'cli'.
	 *
	 * [--referral_status=<status>]
	 * : Status to retrieve referrals for. Accepts any valid referral status.
	 *
	 * If omitted, 'unpaid' will be used.
	 *
	 * ## EXAMPLES
	 *
	 *     # Creates a payout for affiliate edduser1 and referrals 4, 5, and 6
	 *     wp affwp payout create edduser1 4,5,6
	 *
	 *     # Creates a payout for affiliate woouser1, for all of their unpaid referrals, for a total amount of 50
	 *     wp affwp payout create woouser1 all --amount=10
	 *
	 *     # Creates a payout for affiliate ID 142, for all of their unpaid referrals, with a payout method of 'manual'
	 *     wp affwp payout create 142 --method='manual'
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param array $args       Top-level arguments.
	 * @param array $assoc_args Associated arguments (flags).
	 */
	public function create( $args, $assoc_args ) {

		$data = array();

		$referral_args = array(
			'number' => - 1,
			'fields' => 'ids',
		);

		$start_date = Utils\get_flag_value( $assoc_args, 'start_date',   '' );
		$end_date   = Utils\get_flag_value( $assoc_args, 'end_date',     '' );
		$minimum    = Utils\get_flag_value( $assoc_args, 'min_earnings', '' );

		if ( ! empty( $start_date ) ) {
			$referral_args['date']['start'] = sanitize_text_field( $start_date );
		}

		if ( ! empty( $end_date ) ) {
			$referral_args['date']['end'] = sanitize_text_field( $end_date );
		}

		if ( ! empty( $minimum ) ) {
			$minimum = absint( $minimum );
		}

		$referral_args['status'] = Utils\get_flag_value( $assoc_args, 'referral_status', 'unpaid' );

		$referrals = affiliate_wp()->referrals->get_referrals( $referral_args );

		if ( empty( $referrals ) ) {
			\WP_CLI::warning( __( 'No referrals were found matching your criteria. Please try again.', 'affiliate-wp' ) );
		}

		$maps = affiliate_wp()->affiliates->payouts->get_affiliate_ids_by_referrals( $referrals, $referral_args['status'] );

		$to_pay = array();

		foreach ( $maps as $affiliate_id => $referrals ) {
			$amount = 0;

			foreach( $referrals as $referral_id ) {
				if ( $referral = affwp_get_referral( $referral_id ) ) {
					$amount += $referral->amount;
				}
			}

			if ( $amount >= $minimum ) {
				$to_pay[ $affiliate_id ] = array(
					'referrals' => $maps[ $affiliate_id ],
					'amount'    => $amount,
				);
			}
		}

		// Grab flag values.
		$data['payout_method'] = Utils\get_flag_value( $assoc_args, 'payout_method', 'cli' );

		if ( empty( $to_pay ) ) {
			\WP_CLI::warning( __( 'No affiliates matched the minimum earnings amount in order to generate a payout.', 'affiliate-wp' ) );
		} else {
			foreach ( $to_pay as $affiliate_id => $payout_data ) {
				if ( false !== $payout_id = affwp_add_payout( array(
					'affiliate_id'  => $affiliate_id,
					'referrals'     => $payout_data['referrals'],
					'payout_method' => $data['payout_method'],
				) ) ) {
					\WP_CLI::success( sprintf( __( 'A payout has been created for Affiliate #%1$d for %2$s.', 'affiliate-wp' ),
						$affiliate_id,
						html_entity_decode( affwp_currency_filter( affwp_format_amount( $payout_data['amount'] ) ) )
					) );
				} else {
					\WP_CLI::warning( sprintf( __( 'There was a problem generating a payout for Affiliate #%1$d for %2$s.', 'affiliate-wp' ),
						$affiliate_id,
						html_entity_decode( affwp_currency_filter( affwp_format_amount( $payout_data['amount'] ) ) )
					) );
				}
			}
		}
	}

	public function update( $args, $assoc_args ) {
		parent::update( $args, $assoc_args );
	}

	/**
	 * Deletes a payout.
	 *
	 * ## OPTIONS
	 *
	 * <payout_id>
	 * : Payout ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Deletes the payout with ID 20
	 *     wp affwp payout delete 20
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param array $args       Top-level arguments.
	 * @param array $assoc_args Associated arguments (flags, unused).
	 */
	public function delete( $args, $assoc_args ) {
		if ( empty( $args[0] ) || ! is_numeric( $args[0] ) ) {
			\WP_CLI::error( __( 'A valid payout ID is required to proceed.', 'affiliate-wp' ) );
		}

		if ( ! $payout = affwp_get_payout( $args[0] ) ) {
			\WP_CLI::error( __( 'A valid payout ID is required to proceed.', 'affiliate-wp' ) );
		}

		\WP_CLI::confirm( __( 'Are you sure you want to delete this payout?', 'affiliate-wp' ), $assoc_args );

		$deleted = affwp_delete_payout( $payout );

		if ( $deleted ) {
			\WP_CLI::success( __( 'The payout has been successfully deleted.', 'affiliate-wp' ) );
		} else {
			\WP_CLI::error( __( 'The payout could not be deleted.', 'affiliate-wp' ) );
		}
	}

	/**
	 * Displays a list of payouts.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : One or more args to pass to get_payouts().
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each payout.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific payout fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, csv, json, count, ids, yaml. Default: table
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each payout:
	 *
	 * * ID (alias for payout_id)
	 * * amount
	 * * affiliate_id
	 * * affiliate_email
	 * * referrals
	 * * payout_method
	 * * status
	 * * date
	 *
	 * ## EXAMPLES
	 *
	 *     affwp payout list --field=date
	 *
	 *     affwp payout list --amount_min=0 --amount_max=20 --fields=affiliate_id,amount,date
	 *
	 *     affwp payout list --fields=affiliate_id,amount,date --format=json
	 *
	 * @subcommand list
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param array $args       Top-level arguments.
	 * @param array $assoc_args Associated arguments (flags).
	 */
	public function list_( $_, $assoc_args ) {
		$formatter = $this->get_formatter( $assoc_args );

		$fields = $this->get_fields( $assoc_args );

		// Handle ID alias.
		if ( isset( $assoc_args['ID'] ) ) {
			$assoc_args['payout_id'] = $assoc_args['ID'];
			unset( $assoc_args['ID'] );
		}

		$args = $assoc_args;

		if ( 'count' == $formatter->format ) {
			$payouts = affiliate_wp()->affiliates->payouts->count( $args );

			\WP_CLI::line( sprintf( __( 'Number of payouts: %d', 'affiliate-wp' ), $payouts ) );
		} else {
			$payouts = affiliate_wp()->affiliates->payouts->get_payouts( $args );
			$payouts = $this->process_extra_fields( $fields, $payouts );

			if ( 'ids' == $formatter->format ) {
				$payouts = wp_list_pluck( $payouts, 'payout_id' );
			} else {
				$payouts = array_map( function( $payout ) {
					$payout->ID = $payout->payout_id;

					return $payout;
				}, $payouts );
			}

			$formatter->display_items( $payouts );
		}
	}

	/**
	 * Handler for the 'amount' field.
	 *
	 * @since 1.9
	 * @access protected
	 *
	 * @param \AffWP\Affiliate\Payout $item Payout object (passed by reference).
	 */
	protected function amount_field( &$item ) {
		$amount = affwp_currency_filter( affwp_format_amount( $item->amount ) );

		/** This filter is documented in includes/admin/payouts/payouts.php */
		$amount = apply_filters( 'affwp_payout_table_amount', $amount, $item );

		$item->amount = html_entity_decode( $amount );
	}

	/**
	 * Handler for the 'affiliate_email' field.
	 *
	 * @since 1.9
	 * @access protected
	 *
	 * @param \AffWP\Affiliate\Payout $item Payout object (passed by reference).
	 */
	protected function affiliate_email_field( &$item ) {
		$item->affiliate_email = affwp_get_affiliate_email( $item->affiliate_id );
	}

	/**
	 * Handler for the 'date' field.
	 *
	 * Reformats the date for display.
	 *
	 * @since 1.9
	 * @access protected
	 *
	 * @param \AffWP\Affiliate\Payout $item Payout object (passed by reference).
	 */
	protected function date_field( &$item ) {
		$item->date = mysql2date( 'M j, Y', $item->date, false );
	}

}

\WP_CLI::add_command( 'affwp payout', 'AffWP\Affiliate\Payout\CLI\Sub_Commands' );
