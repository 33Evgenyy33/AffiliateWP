<?php
namespace AffWP\Affiliate\Functions;

use AffWP\Tests\UnitTestCase;
use AffWP\Affiliate;

/**
 * Tests for Affiliate functions in affiliate-functions.php.
 *
 * @group affiliates
 * @group functions
 */
class Tests extends UnitTestCase {

	/**
	 * Random string.
	 *
	 * @access protected
	 * @var string
	 */
	protected $rand_str = '';

	/**
	 * Random valid email string.
	 *
	 * @access protected
	 * @var string
	 */
	protected $rand_email = '';

	/**
	 * Users fixture.
	 *
	 * @access protected
	 * @var array
	 * @static
	 */
	protected static $users = array();

	/**
	 * Affiliates fixture.
	 *
	 * @access protected
	 * @var array
	 * @static
	 */
	protected static $affiliates = array();

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {

		self::$users = parent::affwp()->user->create_many( 3 );

		foreach ( self::$users as $user ) {
			self::$affiliates[] = parent::affwp()->affiliate->create( array(
				'user_id' => $user
			) );
		}

	}

	public function setUp() {
		parent::setUp();

		$this->rand_str = rand_str( 5 );

		$this->rand_email = $this->generate_email();
	}

	public function tearDown() {
		// Clean up.
		affiliate_wp()->settings->set( array( 'referral_rate_type' => 'percentage' ), true );

		parent::tearDown();
	}

	//
	// Tests
	//

	/**
	 * @covers ::affwp_is_affiliate()
	 */
	public function test_is_affiliate_with_invalid_user_id_should_return_false() {
		$this->assertFalse( affwp_is_affiliate() );
	}

	/**
	 * @covers ::affwp_is_affiliate()
	 */
	public function test_is_affiliate_with_real_user_should_return_true() {
		$this->assertTrue( affwp_is_affiliate( self::$users[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_id()
	 */
	public function test_get_affiliate_id_with_invalid_user_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_id() );
	}

	/**
	 * @covers ::affwp_get_affiliate_id()
	 */
	public function test_get_affiliate_id_with_real_user_should_return_a_real_affiliate_id() {
		$this->assertEquals( self::$affiliates[0], affwp_get_affiliate_id( self::$users[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_username()
	 */
	public function test_get_affiliate_username_with_invalid_user_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_username() );
	}

	/**
	 * @covers ::affwp_get_affiliate_username()
	 */
	public function test_get_affiliate_username_with_valid_user_should_return_username() {
		$user = get_user_by( 'id', self::$users[0] );

		$this->assertEquals( $user->data->user_login, affwp_get_affiliate_username( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_name()
	 */
	public function test_affwp_get_affiliate_name_should_equal_empty_string() {
		$this->assertSame( '', affwp_get_affiliate_name() );
	}

	/**
	 * @covers ::affwp_get_affiliate_name()
	 */
	public function test_affwp_get_affiliate_name_should_default_to_empty_string() {
		$this->assertSame( '', affwp_get_affiliate_name() );
	}

	/**
	 * @covers ::affwp_get_affiliate_name()
	 */
	public function test_get_affiliate_name_with_no_user_id_should_return_empty_string() {
		$affiliate_id = $this->factory->affiliate->create( array(
			'user_id' => $this->factory->user->create()
		) );

		affwp_update_affiliate( array(
			'affiliate_id' => $affiliate_id,
			'user_id'      => 0
		) );

		$this->assertSame( '', affwp_get_affiliate_name( $affiliate_id ) );

		// Clean up.
		affwp_delete_affiliate( $affiliate_id );
	}

	/**
	 * @covers ::affwp_get_affiliate_name()
	 */
	public function test_affwp_get_affiliate_name_should_return_first_name_last_name() {
		update_user_meta( self::$users[0], 'first_name', 'Alf' );
		update_user_meta( self::$users[0], 'last_name', 'Alferson' );

		$this->assertSame( 'Alf Alferson', affwp_get_affiliate_name( self::$affiliates[0] ) );

		// Clean up.
		update_user_meta( self::$users[0], 'first_name', '' );
		update_user_meta( self::$users[0], 'last_name', '' );
	}

	/**
	 * @covers ::affwp_get_affiliate_name()
	 */
	public function test_affwp_get_affiliate_name_should_return_first_name() {
		update_user_meta( self::$users[1], 'first_name', 'Alf' );

		$this->assertSame( 'Alf', affwp_get_affiliate_name( self::$affiliates[1] ) );

		// Clean up.
		update_user_meta( self::$users[1], 'first_name', '' );
	}

	/**
	 * @covers ::affwp_get_affiliate_name()
	 */
	public function test_affwp_get_affiliate_name_should_return_last_name() {
		update_user_meta( self::$users[2], 'last_name', 'Alferson' );

		$this->assertSame( 'Alferson', affwp_get_affiliate_name( self::$affiliates[2] ) );

		// Clean up.
		update_user_meta( self::$users[2], 'last_name', '' );
	}

	/**
	 * @covers ::affwp_is_active_affiliate()
	 */
	public function test_is_active_affiliate_with_invalid_user_should_return_false() {
		$this->assertFalse( affwp_is_active_affiliate() );
	}

	/**
	 * @covers ::affwp_is_active_affiliate()
	 */
	public function test_is_active_affiliate_with_valid_user_should_return_true() {
		$this->assertTrue( affwp_is_active_affiliate( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_user_id()
	 */
	public function test_get_affiliate_user_id_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_user_id( 0 ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_user_id()
	 */
	public function test_get_affiliate_user_id_with_valid_affiliate_id_should_return_valid_user_id() {
		$this->assertEquals( self::$users[0], affwp_get_affiliate_user_id( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_user_id()
	 */
	public function test_get_affiliate_user_id_with_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_user_id( affwp_get_affiliate() ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_user_id()
	 */
	public function test_get_affiliate_user_id_with_valid_affiliate_object_should_return_valid_user_id() {
		$affiliate = affwp_get_affiliate( self::$affiliates[0] );

		$this->assertSame( self::$users[0], affwp_get_affiliate_user_id( $affiliate ) );
	}

	/**
	 * @covers ::affwp_get_affiliate()
	 */
	public function test_get_affiliate_should_accept_an_affiliate_id() {
		$this->assertInstanceOf( 'AffWP\Affiliate', affwp_get_affiliate( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate()
	 */
	public function test_get_affiliate_should_accept_an_affiliate_object() {
		$affiliate = affiliate_wp()->affiliates->get_object( self::$affiliates[0] );
		$affiliate = affwp_get_affiliate( $affiliate );

		$this->assertInstanceOf( 'AffWP\Affiliate', $affiliate );
		$this->assertEquals( self::$affiliates[0], $affiliate->affiliate_id );
	}

	/**
	 * @covers ::affwp_get_affiliate()
	 */
	public function test_get_affiliate_passed_invalid_id_should_return_false() {
		$this->assertFalse( affwp_get_affiliate() );
	}

	/**
	 * @covers ::affwp_get_affiliate()
	 */
	public function test_get_affiliate_passed_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affwp_get_affiliate() );
	}

	/**
	 * @covers ::affwp_delete_affiliate()
	 */
	function test_delete_affiliate() {

		affwp_delete_affiliate( self::$affiliates[1] );

		// Re-retrieve following deletion.
		$affiliate = affwp_get_affiliate( self::$affiliates[1] );

		$this->assertFalse( $affiliate );
	}

	/**
	 * @covers ::affwp_get_affiliate_status()
	 */
	public function test_get_affiliate_status_passed_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_status( 1 ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_status()
	 */
	public function test_get_affiliate_status_passed_valid_affiliate_id_should_return_status() {
		$this->assertEquals( 'active', affwp_get_affiliate_status( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_status()
	 */
	public function test_get_affiliate_status_passed_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_status( affwp_get_affiliate() ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_status()
	 */
	public function test_get_affiliate_status_passed_valid_affiliate_object_should_return_status() {
		$affiliate = affwp_get_affiliate( self::$affiliates[0] );

		$this->assertEquals( 'active', affwp_get_affiliate_status( $affiliate ) );
	}

	/**
	 * @covers ::affwp_set_affiliate_status()
	 */
	public function test_set_affiliate_inactive_status() {
		$new_status = 'inactive';

		affwp_set_affiliate_status( self::$affiliates[0], $new_status );

		$this->assertEquals( $new_status, affwp_get_affiliate_status( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_set_affiliate_status()
	 */
	public function test_set_affiliate_pending_status() {
		$new_status = 'pending';

		affwp_set_affiliate_status( self::$affiliates[0], $new_status );

		$this->assertEquals( $new_status, affwp_get_affiliate_status( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_set_affiliate_status()
	 */
	public function test_set_affiliate_rejected_status() {
		$new_status = 'rejected';

		affwp_set_affiliate_status( self::$affiliates[0], $new_status );

		$this->assertEquals( $new_status, affwp_get_affiliate_status( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_status_label()
	 */
	function test_get_affiliate_status_label() {

		$this->assertEquals( 'Active', affwp_get_affiliate_status_label( self::$affiliates[0] ) );

		$this->assertTrue( affwp_set_affiliate_status( self::$affiliates[0], 'inactive' ) );

		$this->assertEquals( 'Inactive', affwp_get_affiliate_status_label( self::$affiliates[0] ) );

		$this->assertTrue( affwp_set_affiliate_status( self::$affiliates[0], 'pending' ) );

		$this->assertEquals( 'Pending', affwp_get_affiliate_status_label( self::$affiliates[0] ) );

		$this->assertTrue( affwp_set_affiliate_status( self::$affiliates[0], 'rejected' ) );

		$this->assertEquals( 'Rejected', affwp_get_affiliate_status_label( self::$affiliates[0] ) );

		$this->assertTrue( affwp_set_affiliate_status( self::$affiliates[0], 'active' ) );

		$this->assertEquals( 'Active', affwp_get_affiliate_status_label( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_should_accept_an_affiliate_id_and_default_to_percentage() {
		$this->assertSame( '0.2', affwp_get_affiliate_rate( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_should_accept_an_affiliate_object_and_default_to_percentage() {
		$object = affwp_get_affiliate( self::$affiliates[0] );

		$this->assertSame( '0.2', affwp_get_affiliate_rate( $object ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_formatted_true_should_return_formatted_by_percentage_type_by_default() {
		$this->assertSame( '20%', affwp_get_affiliate_rate( self::$affiliates[0], true ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_with_invalid_affiliate_id_should_default_to_default_rate() {
		$default_rate = affiliate_wp()->settings->get( 'referral_rate', 20 );
		$default_type = affiliate_wp()->settings->get( 'referral_rate_type', 'percentage' );

		if ( 'percentage' === affiliate_wp()->settings->get( 'referral_rate_type', 'percentage' ) ) {
			$default_rate = $default_rate / 100;
		}

		$this->assertEquals( $default_rate, affwp_get_affiliate_rate() );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_with_invalid_id_flat_rate_should_default_to_default_rate() {
		affiliate_wp()->settings->set( array( 'referral_rate_type' => 'flat' ) );

		$default_rate = affiliate_wp()->settings->get( 'referral_rate', 20 );

		$this->assertEquals( $default_rate, affwp_get_affiliate_rate() );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_with_invalid_affiliate_object_should_default_to_default_rate() {
		$default_rate = affiliate_wp()->settings->get( 'referral_rate', 20 );
		$default_type = affiliate_wp()->settings->get( 'referral_rate_type', 'percentage' );

		if ( 'percentage' === $default_type ) {
			$default_rate = $default_rate / 100;
		}

		$this->assertEquals( $default_rate, affwp_get_affiliate_rate( new \stdClass() ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_with_invalid_affiliate_object_flat_rate_should_default_to_default_rate() {
		affiliate_wp()->settings->set( array( 'referral_rate_type' => 'flat' ) );

		$default_rate = affiliate_wp()->settings->get( 'referral_rate', 20 );

		$this->assertEquals( $default_rate, affwp_get_affiliate_rate( new \stdClass() ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_with_invalid_affiliate_id_formatted_true_should_return_formatted_default_rate() {
		$default_rate = affiliate_wp()->settings->get( 'referral_rate', 20 );
		$default_type = affiliate_wp()->settings->get( 'referral_rate_type', 'percentage' );

		if ( 'percentage' === affiliate_wp()->settings->get( 'referral_rate_type', 'percentage' ) ) {
			$default_rate = $default_rate / 100;
		}

		$this->assertSame( affwp_format_rate( $default_rate ), affwp_get_affiliate_rate( 0, true ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_with_invalid_affiliate_id_flat_rate_formatted_true_should_return_formatted_default_rate() {
		affiliate_wp()->settings->set( array( 'referral_rate_type' => 'flat' ), true );

		$default_rate = affiliate_wp()->settings->get( 'referral_rate', 20 );

		$this->assertSame( affwp_format_rate( $default_rate, 'flat' ), affwp_get_affiliate_rate( 0, true ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_with_invalid_affiliate_object_formatted_true_should_return_formatted_default_rate() {
		$default_rate = affiliate_wp()->settings->get( 'referral_rate', 20 );

		if ( 'percentage' === affiliate_wp()->settings->get( 'referral_rate_type', 'percentage' ) ) {
			$default_rate = $default_rate / 100;
		}

		$this->assertSame( affwp_format_rate( $default_rate ), affwp_get_affiliate_rate( new \stdClass(), true ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_with_invalid_affiliate_object_flat_rate_formatted_true_should_return_formatted_default_rate() {
		affiliate_wp()->settings->set( array( 'referral_rate_type' => 'flat' ) );

		$default_rate = affiliate_wp()->settings->get( 'referral_rate', 20 );

		$this->assertSame( affwp_format_rate( $default_rate, 'flat' ), affwp_get_affiliate_rate( new \stdClass(), true ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_with_invalid_affiliate_id_and_product_rate_should_default_to_product_rate() {
		$default_type = affiliate_wp()->settings->get( 'referral_rate_type', 'percentage' );

		if ( 'percentage' === $default_type ) {
			$product_rate = 0.3;
		}

		$this->assertEquals( $product_rate, affwp_get_affiliate_rate( 0, false, 30 ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_with_invalid_affiliate_id_flat_rate_and_product_rate_should_default_to_product_rate() {
		affiliate_wp()->settings->set( array( 'referral_rate_type' => 'flat' ) );

		$product_rate = 30;

		$this->assertEquals( $product_rate, affwp_get_affiliate_rate( 0, false, $product_rate ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_with_invalid_affiliate_object_and_product_rate_should_default_to_product_rate() {
		$default_type = affiliate_wp()->settings->get( 'referral_rate_type', 'percentage' );

		if ( 'percentage' === affiliate_wp()->settings->get( 'referral_rate_type', 'percentage' ) ) {
			$product_rate = 0.3;
		}

		$this->assertEquals( $product_rate, affwp_get_affiliate_rate( new \stdClass(), false, 30 ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate()
	 */
	public function test_get_affiliate_rate_with_invalid_affiliate_object_flat_rate_and_product_rate_should_default_to_product_rate() {
		affiliate_wp()->settings->set( array( 'referral_rate_type' => 'flat' ) );

		$product_rate = 30;

		$this->assertEquals( $product_rate, affwp_get_affiliate_rate( new \stdClass(), false, $product_rate ) );
	}

	/**
	 * @covers ::affwp_affiliate_has_custom_rate()
	 */
	public function test_affiliate_has_custom_rate_passed_an_invalid_affiliate_id_should_always_return_false() {
		$this->assertFalse( affwp_affiliate_has_custom_rate() );
	}

	/**
	 * @covers ::affwp_affiliate_has_custom_rate()
	 */
	public function test_affiliate_has_custom_rate_passed_a_valid_affiliate_id_with_custom_rate_should_return_true() {
		$affiliate = affwp_update_affiliate( array(
			'affiliate_id' => self::$affiliates[0],
			'rate'         => '0.1'
		) );

		$this->assertTrue( affwp_affiliate_has_custom_rate( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_affiliate_has_custom_rate()
	 */
	public function test_affiliate_has_custom_rate_passed_a_valid_affiliate_id_without_custom_rate_should_return_false() {
		$this->assertFalse( affwp_affiliate_has_custom_rate( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate_type()
	 */
	public function test_get_affiliate_rate_type_default_should_be_percentage_type() {
		$this->assertSame( 'percentage', affwp_get_affiliate_rate_type() );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate_type()
	 */
	public function test_get_affiliate_rate_type_filtered_should_not_match_default_percentage_type() {
		add_filter( 'affwp_get_affiliate_rate_type', function() {
			return 'flat';
		} );

		$this->assertSame( 'flat', affwp_get_affiliate_rate_type() );
		$this->assertNotSame( 'percentage', affwp_get_affiliate_rate_type() );

		// Clean up to prevent polluting other tests.
		remove_all_filters( 'affwp_get_affiliate_rate_type' );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate_type()
	 */
	public function test_get_affiliate_rate_type_custom_rate_type_should_not_match_default_percentage_type() {
		add_filter( 'affwp_get_affiliate_rate_types', function( $types ) {
			$types['foobar'] = '';
			return $types;
		} );

		affwp_update_affiliate( array(
			'affiliate_id' => self::$affiliates[0],
			'rate_type'    => 'foobar',
			'rate'         => 'whatever'
		) );

		$this->assertSame( 'foobar', affwp_get_affiliate_rate_type( self::$affiliates[0] ) );
		$this->assertNotSame( 'percentage', affwp_get_affiliate_rate_type( self::$affiliates[0] ) );

		// Clean up to prevent polluting other tests.
		remove_all_filters( 'affwp_get_affiliate_rate_types' );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate_types()
	 */
	function test_get_affiliate_rate_types() {

		$this->assertArrayHasKey( 'percentage', affwp_get_affiliate_rate_types() );
		$this->assertArrayHasKey( 'flat', affwp_get_affiliate_rate_types() );
		$this->assertArrayNotHasKey( 'test', affwp_get_affiliate_rate_types() );

	}

	/**
	 * @covers ::affwp_get_affiliate_email()
	 */
	function test_get_affiliate_email() {

		$args = array(
			'affiliate_id'  => self::$affiliates[0],
			'account_email' => $this->rand_email
		);

		affwp_update_affiliate( $args );

		$this->assertEquals( $this->rand_email, affwp_get_affiliate_email( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_payment_email()
	 */
	function test_get_affiliate_payment_email() {

		$args = array(
			'affiliate_id'  => self::$affiliates[0],
			'payment_email' => $this->rand_email
		);

		affwp_update_affiliate( $args );

		$this->assertEquals( $this->rand_email, affwp_get_affiliate_payment_email( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_earnings()
	 */
	function test_get_affiliate_earnings() {

		$this->assertEquals( 0, affwp_get_affiliate_earnings( self::$affiliates[0] ) );

	}

	/**
	 * @covers ::affwp_get_affiliate_unpaid_earnings()
	 */
	function test_get_affiliate_unpaid_earnings() {

		$this->assertEquals( 0, affwp_get_affiliate_unpaid_earnings( self::$affiliates[0] ) );
		$this->assertEquals( '&#36;0.00', affwp_get_affiliate_unpaid_earnings( self::$affiliates[0], true ) );

	}

	/**
	 * @covers ::affwp_get_affiliate_area_page_id()
	 */
	function test_get_affiliate_area_page_id_should_match_setting() {
		$page_id_from_settings = affiliate_wp()->settings->get( 'affiliates_page' );
		$this->assertSame( $page_id_from_settings, affwp_get_affiliate_area_page_id() );
	}

	/**
	 * @covers ::affwp_get_affiliate_area_page_id()
	 */
	function test_get_affiliate_area_page_id_filtered_different_should_not_match_setting() {
		$page_id_from_settings = affiliate_wp()->settings->get( 'affiliates_page' );

		add_filter( 'affwp_affiliate_area_page_id', function() {
			return 100000;
		} );

		$page_id_from_helper = affwp_get_affiliate_area_page_id();

		$this->assertNotSame( $page_id_from_settings, $page_id_from_helper );

		// Clean up to prevent polluting other tests.
		remove_all_filters( 'affwp_affiliate_area_page_id' );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate_types()
	 */
	public function test_get_affiliate_rate_types_should_return_percentage_and_flat_defaults() {
		$types = affwp_get_affiliate_rate_types();

		$this->assertArrayHasKey( 'percentage', $types );
		$this->assertArrayHasKey( 'flat', $types );
		$this->assertArrayNotHasKey( $this->rand_str, $types );
	}

	/**
	 * @covers ::affwp_get_affiliate_rate_types()
	 */
	public function test_get_affiliate_rate_types_filtered_should_differ_from_defaults() {
		add_filter( 'affwp_get_affiliate_rate_types', function( $types ) {
			$types[ 'foobar' ] = '';
			return $types;
		} );

		$this->assertArrayHasKey( 'foobar', affwp_get_affiliate_rate_types() );

		// Clean up to prevent polluting other tests.
		remove_all_filters( 'affwp_get_affiliate_rate_types' );
	}

	/**
	 * @covers ::affwp_get_affiliate_email()
	 */
	public function test_get_affiliate_email_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_email( 1 ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_email()
	 */
	public function test_get_affiliate_email_with_valid_affiliate_id_should_return_email() {
		affwp_update_affiliate( array(
			'affiliate_id'  => self::$affiliates[0],
			'account_email' => $this->rand_email
		) );

		$this->assertSame( $this->rand_email, affwp_get_affiliate_email( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_email()
	 */
	public function test_get_affiliate_email_with_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_email( affwp_get_affiliate() ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_email()
	 */
	public function test_get_affiliate_email_with_valid_affiliate_object_should_return_email() {
		affwp_update_affiliate( array(
			'affiliate_id'  => self::$affiliates[0],
			'account_email' => $this->rand_email
		) );

		$affiliate = affwp_get_affiliate( self::$affiliates[0] );

		$this->assertSame( $this->rand_email, affwp_get_affiliate_email( $affiliate ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_email()
	 */
	public function test_get_affiliate_email_with_invalid_affiliate_id_and_default_not_false_should_return_default() {
		$this->assertSame( $this->rand_email, affwp_get_affiliate_email( 1, $this->rand_email ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_email()
	 */
	public function test_get_affiliate_email_with_invalid_affiliate_object_and_default_not_false_should_return_default() {
		$this->assertSame( $this->rand_email, affwp_get_affiliate_email( affwp_get_affiliate(), $this->rand_email ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_email()
	 */
	public function test_get_affiliate_email_with_invalid_email_should_return_false() {
		affwp_update_affiliate( array(
			'affiliate_id'  => self::$affiliates[0],
			'account_email' => $this->rand_str
		) );

		$this->assertFalse( affwp_get_affiliate_email( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_email()
	 */
	public function test_get_affiliate_email_with_invalid_email_and_default_not_false_should_return_default() {
		affwp_update_affiliate( array(
			'affiliate_id'  => self::$affiliates[0],
			'account_email' => $this->rand_str
		) );

		$this->assertSame( $this->rand_email, affwp_get_affiliate_email( self::$affiliates[0], $this->rand_email ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_email()
	 */
	public function test_get_affiliate_email_with_empty_email_should_return_false() {
		affwp_update_affiliate( array(
			'affiliate_id'  => self::$affiliates[0],
			'account_email' => ''
		) );

		$this->assertFalse( affwp_get_affiliate_email( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_email()
	 */
	public function test_get_affiliate_email_with_empty_email_and_default_not_false_should_return_default() {
		affwp_update_affiliate( array(
			'affiliate_id'  => self::$affiliates[0],
			'account_email' => ''
		) );

		$this->assertSame( $this->rand_email, affwp_get_affiliate_email( self::$affiliates[0], $this->rand_email ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_payment_email()
	 */
	public function test_get_affiliate_payment_email_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_payment_email( 1 ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_payment_email()
	 */
	public function test_get_affiliate_payment_email_with_valid_affiliate_id_should_return_email() {
		affwp_update_affiliate( array(
			'affiliate_id'  => self::$affiliates[0],
			'payment_email' => $this->rand_email
		) );

		$this->assertSame( $this->rand_email, affwp_get_affiliate_payment_email( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_payment_email()
	 */
	public function test_get_affiliate_payment_email_with_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_payment_email( affwp_get_affiliate() ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_payment_email()
	 */
	public function test_get_affiliate_payment_email_with_valid_affiliate_object_should_return_email() {
		affwp_update_affiliate( array(
			'affiliate_id'  => self::$affiliates[0],
			'payment_email' => $this->rand_email
		) );

		$affiliate = affwp_get_affiliate( self::$affiliates[0] );

		$this->assertSame( $this->rand_email, affwp_get_affiliate_payment_email( $affiliate ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_payment_email()
	 */
	public function test_get_affiliate_payment_email_with_empty_email_should_return_account_email() {
		affwp_update_affiliate( array(
			'affiliate_id'  => self::$affiliates[0],
			'payment_email' => '',
			'account_email' => $this->rand_email
		) );

		$this->assertSame( $this->rand_email, affwp_get_affiliate_payment_email( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_payment_email()
	 */
	public function test_get_affiliate_payment_email_with_invalid_email_should_return_account_email() {
		affwp_update_affiliate( array(
			'affiliate_id'  => self::$affiliates[0],
			'payment_email' => rand_str( 5 ),
			'account_email' => $this->rand_email
		) );

		$this->assertSame( $this->rand_email, affwp_get_affiliate_payment_email( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_payment_email()
	 */
	public function test_get_affiliate_payment_emaik_with_both_invalid_emails_should_return_false() {
		affwp_update_affiliate( array(
			'affiliate_id'  => self::$affiliates[0],
			'payment_email' => rand_str( 5 ),
			'account_email' => rand_str( 5 )
		) );

		$this->assertFalse( affwp_get_affiliate_payment_email( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_login()
	 */
	public function test_get_affiliate_login_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_login( 1 ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_login()
	 */
	public function test_get_affiliate_login_with_valid_affiliate_id_should_return_login() {
		$user_id = affwp_get_affiliate_user_id( self::$affiliates[0] );
		$user    = get_userdata( $user_id );

		$this->assertSame( $user->data->user_login, affwp_get_affiliate_login( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_login()
	 */
	public function test_get_affiliate_login_with_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_login( affwp_get_affiliate() ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_login()
	 */
	public function test_get_affiliate_login_with_valid_affiliate_object_should_return_login() {
		$user_id = affwp_get_affiliate_user_id( self::$affiliates[0] );
		$user    = get_userdata( $user_id );

		$this->assertSame( $user->data->user_login, affwp_get_affiliate_login( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_login()
	 */
	public function test_get_affiliate_login_with_invalid_affiliate_id_and_default_not_false_should_return_default() {
		$this->assertSame( $this->rand_str, affwp_get_affiliate_login( 1, $this->rand_str ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_login()
	 */
	public function test_get_affiliate_login_with_invalid_affiliate_object_and_default_not_false_should_return_default() {
		$this->assertSame( $this->rand_str, affwp_get_affiliate_login( affwp_get_affiliate(), $this->rand_str ) );
	}

	/**
	 * @covers ::affwp_delete_affiliate()
	 */
	public function test_delete_affiliate_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_delete_affiliate( null ) );
	}

	/**
	 * @covers ::affwp_delete_affiliate()
	 */
	public function test_delete_affiliate_with_valid_affiliate_id_should_return_true() {
		$this->assertTrue( affwp_delete_affiliate( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_delete_affiliate()
	 */
	public function test_delete_affiliate_with_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affwp_delete_affiliate( affwp_get_affiliate() ) );
	}

	/**
	 * @covers ::affwp_delete_affiliate()
	 */
	public function test_delete_affiliate_with_valid_affiliate_object_should_return_true() {
		$affiliate = affwp_get_affiliate( self::$affiliates[0] );

		$this->assertTrue( affwp_delete_affiliate( $affiliate ) );
	}

	/**
	 * @covers ::affwp_delete_affiliate()
	 */
	public function test_delete_affiliate_with_delete_data_true_should_delete_referrals() {
		affwp_increase_affiliate_referral_count( self::$affiliates[0] );
		affwp_increase_affiliate_referral_count( self::$affiliates[0] );

		$this->assertEquals( 2, affwp_get_affiliate_referral_count( self::$affiliates[0] ) );

		affwp_delete_affiliate( self::$affiliates[0], $delete_data = true );

		$referrals = affiliate_wp()->referrals->get_referrals( array(
			'affiliate_id' => self::$affiliates[0],
			'number'       => -1
		) );

		$this->assertEmpty( $referrals );
	}

	/**
	 * @covers ::affwp_delete_affiliate()
	 */
	public function test_delete_affiliate_with_delete_data_true_should_delete_visits() {
		affwp_increase_affiliate_visit_count( self::$affiliates[0] );
		affwp_increase_affiliate_visit_count( self::$affiliates[0] );

		$this->assertEquals( 2, affwp_get_affiliate_visit_count( self::$affiliates[0] ) );

		affwp_delete_affiliate( self::$affiliates[0], $delete_data = true );

		$visits = affiliate_wp()->visits->get_visits( array(
			'affiliate_id' => self::$affiliates[0],
			'number'       => -1
		) );

		$this->assertEmpty( $visits );
	}

	/**
	 * @covers ::affwp_delete_affiliate()
	 */
	public function test_delete_affiliate_with_delete_data_true_should_delete_meta() {
		$user_id = affwp_get_affiliate_user_id( self::$affiliates[0] );

		affwp_delete_affiliate( self::$affiliates[0] );

		$this->assertEmpty( get_user_meta( $user_id, 'affwp_referral_notifications' ) );
		$this->assertEmpty( get_user_meta( $user_id, 'affwp_promotion_method' ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_earnings()
	 */
	public function test_get_affiliate_earnings_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_earnings( null ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_earnings()
	 */
	public function test_get_affiliate_earnings_with_valid_affiliate_id_should_return_earnings() {
		$amount = '1000';
		affwp_increase_affiliate_earnings( self::$affiliates[0], $amount );

		$this->assertEquals( $amount, affwp_get_affiliate_earnings( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_earnings()
	 */
	public function test_get_affiliate_earnings_with_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_earnings( affwp_get_affiliate() ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_earnings()
	 */
	public function test_get_affiliate_earnings_with_valid_affiliate_object_should_return_earnings() {
		$amount = '1000';
		affwp_increase_affiliate_earnings( self::$affiliates[0], $amount );

		$this->assertEquals( $amount, affwp_get_affiliate_earnings( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_earnings()
	 */
	public function test_get_affiliate_earnings_empty_earnings_should_return_zero() {
		$this->assertEquals( 0, affwp_get_affiliate_earnings( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_earnings()
	 */
	public function test_get_affiliate_earnings_formatted_true_should_return_formatted_earnings() {
		affwp_increase_affiliate_earnings( self::$affiliates[0], '1000' );
		$this->assertEquals( '&#36;1,000.00', affwp_get_affiliate_earnings( self::$affiliates[0], $formatted = true ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_unpaid_earnings()
	 */
	public function test_get_affiliate_unpaid_earnings_with_invalid_affiliate_id_should_return_zero() {
		$this->assertEquals( 0, affwp_get_affiliate_unpaid_earnings( null ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_unpaid_earnings()
	 */
	public function test_get_affiliate_unpaid_earnings_with_valid_affiliate_id_should_return_unpaid_earnings() {
		$referrals = $this->factory->referral->create_many( 3, array(
			'affiliate_id' => self::$affiliates[0],
			'amount'       => '1000',
			'status'       => 'unpaid'
		) );

		$this->assertSame( 3000.0, affwp_get_affiliate_unpaid_earnings( self::$affiliates[0] ) );

		// Clean up.
		foreach ( $referrals as $referral ) {
			affwp_delete_referral( $referral );
		}
	}

	/**
	 * @covers ::affwp_get_affiliate_unpaid_earnings()
	 */
	public function test_get_affiliate_unpaid_earnings_with_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_unpaid_earnings( affwp_get_affiliate() ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_unpaid_earnings()
	 */
	public function test_get_affiliate_unpaid_earnings_with_valid_affiliate_object_should_return_unpaid_earnings() {
		$referrals = $this->factory->referral->create_many( 2, array(
			'affiliate_id' => self::$affiliates[0],
			'amount'       => '2000',
			'status'       => 'unpaid'
		) );

		$affiliate = affwp_get_affiliate( self::$affiliates[0] );
		$this->assertSame( 4000.0, affwp_get_affiliate_unpaid_earnings( $affiliate ) );

		// Clean up.
		foreach ( $referrals as $referral ) {
			affwp_delete_referral( $referral );
		}
	}

	/**
	 * @covers ::affwp_get_affiliate_unpaid_earnings()
	 */
	public function test_get_affiliate_unpaid_earnings_formatted_true_should_return_formatted_unpaid_earnings() {
		$referrals = $this->factory->referral->create_many( 3, array(
			'affiliate_id' => self::$affiliates[0],
			'amount'       => '50',
			'status'       => 'unpaid'
		) );

		$this->assertSame( '&#36;150.00', affwp_get_affiliate_unpaid_earnings( self::$affiliates[0], $formatted = true ) );

		// Clean up.
		foreach ( $referrals as $referral ) {
			affwp_delete_referral( $referral );
		}
	}

	/**
	 * @covers ::affwp_increase_affiliate_earnings()
	 */
	public function test_increase_affiliate_earnings_with_empty_amount_should_return_false() {
		$this->assertFalse( affwp_increase_affiliate_earnings( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_increase_affiliate_earnings()
	 */
	public function test_increase_affiliate_earnings_should_increase_earnings() {
		$current = affwp_get_affiliate_earnings( self::$affiliates[0] );

		// Increase.
		affwp_increase_affiliate_earnings( self::$affiliates[0], '10' );
		$this->assertEquals( $current + 10, affwp_get_affiliate_earnings( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_decrease_affiliate_earnings()
	 */
	public function test_decrease_affiliate_earnings_should_decrease_earnings() {
		$current = affwp_get_affiliate_earnings( self::$affiliates[0] );

		// Increase temporarily.
		affwp_increase_affiliate_earnings( self::$affiliates[0], '10' );

		// Decrease.
		affwp_decrease_affiliate_earnings( self::$affiliates[0], '10' );

		$this->assertEquals( $current, affwp_get_affiliate_earnings( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_referral_count()
	 */
	public function test_get_affiliate_referral_count_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_referral_count( null ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_referral_count()
	 */
	public function test_get_affiliate_referral_count_with_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_referral_count( affwp_get_affiliate() ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_referral_count()
	 */
	public function test_get_affiliate_referral_count() {
		$this->assertEquals( 0, affwp_get_affiliate_referral_count( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_increase_affiliate_referral_count()
	 */
	public function test_increase_affiliate_referral_count_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_increase_affiliate_referral_count() );
	}

	/**
	 * @covers ::affwp_increase_affiliate_referral_count()
	 */
	public function test_increase_affiliate_referral_count_should_increase_count() {
		$current = affwp_get_affiliate_referral_count( self::$affiliates[0] );

		// Increase.
		affwp_increase_affiliate_referral_count( self::$affiliates[0] );

		$this->assertEquals( ++$current, affwp_get_affiliate_referral_count( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_decrease_affiliate_referral_count()
	 */
	public function test_decrease_affiliate_referral_count_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_decrease_affiliate_referral_count( null ) );
	}

	/**
	 * @covers ::affwp_decrease_affiliate_referral_count()
	 */
	public function test_decrease_affiliate_referral_count_should_decrease_count() {
		$current = affwp_get_affiliate_referral_count( self::$affiliates[0] );

		// Increase temporarily.
		affwp_increase_affiliate_referral_count( self::$affiliates[0] );
		affwp_increase_affiliate_referral_count( self::$affiliates[0] );

		// Decrease.
		affwp_decrease_affiliate_referral_count( self::$affiliates[0] );

		$this->assertEquals( ++$current, affwp_get_affiliate_referral_count( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_visit_count()
	 */
	public function test_get_affiliate_visit_count_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_visit_count( null ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_visit_count()
	 */
	public function test_get_affiliate_visit_count_with_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_visit_count( affwp_get_affiliate() ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_visit_count()
	 */
	function test_get_affiliate_visit_count() {
		$this->assertEquals( 0, affwp_get_affiliate_visit_count( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_increase_affiliate_visit_count()
	 */
	public function test_increase_affiliate_visit_count_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_increase_affiliate_visit_count() );
	}

	/**
	 * @covers ::affwp_increase_affiliate_visit_count()
	 */
	public function test_increase_affiliate_visit_count_should_increase_count() {
		$current = affwp_get_affiliate_visit_count( self::$affiliates[0] );

		// ENHANCE!
		affwp_increase_affiliate_visit_count( self::$affiliates[0] );

		$new_count = affwp_get_affiliate_visit_count( self::$affiliates[0] );

		$this->assertNotEquals( $current, $new_count );
		$this->assertEquals( ++$current, $new_count );
	}

	/**
	 * @covers ::affwp_decrease_affiliate_visit_count()
	 */
	public function test_decrease_affiliate_visit_count_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_decrease_affiliate_visit_count() );
	}

	/**
	 * @covers ::affwp_decrease_affiliate_visit_count()
	 */
	public function test_decrease_affiliate_visit_count_should_decrease_count() {
		$current = affwp_get_affiliate_visit_count( self::$affiliates[0] );

		// Increase temporarily.
		affwp_increase_affiliate_visit_count( self::$affiliates[0] );

		// Decrease. Should be back to the current count.
		affwp_decrease_affiliate_earnings( $current, affwp_get_affiliate_visit_count( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_decrease_affiliate_visit_count()
	 */
	public function test_decrease_affiliate_visit_count_for_no_visits_should_return_false() {
		$this->assertFalse( affwp_decrease_affiliate_visit_count(), self::$affiliates[1] );
	}

	/**
	 * @covers ::affwp_get_affiliate_conversion_rate()
	 */
	public function test_get_affiliate_conversion_rate_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_conversion_rate( null ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_conversion_rate()
	 */
	public function test_get_affiliate_conversion_rate_with_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_conversion_rate( affwp_get_affiliate() ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_conversion_rate()
	 * @todo Add test for rounding
	 */
	public function test_get_affiliate_conversion_rate() {
		$this->assertEquals( '0%', affwp_get_affiliate_conversion_rate( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_campaigns()
	 */
	public function test_get_affiliate_campaigns_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_campaigns( null ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_campaigns()
	 */
	public function test_get_affiliate_campaigns_with_valid_affiliate_id_should_return_campaigns() {

	}

	/**
	 * @covers ::affwp_get_affiliate_campaigns()
	 */
	public function test_get_affiliate_campaigns_with_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_campaigns( affwp_get_affiliate() ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_campaigns()
	 */
	public function test_get_affiliate_campaigns_with_valid_affiliate_object_should_return_campaigns() {

	}

	/**
	 * @covers ::affwp_add_affiliate()
	 */
	public function test_add_affiliate_with_empty_status_should_inherit_active_status() {
		$this->assertSame( 'active', affwp_get_affiliate_status( self::$affiliates[0] ) );
	}

	/**
	 * @covers ::affwp_add_affiliate()
	 */
	public function test_add_affiliate_with_require_approval_setting_true_should_inherit_pending_status() {
		affiliate_wp()->settings->set( array (
			'require_approval' => true
		) );

		$affiliate = affwp_add_affiliate( array(
			'user_id' => $this->factory->user->create()
		) );

		$this->assertSame( 'pending', affwp_get_affiliate_status( $affiliate ) );

		// Clean up.
		affwp_delete_affiliate( $affiliate );
	}

	/**
	 * @covers ::affwp_add_affiliate()
	 */
	public function test_add_affiliate_with_invalid_user_id_should_return_false() {
		$this->assertFalse( affwp_add_affiliate( rand( 100, 300 ) ) );
	}

	/**
	 * @covers ::affwp_add_affiliate()
	 */
	public function test_add_affiliate_for_user_already_an_affiliate_should_return_false() {
		$this->assertFalse( affwp_add_affiliate( self::$users[0] ) );
	}

	/**
	 * @gcovers affwp_update_affiliate()
	 */
	public function test_update_affiliate_with_empty_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_update_affiliate() );
	}

	/**
	 * @covers ::affwp_update_affiliate()
	 */
	public function test_update_affiliate() {
		$affiliate_id = $this->factory->affiliate->create();

		$updated = affwp_update_affiliate( array(
			'affiliate_id'  => $affiliate_id,
			'rate'          => '20',
			'account_email' => $this->rand_email
		) );

		$this->assertTrue( $updated );

		// Clean up.
		affwp_delete_affiliate( $affiliate_id );
	}

	/**
	 * @covers ::affwp_update_profile_settings()
	 */
	public function test_update_profile_settings_with_no_logged_in_user_should_return_false() {
		$this->assertFalse( affwp_update_profile_settings() );
	}

	/**
	 * @covers ::affwp_update_profile_settings()
	 */
	public function test_update_profile_settings_with_empty_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_update_profile_settings() );
	}

	/**
	 * @covers ::affwp_update_profile_settings()
	 */
	public function test_update_profile_settings_with_non_matching_affiliate_id_and_missing_manage_affiliates_cap_should_return_false() {
		$user_id = affwp_get_affiliate_user_id( self::$affiliates[0] );
		wp_set_current_user( $user_id );

		$this->assertFalse( affwp_update_profile_settings( array(
			'affiliate_id' => self::$affiliates[1]
		) ) );
	}

	/**
	 * @covers ::affwp_update_profile_settings()
	 */
	public function test_update_profile_settings_with_manage_affiliates_cap_and_referral_notifications_meta_should_update_meta() {
		// Admins have 'manage_affiliates' cap.
		$user_id = $this->factory->user->create( array(
			'role' => 'administrator'
		) );
		$affiliate_id = affwp_add_affiliate( array(
			'user_id' => $user_id
		) );
		wp_set_current_user( $user_id );

		affwp_update_profile_settings( array(
			'affiliate_id'           => $affiliate_id,
			'referral_notifications' => true
		) );

		$this->assertEquals( 1, get_user_meta( $user_id, 'affwp_referral_notifications', true ) );

		// Clean up.
		affwp_delete_affiliate( $affiliate_id );
	}

	/**
	 * @covers ::affwp_update_profile_settings()
	 */
	public function test_update_profile_settings_with_manage_affiliates_cap_and_empty_referral_notifications_meta_should_delete_meta() {
		// Admins have 'manage_affiliates' cap.
		$user_id = $this->factory->user->create( array(
			'role' => 'administrator'
		) );
		$affiliate_id = affwp_add_affiliate( array(
			'user_id' => $user_id
		) );
		wp_set_current_user( $user_id );

		affwp_update_profile_settings( array(
			'affiliate_id' => $affiliate_id,
		) );

		$this->assertEmpty( get_user_meta( $user_id, 'affwp_referral_notifications', true ) );

		// Clean up.
		affwp_delete_affiliate( $affiliate_id );
	}

	/**
	 * @covers ::affwp_get_affiliate_referral_url()
	 * @todo Add separate tests for parameter combinations
	 */
	public function test_get_affiliate_referral_url_with_no_arguments_should_return_trailingslashed_home_url() {
		$this->assertSame( home_url( '/' ), affwp_get_affiliate_referral_url() );
	}

	/**
	 * @covers ::affwp_get_affiliate_base_url()
	 */
	public function test_get_affiliate_base_url_no_default_setting_should_return_trailingslashed_home_url() {
		$this->assertSame( home_url( '/' ), affwp_get_affiliate_base_url() );
	}

	/**
	 * @covers ::affwp_get_affiliate_base_url()
	 */
	public function test_get_affiliate_base_url_with_non_empty_default_setting_should_return_the_default() {
		$original = affiliate_wp()->settings->get( 'default_referral_url' );

		affiliate_wp()->settings->set( array(
			'default_referral_url' => 'https://affiliatewp.com'
		) );

		$this->assertSame( 'https://affiliatewp.com', affwp_get_affiliate_base_url() );

		// Clean up.
		affiliate_wp()->settings->set( array(
			'default_referral_url' => $original
		) );
	}

	/**
	 * @covers ::affwp_get_affiliate_base_url()
	 */
	public function test_get_affiliate_base_url_with_empty_GET_url_and_no_default_should_return_trailingslashed_home_url() {
		$_GET['url'] = '';

		$this->assertSame( home_url( '/' ), affwp_get_affiliate_base_url() );
	}

	/**
	 * @covers ::affwp_get_affiliate_base_url()
	 */
	public function test_get_affiliate_base_url_with_GET_url_and_no_default_should_return_its_value() {
		$_GET['url'] = 'https://affiliatewp.com';

		$this->assertSame( 'https://affiliatewp.com', affwp_get_affiliate_base_url() );
	}

	/**
	 * @covers ::affwp_get_affiliate_base_url()
	 */
	public function test_get_affiliate_base_url_with_GET_url_value_and_non_empty_default_should_return_the_default() {
		$original = affiliate_wp()->settings->get( 'default_referral_url' );

		$_GET['url'] = 'https://edd.com';

		affiliate_wp()->settings->set( array(
			'default_referral_url' => 'https://affiliatewp.com'
		) );

		$this->assertNotSame( 'https://edd.com', affwp_get_affiliate_base_url() );
		$this->assertSame( 'https://affiliatewp.com', affwp_get_affiliate_base_url() );

		// Clean up.
		affiliate_wp()->settings->set( array(
			'default_referral_url' => $original
		) );
	}

	/**
	 * @covers ::affwp_get_affiliate_area_page_url()
	 */
	function test_get_affiliate_area_page_url_should_match_settings() {
		$affiliates_page_id = affwp_get_affiliate_area_page_id();

		$this->assertSame( get_permalink( $affiliates_page_id ), affwp_get_affiliate_area_page_url() );
	}

	/**
	 * @covers ::affwp_get_affiliate_area_page_url()
	 */
	function test_get_affiliate_area_page_url_with_valid_tab_should_return_tab_url() {
		$affiliates_page_id = affwp_get_affiliate_area_page_id();

		$tab_url = add_query_arg( 'tab', 'stats', get_permalink( $affiliates_page_id ) );

		$this->assertSame( $tab_url, affwp_get_affiliate_area_page_url( 'stats' ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_area_page_url()
	 */
	function test_get_affiliate_area_page_url_with_invalid_tab_should_return_page_url() {
		$this->assertSame( affwp_get_affiliate_area_page_url(), affwp_get_affiliate_area_page_url( rand_str( 10 ) ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_area_page_url()
	 */
	function test_get_affiliate_area_page_url_with_empty_tab_should_return_page_url() {
		$this->assertSame( affwp_get_affiliate_area_page_url(), affwp_get_affiliate_area_page_url( '' ) );
	}

	/**
	 * Utility method to generate an email address.
	 *
	 * @since 1.8
	 */
	public function generate_email() {
		$first_part = rand_str( 5 );
		$domain     = rand_str( 5 );

		return "{$first_part}@{$domain}.dev";
	}

	/**
	 * @covers ::affwp_get_affiliate_payouts()
	 */
	public function test_get_affiliate_payouts_with_invalid_affiliate_id_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_payouts() );
	}

	/**
	 * @covers ::affwp_get_affiliate_payouts()
	 */
	public function test_get_affiliate_payouts_with_valid_affiliate_id_should_return_payouts() {
		$referrals = $this->factory->referral->create_many( 2, array(
			'affiliate_id' => self::$affiliates[0]
		) );

		$payouts = array();

		foreach ( $referrals as $referral )  {
			$payouts[] = $this->factory->payout->create_and_get( array(
				'affiliate_id' => self::$affiliates[0],
				'referrals'    => array( $referral )
			) );
		}

		$results = affwp_get_affiliate_payouts( self::$affiliates[0] );

		$this->assertEqualSets( $payouts, $results );

		// Clean up.
		foreach ( $referrals as $referral ) {
			affwp_delete_referral( $referral );
		}

		foreach ( $payouts as $payout ) {
			affwp_delete_payout( $payout );
		}
	}

	/**
	 * @covers ::affwp_get_affiliate_payouts()
	 */
	public function test_get_affiliate_payouts_with_invalid_affiliate_object_should_return_false() {
		$this->assertFalse( affwp_get_affiliate_payouts( new \stdClass() ) );
	}

	/**
	 * @covers ::affwp_get_affiliate_payouts()
	 */
	public function test_get_affiliate_payouts_with_valid_affiliate_object_should_return_payouts() {
		$affiliate = affwp_get_affiliate( self::$affiliates[1] );

		$referrals = $this->factory->referral->create_many( 2, array(
			'affiliate_id' => $affiliate->ID
		) );

		$payouts = array();

		foreach ( $referrals as $referral )  {
			$payouts[] = $this->factory->payout->create_and_get( array(
				'affiliate_id' => $affiliate->ID,
				'referrals'    => array( $referral )
			) );
		}

		$results = affwp_get_affiliate_payouts( $affiliate );

		$this->assertEqualSets( $payouts, $results );

		// Clean up.
		foreach ( $referrals as $referral ) {
			affwp_delete_referral( $referral );
		}

		foreach ( $payouts as $payout ) {
			affwp_delete_payout( $payout );
		}
	}
}
