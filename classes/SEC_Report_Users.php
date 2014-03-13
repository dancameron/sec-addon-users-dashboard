<?php

class SEC_Report_Users extends Group_Buying_Controller {
	const REPORT_SLUG = 'account_profiles';
	const GIFT_TERM = 'gift';

	public static function init() {
		parent::init();
		
		// Create Report
		add_action( 'gb_reports_set_data', array( get_class(), 'create_report' ) );

		// Filter title
		add_filter( 'gb_reports_get_title', array( get_class(), 'filter_title' ), 10, 2 );

		// Add the navigation
		add_action( 'gb_report_view', array( get_class(), 'add_navigation' ), 1000 );

		// Enqueue
		add_action( 'init', array( get_class(), 'enqueue' ) );
	}

	public function enqueue() {
		// Timepicker
		wp_enqueue_script( 'gb_timepicker' );
		wp_enqueue_style( 'gb_frontend_jquery_ui_style' );
	}

	public function add_navigation() {
		if ( isset( $_GET['report'] ) && $_GET['report'] == self::REPORT_SLUG ) {
			include SEC_PROFILES_REPORT_PATH . '/views/navigation.php';
		}
	}

	public function filter_title( $title, $report ) {
		if ( $report == self::REPORT_SLUG ) {
			return self::__('Account Profiles');
		}
		return $title;
	}

	public function create_report( $report = TRUE ) {

		if ( $report->report != self::REPORT_SLUG )
			return;

		$report->csv_available = TRUE;

		$report->columns = apply_filters( 'set_account_profiles_report_data_column', self::get_profile_summary_columns() );

		$filter = ( isset( $_GET['filter']  && in_array( $_GET['filter'], array( 'any', 'publish', 'draft', 'private', 'trash' ) ) ) ? $_GET['filter'] : 'publish';

		$start_time = ( isset( $_REQUEST['account_profiles_start_date'] ) && strtotime( $_REQUEST['account_profiles_start_date'] ) <= current_time( 'timestamp' ) ) ? $_REQUEST['account_profiles_start_date'] : date( 'm/d/Y', current_time( 'timestamp' )-31536000 );
  		$end_time = ( isset( $_REQUEST['account_profiles_end_date'] ) && strtotime( $_REQUEST['account_profiles_end_date'] ) <= current_time( 'timestamp' ) ) ? $_REQUEST['account_profiles_end_date'] : date( 'm/d/Y', current_time( 'timestamp' ) );

		$report->records = apply_filters( 'set_account_profiles_report_records', self::get_purchase_array( gb_account_merchant_id(), $start_time, $end_time, $filter ) );
	}

	public function get_profile_summary_columns() {
		/**
		 * Setup Columns
		 * @var array
		 */
		$columns = array(
			'name' => self::__( 'Name' ),
			'purchase_total' => self::__( 'Orders' ),
			'purchase_offers_total' => self::__( 'Purchased' ),
			'rewards' => self::__( 'Rewards' ),
			'gift' => self::__( 'Gift' )
		);
		if ( class_exists( 'Registration_Fields' ) && defined( 'Registration_Fields::MOBILE_CODE' ) ) {
			$columns['mobile'] = self::__( 'Mobile' );
		}
		return $columns;
	}

	public function get_purchase_array( $account_merchant_id, $start_time = 'm/d/Y', $time = 'm/d/Y', $filter = 'publish' ) {
		// Paginations
		global $gb_report_pages;
		// Pagination variable
		$showpage = ( isset( $_GET['showpage'] ) ) ? (int)$_GET['showpage']+1 : 1 ;

		// Build an array of merchant's Deals.
		$merchants_deal_ids = array();
		if ( $account_merchant_id ) {
			$merchants_deal_ids = gb_get_merchants_deal_ids( $account_merchant_id );
		}

		$showpage = ( isset( $_GET['showpage'] ) ) ? (int)$_GET['showpage']+1 : 1 ;
		$args=array(
			'post_type' => SEC_Account::POST_TYPE,
			'post_status' => 'publish',
			'posts_per_page' => apply_filters( 'gb_reports_show_records', 50, 'account_profiles' ),
			'paged' => $showpage,
			'fields' => 'ids',
			'date_query' => array(
					array(
						'after'     => $start_time,
						'before'    => date( 'm/d/Y', strtotime( $time )+86400 ), // Add a day since it will not count the date selected otherwise.
						'inclusive' => true,
					),
				),
		);
		$account_query = new WP_Query( $args );
		$gb_report_pages = $account_query->max_num_pages; // set the global for later pagination

		$accounts = array();
		if ( !empty( $account_query->posts ) ) {
			foreach ( $account_query->posts as $account_id ) {
				$account = Group_Buying_Account::get_instance_by_id( $account_id );
				if ( is_a( $account, 'Group_Buying_Account' ) ) {
					// Personal Info
					$account_name = $account->get_name();
					$name = ( strlen( $account_name ) <= 1  ) ? get_the_title( $account->get_ID() ) : $account_name;
					$user_id = SEC_Account::get_user_id_for_account( $account->get_ID() );
					// $user_data = get_userdata( $user_id );

					// mobile
					$mobile = '';
					if ( defined( 'Registration_Fields::MOBILE_CODE' ) ) {
						$mobile = get_post_meta( $account->get_ID(), '_'.Registration_Fields::MOBILE_CODE, TRUE ) . ' ' . get_post_meta( $account->get_ID(), '_'.Registration_Fields::MOBILE, TRUE );
					}

					// Purchases
					$purchases = SEC_Purchase::get_purchases( array(
						'user' => $user_id,
					) );
					$purchased_offer_ids = gb_get_purchased_deals( $user_id );

					// gift
					$gifts_purchased = array(); // build an array of all the titles
					if ( !empty( $purchased_offer_ids ) ) {
						$query_args = array( // search through all purchased offer ids for the gifts
								'post_type' => gb_get_deal_post_type(),
								gb_get_deal_cat_slug() => self::GIFT_TERM,
								'post_status' => 'any',
								'posts_per_page' => -1,
								'fields' => 'ids',
								'post__in' => $purchased_offer_ids
							);
						$gift_ids = get_posts( $query_args );
						foreach ( $gift_ids as $gift_id ) {
							$gifts_purchased[] = '<a href="'.get_permalink( $gift_id ).'">'.get_the_title( $gift_id ).'</a>';
						}
					}
					
					// Credits
					$credits = gb_get_account_balance( $user_id );
					$reward_credits = gb_get_account_balance( $user_id, SEC_Affiliates::CREDIT_TYPE );

					$accounts[] = apply_filters( 'gb_accounts_record_item', array(
							'id' => get_the_ID(),
							'name' => '<a href="'.sec_get_account_profile_mngt_url( $account_id ).'">'.$name.'</a>',
							'mobile' => $mobile,
							'purchase_total' => count( $purchases ),
							'purchase_offers_total' => count( $purchased_offer_ids ),
							'gift' => implode( ', ', $gifts_purchased ),
							'rewards' => $reward_credits
						), $account );
				}
			}
		}
		return $accounts;
	}

}