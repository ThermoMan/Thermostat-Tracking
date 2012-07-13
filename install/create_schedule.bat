schtasks /create /tn ThermoUpdateStatus /tr "\"C:\xampp\htdocs\Thermo\scripts\thermo_update_status.bat\" " /st 00:00 /sc minute /mo 1 /ru WINDOWS_USER /rp PASSWORD

schtasks /create /tn ThermoUpdateTemps /tr "\"C:\xampp\htdocs\Thermo\scripts\thermo_update_temps.bat\" " /st 00:00 /sc minute /mo 30 /ru WINDOWS_USER /rp PASSWORD





