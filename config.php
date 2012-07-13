<?php

// IP address of your thermostat on your network
$thermostatIP = "192.168.0.1";

// Your ZIP code
$ZIP = "90210";

// Server and port for your MySQL instance
$host = "localhost:3306";
// ID with permission to access the thermostat database
$user = "user";
$pass = "password";
// Database name.  Default is "thermo"
$db = "thermo";
// Prefix to attach to all table/procedure names to make unique in unknown environment.
$table_prefix = "thermo_";

$timezone = "America/Chicago";

// Set normal temperature range so the charts always scale the same way
$normal_low = 70;
$normal_high = 100;
// Idea for future, have separate summer/winter values
?>