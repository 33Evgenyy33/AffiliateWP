<?php

class Affiliate_WP_Registrations_Graph extends Affiliate_WP_Graph {

	/**
	 * Retrieve referral data
	 *
	 * @since 1.1
	 */
	public function get_data() {

		$dates = affwp_get_report_dates();

		$start = $dates['year'] . '-' . $dates['m_start'] . '-' . $dates['day'] . ' 00:00:00';
		$end   = $dates['year_end'] . '-' . $dates['m_end'] . '-' . $dates['day_end'] . ' 23:59:59';
		$date  = array(
			'start' => $start,
			'end'   => $end
		);

		$affiliates = affiliate_wp()->affiliates->get_affiliates( array(
			'orderby'  => 'date_registered',
			'order'    => 'ASC',
			'number'   => -1,
			'date'     => $date
		) );

		$affiliate_data = array();
		$affiliate_data[] = array( strtotime( $start ) * 1000 );
		$affiliate_data[] = array( strtotime( $end ) * 1000 );

		if( $affiliates ) {

			foreach( $affiliates as $affiliate ) {

				if( 'today' == $dates['range'] || 'yesterday' == $dates['range'] ) {

					$point = strtotime( $affiliate->date_registered ) * 1000;

					$affiliate_data[ $point ] = array( $point, 1 );

				} else {

					$time      = date( 'Y-n-d', strtotime( $affiliate->date_registered ) );
					$timestamp = strtotime( $time ) * 1000;

					if( array_key_exists( $time, $affiliate_data ) && isset( $affiliate_data[ $time ][1] ) ) {

						$count = $affiliate_data[ $time ][1] += 1;

						$affiliate_data[ $time ] = array( $timestamp, $count );
					
					} else {

						$affiliate_data[ $time ] = array( $timestamp, 1 );
						
					}

					
				}


			}

		}

		$data = array(
			__( 'Affiliate Registrations', 'affiliate-wp' ) => $affiliate_data
		);

		return $data;

	}

}