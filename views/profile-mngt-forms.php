<a href="<?php echo sec_get_users_report_url() ?>"><?php gb_e('Go back to Profile Report') ?></a>

<form action="" method="post" accept-charset="utf-8">

	<?php if ( current_user_can( 'delete_posts' ) || apply_filters( 'merchant_can_manage_profiles', FALSE ) ): ?>
		<?php wp_nonce_field( 'sec_form_action', 'profile_mngt_form_nonce' ); ?>
		<input type="hidden" name="mngt_account_id" value="<?php echo $account->get_id() ?>">
	<?php endif ?>

	<?php 
		///////////////////
		// Account Info //
		/////////////////// ?>

	<?php if ( isset( $mobile ) ): ?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><label for="account_mobile"><?php gb_e( 'Mobile' ) ?>:</label></th>
					<td><?php echo $mobile_code; ?> <input type="text" id="account_mobile" name="account_mobile" value="<?php echo $mobile; ?>" size="40" /></td>
				</tr>
			</tbody>
		</table>
	<?php endif ?>

	<?php 
		///////////////////////
		// Purchase History //
		/////////////////////// ?>

	<script type="text/javascript" charset="utf-8">
		jQuery(document).ready(function($){
			jQuery(".sec_report_activate_voucher").click(function(event) {
				event.preventDefault();
					if( confirm( '<?php gb_e("Are you sure? This will make the voucher immediately available for download.") ?>' ) ) {
						var $activate_button = $( this ),
						activate_voucher_id = $activate_button.attr( 'ref' );
						$( "#"+activate_voucher_id+"_activate" ).fadeOut('slow');
						$.post( gb_ajax_url, { action: 'sec_activate_voucher', voucher_id: activate_voucher_id, activate_voucher_nonce: '<?php echo wp_create_nonce( Group_Buying_Destroy::NONCE ) ?>' },
							function( data ) {
									$( "#"+activate_voucher_id+"_activate_result" ).append( '<?php self::_e( 'Activated' ) ?>' ).fadeIn();
								}
							);
					} else {
						// nothing to do.
					}
			});
			jQuery(".sec_report_deactivate_voucher").on('click', function(event) {
				event.preventDefault();
					if( confirm( '<?php gb_e("Are you sure? This will immediately remove the voucher from customer access.") ?>' ) ) {
						var $deactivate_button = $( this ),
						deactivate_voucher_id = $deactivate_button.attr( 'ref' );
						$( "#"+deactivate_voucher_id+"_deactivate" ).fadeOut('slow');
						$.post( gb_ajax_url, { action: 'sec_deactivate_voucher', voucher_id: deactivate_voucher_id, deactivate_voucher_nonce: '<?php echo wp_create_nonce( Group_Buying_Destroy::NONCE ) ?>' },
							function( data ) {
									$( "#"+deactivate_voucher_id+"_deactivate_result" ).append( '<?php self::_e( 'Deactivated' ) ?>' ).fadeIn();
								}
							);
					} else {
						// nothing to do.
					}
			});
		});
	</script>

	<?php
	if ( !empty( $purchases ) ) {
		echo '<table id="gb_purchases_tables">';
		echo '<tbody>';
		rsort( $purchases );
		// Loop through all the offers a merchant has
		foreach ( $purchases as $purchase_id ) {
			$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
			if ( is_a( $purchase, 'Group_Buying_Purchase' ) ) {
				$user_ip = ( $purchase->get_user_ip() ) ? $purchase->get_user_ip() : gb__( 'unknown' );

				echo '<tr><thead><th colspan="14" align="left">'.get_the_title( $purchase_id ).'<small>&nbsp;&nbsp;&nbsp;&nbsp;'.gb__( 'Total: ' ).gb_get_formatted_money( $purchase->get_total() ).'</small><small>&nbsp;&nbsp;&nbsp;&nbsp;'.gb__( 'User IP: ' ).$user_ip.'</small></th></thead></tr>';

				$vouchers = Group_Buying_Voucher::get_vouchers_for_purchase( $purchase_id );
				if ( !empty( $vouchers ) ) {
					echo '<tr class="v_h"><th abbr="'.get_the_title( $purchase_id ).'" colspan="10">&nbsp;</th><td class="th">'.gb__( 'Voucher ID' ).'</td><td class="th">'.gb__( 'Status Mngt.' ).'</td><td class="th">'.gb__( 'Voucher Serial/Code' ).'</td></tr>';
					foreach ( $vouchers as $voucher_id ) {
						$voucher = Group_Buying_Voucher::get_instance( $voucher_id );
						if ( is_a( $voucher, 'Group_Buying_Voucher' ) ) {
							if ( get_post_status( $voucher_id ) != 'publish' ) {
								$status = gb__( 'Deactivate' );
								$status =  '<span id="'.$voucher_id.'_activate_result"></span><a href="javascript:void(0)" class="sec_report_activate_voucher button disabled" id="'.$voucher_id.'_activate" ref="'.$voucher_id.'">'.gb__('Activate').'</a>';
							} else {
								$status = gb__( 'Activated' );
								$status =  '<span id="'.$voucher_id.'_deactivate_result"></span><a href="javascript:void(0)" class="sec_report_deactivate_voucher button disabled" id="'.$voucher_id.'_deactivate" ref="'.$voucher_id.'">'.gb__('Deactivate').'</a>';
							}
							echo '<tr><th abbr="'.get_the_title( $purchase_id ).'" colspan="4">&nbsp;</th><th class="voucher_name" colspan="6" align="right">'.str_replace( gb__( 'Voucher for ' ), '', get_the_title( $voucher_id ) ).'</th>';
							echo '<td>'.$voucher_id.'</td>';
							echo '<td>'.$status.'</td>';
							echo '<td>'.$voucher->get_serial_number().'</td>';
							echo '</tr>';
						}
					}
				}
			}

		}
		echo '</tbody>';
		echo '</table>';
	}
	?>


	<?php 
		//////////////
		// Credits //
		////////////// ?>

	<?php 
		// Only show rewards
		unset($credit_types['balance']); ?>

	<table class="form-table">
		<tbody>
			<?php foreach ( $credit_types as $key => $data ): ?>
				<tr>
					<th scope="row"><strong><?php echo $data['label']; ?></strong>:</th>
					<td>
						<?php esc_attr_e( $data['balance'] ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php gb_e( 'Action' ) ?>:</th>
					<td>
						<input type="radio" name="account_credit_action[<?php esc_attr_e( $key ); ?>]" value="add" /> <?php gb_e( 'Add' ) ?>&nbsp;&nbsp;&nbsp;<input type="radio" name="account_credit_action[<?php esc_attr_e( $key ); ?>]" value="deduct" /> <?php gb_e( 'Deduct' ) ?>&nbsp;&nbsp;&nbsp;<input type="radio" name="account_credit_action[<?php esc_attr_e( $key ); ?>]" value="change" /> <?php gb_e( 'Change to' ) ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php gb_e( 'Amount' ) ?>:</th>
					<td>
						<input type="text" name="account_credit_balance[<?php esc_attr_e( $key ); ?>]" />
					</td>
				</tr>
				<tr>
					<th scope="row"><?php gb_e( 'Comment' ) ?>:</th>
					<td>
						<textarea name="account_credit_notes[<?php esc_attr_e( $key ); ?>]" rows="4" style="width:99%;"></textarea>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	
	<?php foreach ($credit_types as $key => $data): ?>
		<table id="gb_purchases_tables">
			<h2><?php echo $data['label']; ?> <?php gb_e( 'Logs' ) ?></h2>
			<tbody>
			<thead><th><?php gb_e( 'Date' ) ?></th><th><?php gb_e( 'Recorded by' ) ?></th><th><?php gb_e( 'Notes' ) ?></th><th><?php gb_e( 'Amount' ) ?></th><th><?php gb_e( 'Total' ) ?></th></thead>
		<?php

		$records = Group_Buying_Record::get_records_by_type_and_association( $account->get_ID(), Group_Buying_Accounts::$record_type . '_' . $key );

		if ( apply_filters( 'gb_include_purchases_in_creditlog', '__return_true' ) ) {
			$purchases = Group_Buying_Purchase::get_purchases( array( 'account' => $account->get_ID() ) );
		} else {
			$purchases = array();
		}

		if ( !empty( $purchases ) || !empty( $records ) ) {
			$items = array();

			// Loop through all the records
			foreach ( $records as $record_id ) {
				foreach ( $credit_types as $credit_key => $credit_data ) {

					$record = Group_Buying_Record::get_instance( $record_id );
					$record_data = $record->get_data();

					$record_post = $record->get_post();
					$author = get_userdata( $record_post->post_author );
					$balance = (int)$record_data['current_total'];
					$balance = ( isset( $record_data['current_total_'.$credit_key] ) ) ? $record_data['current_total_'.$credit_key] : $record_data['current_total'] ;
					$note = ( isset( $record_data['note'] ) ) ? $record_data['note'] : self::__('N/A');
					$prior = (int)$record_data['prior_total'];
					$adjustment = ( $balance == (int)$record_data['adjustment_value'] ) ? (int)$record_data['adjustment_value'] - $prior : $balance - $prior ;
					$plusminus = ( $adjustment > 0 ) ? '+' : '';
					$items[get_the_time( 'U', $record_id )] = array(
						'date' => get_the_time( 'U', $record_id ),
						'recorded' => $author->user_login,
						'note' => $note,
						'amount' => $plusminus . $adjustment,
						'total' => $balance,
					);
				}
			}

			// Loop through all the purchases
			foreach ( $purchases as $purchase_id ) {
				$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
				$amount = $purchase->get_total( Group_Buying_Affiliate_Credit_Payments::PAYMENT_METHOD );
				if ( $amount > 0.1 ) {
					// Offer names
					$products = $purchase->get_products();
					$offer_names = array();
					if ( !empty( $purchases ) ) {
						foreach ( $purchases as $purchase_id ) {
							$purchase = Group_Buying_Purchase::get_instance( $purchase_id );
							$products = $purchase->get_products();
							foreach ( $products as $product ) {
								$offer_names[] = get_the_title( $product['deal_id'] );
							}
						}
						$items[get_the_time( 'U', $purchase_id )] = array(
							'date' => get_the_time( 'U', $purchase_id ),
							'recorded' => gb__( 'Customer' ),
							'note' =>  gb__('Purchased: ').' '.implode( ', ', $offer_names ),
							'amount' => number_format( floatval( $amount ), 2 ),
							'total' => gb__( 'N/A' ),
						);
					}
				}
			}
			uasort( $items, array( 'Group_Buying_Records', 'sort_callback' ) );
			foreach ( $items as $key => $value ) {
				echo '<tr><td>'.date( get_option( 'date_format' ).', '.get_option( 'time_format' ), $value['date'] ).'</td>';
				echo '<td>'.$value['recorded'].'</td>';
				echo '<td>'.$value['note'].'</td>';
				echo '<td>'.$value['amount'].'</td>';
				echo '<td>'.$value['total'].'</td>';
				echo '</tr>';
			}
		}

		?>
			</tbody>
		</table>
	<?php endforeach ?>

	<input class="form-submit submit" type="submit" value="<?php gb_e('Update') ?>">
</form>