<?php

/**
 * Plugin Name: Events Manager Tickets for Check In Apps
 * Plugin URI: https://github.com/andyplak/events-manager-covid-bonds
 * Description: Email QR code tickets to users with bookings for events. For use with Check In Apps like Zkipster, RSVPify, GuestManager etc.
 * Version: 1.2
 * Author: Andy Place
 * Author URI: http://www.andyplace.co.uk/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

function em_checkin_init() {

	if( is_admin() ) {
		require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
		require plugin_dir_path( __FILE__ ) . 'src/class-ticket-generator.php';
		require plugin_dir_path( __FILE__ ) . 'src/class-bookings-exporter.php';
		new TicketGenerator();
		new BookingsExporter();
	}
}
add_action( 'plugins_loaded', 'em_checkin_init', 20 );