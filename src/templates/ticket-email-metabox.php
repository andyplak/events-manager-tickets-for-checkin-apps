<p>
    <?php _e('The copy below is used when mailing users their tickets. This does not happen automatically on purhcase, but when requested via the Email Event Tickets page.', 'events-manager-checkin-tickets'); ?>
</p>

<p>
    <?php echo sprintf(
        __('You can use the standard <a href="%s">Events Manager Placeholders</a> to customise the email as well as the following custom placeholders:', 'events-manager-checkin-tickets'),
        'https://wp-events-plugin.com/documentation/placeholders/'
    ); ?>
</p>

<ul>
    <li>#_BOOKINGFIRSTNAME</li>
    <li>#_TICKETBREAKDOWN</li>
    <li>#_TICKETQRCODES</li>
</ul>

<?php wp_editor( get_post_meta($post->ID, '_tickets_email_copy', true), 'tickets_email_copy', ['media_buttons' => false] ); ?>