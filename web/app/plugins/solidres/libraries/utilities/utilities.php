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

/**
 * Utilities handler class
 * @package 	Solidres
 * @subpackage	Utilities
 */
class SR_Utilities {
	public static function translateDayWeekName( $inputs ) {
		$dayMapping = array(
			'0' => __( 'Sun', 'solidres' ),
			'1' => __( 'Mon', 'solidres' ),
			'2' => __( 'Tue', 'solidres' ),
			'3' => __( 'Wed', 'solidres' ),
			'4' => __( 'Thu', 'solidres' ),
			'5' => __( 'Fri', 'solidres' ),
			'6' => __( 'Sat', 'solidres' )
		);
		foreach ( $inputs as $input ) {
			$input->w_day_name = $dayMapping[$input->w_day];
		}
		return $inputs;
	}

	public static function translateText( $text ) {
		if ( strpos( $text, '{lang' ) !== false ) {
			$text = self::filterText( $text );
		}
		return $text;
	}

	public static function getTariffDetailsScaffoldings($config = array())
	{
		$scaffoldings = array();

		$dayMapping = array(
			'0' => __( 'Sun', 'solidres' ),
			'1' => __( 'Mon', 'solidres' ),
			'2' => __( 'Tue', 'solidres' ),
			'3' => __( 'Wed', 'solidres' ),
			'4' => __( 'Thu', 'solidres' ),
			'5' => __( 'Fri', 'solidres' ),
			'6' => __( 'Sat', 'solidres' )
		);

		// If this is package per person or package per room
		if ($config['type'] == 2 || $config['type'] == 3 )
		{
			$scaffoldings[0] = new stdClass();
			$scaffoldings[0]->id = null;
			$scaffoldings[0]->tariff_id = $config['tariff_id'];
			$scaffoldings[0]->price = null;
			$scaffoldings[0]->w_day = 8;
			$scaffoldings[0]->guest_type = $config['guest_type'];
			$scaffoldings[0]->from_age = null;
			$scaffoldings[0]->to_age = null;
			$scaffoldings[0]->date = null;

			return $scaffoldings;
		}
		else // For normal complex tariff
		{
			if ($config['mode'] == 0) // Mode = Week
			{
				for ($i = 0; $i < 7; $i++)
				{
					$scaffoldings[$i] = new stdClass();
					$scaffoldings[$i]->id = null;
					$scaffoldings[$i]->tariff_id = $config['tariff_id'];
					$scaffoldings[$i]->price = null;
					$scaffoldings[$i]->w_day = $i;
					$scaffoldings[$i]->w_day_name = $dayMapping[$i];
					$scaffoldings[$i]->guest_type = $config['guest_type'];
					$scaffoldings[$i]->from_age = (strpos($config['guest_type'], 'child') !== false) ? 0 : null;
					$scaffoldings[$i]->to_age = (strpos($config['guest_type'], 'child') !== false) ? 10 : null;
					$scaffoldings[$i]->date = null;
				}

				return $scaffoldings;
			}
			else // Mode = Day
			{
				$tariffDates = self::calculateWeekDay($config['valid_from'], $config['valid_to']);
				$resultsSortedPerMonth = array();
				$resultsSortedPerMonth[date('Y-m', strtotime($tariffDates[0]))] = array();

				foreach ($tariffDates as $i => $tariffDate)
				{
					$scaffoldings[$i] = new stdClass();
					$scaffoldings[$i]->id = null;
					$scaffoldings[$i]->tariff_id = $config['tariff_id'];
					$scaffoldings[$i]->price = null;
					$scaffoldings[$i]->w_day = date('w', strtotime($tariffDate));
					$scaffoldings[$i]->w_day_name = $dayMapping[$scaffoldings[$i]->w_day];
					$scaffoldings[$i]->guest_type = $config['guest_type'];
					$scaffoldings[$i]->from_age = (strpos($config['guest_type'], 'child') !== false) ? 0 : null;
					$scaffoldings[$i]->to_age = (strpos($config['guest_type'], 'child') !== false) ? 10 : null;
					$scaffoldings[$i]->date = $tariffDate;

					$currentMonth = date('Y-m', strtotime($tariffDate));
					if (!isset($resultsSortedPerMonth[$currentMonth]))
					{
						$resultsSortedPerMonth[$currentMonth] = array();
					}

					$scaffoldings[$i]->w_day_label = $dayMapping[$scaffoldings[$i]->w_day] . ' ' . date('d', strtotime($scaffoldings[$i]->date));
					$scaffoldings[$i]->is_weekend = SR_Utilities::isWeekend($scaffoldings[$i]->date);
					$scaffoldings[$i]->is_today = SR_Utilities::isToday($scaffoldings[$i]->date);
					$resultsSortedPerMonth[$currentMonth][] = $scaffoldings[$i];
				}

				return $resultsSortedPerMonth;
			}
		}
	}

	/* Translate custom field by using language tag. Author: isApp.it Team */
	public static function getLagnCode() {
		$lang_codes = JLanguageHelper::getLanguages('lang_code');
		$lang_code 	= $lang_codes[JFactory::getLanguage()->getTag()]->sef;
		return $lang_code;
	}

	/* Translate custom field by using language tag. Author: isApp.it Team */
	public static function filterText( $text ) {
		if ( strpos( $text, '{lang' ) === false ) return $text;
		$lang_code = self::getLagnCode();
		$regex = "#{lang ".$lang_code."}(.*?){\/lang}#is";
		$text = preg_replace($regex,'$1', $text);
		$regex = "#{lang [^}]+}.*?{\/lang}#is";
		$text = preg_replace($regex,'', $text);
		return $text;
	}

	/**
	 * This simple function return a correct javascript date format pattern based on php date format pattern
	 **/
	public static function convert_date_format_pattern( $input ){
		$mapping = array(
			'd-m-Y' => 'dd-mm-yy',
			'd/m/Y' => 'dd/mm/yy',
			'd M Y' => 'dd M yy',
			'd F Y' => 'dd MM yy',
			'D, d M Y' => 'D, dd M yy',
			'l, d F Y' => 'DD, dd MM yy',
			'Y-m-d' => 'yy-mm-dd',
			'm-d-Y' => 'mm-dd-yy',
			'm/d/Y' => 'mm/dd/yy',
			'M d, Y' => 'M dd, yy',
			'F d, Y' => 'MM dd, yy',
			'D, M d, Y' => 'D, M dd, yy',
			'l, F d, Y' => 'DD, MM dd, yy',
			'F j, Y' => 'MM d, yy',
			'j. F Y' => 'd. MM yy'
		);

		if ( isset( $mapping[$input] ) ) {
			return $mapping[$input];
		} else {
			return $mapping['d-m-Y'];
		}
	}

	/**
	 * Get an array of week days in the period between $from and $to
	 *
	 * @param    string   From date
	 * @param    string   To date
	 *
	 * @return   array	  An array in format array(0 => 'Y-m-d', 1 => 'Y-m-d')
	 */
	public static function calculateWeekDay($from, $to)
	{
		$datetime1 	= new DateTime($from);
		$interval 	= self::calculate_date_diff($from, $to);
		$weekDays 	= array();

		$weekDays[] = $datetime1->format('Y-m-d');

		for ($i = 1; $i <= (int)$interval; $i++)
		{
			$weekDays[] = $datetime1->modify('+1 day')->format('Y-m-d');
		}

		return $weekDays;
	}

	public static function calculate_date_diff($from, $to, $format = '%a')
	{
		$datetime1 = new DateTime($from);
		$datetime2 = new DateTime($to);

		$interval = $datetime1->diff($datetime2);

		return $interval->format($format);
	}

	public static function isApplicableForAdjoiningTariffs($roomTypeId, $checkIn, $checkOut, $excludes = array())
	{
		global $wpdb;

		$result = array();

		$query = '
				(SELECT DISTINCT t1.id
				FROM ' . ($wpdb->prefix . 'sr_tariffs') . ' AS t1
				WHERE t1.valid_to >= \''. $checkIn. '\' AND t1.valid_to <= \'' . $checkOut . '\'
				AND t1.valid_from <= \'' . $checkIn . '\' AND t1.state = 1 AND t1.room_type_id = ' . (int) $roomTypeId . '
				'. (!empty($excludes) ? 'AND t1.id NOT IN (' . implode(',', $excludes) . ')' : '' ). '
				LIMIT 1)
				UNION ALL
				(SELECT DISTINCT t2.id
				FROM ' . ($wpdb->prefix . 'sr_tariffs') . ' AS t2
				WHERE t2.valid_from <= \'' . $checkOut . '\' AND t2.valid_from >= \'' . $checkIn . '\'
				AND t2.valid_to >= \''. $checkOut . '\' AND t2.state = 1 AND t2.room_type_id = ' . (int) $roomTypeId . '
				'. (!empty($excludes) ? 'AND t2.id NOT IN (' . implode(',', $excludes) . ')' : '' ). '
				LIMIT 1)
				';

		$tariffIds = $wpdb->get_results( $query );

		if (count($tariffIds) == 2)
		{
			$query = 'SELECT datediff(t2.valid_from, t1.valid_to)
					FROM ' . ($wpdb->prefix . 'sr_tariffs') . ' AS t1, ' . ($wpdb->prefix . 'sr_tariffs') . ' AS t2
					WHERE t1.id = ' . (int) $tariffIds[0]->id . ' AND t2.id = ' . (int) $tariffIds[1]->id;

			if ($wpdb->get_var( $query ) == 1)
			{
				$result = array($tariffIds[0]->id, $tariffIds[1]->id);
			}
		}

		return $result;
	}

	public static function removeArrayElementsExcept(&$array, $keyToRemain)
	{
		foreach ($array as $key => $val)
		{
			if ($key != $keyToRemain)
			{
				unset($array[$key]);
			}
		}
	}

	/**
	 * @param        $checkin
	 * @param        $checkout
	 * @param        $tariffLimitCheckin
	 * @param string $tariffLimitCheckout
	 *
	 * @return bool
	 *
	 * @since 0.8.6
	 */
	public static function areValidDatesForTariffLimit($checkin, $checkout, $tariffLimitCheckin, $tariffLimitCheckout='')
	{
		if (is_array($tariffLimitCheckin))
		{
			$limitCheckinArray = $tariffLimitCheckin;
		}
		else
		{
			$limitCheckinArray = json_decode($tariffLimitCheckin, true);
		}

		$checkinDate = new DateTime($checkin);
		$dayInfo = getdate($checkinDate->format('U'));

		// If the current check in date does not match the allowed check in dates, we ignore this tariff
		if (!in_array($dayInfo['wday'], $limitCheckinArray))
		{
			return false;
		}

		return true;
	}

	/**
	 * Check the given tariff to see if it satisfy the occupancy options
	 *
	 * @param   $complexTariff          The tariff to check for
	 * @param   $roomsOccupancyOptions  The selected occupancy options (could be for a single room or multi rooms)
	 * @return  boolean
	 *
	 * @since 0.8.6
	 */
	public static function areValidDatesForOccupancy($complexTariff, $roomsOccupancyOptions)
	{
		if (empty($roomsOccupancyOptions))
		{
			return true;
		}

		$isValidPeopleRange = true;
		$peopleRangeMatchCount = count($roomsOccupancyOptions);

		foreach ($roomsOccupancyOptions as $roomOccupancyOptions)
		{
			if (isset($roomOccupancyOptions['guests']))
			{
				$totalPeopleRequested = $roomOccupancyOptions['guests'];
			}
			else
			{
				$totalPeopleRequested = $roomOccupancyOptions['adults'] + $roomOccupancyOptions['children'];
			}

			if ($complexTariff->p_min > 0 && $complexTariff->p_max > 0)
			{
				$isValidPeopleRange = $totalPeopleRequested >= $complexTariff->p_min && $totalPeopleRequested <= $complexTariff->p_max;
			}
			elseif ( empty($complexTariff->p_min) && $complexTariff->p_max > 0)
			{
				$isValidPeopleRange = $totalPeopleRequested <= $complexTariff->p_max;
			}
			elseif ($complexTariff->p_min > 0 && empty($complexTariff->p_max))
			{
				$isValidPeopleRange = $totalPeopleRequested >= $complexTariff->p_min;
			}

			if (!$isValidPeopleRange)
			{
				$peopleRangeMatchCount--;
			}
		}

		if ($peopleRangeMatchCount == 0)
		{
			return false;
		}

		return true;
	}

	/**
	 * Check the given tariff to see if it satisfy the length of stay (LOS) requirements
	 *
	 * @param   $complexTariff          The tariff to check for
	 * @param   $checkin
	 * @param   $checkout
	 * @param   $lengthOfStay
	 *
	 * @return  boolean
	 *
	 * @since 2.2.0
	 */
	public static function areValidDatesForLenghtOfStay($complexTariff, $checkin, $checkout, $lengthOfStay)
	{
		$solidres_tariff = new SR_Tariff();
		$tariff = $solidres_tariff->load($complexTariff->id, 1);

		foreach ($tariff->details as $type => $dates)
		{
			$minLOS = 0;
			$maxLOS = 0;
			for ($i = 0; $i < $lengthOfStay; $i++)
			{
				$dateToCheck = new DateTime($checkin);
				$dateToCheckFormatted = $dateToCheck->modify('+' . $i . ' day')->format('Y-m-d');

				if (!empty($dates[$dateToCheckFormatted]->min_los) && $dates[$dateToCheckFormatted]->min_los > $minLOS)
				{
					$minLOS = $dates[$dateToCheckFormatted]->min_los;
				}

				if (!empty($dates[$dateToCheckFormatted]->max_los) && $dates[$dateToCheckFormatted]->max_los > $maxLOS)
				{
					$maxLOS = $dates[$dateToCheckFormatted]->max_los;
				}
			}

			if (empty($minLOS) && empty($maxLOS))
			{
				return true;
			}

			if (!empty($minLOS) && $minLOS > $lengthOfStay)
			{
				return false;
			}

			if (!empty($maxLOS) && $maxLOS < $lengthOfStay)
			{
				return false;
			}

			return true;
		}
	}

	public static function isWeekend($date)
	{
		return (date('N', strtotime($date)) >= 6);
	}

	public static function isToday($date)
	{
		return date('Y-m-d') == date('Y-m-d', strtotime($date));
	}
}