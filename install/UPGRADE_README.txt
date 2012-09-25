This is the set of instructions on how to upgrade your version 1 code install to version 2.  If you do not have a prior version installed, check the INSTALL_README.txt document for those instructions.


If at any time through the install process you encounter an error that blocks you, simply re-enable the old data collection process and disable the new one and you will not lose any data while you are working to solve the installation issues.

-- index --

1. Install the new code
2. Disable the old data collection
3. Enable the new data collection
4. Verify that it's all working.
5. Use phpMyAdmin to dump the old data.
7. Run the script to load the old data in the new tables.
8. Verify that the history is working.
9. Delete the old install and drop the old tables.


--step by step--

1. Install the new code
	a. Download the code from GitHub
	b. Unzip locally
	c. Connect to your web host and upload the files into a NEW subdirectory - not on top of your old location (suggest "thermo2")
	d. Run the SQL that creates the new tables.  Make sure to choose a NEW table prefix (suggest "thermo2__")
	e. Edit the config.php and add the relevant data
	f. Using phpMyAdmin or some other tool, add record(s) for your thermostat

2. Disable the old data collection
	a. Probably crontab if you are on a unix server.
	b. The best way for now is to comment out the line, but not remove it from your cron entry.  Add # to the front of the line

3. Enable the new data collection
	a. Probably crontab if you are on a unix server.
	b. Copy the old lines from your cron and paste them in as duplicates and then remove the comment marker form the new one and change the directory name to match your choice from step 1c
	
4. Verify that it's all working.
	The best way
	a. Using phpMyAdmin watch the hvac_status table for updates every minute
	b. Using phpMyAdmin watch the temperatures table for updates every 30 minutes
	c. Load the new web page and see if you are collecting new data
	
	The second best way
	a. Using ls -l watch the scripts sub-directory for four "touch" files.
	 thermo_update_status.start
	 thermo_update_status.end
	 thermo_update_temps.start
	 thermo_update_temps.end
	 These files should update at the same rate as the DB.
	b. Load the new web page and see if you are collecting new data.
	
5. Use phpMyAdmin to dump the old data.
	There is a set of experimental SQL to backup the database, but using phpMyAdmin is far safer.

6. Run the script to load the old data in the new tables.
	There is a set of SQL to import the data from teh old tables to the new tables.
	You may have issues with duplicates that require manual intervention.  The old tables didn't care about duplicates and the new ones do.
	
7. Verify that the history is working.
	a. Load the new web page and see if you can see teh OLD data.
	
8. Delete the old install and drop the old tables.
	a. There is some SQL that will allow you to drop the old tables.  Be VERY careful when running it.
	b. Delete the old directory (probably "thermo")
	
	
Et Voila!




