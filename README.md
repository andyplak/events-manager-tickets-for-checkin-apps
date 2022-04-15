# Events Manager - Tickets for Check In Apps
Events Manager Add On for generating and emailing QR code based tickets to be used for checking in guests on arrival.

This plugin was born out of the need for an on site check in system for people booked into events.
One of the requirements was to have an app held by door staff that would scan QR codes and check users in quickly and effectively as they arrive.
Another, was that this app would need to work offline in the case of limited internet conectivity (think in the middle of a field for a festival).

Events Manager alone would not be able to provide this without a lot of investment. Furthermore there is no need as there are many companies
offering these type of solutions, including but not limited to the following:

- [Attendium](https://attendium.com/event-check-in-app)
- [Guest Manager](https://www.guestmanager.com/event-check-in-app/)
- [RSVPify](https://rsvpify.com/event-check-in-app/)
- [Zkipster](https://www.zkipster.com/guestlistapp/)

This plugin provides and interface between Events Manager and these check in apps. In it simplest form, it povides the following funtions:

- Emails all users booked on an event a configurable message with event details with a PDF attachment with QR codes for each ticket.
- Allows export of all ticket bookings for an event with the QR code text string ready for import into the mobile app's guest list.

A couple of points to note:
- This is a one way setup. The apps do not communicate back to Events Manager with check in info.
- The ticket emails are not sent out at the time of booking. These are designed to be done on mass at a specific point in time
and can be done in batches via the Events Manager interface or per booking via a specific link

To date (April 2022), this has been extensivly tested with zkipster.
