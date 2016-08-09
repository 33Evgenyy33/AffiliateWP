<?php
/**
 * 'Payouts' Admin
 *
 * @package    AffiliateWP\Admin\Payouts
 * @copyright  Copyright (c) 2014, Pippin Williamson
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      1.9
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

include AFFILIATEWP_PLUGIN_DIR . 'includes/admin/payouts/screen-options.php';

function affwp_payouts_admin() {

	$action = null;

	if ( isset( $_GET['action2'] ) && '-1' !== $_GET['action2'] ) {
		$action = $_GET['action2'];
	} elseif ( isset( $_GET['action'] ) && '-1' !== $_GET['action'] ) {
		$action = $_GET['action'];
	}

	if ( 'view_payout' === $action ) {
		include AFFILIATEWP_PLUGIN_DIR . 'includes/admin/payouts/view.php';
	} else {

		$payouts_table = new AffWP_Payouts_Table();
		$payouts_table->prepare_items();
?>
		<div class="wrap">
			<h2><?php _e( 'Payouts', 'affiliate-wp' ); ?></h2>
			<?php
			/**
			 * Fires at the top of the Payouts page (outside the form element).
			 *
			 * @since 1.9
			 */
			do_action( 'affwp_payouts_page_top' );
			?>
			<form id="affwp-affiliates-filter" method="get" action="<?php echo admin_url( 'admin.php?page=affiliate-wp' ); ?>">
				<input type="hidden" name="page" value="affiliate-wp-payouts" />

				<?php $payouts_table->views() ?>
				<?php $payouts_table->display() ?>
			</form>
			<?php
			/**
			 * Fires at the bottom of the Payouts page (outside the form element).
			 *
			 * @since 1.9
			 */
			do_action( 'affwp_affiliates_page_bottom' );
			?>
		</div>
<?php

	}

}

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * AffWP_Affiliates_Table Class
 *
 * Renders the Affiliates table on the Affiliates page
 *
 * @since 1.0
 */
class AffWP_Payouts_Table extends WP_List_Table {

	/**
	 * Default number of items to show per page
	 *
	 * @since 1.9
	 * @access public
	 * @var string
	 */
	public $per_page = 30;

	/**
	 * Total number of payouts found.
	 *
	 * @since 1.9
	 * @access public
	 * @var int
	 */
	public $total_count;

	/**
	 * Number of 'paid' payouts found.
	 *
	 * @var string
	 * @since 1.0
	 */
	public $paid_count;

	/**
	 *  Number of 'failed' payouts found
	 *
	 * @var string
	 * @since 1.0
	 */
	public $failed_count;

	/**
	 * Payouts table constructor.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;

		parent::__construct( array(
			'singular'  => 'payout',
			'plural'    => 'payouts',
			'ajax'      => false
		) );

		$this->get_payout_counts();
	}

	/**
	 * Retrieves the payout view types.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @return array $views All the views available.
	 */
	public function get_views() {
		$base         = admin_url( 'admin.php?page=affiliate-wp-payouts' );
		$current      = isset( $_GET['status'] ) ? $_GET['status'] : '';
		$total_count  = '&nbsp;<span class="count">(' . $this->total_count    . ')</span>';
		$paid_count   = '&nbsp;<span class="count">(' . $this->paid_count . ')</span>';
		$failed_count = '&nbsp;<span class="count">(' . $this->failed_count  . ')</span>';

		$views = array(
			'all'    => sprintf( '<a href="%s"%s>%s</a>', esc_url( remove_query_arg( 'status', $base ) ), $current === 'all' || $current == '' ? ' class="current"' : '', _x( 'All', 'payouts', 'affiliate-wp') . $total_count ),
			'paid'   => sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( 'status', 'paid', $base ) ), $current === 'paid' ? ' class="current"' : '', __( 'Paid', 'affiliate-wp') . $paid_count ),
			'failed' => sprintf( '<a href="%s"%s>%s</a>', esc_url( add_query_arg( 'status', 'failed', $base ) ), $current === 'failed' ? ' class="current"' : '', __( 'Failed', 'affiliate-wp') . $failed_count ),
		);

		return $views;
	}

	/**
	 * Retrieves the payouts table columns.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @return array $columns Array of all the payouts list table columns.
	 */
	public function get_columns() {
		$columns = array(
			'cb'            => '<input type="checkbox" />',
			'payout_id'     => __( 'Payout ID', 'affiliate-wp' ),
			'amount'        => _x( 'Amount', 'payout', 'affiliate-wp' ),
			'affiliate'     => __( 'Affiliate', 'affiliate-wp' ),
			'referrals'     => __( 'Referrals', 'affiliate-wp' ),
			'payout_method' => __( 'Payout Method', 'affiliate-wp' ),
			'status'        => _x( 'Status', 'payout', 'affiliate-wp' ),
			'date'          => _x( 'Date', 'payout', 'affiliate-wp' ),
			'actions'       => __( 'Actions', 'affiliate-wp' ),
		);

		/**
		 * Filters the payouts list table columns.
		 *
		 * @since 1.9
		 *
		 * @param array $columns List table columns.
		 */
		return apply_filters( 'affwp_payout_table_columns', $columns );
	}

	/**
	 * Retrieves the payouts table's sortable columns.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @return array Array of all the sortable columns
	 */
	public function get_sortable_columns() {
		return array(
			'payout_id'     => array( 'payout_id', false ),
			'amount'        => array( 'amount', false ),
			'affiliate'     => array( 'affiliate', false ),
			'payout_method' => array( 'payout_method', false ),
			'status'        => array( 'status', false ),
			'date'          => array( 'date', false ),
		);
	}

	/**
	 * Renders the checkbox column.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param \AffWP\Affiliate\Payout $payout Current payout object.
	 * @return string Checkbox markup.
	 */
	function column_cb( $payout ) {
		return '<input type="checkbox" name="payout_id[]" value="' . absint( $payout->ID ) . '" />';
	}

	/**
	 * Renders the 'Payout ID' column
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param \AffWP\Affiliate\Payout $payout Current payout object.
	 * @return string Payout ID.
	 */
	public function column_payout_id( $payout ) {
		$value = esc_html( $payout->ID );

		/**
		 * Filters the value of the 'Payout ID' column in the payouts list table.
		 *
		 * @since 1.9
		 *
		 * @param int                     $value  Payout ID.
		 * @param \AffWP\Affiliate\Payout $payout Current payout object.
		 */
		return apply_filters( 'affwp_payout_table_payout_id', $value, $payout );
	}

	/**
	 * Renders the 'Amount' column.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param \AffWP\Affiliate\Payout $payout Current payout object.
	 * @return string Payout ID.
	 */
	public function column_amount( $payout ) {
		$value = affwp_currency_filter( affwp_format_amount( $payout->amount ) );

		/**
		 * Filters the value of the 'Amount' column in the payouts list table.
		 *
		 * @since 1.9
		 *
		 * @param string                  $$value Formatted payout amount.
		 * @param \AffWP\Affiliate\Payout $payout Current payout object.
		 */
		return apply_filters( 'affwp_referral_table_amount', $value, $payout );
	}

	/**
	 * Renders the 'Affiliate' column.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param \AffWP\Affiliate\Payout $payout Current payout object.
	 * @return string Linked affiliate name and ID.
	 */
	function column_affiliate( $payout ) {
		$url = add_query_arg( array(
			'page'         => 'affiliate-wp-affiliates',
			'action'       => 'view_affiliate',
			'affiliate_id' => $payout->affiliate_id
		), admin_url( 'admin.php' ) );

		$name      = affiliate_wp()->affiliates->get_affiliate_name( $payout->affiliate_id );
		$affiliate = affwp_get_affiliate( $payout->affiliate_id );

		if ( $affiliate && $name ) {
			$value = sprintf( '<a href="%1$s">%2$s</a> (ID: %3$s)',
				esc_url( $url ),
				esc_html( $name ),
				esc_html( $affiliate->ID )
			);
		} else {
			$value = __( '(user deleted)', 'affiliate-wp' );
		}

		/**
		 * Filters the value of the 'Affiliate' column in the payouts list table.
		 *
		 * @since 1.9
		 *
		 * @param mixed                   $value  Column value.
		 * @param \AffWP\Affiliate\Payout $payout Current payout object.
		 */
		return apply_filters( 'affwp_payout_table_affiliate', $value, $payout );
	}

	/**
	 * Renders the 'Referrals' column.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param \AffWP\Affiliate\Payout $payout Current payout object.
	 * @return string Linked affiliate name and ID.
	 */
	public function column_referrals( $payout ) {
		$referrals = affiliate_wp()->affiliates->payouts->get_referral_ids( $payout );
		$links     = array();
		$base      = admin_url( 'admin.php?page=affiliate-wp-referrals&action=edit_referral&referral_id=' );

		foreach ( $referrals as $referral_id ) {
			$links[] = sprintf( '<a href="%1$s">%2$s</a>',
				esc_url( $base . $referral_id ),
				esc_html( $referral_id )
			);
		}

		$value = implode( ', ', $links );

		/**
		 * Filters the value of the 'Referrals' column in the payouts list table.
		 *
		 * @since 1.9
		 *
		 * @param mixed                   $value  Column value.
		 * @param \AffWP\Affiliate\Payout $payout Current payout object.
		 */
		return apply_filters( 'affwp_payout_table_referrals', $value, $payout );
	}

	/**
	 * Renders the 'Payout Method' column.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param \AffWP\Affiliate\Payout $payout Current payout object.
	 * @return string Payout method.
	 */
	public function column_payout_method( $payout ) {
		$value = esc_html( $payout->payout_method );

		/**
		 * Filters the value of the 'Payout Method' column in the payouts list table.
		 *
		 * @since 1.9
		 *
		 * @param string                  $value  Payout method.
		 * @param \AffWP\Affiliate\Payout $payout Current payout object.
		 */
		return apply_filters( 'affwp_payout_table_payout_method', $value, $payout );
	}

	/**
	 * Renders the 'Date' column.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param \AffWP\Affiliate\Payout $payout Current payout object.
	 * @return string Localized payout date.
	 */
	public function column_date( $payout ) {
		$value = date_i18n( get_option( 'date_format' ), strtotime( $payout->date ) );

		/**
		 * Filters the value of the 'Date' column in the payouts list table.
		 *
		 * @since 1.9
		 *
		 * @param string                  $value  Localized payout date.
		 * @param \AffWP\Affiliate\Payout $payout Current payout object.
		 */
		return apply_filters( 'affwp_payout_table_date', $value, $payout );
	}

	/**
	 * Renders the 'Actions' column.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @see WP_List_Table::row_actions()
	 *
	 * @param \AffWP\Affiliate\Payout $payout Current payout object.
	 * @return string Action links markup.
	 */
	function column_actions( $payout ) {

		$row_actions['view'] = '<a href="' . esc_url( add_query_arg( array( 'affwp_notice' => false, 'action' => 'view_payout', 'payout_id' => $payout->ID ) ) ) . '">' . __( 'View', 'affiliate-wp' ) . '</a>';

		if ( strtolower( $payout->status ) == 'failed' ) {
			$row_actions['retry'] = '<a href="' . wp_nonce_url( add_query_arg( array( 'affwp_notice' => 'payout_retried', 'action' => 'retry_payment', 'payout_id' => $payout->ID ) ), 'payout-nonce' ) . '">' . __( 'Retry Payment', 'affiliate-wp' ) . '</a>';
		}

		/**
		 * Filters the row actions for the payouts list table row.
		 *
		 * @since 1.9
		 *
		 * @param array                   $row_actions Row actions markup.
		 * @param \AffWP\Affiliate\Payout $payout      Current payout object.
		 */
		$row_actions = apply_filters( 'affwp_affiliate_row_actions', $row_actions, $payout );

		return $this->row_actions( $row_actions, true );
	}

	/**
	 * Renders the 'Status' column.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param \AffWP\Affiliate\Payout $payout Current payout object.
	 * @return string Payout status.
	 */
	public function column_status( $payout ) {
		$value = sprintf( '<span class="affwp-status %1$s"><i></i>%2$s</span>',
			esc_attr( $payout->status ),
			affwp_get_payout_status_label( $payout )
		);

		/**
		 * Filters the value of the 'Status' column in the payouts list table.
		 *
		 * @since 1.9
		 *
		 * @param string                  $value  Payout status.
		 * @param \AffWP\Affiliate\Payout $payout Current payout object.
		 */
		return apply_filters( 'affwp_referral_table_status', $value, $payout );
	}

	/**
	 * Renders the default output for a custom column in the payouts list table.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @param \AffWP\Affiliate\Payout $payout      Current payout object.
	 * @param string                  $column_name The name of the column.
	 * @return string Column name.
	 */
	function column_default( $payout, $column_name ) {
		$value = isset( $payout->$column_name ) ? $payout->$column_name : '';

		/**
		 * Filters the value of the default column in the payouts list table.
		 *
		 * The dynamic portion of the hook name, `$column_name`, refers to the column name.
		 *
		 * @since 1.9
		 *
		 * @param mixed                   $value  Column value.
		 * @param \AffWP\Affiliate\Payout $payout Current payout object.
		 */
		return apply_filters( 'affwp_payout_table_' . $column_name, $value, $payout );
	}

	/**
	 * Message to be displayed when there are no items.
	 *
	 * @since 1.9
	 * @access public
	 */
	function no_items() {
		_e( 'No payouts found.', 'affiliate-wp' );
	}

	/**
	 * Retrieves the bulk actions.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @return array $actions Array of the bulk actions.
	 */
	public function get_bulk_actions() {
		$actions = array(
			'retry_payment' => __( 'Retry Payment', 'affiliate-wp' ),
		);

		/**
		 * Filters the list of bulk actions for the payouts list table.
		 *
		 * @since 1.9
		 *
		 * @param array $actions Bulk actions.
		 */
		return apply_filters( 'affwp_payout_bulk_actions', $actions );
	}

	/**
	 * Processes the bulk actions.
	 *
	 * @since 1.9
	 * @access public
	 */
	public function process_bulk_action() {
		// @todo Hook up bulk actions.
	}

	/**
	 * Retrieves the payout counts.
	 *
	 * @since 1.9
	 * @access public
	 */
	public function get_payout_counts() {
		$this->paid_count   = affiliate_wp()->affiliates->payouts->count( array( 'status' => 'paid' ) );
		$this->failed_count = affiliate_wp()->affiliates->payouts->count( array( 'status' => 'failed' ) );
		$this->total_count  = $this->paid_count + $this->failed_count;
	}

	/**
	 * Retrieves all the data for all the payouts.
	 *
	 * @since 1.9
	 * @access public
	 *
	 * @return array Array of all the data for the payouts.
	 */
	public function payouts_data() {

		$page    = isset( $_GET['paged'] )    ? absint( $_GET['paged'] )          :      1;
		$status  = isset( $_GET['status'] )   ? sanitize_key( $_GET['status'] )   :     '';
		$order   = isset( $_GET['order'] )    ? sanitize_key( $_GET['order'] )    : 'DESC';
		$orderby = isset( $_GET['orderby'] )  ? sanitize_key( $_GET['orderby'] )  : 'payout_id';

		$per_page = $this->get_items_per_page( 'affwp_edit_payouts_per_page', $this->per_page );

		$payouts = affiliate_wp()->affiliates->payouts->get_payouts( array(
			'number'  => $per_page,
			'offset'  => $per_page * ( $page - 1 ),
			'status'  => $status,
			'orderby' => $orderby,
			'order'   => $order
		) );
		return $payouts;
	}

	/**
	 * Sets up the final data for the payouts list table.
	 *
	 * @since 1.9
	 * @access public
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'affwp_edit_payouts_per_page', $this->per_page );

		$columns = $this->get_columns();

		$hidden = array();

		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$current_page = $this->get_pagenum();

		$status = isset( $_GET['status'] ) ? $_GET['status'] : 'any';

		switch( $status ) {
			case 'paid':
				$total_items = $this->paid_count;
				break;
			case 'failed':
				$total_items = $this->failed_count;
				break;
			case 'any':
				$total_items = $this->total_count;
				break;
		}

		$this->items = $this->payouts_data();

		$this->set_pagination_args( array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page )
			)
		);
	}
}
