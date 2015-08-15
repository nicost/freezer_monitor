# freezer_monitor
PHP scripts to automate monitoring of Omega Temperature sensors

This code automates monitoring of the Omega UWTC-REC3 wireless receiver (http://www.omega.com/pptst/UWTC-REC3.html).
It consists of two cron jobs, one of which reads of the receiver and stores the temperetures in a rrdtool database.
The other script reads the temperatures back out of the database, and sends out an alert if the average temp of the
last 30 minutes was higer than a preset maximum.  Only a single alert (email) is send out, until a lockfile is 
cleared.


Freezer Monitor Setup Instructions
v1 June 2014 J.DeRisi

Set up:
(Assumes a linux/apache/php system with rrdtool already installed)

1. Copy the files in the repository to your web directory.

2. Edit "freezer_config.dat"
        Each line corresponds to a freezer to monitor and consists of 5 fields.
        0: temperature probe id. Corresponds to the id you set for the remote probe
        1: rrdtool database name (without the .rrd extension)
        2: legend name for the graph
        3: html line color for the graph.
        4: ip address (ie. tcp://myfreezer.ucsf.edu or tcp://111.111.111.111 )

3. Edit "freezers_contact.dat"
        Each line corresponds to an email contact.
        Three fields, tab delimited. You can have multiple contacts per freezer.
        0: rrdtool database name (without the .rrd extension)
        1: email address
        2: short name of addressee (ie. joe)
        
4. Edit the file "EMailInfo.php" 
        - Make sure that the provided information is valid so that an email can be send.
        - Also edit the function getSysAdminEmail to direct mesages pertaining to the monitoring system itself.

4. Edit "thermo_includes.php"
        - At the top, change titles for the graphs, the receiver IP addresses, and webpage title
        - Change $threshold if you want something else besides -60

5. Make the databases.
        In the same directory create the rrdtool databases: (change filename of course)

rrdtool create myfreezer1.rrd --step 300 DS:freezer1:GAUGE:600:-100:30 RRA:AVERAGE:0.5:1:2016

        Make sure the file is read/writeable by apache/php or all

6. Test the connection to the receivers. Run crontemp-v3.php from your web browser
        http://replaceme/crontemp-v3.php

        You should get a list of the receivers and the raw data from each.
        If successful, each database file will be updated, graphs will be made.
        Load freezers_m.html to see the graphs (not enough data yet, though)

        RAW output from the receiver will look like:

        RAW:
        1 230 230 -76.6 C 22.7 C
        3 231 230 -91.1 C 22.7 C
        etc...

7. Set up the cron jobs. You can do this by editing the crontab file directly, or use
webmin "Scheduled Cron Jobs" under system

        Every ~5 minutes, run:
        curl http://myipaddress/crontemp-v3.php > /dev/null 2>&1

        Every ~5 minutes run:
        curl http://myipaddress/cronwatch-v1.php > /dev/null 2>&1

        crontemp logs the data and makes the graphs. cronwatch checks thresholds, emails if
        necessary.
