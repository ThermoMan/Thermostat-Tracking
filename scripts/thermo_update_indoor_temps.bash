#!/bin/bash +x

# This grabs the current directory from the launch command
LOC=`dirname $0`

# Deal with alias or symlink or other feldercarb
LOC=`readlink -f ${LOC}`

cd ${LOC}
cd ..
. ./config.bash

NOW_CHANGED=${NOW:0:15}"0:00"

${PHP} ${LOC}/thermo_update_indoor_temps.php "${NOW_CHANGED}"
