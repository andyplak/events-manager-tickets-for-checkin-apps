<div style="margin: 0 15%">
    <h2>Hi <?php echo $name ?>,</h2>
    <h2>Here are your <?php echo strip_tags( $event->event_name ) ?> tickets:</h2>
    <style>
    table, th, td {
    border: 1px solid black;
    border-collapse: collapse;
    padding: 8px;
    }
    </style>
    <table style="width:100%">
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
    <br>
    <div style="text-center">
        <img src="https://www.quirkycampers.com/uk/wp-content/uploads/2022/11/Untitled-design-8-2.png"/>
    </div>
    <br>

    <?php foreach( $tickets as $ticket ) : ?>
        <div style="page-break-inside: avoid;">
            <br>
            <table style="width:100%">
                <tr>
                    <td colspan="2" style="text-align:center;"><strong><?php echo $ticket['ticket'] ?></strong></td>
                </tr>
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
                <tr>
                    <td colspan="2" style="text-align:center;">
                        <img src="<?php echo plugins_url('events-manager-tickets-for-checkin-apps/src/qrcode.php?s=qrl&sf=10&d='.$ticket['qr_str'] ) ?>" />
                    </td>
                </tr>
            <?php
                $display_fields = [
                    'when_are_you_planning_to_arrive_f' => 'Arrval',
                    'vehicle_length'                    => 'Oversized',
                    'accessibility'                     => 'ACA'
                ];
            ?>
            <?php foreach( $display_fields as $key => $label ) : ?>
                <?php if( isset( $ticket['booking_form'][ $key ] ) ) :?>
                    <tr>
                        <td><strong><?php echo $label ?></strong></td>
                        <td><?php echo $ticket['booking_form'][$key] ?></td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            </table>
            <br>
            <div style="text-center">
                <img src="https://www.quirkycampers.com/uk/wp-content/uploads/2022/11/Untitled-design-8-2.png"/>
            </div>
        </div>
    <?php endforeach; ?>
</div>