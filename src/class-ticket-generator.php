<?php

class TicketGenerator {

	private $current_tickets;

	public function __construct() {
		add_action( 'admin_menu', [$this, 'onAdminMenu'], 90 );
		add_action( 'add_meta_boxes_'.EM_POST_TYPE_EVENT, [$this, 'ticketEmailCopyMetaBox'], 11 );
		add_action( 'save_post', [$this, 'saveTicketEmailCopy'] );
		add_action( 'em_bookings_single_metabox_footer', [$this, 'singleBookingSendTicketForm'] );
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

				$person_tickets = [];
				$batch_qty = isset( $_POST['batch_qty'] ) ? absint( $_POST['batch_qty'] ) : 10;

				if( isset( $_POST[ 'booking_id' ] ) ) {
					$em_booking = new EM_Booking( $_POST[ 'booking_id' ] );
					$bookings = new EM_Bookings();

					if( $em_booking->booking_id ) {
						$bookings->bookings = [ $em_booking ];
					}
				}else{
					// Look up all confirmed bookings
					$bookings = new EM_Bookings( $event );
				}

				foreach( $bookings->get_bookings() as $booking ) {

					if( !isset( $_POST['force_send'] ) && isset( $booking->booking_meta['tickets_emailed'] ) ) {
						// Do not send if already sent
						continue;
					}

					// Get email of user who booked
					$person = $booking->get_person();

					foreach( $booking->get_tickets_bookings() as $ticket_booking ) {

						$ticket = $ticket_booking->get_ticket();

						for($i = 1; $i <= $ticket_booking->ticket_booking_spaces; $i++ ) {

							$qr_string = $booking->booking_id . '-' . $ticket->ticket_id . '-' .$i;
							if( isset( $_POST['append_email_qr'] ) && $_POST['append_email_qr'] ) {
								$qr_string.= ' '.$person->user_email;
							}

							$person_tickets[ $person->user_email ][] = [
								'qr_str'       => $qr_string,
								'email'        => $person->user_email,
								'name'         => $person->get_name(),
								'ticket'       => $ticket->ticket_name,
								'date'         => $booking->date()->format('d/m/Y \a\t H:i'),
								'bk_id'        => $ticket_booking->booking_id,
								'wc_oid'       => ( isset( $booking->booking_meta['woocommerce'] ) ? $booking->booking_meta['woocommerce']['order_id'] : null ),
								'booking_form' => $booking->meta['booking']
							];
						}
					}
				}

				// Refactor to have confirmation screen?
				// Include step template
				// Sample message, list of users to be emailed
				// Hidden form for step 3


				if( empty( $person_tickets ) ) {
					$errors[] = __('No ticket bookings with found for search criteria.', 'events-manager-checkin-tickets');
				}
			}

			if( empty( $errors ) ) {

				#global $EM_Mailer;
				echo '<p>'.__('Sending emails:', 'events-manager-checkin-tickets').'</p>';

				add_filter( 'em_event_output_placeholder', [$this, 'onEmEventOutputPlaceholder'], 10, 5 );

				$i = 0;

				foreach( $person_tickets as $email => $tickets ) {

					$user = get_user_by( 'email', $email );
					if($user) {
						$name = get_user_meta( $user->ID, 'first_name', true );
					}else{
						$name = $tickets[0]['name'];
					}

					$subject = $_POST['subject'];
					$mpdf    = new \Mpdf\Mpdf(['debug' => true]); // Debug true so we get a warning instead of an empty pdf in the case of issues

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
echo $content;die;
					$mpdf->WriteHTML($content);
					#$mpdf->Output();

					$pdf_filename = $event->event_slug . '-tickets.pdf';
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

	public function singleBookingSendTicketForm( $em_booking ) {
		?>
		<div id="em-gateway-send-tickts" class="stuffbox">
			<h3>
				<?php _e('Send Tickets', 'events-manager-checkin-tickets'); ?>
			</h3>
			<div class="inside">
				<p><?php _e('Send or resend check in tickets to the user for this booking', 'events-manager-checkin-tickets'); ?></p>

				<form action="<?php echo admin_url( 'edit.php?post_type=event&page=events-email-tickets' ) ?>" method="POST">
					<?php wp_nonce_field( 'email-tickets-'.get_current_user_id(), 'events-manager-checkin-tickets' ); ?>
					<input type="hidden" name="event_id" value="<?php echo $em_booking->event_id ?>" />
					<input type="hidden" name="booking_id" value="<?php echo $em_booking->booking_id ?>" />
					<input type="hidden" name="force_send" value="1" />

					<table class="form-table">
						<tbody>
							<tr>
								<th><label for="subject"><?php _e('Email subject', 'events-manager-checkin-tickets' ) ?></label></th>
								<td>
									<input type="text" name="subject" class="regular-text" /><br />
									<em><?php _e('Leave blank for the default  {event-name} tickets for {firstname}.', 'events-manager-checkin-tickets' ) ?></em>
								</td>
							</tr>
							<tr>
								<th></th>
								<td>
									<?php if( isset( $em_booking->booking_meta['tickets_emailed'] ) ) : ?>
										<p><?php echo sprintf( __( 'Check in tickets emailed to the user for this booking on %s', 'events-manager-checkin-tickets'), date('d/m/Y H:i') ); ?>
										<p><input type="submit" name="submit" class="button button-primary" value="<?php _e('Re-send Tickets', 'events-manager-checkin-tickets'); ?>" /></p>
									<?php else: ?>
										<p><input type="submit" name="submit" class="button button-primary" value="<?php _e('Send Tickets', 'events-manager-checkin-tickets'); ?>" /></p>
									<?php endif; ?>
								</td>
						</tbody>
					</table>
				</form>
			</div>
		</div>
		<?php
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
