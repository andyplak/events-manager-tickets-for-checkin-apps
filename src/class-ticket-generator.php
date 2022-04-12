<?php

class TicketGenerator {

	private $current_tickets;

	public function __construct() {
		add_action( 'admin_menu', [$this, 'onAdminMenu'], 90 );
		add_action( 'add_meta_boxes_'.EM_POST_TYPE_EVENT, [$this, 'ticketEmailCopyMetaBox'], 11 );
		add_action( 'save_post', [$this, 'saveTicketEmailCopy'] );
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
					$date = new DateTime( $_POST['purchased_since'] );
				} catch ( exception $e ) {
					$errors[] = __('Please enter a valid date', 'events-manager-checkin-tickets');
				}
			}

			$event = new EM_Event( absint( $_POST['event_id'] ) );

			if( is_null( $event->ID ) ) {
				$errors[] = __('Event not found', 'events-manager-checkin-tickets');
			}

			$message = get_post_meta( $event->post_id, '_tickets_email_copy', true);
			if( !$message ) {
				$errors[] = __('Ticket email message not configured. Please edit the event to set this up.', 'events-manager-checkin-tickets');
			}

			if( empty( $errors ) ) {

				// Look up all confirmed bookings
				$bookings = new EM_Bookings( $event );
				$person_tickets = [];
				$batch_qty = absint( $_POST['batch_qty'] );

				foreach( $bookings->get_bookings() as $booking ) {

					if( isset( $booking->booking_meta['tickets_emailed'] ) ) {
						// Do not send if already sent
						continue;
					}

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
								'date'   => $booking->date()->format('d/m/Y \a\t H:i'),
								'bk_id'  => $ticket_booking->booking_id
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

				add_filter( 'em_event_output_placeholder', [$this, 'onEmEventOutputPlaceholder'], 10, 5 );

				$i = 0;

				foreach( $person_tickets as $email => $tickets ) {

					$user    = get_user_by( 'email', $email );
					$subject = $_POST['subject'];
					$mpdf    = new \Mpdf\Mpdf(['debug' => true]);

					if( !$subject ) {
						$subject = strip_tags( $event->event_name );
						$subject.= ' '.__('for', 'events-manager-checkin-tickets').' ';
						$subject.= $user->first_name;
					}

					// Store tickets for use in event->output filters
					$this->current_tickets = $tickets;
					$body = $event->output( $message );

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

					if( $sent ) {
						// Log time sent against the original booking
						$bookings_email_sent = [];

						foreach( $tickets as $ticket ) {
							$bookings_email_sent[] = $ticket['bk_id'];
						}
						$bookings_email_sent = array_unique( $bookings_email_sent, SORT_NUMERIC );

						foreach( $bookings_email_sent as $booking_id ) {
							$em_booking = new EM_Booking( $booking_id );
							$em_booking->update_meta( 'tickets_emailed', date('U') );
						}
					}

					$i++;
					if( $i == $batch_qty ) {
						break;
					}
				}

				remove_filter( 'em_event_output_placeholder', [$this, 'onEmEventOutputPlaceholder'], 10, 5 );

				return;
			}
		}

		include 'templates/email-tickets-form.php';
	}

	public function ticketEmailCopyMetaBox( $post ) {
		global $EM_Event;

		if( get_option('dbem_rsvp_enabled') && !empty($EM_Event->event_id) && $EM_Event->event_rsvp ) {
			add_meta_box(
				'em-event-tickets-email',
				__('Tickets Email', 'events-manager-checkin-tickets'),
				[$this ,'metaBoxTicketEmail'],
				EM_POST_TYPE_EVENT,
				'normal',
				'default'
			);
		}
	}

	public function metaBoxTicketEmail() {
		global $post;

		include 'templates/ticket-email-metabox.php';
	}

	public function saveTicketEmailCopy() {
		global $post;

		if(isset($_POST['tickets_email_copy'])){
			update_post_meta( $post->ID, '_tickets_email_copy', $_POST['tickets_email_copy'] );
		}
	}

	public function onEmEventOutputPlaceholder( $replace, $event, $full_result, $target, $placeholder_atts ) {

		switch( $full_result ) {
			case '#_BOOKINGFIRSTNAME' :
				$user = get_user_by('email', $this->current_tickets[0]['email'] );
				$replace = $user->first_name;
				break;
			case '#_TICKETBREAKDOWN' :
				if( $target == 'html' ) {
					$replace = '<ul>';
					foreach( $this->current_tickets as $ticket ) {
						$replace .= '<li><strong>'.$ticket['ticket'].'</strong><br />';
						$replace .= 'Ticket ID: '.trim(str_replace( $ticket['email'], '', $ticket['qr_str'] )).'</li>';
					}
					$replace .= '</ul>';
				}else{
					// to do...
					// open source so feel free
				}
				break;
			case '#_TICKETQRCODES' :
				if( $target == 'html' ) {
					$replace = '';
					foreach( $this->current_tickets as $ticket ) {
						$replace .= '<hr /><p><strong>' . $ticket['ticket'] . '</strong></p>';
						$replace .= '<img src="' . plugins_url('events-manager-tickets-for-checkin-apps/src/qrcode.php?s=qrl&sf=10&d='.$ticket['qr_str'] ) . '" />';
					}
				}
				break;
		}

		return $replace;
	}

}
