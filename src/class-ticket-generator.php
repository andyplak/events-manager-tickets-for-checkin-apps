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

			if( empty( $_POST['subject'] ) ) {
				$errors[] = __('Please add the email subject', 'events-manager-checkin-tickets');
			}

			if( !empty( $_POST['purchased_since'] ) ) {
				try {
					$date = new DateTime( $_POST['purchased_since'] );
				} catch ( exception $e ) {
					$errors[] = __('Please enter a valid date', 'events-manager-checkin-tickets');
				}
			}

			if( empty( $errors ) ) {

				// Look up all confirmed bookings
				$event    = new EM_Event( absint( $_POST['event_id'] ) );
				$bookings = new EM_Bookings( $event );
				$person_tickets  = [];

				foreach( $bookings->get_bookings() as $booking ) {

					// Get email of user who booked
					$person = $booking->get_person();

					foreach( $booking->get_tickets_bookings() as $ticket_booking ) {

						$ticket = $ticket_booking->get_ticket();

						for($i = 1; $i <= $ticket_booking->ticket_booking_spaces; $i++ ) {

							$qr_string = $booking->booking_id . '-' . $ticket->ticket_id . '-' .$i;
							if( $_POST['append_email_qr'] ) {
								$qr_string.= ' '.$person->user_email;
							}

							$person_tickets[ $person->user_email ][] = [
								'qr_str' => $qr_string,
								'email'  => $person->user_email,
								'name'   => $person->get_name(),
								'ticket' => $ticket->ticket_name,
								'date'   => $booking->date()->format('d/m/Y \a\t H:i')
							];
						}
					}
				}

				// Refactor to have confirmation screen?
				// Include step template
				// Sample message, list of users to be emailed
				// Hidden form for step 3

				#global $EM_Mailer;
				echo '<p>'.__('Sending emails:', 'events-manager-checkin-tickets').'</p>';

				foreach( $person_tickets as $email => $tickets ) {
					$user    = get_user_by( 'email', $email );
					$subject = $_POST['subject'];
					$message = $_POST['message'];
					$mpdf    = new \Mpdf\Mpdf(['debug' => true]);

					ob_start();
					include 'templates/email-body.php';
					$body = ob_get_contents();
					ob_end_clean();

					ob_start();
					include 'templates/pdf-body.php';
					$content = ob_get_contents();
					ob_end_clean();

					$mpdf->WriteHTML($content);
					#$mpdf->Output();

					$pdf_filename = $event->event_slug . '.pdf';
					$pdf_content  = $mpdf->output($pdf_filename, \Mpdf\Output\Destination::STRING_RETURN );
					$pdf_path     = EM_Mailer::add_email_attachment( $pdf_filename, $pdf_content );

					// EM Mailer in compatible with Post SMTP plugin
					#$attachment   = [
					#	'name'   => 'EventTickets.pdf',
					#	'type'   => 'application/pdf',
					#	'path'   => $pdf_path,
					#	'delete' => true
					#];
					#
					#$sent = $EM_Mailer->send( $subject, $body, $email, [ $attachment ] );

					// Use WP Mail instead
					$headers = ['Content-Type: text/html; charset=UTF-8'];
					$sent = wp_mail( $email, $subject, $body, $headers, [ $pdf_path ] );

					$notice_class = $sent ? 'notice-success' : 'notice-error';
					echo '<div class="notice '.$notice_class.'">'.$email.'</div>';
				}
				echo '</ul>';

				return;
			}
		}

		include 'templates/email-tickets-form.php';
	}
}
