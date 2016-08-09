<?php

class Affiliate_WP_Payouts_DB extends Affiliate_WP_DB {

	/**
	 * Cache group for queries.
	 *
	 * @internal DO NOT change. This is used externally both as a cache group and shortcut
	 *           for accessing db class instances via affiliate_wp()->{$cache_group}->*.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $cache_group = 'payouts';

	/**
	 * Object type to query for.
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $query_object_type = 'AffWP\Affiliate\Payout';

	/**
	 * Affiliate_WP_Payouts_DB constructor.
	 *
	 * @since 1.9
	 * @access public
	*/
	public function __construct() {
		global $wpdb;

		if( defined( 'AFFILIATE_WP_NETWORK_WIDE' ) && AFFILIATE_WP_NETWORK_WIDE ) {
			// Allows a single payouts table for the whole network.
			$this->table_name  = 'affiliate_wp_payouts';
		} else {
			$this->table_name  = $wpdb->prefix . 'affiliate_wp_payouts';
		}
		$this->primary_key = 'payout_id';
		$this->version     = '1.0';
	}

	/**
	 * Retrieves a payout object.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @see Affiliate_WP_DB::get_core_object()
	 *
	 * @param int $payout Payout ID or object.
	 * @return AffWP\Affiliate\Payout|false Payout object, null otherwise.
	 */
	public function get_object( $payout ) {
		return $this->get_core_object( $payout, $this->query_object_type );
	}

	/**
	 * Retrieves table columns and date types.
	 *
	 * @since 1.9
	 * @access public
	*/
	public function get_columns() {
		return array(
			'payout_id'     => '%d',
			'affiliate_id'  => '%d',
			'referrals'     => '%s',
			'amount'        => '%s',
			'payout_method' => '%s',
			'status'        => '%s',
			'date'          => '%s',
		);
	}

	/**
	 * Retrieves default column values.
	 *
	 * @since 1.9
	 * @access public
	 */
	public function get_column_defaults() {
		return array(
			'payout_id' => 0,
			'status'    => 'paid',
			'date'      => date( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Adds a new single payout.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param array $args {
	 *     Optional. Array of arguments for adding a new payout. Default empty array.
	 *
	 *     @type int        $affiliate_id  Affiliate ID the payout should be associated with.
	 *     @type array      $referrals     Referral ID or array of IDs to associate the payout with.
	 *     @type float      $amount        Payout amount.
	 *     @type string     $payout_method Payout method.
	 *     @type string     $status        Payout status. Will be 'paid' unless there's a problem.
	 *     @type int|string $date          Date string or timestamp for when the payout was created.
	 * }
	 * @return int|false Payout ID if successfully added, otherwise false.
	 */
	public function add( $data = array() ) {

		$defaults = array(
			'affiliate_id' => 0,
			'referrals'    => array(),
			'amount'       => 0,
		);

		$args = wp_parse_args( $data, $defaults );

		if ( ! affiliate_wp()->affiliates->affiliate_exists( $args['affiliate_id'] ) ) {
			return false;
		}

		if ( ! is_array( $args['referrals'] ) ) {
			$args['referrals'] = (array) $args['referrals'];
		}

		foreach ( $args['referrals'] as $index => $referral_id ) {
			if ( $referral = affwp_get_referral( $referral_id ) ) {
				// Referral affiliate doesn't match.
				if ( $args['affiliate_id'] !== $referral->affiliate_id ) {
					unset( $args['referrals'][ $index ] );
				}
			} else {
				// Referral doesn't exist. Drop it.
				unset( $args['referrals'][ $index ] );
			}
		}

		$referrals = $args['referrals'];

		if ( empty( $args['referrals'] ) ) {
			$add = false;
		} else {
			$args['referrals'] = implode( ',', $args['referrals'] );

			$add = $this->insert( $args, 'payout' );
		}

		if ( $add ) {
			/**
			 * Fires immediately after a payout has been successfully inserted.
			 *
			 * @since 1.9
			 *
			 * @param int $add New payout ID.
			 */
			do_action( 'affwp_insert_payout', $add );

			// Add the payout IDs to the referral records.
			foreach ( $referrals as $referral_id ) {
				affiliate_wp()->referrals->update( $referral_id, array( 'payout_id' => $add ), '', 'referral' );
			}

			return $add;
		}

		return false;
	}

	/**
	 * Builds an associative array of affiliate IDs to their corresponding referrals.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param array  $referrals Array of referral IDs.
	 * @param string $status    Optional. Required referral status. Pass an empty string to disable.
	 *                          Default 'paid'.
	 * @return array Associative array of affiliates to referral IDs where affiliate IDs
	 *               are the index with a sub-array of corresponding referral IDs. Referrals
	 *               with a status other than 'paid' will be skipped.
	 */
	public function get_affiliate_ids_by_referrals( $referrals, $status = 'paid' ) {
		$referrals = array_map( 'affwp_get_referral', $referrals );

		$affiliates = array();

		foreach ( $referrals as $referral ) {
			if ( ! empty( $status ) && $status !== $referral->status ) {
				continue;
			}

			$affiliates[ $referral->affiliate_id ][] = $referral->ID;
		}

		return $affiliates;
	}

	/**
	 * Builds an array of payout IDs given an associative array of affiliate IDS to their
	 * corresponding referral IDs.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param array $affiliates Associative array of affiliate IDs to their corresponding
	 *                          referral IDs.
	 * @return array List of payout IDs for all referrals.
	 */
	public function get_payout_ids_by_affiliates( $affiliates ) {
		$payout_ids = array();

		if ( ! empty( $affiliates ) ) {
			foreach ( $affiliates as $affiliate => $referrals ) {
				foreach ( $referrals as $referral ) {
					$payout_ids[] = (int) affiliate_wp()->referrals->get_column( 'payout_id', $referral );
				}
			}
		}

		return array_unique( $payout_ids );
	}

	/**
	 * Retrieves all payout IDs for a set of referrals, regardless of affiliate association.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param array  $referrals Array of referral IDs.
	 * @param string $status    Optional. Required referral status. Pass an empty string to disable.
	 *                          Default 'paid'.
	 * @return array Array of payout IDs.
	 */
	public function get_payout_ids_by_referrals( $referrals, $status = 'paid' ) {
		return $this->get_payout_ids_by_affiliates( $this->get_affiliate_ids_by_referrals( $referrals, $status ) );
	}

	/**
	 * Retrieve payouts from the database
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param array $args {
	 *     Optional. Arguments for querying affiliates. Default empty array.
	 *
	 *     @type int          $number        Number of payouts to query for. Default 20.
	 *     @type int          $offset        Number of payouts to offset the query for. Default 0.
	 *     @type int|array    $payout_id     Payout ID or array of payout IDs to explicitly retrieve. Default 0.
	 *     @type int|array    $affiliate_id  Affiliate ID or array of affiliate IDs to retrieve payouts for. Default 0.
	 *     @type int|array    $referrals     Array of referral IDsReferral ID or array of referral IDs to retrieve payouts for.
	 *     @type float|array  $amount        {
	 *         Payout amount to retrieve payouts for or min/max range to retrieve payouts for.
	 *
	 *         @type float $min Minimum payout amount.
	 *         @type float $max Maximum payout amount. Use -1 for no limit.
	 *     }
	 *     @type string       $payout_method Payout method to retrieve payouts for.
	 *     @type string|array $date          {
	 *         Date string or start/end range to retrieve payouts for.
	 *
	 *         @type string $start Start date to retrieve payouts for.
	 *         @type string $end   End date to retrieve payouts for.
	 *     }
	 *     @type string       $status        Payout status. Default is 'paid' unless there's a problem.
	 *     @type string       $order         How to order returned payout results. Accepts 'ASC' or 'DESC'.
	 *                                       Default 'DESC'.
	 *     @type string       $orderby       Payouts table column to order results by. Accepts any AffWP\Affiliate\Payout
	 *                                       field. Default 'payout_id'.
	 * }
	 * @param bool  $count Optional. Whether to return only the total number of results found. Default false.
	 * @return array Array of payout objects (if found).
	 */
	public function get_payouts( $args = array(), $count = false ) {
		global $wpdb;

		$defaults = array(
			'number'        => 20,
			'offset'        => 0,
			'payout_id'     => 0,
			'affiliate_id'  => 0,
			'referrals'     => 0,
			'amount'        => 0,
			'payout_method' => '',
			'status'        => 'paid',
			'date'          => '',
			'order'         => 'DESC',
			'orderby'       => 'payout_id',
			'search'        => false,
		);

		$args = wp_parse_args( $args, $defaults );

		if( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		$where = '';

		// Specific payouts.
		if( ! empty( $args['payout_id'] ) ) {

			$where .= empty( $where ) ? "WHERE " : "AND ";

			if( is_array( $args['payout_id'] ) ) {
				$payout_ids = implode( ',', array_map( 'intval', $args['payout_id'] ) );
			} else {
				$payout_ids = intval( $args['payout_id'] );
			}

			$payout_ids = esc_sql( $payout_ids );

			$where .= "`payout_id` IN( {$payout_ids} ) ";

			unset( $payout_ids );
		}

		// Affiliate(s).
		if ( ! empty( $args['affiliate_id'] ) ) {

			$where .= empty( $where ) ? "WHERE " : "AND ";

			if ( is_array( $args['affiliate_id'] ) ) {
				$affiliates = implode( ',', array_map( 'intval', $args['affiliate_id'] ) );
			} else {
				$affiliates = intval( $args['affiliate_id'] );
			}

			$affiliates = esc_sql( $affiliates );

			if ( ! empty( $args['search'] ) ) {
				$where .= "`affiliate_id` LIKE '%%" . $affiliates . "%%' ";
			} else {
				$where .= "`affiliate_id` IN( {$affiliates} ) ";
			}
		}

		// Referral ID(s).
		if ( ! empty( $args['referrals'] ) ) {

			if ( ! is_array( $args['referrals'] ) ) {
				$args['referrals'] = (array) $args['referrals'];
			}

			$payout_ids = $this->get_payout_ids_by_referrals( $args['referrals'] );

			if ( ! empty( $payout_ids ) ) {
				$where .= empty( $where ) ? "WHERE " : "AND ";

				$payout_ids = esc_sql( implode( ',', $payout_ids ) );

				if ( ! empty( $args['search'] ) ) {
					$where .= "`payout_id` LIKE '%%" . $payout_ids . "%%' ";
				} else {
					$where .= "`payout_id` IN( {$payout_ids} ) ";
				}
			}

			unset( $payout_ids );
		}

		// Amount.
		if ( ! empty( $args['amount'] ) ) {

			if ( is_array( $args['amount'] ) ) {

				if ( ! empty( $args['amount']['min'] ) ) {

				}

				if ( ! empty( $args['amount']['max'] ) ) {

				}

			} else {


			}

		}

		// Payout method.
		if ( ! empty( $args['payout_method'] ) ) {

			$where .= empty( $where ) ? "WHERE " : "AND ";

			$payment_method = esc_sql( $args['payout_method'] );

			$where .= "`payout_method` = '" . $payout_method . "' ";
		}

		// Status.
		if ( ! empty( $args['status'] ) ) {

			$where .= empty( $where ) ? "WHERE " : "AND ";

			if ( ! in_array( $args['status'], array( 'paid', 'failed' ), true ) ) {
				$args['status'] = 'paid';
			}

			$status = esc_sql( $args['status'] );

			$where .= "`status` = '" . $status . "' ";
		}

		// Date.
		if( ! empty( $args['date'] ) ) {

			if( is_array( $args['date'] ) ) {

				if( ! empty( $args['date']['start'] ) ) {

					if( false !== strpos( $args['date']['start'], ':' ) ) {
						$format = 'Y-m-d H:i:s';
					} else {
						$format = 'Y-m-d 00:00:00';
					}

					$start = esc_sql( date( $format, strtotime( $args['date']['start'] ) ) );

					if ( ! empty( $where ) ) {
						$where .= " AND `date` >= '{$start}'";
					} else {
						$where .= " WHERE `date` >= '{$start}'";
					}

				}

				if ( ! empty( $args['date']['end'] ) ) {

					if ( false !== strpos( $args['date']['end'], ':' ) ) {
						$format = 'Y-m-d H:i:s';
					} else {
						$format = 'Y-m-d 23:59:59';
					}

					$end = esc_sql( date( $format, strtotime( $args['date']['end'] ) ) );

					if( ! empty( $where ) ) {
						$where .= " AND `date` <= '{$end}'";
					} else {
						$where .= " WHERE `date` <= '{$end}'";
					}

				}

			} else {

				$year  = date( 'Y', strtotime( $args['date'] ) );
				$month = date( 'm', strtotime( $args['date'] ) );
				$day   = date( 'd', strtotime( $args['date'] ) );

				if( empty( $where ) ) {
					$where .= " WHERE";
				} else {
					$where .= " AND";
				}

				$where .= " $year = YEAR ( date ) AND $month = MONTH ( date ) AND $day = DAY ( date )";
			}

		}

		$orderby = array_key_exists( $args['orderby'], $this->get_columns() ) ? $args['orderby'] : $this->primary_key;

		// Non-column orderby exception;
		if ( 'amount' === $args['orderby'] ) {
			$orderby = 'amount+0';
		}

		// There can be only two orders.
		if ( 'DESC' === strtoupper( $args['order'] ) ) {
			$order = 'DESC';
		} else {
			$order = 'ASC';
		}

		// Overload args values for the benefit of the cache.
		$args['orderby'] = $orderby;
		$args['order']   = $order;

		$key = ( true === $count ) ? md5( 'affwp_payouts_count' . serialize( $args ) ) : md5( 'affwp_payouts_' . serialize( $args ) );

		$last_changed = wp_cache_get( 'last_changed', $this->cache_group );
		if ( ! $last_changed ) {
			wp_cache_set( 'last_changed', microtime(), $this->cache_group );
		}

		$cache_key = "{$key}:{$last_changed}";

		$results = wp_cache_get( $cache_key, $this->cache_group );

		if ( false === $results ) {

			if ( true === $count ) {

				$results = absint( $wpdb->get_var( "SELECT COUNT({$this->primary_key}) FROM {$this->table_name} {$where};" ) );

			} else {

				$results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT * FROM {$this->table_name} {$where} ORDER BY {$orderby} {$order} LIMIT %d, %d;",
						absint( $args['offset'] ),
						absint( $args['number'] )
					)
				);

			}
		}

		// Convert to AffWP\Affiliate\Payout objects.
		if ( is_array( $results ) ) {
			$results = array_map( 'affwp_get_payout', $results );
		}

		wp_cache_add( $cache_key, $results, $this->cache_group, HOUR_IN_SECONDS );

		return $results;

	}

	/**
	 * Retrieves the number of results found for a given query.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param array $args Arguments to pass to get_payouts().
	 * @return int Number of payouts.
	 */
	public function count( $args = array() ) {
		return $this->get_payouts( $args, true );
	}

	/**
	 * Checks if a payout exists.
	 *
	 * @since 1.9
	 * @access public
	*/
	public function payout_exists( $payout_id = 0 ) {

		global $wpdb;

		if ( empty( $payout_id ) ) {
			return false;
		}

		$payout = $wpdb->query( $wpdb->prepare( "SELECT 1 FROM {$this->table_name} WHERE {$this->primary_key} = %d;", $payout_id ) );

		return ! empty( $payout );
	}

	/**
	 * Retrieves an array of referral IDs stored for the payout.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param AffWP\Affiliate\Payout|int $payout Payout object or ID.
	 * @return array List of referral IDs.
	 */
	public function get_referral_ids( $payout ) {
		if ( ! $payout = affwp_get_payout( $payout ) ) {
			$referral_ids = array();
		} else {
			$referral_ids = array_map( 'intval', explode( ',', $payout->referrals ) );
		}
		return $referral_ids;
	}

	/**
	 * Creates the table.
	 *
	 * @since 1.9
	 * @access public
	*/
	public function create_table() {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
			payout_id bigint(20) NOT NULL AUTO_INCREMENT,
			affiliate_id bigint(20) NOT NULL,
			referrals mediumtext NOT NULL,
			amount mediumtext NOT NULL,
			payout_method tinytext NOT NULL,
			status tinytext NOT NULL,
			date datetime NOT NULL,
			PRIMARY KEY  (payout_id),
			KEY affiliate_id (affiliate_id)
			) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version );
	}

}
