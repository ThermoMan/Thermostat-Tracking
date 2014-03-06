cd C:\xampp\htdocs\thermo2\

set NOW=%DATE% %TIME:~0,8%
set NOW_CHANGED=%NOW:~0,15%0:00

C:\xampp\php\php C:\xampp\htdocs\thermo2\scripts\thermo_update_temps.php "%NOW_CHANGED%"