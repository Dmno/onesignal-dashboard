The entire project is created using the Symfony php framework.

This project allows to control an entire Onesignal push message sending platform via api. All the user needs to do is to add an api key of his account.

All the apps (app in this platform is a domain with users or subscribers attached to them) are automatically fetched and created in the system, user statistics are tracked and updated with cronjobs.

When there are apps present - the user can create notifications, send them, schedule send, control them and see their statistics.

Users can store and control images into the server that are then used in the notifications.

Created a custom automatic sending by "campaigns", campaigns are sorted by countries and allow the user to assign already created notifications for sending periodically by selecting a needed time and weekday.
+ This sending is executed by another project in a different server, since the sending happens often and with huge amounts of data.

The user can also control a bunch of settings of the server and change the main domain of all the apps in the Onesignal platform.
