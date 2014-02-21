#!/usr/bin/ksh
LOC=`dirname $0`
cd ${LOC}
. ../config.ksh

NOW=`date "+%Y-%m-%d %H:%M:%S"`
NOW_CHANGED=${NOW:0:15}"0:00"

/usr/local/bin/php ${LOC}/thermo_update_temps.php "${NOW_CHANGED}"