<?php
/*------------------------------------------------------------------------
  Solidres - Hotel booking plugin for WordPress
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   http://www.solidres.com
  @Copyright Copyright (C) 2013 - 2020 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$dateFormat   = get_option( 'date_format', 'd-m-Y' );
$jsDateFormat = SR_Utilities::convert_date_format_pattern( $dateFormat );
$tzoffset     = get_option( 'timezone_string' );
$tzoffset     = $tzoffset == '' ? 'UTC' : $tzoffset;
$timezone     = new DateTimeZone( $tzoffset );
$options_plugin   = get_option('solidres_plugin');
$isDiscountPreTax = @$options_plugin['discount_pre_tax'];

//get_header( 'booking' ); ?>

<?php
	/**
	 * solidres_before_main_content hook
	 *
	 * @hooked solidres_output_content_wrapper - 10 (outputs opening divs for the content)
	 * @hooked solidres_breadcrumb - 20
	 */
	do_action( 'solidres_before_main_content' );
?>
	<div id="solidres" class="reservation-final">
		<div class="alert alert-success">
			<p>
				<?php printf( __( "<h3>Thank you %s! Your reservation number %s has been completed successfully.</h3><ul> <li>We've sent a confirmation email to %s</li><li>We've also notified %s about your upcoming stay</li><li><a href='%s'>Click here</a> to return to our home page.</li></ul>", 'solidres' ), $reservation->customer_firstname, $reservation->code, $reservation->customer_email, $reservation->reservation_asset_name, home_url() ); ?>
			</p>
		</div>

		<h3><?php _e( 'Hotel information', 'solidres' ) ?></h3>

		<table class="table table-striped">
			<thead></thead>
			<tbody>
			<tr>
				<td>
					<?php _e( 'Name', 'solidres' ) ?>
				</td>
				<td>
					<?php echo apply_filters( 'solidres_asset_name', $asset->name ) ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php _e( 'Address', 'solidres' ) ?>
				</td>
				<td>
					<?php echo $asset->address_1 .', '.
					           (!empty($asset->city) ? $asset->city.', ' : '').
					           (!empty($asset->postcode) ? $asset->postcode.', ' : '').
					           $country->name ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php _e( 'Email', 'solidres' ) ?>
				</td>
				<td>
					<?php echo $asset->email ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php _e( 'Phone', 'solidres' ) ?>
				</td>
				<td>
					<?php echo $asset->phone ?>
				</td>
			</tr>
			</tbody>
		</table>

		<h3><?php _e( 'Your booking information', 'solidres' ) ?></h3>

		<table class="table table-striped">
			<thead></thead>
			<tbody>
			<tr>
				<td>
					<?php _e( 'Booking number', 'solidres' ) ?>
				</td>
				<td>
					<?php echo $reservation->code ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php _e( 'Email', 'solidres' ) ?>
				</td>
				<td>
					<?php echo $reservation->customer_email ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php _e( 'Booking details', 'solidres' ) ?>
				</td>
				<td>
					<?php
					if (!isset($reservation->booking_type)) :
						$reservation->booking_type = 0;
					endif;

					if ($reservation->booking_type == 0) :
						printf( _n( '%d night', '%d nights', $length_of_stay, 'solidres' ), $length_of_stay ) ;
					else :
						printf( _n( '%d day', '%d days', $length_of_stay + 1, 'solidres' ), $length_of_stay ) ;
					endif;
					?>,

					<?php
					$room_count = count($reservation->reserved_room_details);
					printf( _n( '%s room', '%s rooms', $room_count, 'solidres' ), $room_count )
					?>

				</td>
			</tr>
			<tr>
				<td>
					<?php _e( 'Check-in', 'solidres' ) ?>
				</td>
				<td>
					<?php
					$checkin = new DateTime( $reservation->checkin, $timezone );
                    echo $checkin->format( $dateFormat ) ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php _e( 'Check-out', 'solidres' ) ?>
				</td>
				<td>
					<?php
					$checkout = new DateTime( $reservation->checkout, $timezone );
                    echo $checkout->format( $dateFormat ) ?>
				</td>
			</tr>
			<tr>
				<td>
					<?php _e( 'Total price', 'solidres' ) ?>
				</td>
				<td>
					<?php
                    if ($isDiscountPreTax) :
					    $base_currency = new SR_Currency( $reservation->total_price_tax_excl - $reservation->total_discount + $reservation->tax_amount + $reservation->total_extra_price_tax_incl, $reservation->currency_id );
                    else :
	                    $base_currency = new SR_Currency( $reservation->total_price_tax_excl + $reservation->tax_amount - $reservation->total_discount + $reservation->total_extra_price_tax_incl, $reservation->currency_id );
                    endif;

					echo $base_currency->format()
					?>
				</td>
			</tr>
			</tbody>
		</table>

		<h3><?php _e( 'Room details', 'solidres' ) ?> </h3>

		<table>
			<thead></thead>
			<tbody>
			<?php
			$reserved_room_details = $reservation->reserved_room_details;
			foreach($reserved_room_details as $room) : ?>
				<dl>
					<dt>
						<?php echo apply_filters( 'solidres_roomtype_name', $room->room_type_name ) ?>
						(
						<?php
						printf( _n( '%d adult', '%d adults', $room->adults_number, 'solidres' ), $room->adults_number );

						_e( ' and ', 'solidres' );

						printf( _n( '%d child', '%d children', $room->children_number, 'solidres' ) , $room->children_number );
						?>
						)
					</dt>
					<dd><?php _e( 'Guest full name', 'solidres' ) ?>: <?php echo $room->guest_fullname ?></dd>
					<dd>
						<?php
						if (is_array($room->other_info)) :
							foreach ($room->other_info as $info) :
								if (substr($info->key, 0, 7) == 'smoking') :
									echo __( 'Smoking', 'solidres' ) . ': ' . ($info->value == '' ? __( 'No preferences', 'solidres' ) : ($info->value == 1 ? __( 'Yes', 'solidres' ): __( 'No', 'solidres' ) )  ) ;
								endif;
							endforeach;
						endif
						?>
					</dd>
					<dd>
						<?php
						$room_price_currency = clone $base_currency;
						$room_price_currency->set_value(isset($room->room_price_tax_incl) ? $room->room_price_tax_incl : $room->room_price);
						echo __( 'Room cost', 'solidres' ) . ': ' . $room_price_currency->format();

						$get_extras_of_room = $wpdb->get_results( $wpdb->prepare( "SELECT x.*, extra.id, extra.name as extra_name FROM {$wpdb->prefix}sr_reservation_room_extra_xref as x INNER JOIN {$wpdb->prefix}sr_extras as extra ON extra.id = x.extra_id WHERE reservation_id = %d AND room_id = %d", $reservation->id, $room->room_id ) );
						?>

						<?php if ( ! empty( $get_extras_of_room ) ) : ?>
                            <table class="form-table room_extra_table">
                                <thead>
                                <tr>
                                    <th><?php _e( 'Name', 'solidres' ); ?></th>
                                    <th><?php _e( 'Quantity', 'solidres' ); ?></th>
                                    <th><?php _e( 'Price', 'solidres' ); ?></th>
                                </tr>
                                </thead>
                                <tbody>
								<?php foreach ( $get_extras_of_room as $get_extra_of_room ) :
									$extra_price = clone $base_currency;
									$extra_price->set_value( isset ( $get_extra_of_room->extra_price ) ? $get_extra_of_room->extra_price : 0 );
									?>
                                    <tr>
                                        <td><?php echo $get_extra_of_room->extra_name ?></td>
                                        <td><?php echo $get_extra_of_room->extra_quantity; ?></td>
                                        <td><?php echo $extra_price->format(); ?></td>
                                    </tr>
								<?php endforeach ?>
                                </tbody>
                            </table>
						<?php endif ?>
					</dd>
				</dl>
			<?php endforeach ?>
			</tbody>
		</table>
	</div>
<?php
	/**
	 * solidres_after_main_content hook
	 *
	 * @hooked solidres_output_content_wrapper_end - 10 (outputs closing divs for the content)
	 */
	do_action( 'solidres_after_main_content' );
?>

<?php
	/**
	 * solidres_sidebar hook
	 *
	 * @hooked solidres_get_sidebar - 10
	 */
	//do_action( 'solidres_sidebar' );
?>

<?php //get_footer( 'booking' );

do_action( 'solidres_after_display_reservation_final_screen', $reservation->code );

do_action( 'solidres_reservation_cleanup' );
