<?php 

/**
* Periodically send the SS report to a few users.
*/
class SEC_Report_User_Profiles extends Group_Buying_Controller {
	const EDIT_PATH_OPTION = 'sec_accounts_edit_path';
	const EDIT_QUERY_VAR = 'sec_accounts_edit';
	const EDIT_ACCOUNT_QUERY_VAR = 'sec_edit_account';
	const FORM_ACTION = 'sec_accounts_edit';
	private static $edit_path = 'account/mngt';
	private static $account_id;
	private static $account;
	private static $instance;
	
	public static function init() {
		self::$edit_path = get_option( self::EDIT_PATH_OPTION, self::$edit_path );
		self::register_settings();
		add_action( 'gb_router_generate_routes', array( get_class(), 'register_route_callback' ), 100, 1 );

		add_action( 'wp_ajax_nopriv_sec_deactivate_voucher',  array( get_class(), 'maybe_deactivate_voucher' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_sec_activate_voucher',  array( get_class(), 'maybe_activate_voucher' ), 10, 0 );
		add_action( 'wp_ajax_sec_deactivate_voucher',  array( get_class(), 'maybe_deactivate_voucher' ), 10, 0 );
		add_action( 'wp_ajax_sec_activate_voucher',  array( get_class(), 'maybe_activate_voucher' ), 10, 0 );
	}

	/**
	 * Hooked on init add the settings page and options.
	 *
	 */
	public static function register_settings() {
		// Settings
		$settings = array(
			self::EDIT_PATH_OPTION => array(
				'weight' => 124,
				'settings' => array(
					self::EDIT_PATH_OPTION => array(
						'label' => self::__( 'Merchant Edit Profile Path' ),
						'option' => array(
							'label' => trailingslashit( get_home_url() ),
							'type' => 'text',
							'default' => self::$edit_path
							)
						)
					)
				)
			);
		do_action( 'gb_settings', $settings, Group_Buying_UI::SETTINGS_PAGE );
	}

	/**
	 * Register the path callback for the edit page
	 *
	 * @static
	 * @param GB_Router $router
	 * @return void
	 */
	public static function register_route_callback( GB_Router $router ) {
		$args = array(
			'path' => trailingslashit( self::$edit_path ). '([^/]+)/?$',
			'query_vars' => array(
				self::EDIT_ACCOUNT_QUERY_VAR => 1
			),
			'title' => 'Edit Account',
			'page_arguments' => array( self::EDIT_ACCOUNT_QUERY_VAR ),
			'title_callback' => array( get_class(), 'get_title' ),
			'page_callback' => array( get_class(), 'on_edit_page' ),
			'access_callback' => array( get_class(), 'access_restriction' ),
			'template' => array(
				self::get_template_path().'/'.str_replace( '/', '-', self::$edit_path ).'.php', // non-default edit path
				self::get_template_path().'/form.php', // theme override
				self::get_template_path().'/merchant.php', // theme override
				GB_PATH.'/views/public/merchant.php', // default
				SEC_PROFILES_REPORT_PATH . '/views/profile-mngt.php', // default
			),
		);
		$router->add_route( self::EDIT_QUERY_VAR, $args );
	}

	/**
	 *
	 *
	 * @static
	 * @return string The URL to the edit deal page
	 */
	public static function get_url( $account_id = null ) {
		if ( !$account_id )
			return '';

		if ( self::using_permalinks() ) {
			return trailingslashit( home_url() ).trailingslashit( self::$edit_path ).trailingslashit( $account_id );
		} else {
			$router = GB_Router::get_instance();
			return $router->get_url( self::EDIT_QUERY_VAR, array( self::EDIT_ACCOUNT_QUERY_VAR => $account_id ) );
		}
	}

	public function access_restriction() {
		if ( current_user_can( 'delete_posts' ) || apply_filters( 'merchant_can_manage_profiles', FALSE ) ) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * We're on the edit deal page
	 *
	 * @static
	 * @return void
	 */
	public static function on_edit_page( $account_id = 0 ) {
		// by instantiating, we process any submitted values
		$edit_page = self::get_instance();

		if ( !$account_id ) {
			wp_redirect( sec_get_users_report_url() );
			exit();
		}

		self::$account_id = $account_id;
		$account = Group_Buying_Account::get_instance_by_id( $account_id );
		if ( is_a( $account, 'Group_Buying_Account' ) ) {
			self::$account = $account;
			// display the edit form
			$edit_page->view_mngt_profile();
			return;
		}
		wp_redirect( sec_get_users_report_url() );
		exit();
	}

	/**
	 * View the page
	 *
	 * @return void
	 */
	public function view_mngt_profile() {
		remove_filter( 'the_content', 'wpautop' );

		// Timepicker
		wp_enqueue_script( 'gb_frontend_deal_submit' );
		wp_enqueue_style( 'gb_frontend_deal_submit_timepicker_css' );

		$account = self::$account;

		// Credits
		$types = apply_filters( 'gb_account_credit_types', array() );
		$credit_fields = array();
		foreach ( $types as $key => $label ) {
			$credit_fields[$key] = array(
				'balance' => $account->get_credit_balance( $key ),
				'label' => $label,
			);
		}
		$credit_types = apply_filters( 'gb_account_meta_box_credit_types', $credit_fields, $account );
		// Args for template
		$args = array(
			'account' => $account,
			'first_name' => $account->get_name( 'first' ),
			'last_name' => $account->get_name( 'last' ),
			'street' => isset( $address['street'] )?$address['street']:'',
			'city' => isset( $address['city'] )?$address['city']:'',
			'zone' => isset( $address['zone'] )?$address['zone']:'',
			'postal_code' => isset( $address['postal_code'] )?$address['postal_code']:'',
			'country' => isset( $address['country'] )?$address['country']:'',
			'mobile' => $account->get_name( 'first' ),
			'purchases' => Group_Buying_Purchase::get_purchases( array( 'account' => $account->get_ID() ) ),
			'credit_types' => $credit_fields
			);
		if ( defined( 'Registration_Fields::MOBILE' ) ) {
			$args['mobile_code'] = get_post_meta( $account->get_ID(), '_'.Registration_Fields::MOBILE_CODE, TRUE );
			$args['mobile'] = get_post_meta( $account->get_ID(), '_'.Registration_Fields::MOBILE, TRUE );
		}
		$args = apply_filters( 'load_view_args_profile-mngt', $args );
		if ( !empty( $args ) ) extract( $args );

		// Show the form
		include( SEC_PROFILES_REPORT_PATH . '/views/profile-mngt-forms.php' );
	}

	public function get_title( $title ) {
		$account = Group_Buying_Account::get_instance_by_id( self::$account_id );
		$account_name = $account->get_name();
		$name = ( strlen( $account_name ) <= 1  ) ? get_the_title( $account->get_ID() ) : $account_name;
		return sprintf( self::__( "Account: %s" ), $name );
	}

	/**
	 *
	 *
	 * @static
	 * @return bool Whether the current query is a edit page
	 */
	public static function is_mngt_profile() {
		return GB_Router_Utility::is_on_page( self::EDIT_QUERY_VAR );
	}

	/*
	 * Singleton Design Pattern
	 * ------------------------------------------------------------- */
	private function __clone() {
		// cannot be cloned
		trigger_error( __CLASS__.' may not be cloned', E_USER_ERROR );
	}
	private function __sleep() {
		// cannot be serialized
		trigger_error( __CLASS__.' may not be serialized', E_USER_ERROR );
	}
	public static function get_instance() {
		if ( !( self::$instance && is_a( self::$instance, __CLASS__ ) ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		self::do_not_cache();
		if ( isset( $_POST['profile_mngt_form_nonce'] ) && wp_verify_nonce( $_POST['profile_mngt_form_nonce'], 'sec_form_action' ) ) {
			$this->process_form_submission();
		}
	}

	private function process_form_submission() {
		if ( isset( $_POST['mngt_account_id'] ) ) {
			$account = Group_Buying_Account::get_instance_by_id( $_POST['mngt_account_id'] );
		}
		if ( is_a( $account, 'Group_Buying_Account' ) ) {
			// save mobile
			if ( defined( 'Registration_Fields::MOBILE' ) ) {
				if ( isset( $_POST['account_mobile'] ) ) {
					$phone = preg_replace( '/[^0-9]/', '', $_POST['account_mobile'] );
					delete_post_meta( $account->get_ID(), '_'.Registration_Fields::MOBILE );
					add_post_meta( $account->get_ID(), '_'.Registration_Fields::MOBILE, $phone );
				}
			}
			// save credit updates
			if ( is_callable( array( 'Group_Buying_Accounts', 'save_meta_box_gb_account_credits' ) ) ) {
				Group_Buying_Accounts::save_meta_box_gb_account_credits( $account, $account->get_id(), '' );
			} 
			else {
				self::_save_meta_box_gb_account_credits( $account, $account->get_id() );
			}
			
		}
	}

	private static function _save_meta_box_gb_account_credits( Group_Buying_Account $account ) {
		if ( isset( $_POST['account_credit_balance'] ) && is_array( $_POST['account_credit_balance'] ) ) {
			$types = array_keys( apply_filters( 'gb_account_credit_types', array() ) );
			foreach ( $_POST['account_credit_balance'] as $key => $value ) {
				if ( in_array( $key, $types ) && is_numeric( $value ) ) {
					$balance = $account->get_credit_balance( $key );
					switch ( $_POST['account_credit_action'][$key] ) {
					case 'add':
						$total = $balance+$value;
						break;
					case 'deduct':
						$total = $balance-$value;
						break;
					case 'change':
						$total = $value;
						break;
					}
					$account->set_credit_balance( $total, $key );
					$data = array();
					$data['note'] = $_POST['account_credit_notes'][$key];
					$data['adjustment_value'] = $value;
					$data['current_total'] = $total;
					$data['prior_total'] = $balance;
					do_action( 'gb_new_record', $data, Group_Buying_Accounts::$record_type . '_' . $key, gb__( 'Credit Adjustment' ), get_current_user_id(), $account->get_ID() );
					do_action( 'gb_save_meta_box_gb_account_credits', $account, 0, $_POST );
				}
			}
		}
	}



	public static function maybe_deactivate_voucher() {

		if ( !isset( $_REQUEST['deactivate_voucher_nonce'] ) )
			wp_die( 'Forget something?' );

		$nonce = $_REQUEST['deactivate_voucher_nonce'];
		if ( !wp_verify_nonce( $nonce, Group_Buying_Destroy::NONCE ) )
        	wp_die( 'Not going to fall for it!' );

        if ( current_user_can( 'delete_posts' ) || apply_filters( 'merchant_can_manage_profiles', FALSE ) ) {

			$voucher_id = $_REQUEST['voucher_id'];
			$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
			if ( !is_a( $voucher, 'Group_Buying_Voucher' ) )
				return;

			if ( $voucher->is_active() ) {
				$voucher->mark_pending();
				do_action( 'gb_voucher_deactivated', $voucher_id );
			}
		}
	}

	public static function maybe_activate_voucher() {

		if ( !isset( $_REQUEST['activate_voucher_nonce'] ) )
			wp_die( 'Forget something?' );

		$nonce = $_REQUEST['activate_voucher_nonce'];
		if ( !wp_verify_nonce( $nonce, Group_Buying_Destroy::NONCE ) )
        	wp_die( 'Not going to fall for it!' );

        if ( current_user_can( 'delete_posts' ) || apply_filters( 'merchant_can_manage_profiles', FALSE ) ) {

			$voucher_id = $_REQUEST['voucher_id'];
			$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
			if ( !is_a( $voucher, 'Group_Buying_Voucher' ) )
				return;

			if ( $voucher->is_active() ) {
				$voucher->activate();
				do_action( 'gb_voucher_activated', $voucher_id );
			}
		}
	}

}