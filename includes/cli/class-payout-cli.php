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
	 * <username|ID>
	 * : Affiliate username or ID
	 *
	 * <referrals>
	 * : Referral ID or comma-separated list of referral IDs to associate with the payout.
	 *
	 * * Pass 'all' to generate a payout for all unpaid referrals for this affiliate.
	 * * Pass 'filter' to filter referrals using the amount and/or date options.
	 *
	 * [--amount=<number>]
	 * : Payout amount. Intended to be used in conjunction with the 'filter' option for the <referrals> argument.
	 *
	 * [--amount_compare=<operator>]
	 * : Comparison operator to use in conjunction with --amount. Accepts '>', '<', '>=', '<=', '=', or '!='.
	 *
	 * Intended to be used in conjunction with the 'filter' option for the <referrals> argument.
	 *
	 * [--amount_min=<number>]
	 * : Minimum amount to search for. --amount_max must also be passed for this to work.
	 *
	 * Intended to be used in conjunction with the 'filter' option for the <referrals> argument.
	 *
	 * [--amount_max=<number>]
	 * : Maximum amount to search for. --amount_min must also be passed for this to work.
	 *
	 * Intended to be used in conjunction with the 'filter' option for the <referrals> argument.
	 *
	 * [--date=<date>]
	 * : Date to pay out referrals for. Intended to be used in conjunction with the 'filter' option for the
	 * <referrals> argument. Should be used in place of --date_start and/or --date_end.
	 *
	 * [--start_date=<date>]
	 * : Starting date to pay out referrals for. Can be used without --date_end to pay out referrals
	 * on or after this date.
	 *
	 * Intended to be used in conjunction with the 'filter' option for the <referrals> argument.
	 *
	 * [--end_date=<date>]
	 * : Starting date to pay out referrals for. Can be used without --date_start to pay out referrals
	 * on or before this date.
	 *
	 * Intended to be used in conjunction with the 'filter' option for the <referrals> argument.
	 *
	 * [--method=<method>]
	 * : Payout method. Default empty.
	 *
	 * [--status=<status>]
	 * : Payout status. Accepts 'paid', or 'failed'.
	 *
	 * If not specified, 'paid' will be used.
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
		if ( empty( $args[0] ) ) {
			\WP_CLI::error( __( 'A valid affiliate username or ID must be specified as the first argument.', 'affiliate-wp' ) );
		}

		if ( empty( $args[1] ) ) {
			\WP_CLI::error( __( 'A valid referral ID, comma-separated list of IDs, or "all" must be specified as the second argument', 'affiliate-wp' ) );
		}

		if ( ! $affiliate = affwp_get_affiliate( $args[0] ) ) {
			\WP_CLI::error( sprintf( __( 'An affiliate with the ID or username "%s" does not exist. See wp affwp affiliate create for adding affiliates.', 'affiliate-wp' ), $args[0] ) );
		}

		$data = array();

		// Grab flag values.
		$data['payout_method']  = Utils\get_flag_value( $assoc_args, 'method'        , ''     );
		$data['status']         = Utils\get_flag_value( $assoc_args, 'status'        , 'paid' );
		$data['affiliate_id']   = $affiliate->ID;

		$referral_args = array(
			'number'       => - 1,
			'status'       => 'unpaid',
			'affiliate_id' => $affiliate->ID,
			'fields'       => 'ids',
		);

		if ( 'all' === $args[1] ) {
			$data['referrals'] = affiliate_wp()->referrals->get_referrals( $referral_args );
		} elseif ( 'filter' === $args[1] ) {
			// Amounts filters.
			$amount         = Utils\get_flag_value( $assoc_args, 'amount',         '' );
			$amount_compare = Utils\get_flag_value( $assoc_args, 'amount_compare', '' );
			$amount_min     = Utils\get_flag_value( $assoc_args, 'amount_min',     0  );
			$amount_max     = Utils\get_flag_value( $assoc_args, 'amount_max',     0  );

			if ( ! empty( $amount_min ) && ! empty( $amount_max ) ) {
				$referral_args['amount'] = array(
					'min' => $amount_min,
					'max' => $amount_max
				);
			} elseif ( ! empty( $amount ) ) {

				$referral_args['amount'] = $amount;

				if ( ! empty( $amount_compare ) ) {
					$referral_args['amount_compare'] = $amount_compare;
				}
			}

			// Date filters.
			$date       = Utils\get_flag_value( $assoc_args, 'date',       '' );
			$start_date = Utils\get_flag_value( $assoc_args, 'start_date', '' );
			$end_date   = Utils\get_flag_value( $assoc_args, 'end_date',   '' );

			if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
				$referral_args['date'] = array(
					'start' => $start_date,
					'end'   => $end_date
				);
			} elseif ( ! empty( $start_date ) && empty( $end_date ) ) {
				$referral_args['date']['start'] = $start_date;
			} elseif ( ! empty( $end_date ) && empty( $start_date ) ) {
				$referral_args['date']['end'] = $end_date;
			} elseif ( ! empty( $date ) ) {
				$referral_args['date'] = $date;
			}

			if ( empty( $referral_args['date'] ) || ( empty( $referral_args['amount'] ) ) ) {
				\WP_CLI::error( __( 'Date or amount parameters must be defined to retrieve referrals using the "filter" option.', 'affiliate-wp' ) );
			}

			$data['referrals'] = affiliate_wp()->referrals->get_referrals( $referral_args );
		} elseif ( false !== strpos( $args[1], ',' ) ) {
			$data['referrals'] = wp_parse_id_list( $args[1] );
		} else {
			$data['referrals'] = $args[1];
		}

		if ( ! in_array( $data['status'], array( 'paid', 'failed' ) ) ) {
			$data['status'] = 'paid';
		}

		$payout_id = affwp_add_payout( $data );

		if ( $payout_id ) {
			$payout = affwp_get_payout( $payout_id );
			\WP_CLI::success( sprintf( __( 'A payout with the ID "%d" has been created.', 'affiliate-wp' ), $payout->ID ) );
		} else {
			\WP_CLI::error( __( 'The payout could not be added.', 'affiliate-wp' ) );
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
