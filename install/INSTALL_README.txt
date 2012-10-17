Requirements:
PHP 5.2.4 or later
Mysql

Install:
1. Place the folder somewhere in your web root.

2. Connect to mysql and create the database and user.

Example:
create database therm
create user 'therm'@'localhost' identified by 'SOMEPASS';
grant all on therm.* to 'therm'@'localhost';

3. Create the tables

Modify the install/create_tables.sql

At the end, enter statements for each thermostat you wish to configure:

INSERT INTO `thermostats` (`ip`,`name`,`model`) VALUES ('192.168.1.171',
'Downstairs','CT30');

Then create the tables and import the data:
Example:
mysql therm -p < install/create_tables.sql
where therm is the database and -p prompts for the admin user password

4. You may need to setup mysql timezone data:

mysql_tzinfo_to_sql /usr/share/zoneinfo | mysql -u root mysql

5. Modify config.php

Set the zip code and the type of external weather (temperature/humidity) API to use.

6. Manually run scripts/thermo_update_status.php and scripts/thermo_update_temps.php

7. Add the scripts to the cron job/scheduled tasks. See install/create_schedule