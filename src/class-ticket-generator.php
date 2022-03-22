<?php

class TicketGenerator {

    public function __construct() {
        add_action('admin_menu', [$this, 'onAdminMenu'], 90 );
    }

    public function onAdminMenu() {

        add_submenu_page(
            'edit.php?post_type=event',
            'Email Tickets',
            'Email Event Tickets',
            'manage_options',
            'events-email-tickets',
            [$this, 'form']
        );
    }

    public function form() {

        $errors = [];

        // Step 3
            // Process the emails
            // queue system? send limits?
            // Add to queue (CPT?)
                // content,
                // sent date


        if( isset( $_POST['submit'] )) {
            check_admin_referer( 'email-tickets-'.get_current_user_id(), 'events-manager-checkin-tickets' );

            if( empty( $_POST['event_id'] ) ) {
                $errors[] = __('Please select an event', 'events-manager-checkin-tickets');
            }

            if( !empty( $_POST['purchased_since'] ) ) {
                try {
                    $date = new DateTime( '2020-13-32' );// $_POST['purchased_since'] );
                } catch ( exception $e ) {
                    $errors[] = __('Please enter a valid date', 'events-manager-checkin-tickets');
                }
            }


            if( empty( $errors ) ) {

                #echo '<pre>';var_dump( $_REQUEST );echo '</pre>';

                // Look up all confirmed bookings
                $event    = new EM_Event( absint( $_POST['event_id'] ) );
                $bookings = new EM_Bookings( $event );
                $tickets  = [];

                foreach( $bookings->get_bookings() as $booking ) {

                    // Get email of user who booked
                    $person = $booking->get_person();
                    #echo '<pre>';var_dump( $person );echo '</pre><hr />';

                    echo '<pre>';var_dump( $booking );echo '</pre><hr />';
                    foreach( $booking->get_tickets_bookings() as $ticket_booking ) {
                        echo '<pre>';var_dump( $ticket_booking );echo '</pre>';

                        $ticket = $ticket_booking->get_ticket();

                        for($i = 1; $i <= $ticket_booking->ticket_booking_spaces; $i++ ) {
                            $tickets[ $person->user_email ][] = [
                                'id'     => $booking->booking_id . '_' . $ticket->ticket_id . '_' .$i,
                                'email'  => $person->user_email,
                                'name'   => $person->get_name(),
                                'ticket' => $ticket->ticket_name,
                                'date'   => $booking->date()->format()
                            ];
                        }
                    }
                    echo '<hr />';
                }

                echo '<pre>';var_dump( $tickets );echo '</pre>';

                // Include step template
                // Sample message, list of users to be emailed
                // Hidden form for step 3
                //return;
            }

        }


        include 'templates/email-tickets-form.php';
    }
}
