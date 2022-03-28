<?php

/**
 * For the guest list, it can be desirable to export booking_tickets with every single ticket on it's own row.
 * EM gives the option of exporting per booking, or per booking_ticket 'type', where tickets are grouped respectively.
 * In certain circumstances we need every single ticket on its own row without any grouping.
 */
class BookingsExporter {

    private $ticket_space_counter = null;

	public function __construct() {
        add_action('init', [$this, 'before_em_init_actions'],10);
        add_action('em_bookings_table_export_options', [$this, 'em_bookings_table_export_options']);

        add_filter('em_bookings_table_cols_tickets_template', [$this, 'em_bookings_table_cols_tickets_template']);
        add_filter('em_bookings_table_rows_col_ticket_qr', [$this, 'em_bookings_table_rows_col_ticket_qr'], 10, 5);
	}

    /**
     * Jump in before em_init_actions and hijack export if key fields are set
     */
    public function before_em_init_actions() {
        if( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'export_bookings_csv' && wp_verify_nonce($_REQUEST['_wpnonce'], 'export_bookings_csv')) {
            if( isset( $_REQUEST['no_ticket_grouping'] ) && $_REQUEST['no_ticket_grouping'] ) {
                $this->build_csv();
            }
        }
    }

    public function em_bookings_table_export_options() {
        if( !get_option('dbem_bookings_tickets_single') ) {
            ?>
            <p>
                <?php esc_html_e('Do not group tickets by type','events-manager-checkin-tickets')?> <input type="checkbox" name="no_ticket_grouping" value="1" />
                <a href="#" title="<?php esc_attr_e('Add each ticket booking on a seperate row, irrespective of ticket type.','events-manager-checkin-tickets'); ?>">?</a>
            </p>
            <?php
        }
    }

    public function em_bookings_table_cols_tickets_template( $cols ) {
        $cols['ticket_qr'] = __('QR Code ID', 'events-manager-checkin-tickets');
        return $cols;
    }

    /**
     * Build QR code value (booking_id - ticket_id - space counter)
     */
    public function em_bookings_table_rows_col_ticket_qr($val, $EM_Booking, $EM_Bookings_Table, $format, $object) {
        $val = $EM_Booking->booking_id;

        if( get_class($object) == 'EM_Ticket_Booking' ){
			$EM_Ticket_Booking = $object;
			$EM_Ticket         = $EM_Ticket_Booking->get_ticket();

            $val .= '-' . $EM_Ticket->ticket_id;

            if( $this->ticket_space_counter ) {
                $val .= '-' . $this->ticket_space_counter;
            }
        }

        return $val;
    }

    /**
     * Prepares bookings for export and builds csv file
     *
     * build_csv is a modified version of the routing from em-actions.php but with changes to ensure every
     * single ticket per booking is on its own row, irrespective of ticket type. No sutiable hooks to change just the row data
     * so have lifted the code in it's entirity.
     */
    private function build_csv() {
        if( !empty($_REQUEST['event_id']) ){
			$EM_Event = em_get_event( absint($_REQUEST['event_id']) );
		}
		//sort out cols
		if( !empty($_REQUEST['cols']) && is_array($_REQUEST['cols']) ){
			$cols = array();
			foreach($_REQUEST['cols'] as $col => $active){
				if( $active ){ $cols[] = $col; }
			}
			$_REQUEST['cols'] = $cols;
		}
		$_REQUEST['limit'] = 0;

		//generate bookings export according to search request
		$show_tickets = !empty($_REQUEST['show_tickets']);
		$EM_Bookings_Table = new EM_Bookings_Table($show_tickets);
		header("Content-Type: application/octet-stream; charset=utf-8");
		$file_name = !empty($EM_Event->event_slug) ? $EM_Event->event_slug:get_bloginfo();
		header("Content-Disposition: Attachment; filename=".sanitize_title($file_name)."-bookings-export.csv");
		do_action('em_csv_header_output');
		echo "\xEF\xBB\xBF"; // UTF-8 for MS Excel (a little hacky... but does the job)
		if( !defined('EM_CSV_DISABLE_HEADERS') || !EM_CSV_DISABLE_HEADERS ){
			if( !empty($_REQUEST['event_id']) ){
				echo __('Event','events-manager') . ' : ' . $EM_Event->event_name .  "\n";
				if( $EM_Event->location_id > 0 ) echo __('Where','events-manager') . ' - ' . $EM_Event->get_location()->location_name .  "\n";
				echo __('When','events-manager') . ' : ' . $EM_Event->output('#_EVENTDATES - #_EVENTTIMES') .  "\n";
			}
			$EM_DateTime = new EM_DateTime(current_time('timestamp'));
			echo sprintf(__('Exported booking on %s','events-manager'), $EM_DateTime->format('D d M Y h:i')) .  "\n";
		}
		$delimiter = !defined('EM_CSV_DELIMITER') ? ',' : EM_CSV_DELIMITER;
		$delimiter = apply_filters('em_csv_delimiter', $delimiter);
		//Rows
		$EM_Bookings_Table->limit = 150; //if you're having server memory issues, try messing with this number
		$EM_Bookings = $EM_Bookings_Table->get_bookings();
		$handle = fopen("php://output", "w");

		$csv_headers = $EM_Bookings_Table->get_headers(true);
        fputcsv($handle, $csv_headers, $delimiter);

        // Note the position of the booking spaces column header
        $booking_spaces_pos = array_search( 'booking_spaces', array_keys( $csv_headers ) );

		while( !empty($EM_Bookings->bookings) ){
			foreach( $EM_Bookings->bookings as $EM_Booking ) { /* @var EM_Booking $EM_Booking */
				//Display all values
				if( $show_tickets ){
					foreach($EM_Booking->get_tickets_bookings()->tickets_bookings as $EM_Ticket_Booking){ /* @var EM_Ticket_Booking $EM_Ticket_Booking */

                        // Custom code to split ticket bookings
                        for($i = 1; $i <= $EM_Ticket_Booking->ticket_booking_spaces; $i++ ) {
                            $this->ticket_space_counter = $i;
						    $row = $EM_Bookings_Table->get_row_csv($EM_Ticket_Booking);
                            if( $booking_spaces_pos ) {
                                $row[ $booking_spaces_pos ] = 1;
                            }
						    fputcsv($handle, $row, $delimiter);
                        }
                        // End custom code
					}
				}else{
					$row = $EM_Bookings_Table->get_row_csv($EM_Booking);
					fputcsv($handle, $row, $delimiter);
				}
			}
			//reiterate loop
			$EM_Bookings_Table->offset += $EM_Bookings_Table->limit;
			$EM_Bookings = $EM_Bookings_Table->get_bookings();
		}
		fclose($handle);
		exit();
    }

}
