<?php
/*------------------------------------------------------------------------
  Solidres - Hotel booking plugin for WordPress
  ------------------------------------------------------------------------
  @Author    Solidres Team
  @Website   http://www.solidres.com
  @Copyright Copyright (C) 2013 - 2020 Solidres. All Rights Reserved.
  @License   GNU General Public License version 3, or later
------------------------------------------------------------------------*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * RoomType handler class
 * @package 	Solidres
 * @subpackage	RoomType
 * @since 		0.1.0
 */
class SR_Tariff {

	public $type_name_mapping = array();

	const PER_ROOM_PER_NIGHT = 0;

	const PER_PERSON_PER_NIGHT = 1;

	const PACKAGE_PER_ROOM = 2;

	const PACKAGE_PER_PERSON = 3;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->type_name_mapping = array(
			0 => __( 'Rate per room per night', 'solidres' ),
			1 => __( 'Rate per person per night', 'solidres' ),
			2 => __( 'Package per room', 'solidres' ),
			3 => __( 'Package per person', 'solidres' ),
		);
	}

	/**
	 * Delete a single tariff
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public function delete( $id ) {
		$this->wpdb->query( $this->wpdb->prepare("DELETE FROM {$this->wpdb->prefix}sr_tariff_details WHERE tariff_id = %d", $id) );
		$this->wpdb->query( $this->wpdb->prepare("UPDATE {$this->wpdb->prefix}sr_reservation_room_xref SET tariff_id = NULL WHERE tariff_id = %d", $id) );
		$result = $this->wpdb->query( $this->wpdb->prepare("DELETE FROM {$this->wpdb->prefix}sr_tariffs WHERE id = %d", $id) );

		return $result;
	}

	/**
	 * Get a single tariff by id
	 *
	 * @param $id
	 *
	 * @return mixed
	 */
	public function load( $id, $forCalc = 0 ) {

		$dayMapping = array(
			'0' => __( 'Sun', 'solidres' ),
			'1' => __( 'Mon', 'solidres' ),
			'2' => __( 'Tue', 'solidres' ),
			'3' => __( 'Wed', 'solidres' ),
			'4' => __( 'Thu', 'solidres' ),
			'5' => __( 'Fri', 'solidres' ),
			'6' => __( 'Sat', 'solidres' )
		);

		if (!isset($id) || empty($id)) {
			$tariff = new stdClass();
			$tariff->id = NULL;
			$tariff->currency_id = NULL;
			$tariff->customer_group_id = '';
			$tariff->valid_from = date( 'd-m-Y' );
			$tariff->valid_to = date( 'd-m-Y', strtotime("+1 day") );
			$tariff->room_type_id = NULL;
			$tariff->title = NULL;
			$tariff->description = NULL;
			$tariff->d_min = '0';
			$tariff->d_max = '7';
			$tariff->d_interval = '0';
			$tariff->p_min = '0';
			$tariff->p_max = '0';
			$tariff->type = '0';
			$tariff->limit_checkin = '["0","1","2","3","4","5","6"]';
			$tariff->state = '1';
			$tariff->mode = '0';
		} else {
			$tariff = $this->wpdb->get_row( "SELECT t.*, cgroup.name as customer_group_name 
												FROM {$this->wpdb->prefix}sr_tariffs as t
												LEFT JOIN {$this->wpdb->prefix}sr_customer_groups as cgroup ON cgroup.id = t.customer_group_id
												WHERE t.id = $id
											");

			$tariff->type_name         = $this->type_name_mapping[ $tariff->type ];
			$tariff->valid_from        = $tariff->valid_from != '0000-00-00' ? date( 'd-m-Y', strtotime( $tariff->valid_from ) ) : '00-00-0000';
			$tariff->valid_to          = $tariff->valid_to != '0000-00-00' ? date( 'd-m-Y', strtotime( $tariff->valid_to ) ) : '00-00-0000';
			$tariff->customer_group_id = is_null( $tariff->customer_group_id ) ? '' : $tariff->customer_group_id;
			$tariff->limit_checkin     = isset( $tariff->limit_checkin ) ? json_decode( $tariff->limit_checkin ) : null;

			if ( (int) $tariff->type == self::PER_ROOM_PER_NIGHT ) {
				$results = $this->load_details( $tariff->id, null, $tariff->mode, $forCalc );

				if ( ! empty( $results ) ) {
					if ($tariff->mode == 1 && $forCalc == 0) {
						$resultsSortedPerMonth = array();
						$resultsSortedPerMonth[date('Y-m', strtotime($results[0]->date))] = array();
						foreach ($results as $result) {
							$currentMonth = date('Y-m', strtotime($result->date));
							if (!isset($resultsSortedPerMonth[$currentMonth])) {
								$resultsSortedPerMonth[$currentMonth] = array();
							}
							$result->w_day_label = $dayMapping[$result->w_day] . ' ' . date('d', strtotime($result->date));
							$result->is_weekend = SR_Utilities::isWeekend($result->date);
							$result->is_today = SR_Utilities::isToday($result->date);
							$resultsSortedPerMonth[$currentMonth][] = $result;
						}
						$tariff->details['per_room'] = $resultsSortedPerMonth;
					} else {
						$tariff->details['per_room'] = $results;
						$tariff->details['per_room'] = SR_Utilities::translateDayWeekName( $tariff->details['per_room'] );
					}
				} else {
					$tariff->details['per_room'] = SR_Utilities::getTariffDetailsScaffoldings( array(
						'tariff_id' => $tariff->id,
						'guest_type' => null,
						'type' => $tariff->type,
						'mode' => $tariff->mode,
						'valid_from' => $tariff->valid_from,
						'valid_to' => $tariff->valid_to
					) );
				}
			} else if ( (int) $tariff->type == self::PER_PERSON_PER_NIGHT ) {
				// Query to get tariff details for each guest type
				// First we need to get the occupancy number
				$solidres_room_type = new SR_Room_Type();
				$room_type = $solidres_room_type->load( $tariff->room_type_id );
				$occupancy_adult = $room_type->occupancy_adult;
				$occupancy_child = $room_type->occupancy_child;
				$occupancy_child_age_range = $room_type->occupancy_child_age_range;

				// @since 2.4.0, now child age ranges are decoupled with child quantity
				// For backward compatibility purpose, if the new field is not defined, we assign the old value for it
				if (0 == $occupancy_child_age_range)
				{
					$occupancy_child_age_range = $occupancy_child;
				}

				// Get tariff details for all adults
				for ( $i = 1; $i <= $occupancy_adult; $i ++ ) {
					$results = $this->load_details( $tariff->id, 'adult' . $i, $tariff->mode, $forCalc );

					if ( ! empty( $results ) ) {

						if ($tariff->mode == 1 && $forCalc == 0) {
							$resultsSortedPerMonth = array();
							$resultsSortedPerMonth[date('Y-m', strtotime($results[0]->date))] = array();
							foreach ($results as $result) {
								$currentMonth = date('Y-m', strtotime($result->date));
								if (!isset($resultsSortedPerMonth[$currentMonth])) {
									$resultsSortedPerMonth[$currentMonth] = array();
								}
								$result->w_day_label = $dayMapping[$result->w_day] . ' ' . date('d', strtotime($result->date));
								$result->is_weekend = SR_Utilities::isWeekend($result->date);
								$result->is_today = SR_Utilities::isToday($result->date);
								$resultsSortedPerMonth[$currentMonth][] = $result;
							}
							$tariff->details['adult'.$i] = $resultsSortedPerMonth;
						} else {
							$tariff->details[ 'adult' . $i ] = $results;
							$tariff->details['adult'.$i] = SR_Utilities::translateDayWeekName($tariff->details['adult'.$i]);
						}

					} else {
						$tariff->details[ 'adult' . $i ] = SR_Utilities::getTariffDetailsScaffoldings( array(
							'tariff_id'  => $tariff->id,
							'guest_type' => 'adult' . $i,
							'type'       => $tariff->type,
							'mode'       => $tariff->mode,
							'valid_from' => $tariff->valid_from,
							'valid_to'   => $tariff->valid_to
						) );
					}
				}

				// Get tariff details for all children
				for ( $i = 1; $i <= $occupancy_child_age_range; $i ++ ) {
					$results = $this->load_details( $tariff->id, 'child' . $i, $tariff->mode, $forCalc );

					if ( ! empty( $results ) ) {

						if ($forCalc == 0) {
							$tariff->{'child'.$i}['from_age'] = $results[0]->from_age;
							$tariff->{'child'.$i}['to_age'] = $results[0]->to_age;
						}

						if ($tariff->mode == 1 && $forCalc == 0)
						{
							$resultsSortedPerMonth = array();
							$resultsSortedPerMonth[date('Y-m', strtotime($results[0]->date))] = array();

							foreach ($results as $result)
							{
								$currentMonth = date('Y-m', strtotime($result->date));
								if (!isset($resultsSortedPerMonth[$currentMonth]))
								{
									$resultsSortedPerMonth[$currentMonth] = array();
								}
								$result->w_day_label = $dayMapping[$result->w_day] . ' ' . date('d', strtotime($result->date));
								$result->is_weekend = SR_Utilities::isWeekend($result->date);
								$result->is_today = SR_Utilities::isToday($result->date);
								$resultsSortedPerMonth[$currentMonth][] = $result;
							}
							$tariff->details['child'.$i] = $resultsSortedPerMonth;
						}
						else
						{
							$tariff->details[ 'child' . $i ] = $results;
							$tariff->details[ 'child' . $i ] = SR_Utilities::translateDayWeekName( $tariff->details[ 'child' . $i ] );
						}
					} else {
						$tariff->details[ 'child' . $i ] = SR_Utilities::getTariffDetailsScaffoldings( array(
							'tariff_id'  => $tariff->id,
							'guest_type' => 'child' . $i,
							'type'       => $tariff->type,
							'mode'       => $tariff->mode,
							'valid_from' => $tariff->valid_from,
							'valid_to'   => $tariff->valid_to
						) );
					}
				}
			} else if ( (int) $tariff->type == self::PACKAGE_PER_ROOM ) {
				$results = $this->load_details( $tariff->id );

				if ( ! empty( $results ) ) {
					$tariff->details['per_room'] = $results;
				} else {
					$tariff->details['per_room'] = SR_Utilities::getTariffDetailsScaffoldings( array(
						'tariff_id'  => $tariff->id,
						'guest_type' => null,
						'type'       => $tariff->type,
						'mode'       => $tariff->mode,
						'valid_from' => $tariff->valid_from,
						'valid_to'   => $tariff->valid_to
					) );
				}
			} else if ( (int) $tariff->type == self::PACKAGE_PER_PERSON ) {
				// Query to get tariff details for each guest type
				// First we need to get the occupancy number
				$solidres_room_type = new SR_Room_Type();
				$room_type = $solidres_room_type->load( $tariff->room_type_id );
				$occupancy_adult = $room_type->occupancy_adult;
				$occupancy_child = $room_type->occupancy_child;
				$occupancy_child_age_range = $room_type->occupancy_child_age_range;

				// @since 2.4.0, now child age ranges are decoupled with child quantity
				// For backward compatibility purpose, if the new field is not defined, we assign the old value for it
				if (0 == $occupancy_child_age_range)
				{
					$occupancy_child_age_range = $occupancy_child;
				}

				// Get tariff details for all adults
				for ( $i = 1; $i <= $occupancy_adult; $i ++ ) {
					$results = $this->load_details( $tariff->id, 'adult' . $i, $tariff->mode );

					if ( ! empty( $results ) ) {
						$tariff->details[ 'adult' . $i ] = $results;
					} else {
						$tariff->details[ 'adult' . $i ] = SR_Utilities::getTariffDetailsScaffoldings( array(
							'tariff_id'  => $tariff->id,
							'guest_type' => 'adult' . $i,
							'type'       => $tariff->type,
							'mode'       => $tariff->mode,
							'valid_from' => $tariff->valid_from,
							'valid_to'   => $tariff->valid_to
						) );
					}
				}

				// Get tariff details for all children
				for ( $i = 1; $i <= $occupancy_child_age_range; $i ++ ) {
					$results = $this->load_details( $tariff->id, 'child' . $i, $tariff->mode );

					if ( ! empty( $results ) ) {
						$tariff->{'child'.$i}['from_age'] = $results[0]->from_age;
						$tariff->{'child'.$i}['to_age']   = $results[0]->to_age;
						$tariff->details[ 'child' . $i ]  = $results;
					} else {
						$tariff->details[ 'child' . $i ] = SR_Utilities::getTariffDetailsScaffoldings( array(
							'tariff_id'  => $tariff->id,
							'guest_type' => 'child' . $i,
							'type'       => $tariff->type,
							'mode'       => $tariff->mode,
							'valid_from' => $tariff->valid_from,
							'valid_to'   => $tariff->valid_to
						) );
					}
				}
			}
		}

		return $tariff;
	}

	/**
	 * Get a single tariff by room type id
	 *
	 * @param        $room_type_id
	 * @param bool   $standard
	 * @param string $output
	 *
	 * @param string $checkin
	 * @param string $checkout
	 * @param int    $state
	 * @param int    $customer_group_id
	 *
	 * @return mixed
	 */
	public function load_by_room_type_id( $room_type_id, $standard = true, $output = OBJECT, $checkin = '', $checkout = '', $state = 1, $customer_group_id = NULL ) {
		$query = "SELECT t.*, cgroup.name as customer_group_name FROM {$this->wpdb->prefix}sr_tariffs AS t LEFT JOIN {$this->wpdb->prefix}sr_customer_groups as cgroup ON cgroup.id = t.customer_group_id";
		if ( $standard ) {
			$query .= " WHERE t.valid_from = '0000-00-00' AND t.valid_to = '0000-00-00'";
		} else {
			$query .= " WHERE t.valid_from != '0000-00-00' AND t.valid_to != '0000-00-00'";
			if ( !empty($checkin) && !empty($checkout)) {
				$query .= " AND t.valid_from <= %s AND t.valid_to >= %s";
			}
		}

		$query .= " AND t.room_type_id = %d";

		if ( isset($state) ) {
			$query .= " AND t.state = " . (int) $state;
		}

		// Filter by customer group id
		// -1 means no checking, load them all
		// NULL means load tariffs for Public customer group
		// any other value > 0 means load tariffs belong to specific groups
		if ( $customer_group_id != -1) {
			$query .= " AND t.customer_group_id " . ( $customer_group_id === NULL ? 'IS NULL' : '= ' .(int) $customer_group_id );
		}

		$query .= ' ORDER BY t.valid_from ASC';

		if (!$standard) {
			if ( !empty($checkin) && !empty($checkout)) {
				$tariffs = $this->wpdb->get_results( $this->wpdb->prepare($query, $checkin, $checkout, $room_type_id), $output );
			} else {
				$tariffs = $this->wpdb->get_results( $this->wpdb->prepare($query, $room_type_id), $output );
			}

			foreach ($tariffs as &$tariff) {
				$tariff = $this->load($tariff->id, $standard);
			}
			return $tariffs;
		} else {
			return $this->wpdb->get_results( $this->wpdb->prepare($query, $room_type_id), $output );
		}
	}

	public function load_details( $id, $guest_type = NULL, $tariff_mode = 0, $for_calc = 0 ) {

		$orderBy = 'w_day ASC';
		if ($tariff_mode == 1) {
			$orderBy = 'date ASC';
		}

		if ( isset($guest_type) ) {
			$query = $this->wpdb->prepare(
				"SELECT date, id, tariff_id, price, w_day, guest_type, from_age, to_age, min_los, max_los 
				FROM {$this->wpdb->prefix}sr_tariff_details
				WHERE tariff_id = %d AND guest_type = %s
				ORDER BY " . $orderBy ,
				array( $id, $guest_type )
			);
		} else {
			$query = $this->wpdb->prepare(
				"SELECT date, id, tariff_id, price, w_day, guest_type, from_age, to_age, min_los, max_los 
				FROM {$this->wpdb->prefix}sr_tariff_details
				WHERE tariff_id = %d
				ORDER BY " . $orderBy ,
				array( $id )
			);
		}

		if ($for_calc == 1 && $tariff_mode == 1) {
			return $this->wpdb->get_results( $query, OBJECT_K );
		}
		return $this->wpdb->get_results( $query );
	}

	public function save($tariff) {
		if (!empty($tariff['limit_checkin']) && is_array($tariff['limit_checkin'])) {
			$tariff['limit_checkin'] = json_encode($tariff['limit_checkin']);
		}

		$dayMapping = array(
			'0' => __( 'Sun', 'solidres' ),
			'1' => __( 'Mon', 'solidres' ),
			'2' => __( 'Tue', 'solidres' ),
			'3' => __( 'Wed', 'solidres' ),
			'4' => __( 'Thu', 'solidres' ),
			'5' => __( 'Fri', 'solidres' ),
			'6' => __( 'Sat', 'solidres' )
		);

		if ($tariff['customer_group_id'] === '') {
			$tariff['customer_group_id'] = NULL;
		}

		$tariff['valid_from'] = date('Y-m-d', strtotime($tariff['valid_from']));
		$tariff['valid_to'] = date('Y-m-d', strtotime($tariff['valid_to']));

		if (isset($tariff['id']) && $tariff['id'] > 0) {
			$current_tariff = $this->load($tariff['id'], false);

			// Delete tariff details in case of changing type or changing mode
			// Only apply for complex tariff, not standard tariff
			if ($current_tariff->type != $tariff['type']) {
				$this->wpdb->get_results( "DELETE FROM {$this->wpdb->prefix}sr_tariff_details WHERE tariff_id = $current_tariff->id" );
			}

			// In case of changing dates
			$newValidFrom = date('Y-m-d', strtotime($tariff['valid_from']));
			$newValidTo = date('Y-m-d', strtotime($tariff['valid_to']));
			if ($tariff['mode'] == 1 && ($current_tariff->valid_from != $newValidFrom || $current_tariff->valid_to != $newValidTo))
			{
				$newTariffDates = SR_Utilities::calculateWeekDay($newValidFrom, $newValidTo);

				// Delete dates that doesn't belong to new tariff dates
				$this->wpdb->query( $this->wpdb->prepare("DELETE FROM {$this->wpdb->prefix}sr_tariff_details 
						WHERE tariff_id = %d AND ((date < %s OR date > %s))", $current_tariff->id, $newValidFrom, $newValidTo));


				if ($current_tariff->type == self::PER_ROOM_PER_NIGHT)
				{
					$newTariffDetails = array();
					foreach ( $newTariffDates as $i => $tariffDate )
					{
						// Merge existing details if available
						foreach ( $tariff['details']['per_room'] as $detail )
						{
							if ( $detail['date'] == $tariffDate )
							{
								$newTariffDetails[ $i ] = $detail;
								continue 2;
							}
						}

						$newTariffDetails[ $i ]['id']         = null;
						$newTariffDetails[ $i ]['tariff_id']  = $current_tariff->id;
						$newTariffDetails[ $i ]['price']      = null;
						$newTariffDetails[ $i ]['w_day']      = date( 'w', strtotime( $tariffDate ) );
						$newTariffDetails[ $i ]['w_day_name'] = $dayMapping[ $newTariffDetails[ $i ]['w_day'] ];
						$newTariffDetails[ $i ]['guest_type'] = null;
						$newTariffDetails[ $i ]['from_age']   = null;
						$newTariffDetails[ $i ]['to_age']     = null;
						$newTariffDetails[ $i ]['date']       = $tariffDate;
						$newTariffDetails[ $i ]['min_los']    = null;
						$newTariffDetails[ $i ]['max_los']    = null;
					}

					$tariff['details']['per_room'] = $newTariffDetails;
				}
				else if ( (int) $current_tariff->type == self::PER_PERSON_PER_NIGHT )
				{
					$solidres_roomtype = new SR_Room_Type();
					$roomType = $solidres_roomtype->load($current_tariff->room_type_id);
					$occupancyAdult = $roomType->occupancy_adult;
					$occupancyChild = $roomType->occupancy_child;

					for ($adultCount = 1; $adultCount <= $occupancyAdult; $adultCount ++)
					{
						$adultIndex = 'adult'.$adultCount;
						$newTariffDetails = array();
						foreach ( $newTariffDates as $i => $tariffDate )
						{
							// Merge existing details if available
							foreach ( $tariff['details'][$adultIndex] as $detail )
							{
								if ( $detail['date'] == $tariffDate )
								{
									$newTariffDetails[ $i ] = $detail;
									continue 2;
								}
							}

							$newTariffDetails[ $i ]['id']         = null;
							$newTariffDetails[ $i ]['tariff_id']  = $current_tariff->id;
							$newTariffDetails[ $i ]['price']      = null;
							$newTariffDetails[ $i ]['w_day']      = date( 'w', strtotime( $tariffDate ) );
							$newTariffDetails[ $i ]['w_day_name'] = $dayMapping[ $newTariffDetails[ $i ]['w_day'] ];
							$newTariffDetails[ $i ]['guest_type'] = $adultIndex;
							$newTariffDetails[ $i ]['from_age']   = null;
							$newTariffDetails[ $i ]['to_age']     = null;
							$newTariffDetails[ $i ]['date']       = $tariffDate;
							$newTariffDetails[ $i ]['min_los']    = null;
							$newTariffDetails[ $i ]['max_los']    = null;
						}

						$tariff['details'][$adultIndex] = $newTariffDetails;
					}

					for ($childCount = 1; $childCount <= $occupancyChild; $childCount++)
					{
						$childIndex = 'child'.$childCount;
						$newTariffDetails = array();
						foreach ( $newTariffDates as $i => $tariffDate )
						{
							// Merge existing details if available
							foreach ( $tariff['details'][$childIndex] as $detail )
							{
								if ( $detail['date'] == $tariffDate )
								{
									$newTariffDetails[ $i ] = $detail;
									continue 2;
								}
							}

							$newTariffDetails[ $i ]['id']         = null;
							$newTariffDetails[ $i ]['tariff_id']  = $current_tariff->id;
							$newTariffDetails[ $i ]['price']      = null;
							$newTariffDetails[ $i ]['w_day']      = date( 'w', strtotime( $tariffDate ) );
							$newTariffDetails[ $i ]['w_day_name'] = $dayMapping[ $newTariffDetails[ $i ]['w_day'] ];
							$newTariffDetails[ $i ]['guest_type'] = $childIndex;
							$newTariffDetails[ $i ]['from_age']   = null;
							$newTariffDetails[ $i ]['to_age']     = null;
							$newTariffDetails[ $i ]['date']       = $tariffDate;
							$newTariffDetails[ $i ]['min_los']    = null;
							$newTariffDetails[ $i ]['max_los']    = null;
						}

						$tariff['details'][$childIndex] = $newTariffDetails;
					}
				}
			}

			$this->wpdb->update( $this->wpdb->prefix . 'sr_tariffs',
				array(
					'currency_id'       => $tariff['currency_id'],
					'customer_group_id' => $tariff['customer_group_id'],
					'valid_from'        => $tariff['valid_from'],
					'valid_to'          => $tariff['valid_to'],
					'room_type_id'      => $tariff['room_type_id'],
					'title'             => $tariff['title'],
					'description'       => $tariff['description'],
					'd_min'             => $tariff['d_min'],
					'd_max'             => $tariff['d_max'],
					'd_interval'        => $tariff['d_interval'],
					'p_min'             => $tariff['p_min'],
					'p_max'             => $tariff['p_max'],
					'type'              => $tariff['type'],
					'limit_checkin'     => $tariff['limit_checkin'],
					'state'             => $tariff['state'],
					'mode'              => (isset($tariff['mode']) ? $tariff['mode'] : 0),
				),
				array(
					'id' => $tariff['id'],
				),
				array(
					'%d',
					'%d',
					'%s',
					'%s',
					'%d',
					'%s',
					'%s',
					'%d',
					'%d',
					'%d',
					'%d',
					'%d',
					'%d',
					'%s',
					'%d',
					'%d',
				)
			);
		} else {
			$this->wpdb->insert( $this->wpdb->prefix . 'sr_tariffs',
				array(
					'currency_id'       => $tariff['currency_id'],
					'customer_group_id' => $tariff['customer_group_id'],
					'valid_from'        => $tariff['valid_from'],
					'valid_to'          => $tariff['valid_to'],
					'room_type_id'      => $tariff['room_type_id'],
					'title'             => $tariff['title'],
					'description'       => $tariff['description'],
					'd_min'             => $tariff['d_min'],
					'd_max'             => $tariff['d_max'],
					'd_interval'        => $tariff['d_interval'],
					'p_min'             => $tariff['p_min'],
					'p_max'             => $tariff['p_max'],
					'type'              => $tariff['type'],
					'limit_checkin'     => $tariff['limit_checkin'],
					'state'             => $tariff['state'],
					'mode'              => (isset($tariff['mode']) ? $tariff['mode'] : 0),
				)
			);

			$insert_id = $this->wpdb->insert_id;
		}

		$tariff_id =  isset($tariff['id']) && $tariff['id'] > 0 ? $tariff['id'] : $insert_id;

		// Now process the tariff details (ref: J:onTariffAfterSave)
		if (isset($tariff['details'])) {
			foreach ($tariff['details'] as $tariff_type => $details) {
				foreach ($details as $detail) {
					$detail['tariff_id'] = $tariff_id;
					if (($tariff['type'] == 1 || $tariff['type'] == 3)
					    && substr($detail['guest_type'], 0, 5) == 'child')
					{
						$detail['from_age'] = $tariff[$detail['guest_type']]['from_age'];
						$detail['to_age'] = $tariff[$detail['guest_type']]['to_age'];
					}

					$solidres_tariff_detail = new SR_Tariff_Detail();
					$solidres_tariff_detail->save($detail);
				}
			}
		}

		return $tariff_id ;
	}

	public function enableTariff($id)
	{
		return $this->wpdb->query( $this->wpdb->prepare("UPDATE {$this->wpdb->prefix}sr_tariffs SET state = 1 WHERE id = %d", $id) );
	}

	public function disableTariff($id)
	{
		return $this->wpdb->query( $this->wpdb->prepare("UPDATE {$this->wpdb->prefix}sr_tariffs SET state = 0 WHERE id = %d", $id) );
	}
}