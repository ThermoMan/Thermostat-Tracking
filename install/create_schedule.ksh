#!/usr/bin/ksh

# Replace this with a shell script that creates unix cron jobs shown in the lines below

* * * * * /usr/bin/php ~/thermo2/scripts/thermo_update_status.php >> ~/thermo2/logs/thermoupdatestatus.log

0,30 * * * * /usr/bin/php ~/thermo2/scripts/thermo_update_temps.php >> ~/thermo2/logs/thermoupdatetemps.log