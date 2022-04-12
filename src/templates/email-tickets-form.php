<div class="wrap">
	<h1><?php _e('Email Event Tickets', 'events-manager-checkin-tickets' ) ?></h1>
	<p><?php _e('Generate and email QR Code tickets based on completed bookings for an Event.', 'events-manager-checkin-tickets' ) ?><p>

	<?php if( !empty($errors) ) : ?>
		<div class="notice notice-error">
			<?php foreach( $errors as $error ) : ?>
				<p><?php echo $error ?></p>
			<?php endforeach ?>
		</div>
	<?php endif; ?>

	<form method="POST">
		<?php wp_nonce_field( 'email-tickets-'.get_current_user_id(), 'events-manager-checkin-tickets' ); ?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th><label for="event_id"><?php _e('Event', 'events-manager-checkin-tickets' ) ?></label></th>
					<td>
						<select name="event_id" class="regular-text" required>
							<option value=""><?php _e('Select Event', 'events-manager-checkin-tickets' ) ?></option>
							<?php foreach( EM_Events::get() as $event ) : ?>
								<option
									value="<?php echo $event->event_id ?>"
									<?php echo ( isset( $_POST['event_id'] ) && $_POST['event_id'] == $event->event_id ? 'selected' : '' ) ?>
								>
									<?php echo $event->event_name ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<!--
				<tr>
					<th><label for="purchased_since"><?php _e('Purchased since', 'events-manager-checkin-tickets' ) ?></label></th>
					<td><input type="date" name="purchased_since" /></td>
				</tr>
				<tr>
					<th><label for="ticket_type"><?php _e('Send Tickets', 'events-manager-checkin-tickets' ) ?></label></th>
					<td>
						<input type="radio" name="ticket_type" value="booking" /> <?php _e('Per Booking', 'events-manager-checkin-tickets' ) ?><br />
						<input type="radio" name="ticket_type" value="ticket" checked /> <?php _e('Per Ticket', 'events-manager-checkin-tickets' ) ?><br />
					</td>
				</tr>
				<tr>
					<th><label for="qr_field">Field for QR Code</label></th>
					<td>
						<input type="radio" name="qr_field" value="booking_id" /> Per Booking', 'events-manager-checkin-tickets' ) ?><br />
						<input type="radio" name="qr_field" value="email" /> Email Address', 'events-manager-checkin-tickets' ) ?><br />
					</td>
				</tr>
				-->
				<tr>
					<th><label for="append_email_qr"><?php _e('Append email to QR code', 'events-manager-checkin-tickets' ) ?></label></th>
					<td>
						<input type="checkbox" name="append_email_qr" checked />
						<em><?php _e('Append email address to the qr code identifier.', 'events-manager-checkin-tickets' ) ?></em><br />
						<em><?php _e('Can help to prevent ticket fraud on some platforms like Zkipster.', 'events-manager-checkin-tickets' ) ?></em>
					</td>
				</tr>
				<!--
				<tr>
					<th><label for="group_orders"><?php _e('Group bookings per email', 'events-manager-checkin-tickets' ) ?></label></th>
					<td>
						<input type="checkbox" name="group_orders" checked />
						<em><?php _e('For users with more than one booking, combine their tickets into a single order', 'events-manager-checkin-tickets' ) ?></em>
					</td>
				</tr>
				-->
				<tr>
					<th><label for="subject"><?php _e('Email subject', 'events-manager-checkin-tickets' ) ?></label></th>
					<td>
						<input type="text" name="subject" class="regular-text" /><br />
						<em><?php _e('Leave blank for the default  {event-name} tickets for {firstname}.', 'events-manager-checkin-tickets' ) ?></em>
					</td>
				</tr>
				<tr>
					<th><label for="batch_qty"><?php _e('Batch Quantity', 'events-manager-checkin-tickets' ) ?></label></th>
					<td>
						<select name="batch_qty" class="regular-text" required>
							<?php foreach( [10,25,50,75,100] as $qty ) : ?>
								<option
									value="<?php echo $qty ?>"
									<?php echo ( isset( $_POST['batch_qty'] ) && $_POST['batch_qty'] == $qty ? 'selected' : '' ) ?>
								>
									<?php echo $qty ?>
								</option>
							<?php endforeach; ?>
						</select>
						<br />
						<em><?php _e('To avoid hogging server resources, hitting API limits etc, run the email generation in batches.', 'events-manager-checkin-tickets' ) ?></em>
					</td>
				</tr>
				<tr>
					<th></th>
					<td><input type="submit" name="submit" class="button button-primary" value="Submit" /></td>
				</tr>
			</tbody>
		</table>
	</form>
</div>