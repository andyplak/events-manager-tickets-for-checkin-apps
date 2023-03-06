<h2>Hi <?php echo get_user_meta( $user->ID, 'first_name', true ); ?>,</h2>
<h2>Here are your <?php echo strip_tags( $event->event_name ) ?> tickets:</h2>
<style>
table, th, td {
  border: 1px solid black;
  border-collapse: collapse;
  padding: 8px;
}
</style>
<table style="width:50%">
    <tr>
        <td style="background-color:#419E8A;">
            <img src="https://www.quirkycampers.com/uk/wp-content/uploads/2023/02/Camp-Quirky-Logo.png" style="width:150px;height:150px;">
        </td>
        <td>
            <i><p>Please download and show me on your mobile device on arrival.</p>
            As we are a sustainable event, please only print me if <strong>absolutely necessary</strong>. For the sake of the trees.</i>
        </td>
    </tr>
</table>
<br>

<?php foreach( $tickets as $ticket ) : ?>
    <hr />
    <br>
    <table style="width:50%">
        <tr>
            <td><strong><?php echo $ticket['ticket'] ?></strong></td>
        </tr>
    </table>

    <table style="width:50%">
        <tr>
            <td><strong>Name</strong></td>
            <td><?php echo $ticket['name'] ?></td>
        </tr>
        <tr>
            <td><strong>Email</strong></td>
            <td><?php echo $ticket['email'] ?></td>
        </tr>
        <?php if( $ticket['wc_oid'] ): ?>
        <tr>
            <td><strong>Order ID</strong></td>
            <td><?php echo $ticket['wc_oid'] ?></td>
        </tr>
        <?php endif; ?>
        <tr>
            <td><strong>Ticket ID</strong></td>
            <td><?php echo str_replace( $ticket['email'], '', $ticket['qr_str'] ) ?></td>
        </tr>
    </table>
    <table style="width:50%">
        <tr>
            <td>
                <img src="<?php echo plugins_url('events-manager-tickets-for-checkin-apps/src/qrcode.php?s=qrl&sf=10&d='.$ticket['qr_str'] ) ?>" />
            </td>
        </tr>
    </table>
    <br>
    <?php _dump( $ticket['reg_fields'] ) ?>
    <table style="width:50%">
        <tr>
            <td><strong>Arrival</strong></td>
            <td><?php echo $ticket['booking_form']['comment'] ?></td>
        </tr>
        <tr>
            <td><strong>Oversized</strong></td>
            <td><?php echo $ticket['booking_form']['vehicle_length'] ?></td>
        </tr>
        <tr>
            <td><strong>ACA</strong></td>
            <td><?php echo $ticket['booking_form']['accessibility'] ?></td>
        </tr>
    </table>
<?php endforeach; ?>