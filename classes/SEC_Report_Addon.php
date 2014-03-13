<?php

/**
 * Load via GBS Add-On API
 */
class SEC_Report_Addon extends Group_Buying_Controller {
	
	public static function init() {
		require_once('SEC_Report_Users.php');
		require_once('SEC_Report_User_Profiles.php');
		require_once( SEC_PROFILES_REPORT_PATH . '/library/template-tags.php');
		
		SEC_Report_Users::init();
		SEC_Report_User_Profiles::init();
	}

	public static function gb_addon( $addons ) {
		if ( self::using_permalinks() ) {
			$link = add_query_arg( array( 'report' => 'account_profiles' ), home_url( trailingslashit( get_option( Group_Buying_Reports::REPORTS_PATH_OPTION ) ) ) );
		} else {
			$link = add_query_arg( array( Group_Buying_Reports::REPORT_QUERY_VAR => 1, 'report' => 'account_profiles'  ), home_url() );
		}
		$addons['sec_user_profiling'] = array(
			'label' => self::__( 'User Profiling Report' ),
			'description' => sprintf( self::__( 'Profile Dashboard and Profile Updates. <a href="%s" class="button">Report</a>' ), $link ),
			'files' => array(),
			'callbacks' => array(
				array( __CLASS__, 'init' ),
			),
			'active' => TRUE,
		);
		return $addons;
	}

}