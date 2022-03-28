Hi <?php echo $user->first_name ?>,

<?php echo $message ?>

<?php foreach( $tickets as $ticket ) : ?>

<hr />

<strong><?php echo $ticket['ticket'] ?></strong>

Purchased on <?php echo $ticket['date'] ?>

<img src="<?php echo plugins_url('events-manager-tickets-for-checkin-apps/src/qrcode.php?s=qrl&sf=10&d='.$ticket['qr_str'] ) ?>" />

<?php endforeach; ?>