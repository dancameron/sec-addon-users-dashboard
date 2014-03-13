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
		add_action( 'gb_router_generate_routes', array( get_class(), 'register_path_callback' ), 100, 1 );
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
	public static function register_path_callback( GB_Router $router ) {
		$args = array(
			'path' => trailingslashit( self::$edit_path ). '([^/]+)/?$',
			'query_vars' => array(
				self::EDIT_ACCOUNT_QUERY_VAR => 1
			),
			'title' => 'Edit Account',
			'page_arguments' => array( self::EDIT_ACCOUNT_QUERY_VAR ),
			'title_callback' => array( get_class(), 'get_title' ),
			'page_callback' => array( get_class(), 'on_edit_page' ),
			'access_callback' => array( get_class(), 'login_required' ),
			'template' => array(
				self::get_template_path().'/'.str_replace( '/', '-', self::$edit_path ).'.php', // non-default edit path
				self::get_template_path().'/form.php', // theme override
				self::get_template_path().'/merchant.php', // theme override
				SEC_PATH.'/views/public/merchant.php', // default
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

		$account = Group_Buying_Account::get_instance( $account_id );
		if ( is_a( $account, 'Group_Buying_Account' ) ) {
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
		include( SEC_PROFILES_REPORT_PATH . '/profile-mngt.php' );
	}

	public function get_title( $title ) {
		$title = get_the_title( self::$offer_id );
		return sprintf( self::__( "Edit: %s" ), $title );
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
		if ( isset( $_POST['gb_account_action'] ) && $_POST['gb_account_action'] == self::FORM_ACTION ) {
			$this->process_form_submission();
		}
	}

	private function process_form_submission() {
		error_log( 'post: ' . print_r( $_POST, TRUE ) );
	}

}