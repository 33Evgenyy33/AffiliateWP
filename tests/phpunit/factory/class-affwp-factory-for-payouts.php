<?php

class AffWP_Factory_For_Payouts extends WP_UnitTest_Factory_For_Thing {

	function __construct( $factory = null ) {
		parent::__construct( $factory );
	}

	function create_object( $args ) {
		$affiliate = new AffWP_Factory_For_Affiliates();
		$referral  = New AffWP_Factory_For_Referrals();

		// Only create the associated affiliate if one wasn't supplied.
		if ( empty( $args['affiliate_id'] ) ) {
			$args['affiliate_id'] = $affiliate->create();
		}

		if ( empty( $args['referrals'] ) ) {
			$args['referrals'] = $referral->create_many( 3, array(
				'affiliate_id' => $args['affiliate_id']
			) );
		}

		return affiliate_wp()->affiliates->payouts->add( $args );
	}

	/**
	 * Stub out copy of parent method for IDE type hinting purposes.
	 *
	 * @param array $args
	 * @param null  $generation_definitions
	 *
	 * @return \AffWP\Affiliate\Payout|int
	 */
	function create_and_get( $args = array(), $generation_definitions = null ) {
		return parent::create_and_get( $args, $generation_definitions );
	}

	function update_object( $payout_id, $fields ) {
		return affiliate_wp()->affiliates->payouts->update( $payout_id, $fields, '', 'payout' );
	}

	/**
	 * Stub out copy of parent method for IDE type hinting purposes.
	 *
	 * @param $referral_id
	 *
	 * @return \AffWP\Affiliate\Payout|false
	 */
	function get_object_by_id( $payout_id ) {
		return affwp_get_referral( $payout_id );
	}
}
