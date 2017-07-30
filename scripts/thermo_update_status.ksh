#!/usr/bin/ksh

# This grabs the current directory from the launch command
LOC=`dirname $0`

# Deal with alias or symlink or other feldercarb
LOC=`readlink -f ${LOC}`

cd ${LOC}
cd ..
. ./config.ksh

NOW=`date "+%Y-%m-%d %H:%M:%S"`

#/usr/bin/php ${LOC}/thermo_update_status.php
/usr/local/php70/bin/php ${LOC}/thermo_update_status.php
#/usr/local/bin/php-7.1 ${LOC}/thermo_update_status.php