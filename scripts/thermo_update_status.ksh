#!/usr/bin/ksh

## Deal with alias or symlink or other feldercarb
realpath ()
{
	f=$@;
	if [ -d "$f" ]; then
		base="";
		dir="$f";
	else
		base="/$(basename "$f")";
		dir=$(dirname "$f");
	fi;
	dir=$(cd "$dir" && /bin/pwd);
	echo "$dir$base"
}

# This grabs the current directory from the launch command
LOC=`dirname $0`
#LOC=`realpath ${LOC}`
cd ${LOC}
. ../config.ksh

NOW=`date "+%Y-%m-%d %H:%M:%S"`

/usr/local/bin/php ${LOC}/thermo_update_status.php