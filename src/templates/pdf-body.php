<h2>Your <?php echo strip_tags( $event->event_name ) ?></h2>

<?php foreach( $tickets as $ticket ) : ?>

<hr />

<p>
    <strong><?php echo $ticket['ticket'] ?></strong><br />
    Purchased date: <?php echo $ticket['date'] ?><br />
    Ticket ID: <?php echo str_replace( $ticket['email'], '', $ticket['qr_str'] ) ?><br />
    Email: <?php echo $ticket['email'] ?><br />
</p>

<img src="<?php echo plugins_url('events-manager-tickets-for-checkin-apps/src/qrcode.php?s=qrl&sf=10&d='.$ticket['qr_str'] ) ?>" />

<?php endforeach; ?>