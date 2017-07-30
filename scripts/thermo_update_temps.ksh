#!/usr/bin/ksh

# This grabs the current directory from the launch command
LOC=`dirname $0`

# Deal with alias or symlink or other feldercarb
LOC=`readlink -f ${LOC}`

cd ${LOC}
cd ..
. ./config.ksh

NOW=`date "+%Y-%m-%d %H:%M:%S"`
NOW_CHANGED=${NOW:0:15}"0:00"

#/usr/bin/php ${LOC}/thermo_update_temps.php "${NOW_CHANGED}"
/usr/local/php70/bin/php ${LOC}/thermo_update_temps.php "${NOW_CHANGED}"
