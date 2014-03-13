<?php 

/**
 * Report url
 * @return  
 */
function sec_get_users_report_url() {
	if ( Group_Buying_Controller::using_permalinks() ) {
		$link = add_query_arg( array( 'report' => SEC_Report_Users::REPORT_SLUG ), home_url( trailingslashit( get_option( Group_Buying_Reports::REPORTS_PATH_OPTION ) ) ) );
	} else {
		$link = add_query_arg( array( Group_Buying_Reports::REPORT_QUERY_VAR => 1, 'report' => SEC_Report_Users::REPORT_SLUG  ), home_url() );
	}
	return $link;
}

/**
 * Return the url to the account profile management page.
 * 
 * @param  integer $account_id 
 * @return string              
 */
function sec_get_account_profile_mngt_url( $account_id = 0 ) {
	return SEC_Report_User_Profiles::get_url( $account_id );
}
	function sec_account_profile_mngt_url( $account_id = 0 ) {
		echo sec_get_account_profile_mngt_url();
	}