<?php
/**
	*  While each of the thermo and elec projects are stand-alone, the goal was
	* always to be able to combine the data sets.  To that end, the respective
	* index.php files get a rename and a new index takes thier place.  This is
	* step 1 the combination process.  Step 2 will be to allow teh two projects
	* to be installed in the same directory and operate with a common infrastrcture.
	*
	*  The combinatin code will be a new project that leverages exsiting portions
	* of the other two projects and will not by itself be a stand-alone project.
	*
	*/

$thermo_page = 'thermo.php';
$elec_page = 'elec.php';

if( file_exists( $thermo_page ) )
{
	header( 'location:'.$thermo_page );
}
if( file_exists( $elec_page ) )
{
	header( 'location:'.$elec_page );
}

?>