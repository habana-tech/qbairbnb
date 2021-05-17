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

$wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_tariffs ADD COLUMN mode TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' AFTER state;" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_tariff_details ADD COLUMN `date` DATE NULL DEFAULT NULL AFTER to_age;" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_tariffs ADD d_interval TINYINT UNSIGNED NULL DEFAULT 0 AFTER d_max;" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_tariffs CHANGE d_min d_min SMALLINT NULL DEFAULT NULL;" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_tariffs CHANGE d_max d_max SMALLINT NULL DEFAULT NULL;" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_tariff_details ADD min_los INT(11) UNSIGNED NULL DEFAULT NULL  AFTER date;" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_tariff_details ADD max_los INT(11) UNSIGNED NULL DEFAULT NULL  AFTER min_los;" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_room_types ADD occupancy_child_age_range TINYINT(2) UNSIGNED NOT NULL DEFAULT '0' AFTER occupancy_child;" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_reservations CHANGE `payment_method_id` `payment_method_id` VARCHAR(50) NOT NULL DEFAULT '0';" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_reservations CHANGE `payment_method_txn_id` `payment_method_txn_id` VARCHAR(100) NULL DEFAULT NULL;" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_reservations CHANGE `code` `code` VARCHAR(100) NOT NULL;" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_reservations CHANGE `customer_vat_number` `customer_vat_number` VARCHAR(50) NULL DEFAULT NULL;" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_reservations CHANGE `origin` `origin` VARCHAR(50) NULL DEFAULT NULL;" );
$wpdb->query( "ALTER TABLE {$wpdb->prefix}sr_reservations DROP COLUMN `invoice_number`;" );