#!/usr/bin/perl -w
# retrieve_backup_cron_from_mysql.pl Copyright (C) 2005 VerCom Systems, Inc. & Ron Hartmann (rhartmann@vercomsystems.com)
# Asterisk Management Portal Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)

# this program is in charge of looking into the database and creating crontab jobs for each of the Backup Sets
# The crontab file is for user asterisk.
#
# The program preserves any other cron jobs (Not part of the backup) that are installed for the user asterisk 
#

# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.

# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.



use DBI;
################### BEGIN OF CONFIGURATION ####################

# the name of the extensions table
$table_name = "Backup";
# the path to the extensions.conf file
# WARNING: this file will be substituted by the output of this program
$Backup_cron = "/etc/asterisk/backup.conf";
# the name of the box the MySQL database is running on
$hostname = "localhost";
# the name of the database our tables are kept
$database = "asterisk";

################### END OF CONFIGURATION #######################
open(FILE, "/etc/amportal.conf") || die "Failed to open amportal.conf\n";
while (<FILE>) {
    chomp;                  # no newline
    s/#.*//;                # no comments
    s/^\s+//;               # no leading white
    s/\s+$//;               # no trailing white
    next unless length;     # anything left?
    my ($var, $value) = split(/\s*=\s*/, $_, 2);
    $User_Preferences{$var} = $value;
} 
close(FILE);

# username to connect to the database
$username = $User_Preferences{"AMPDBUSER"} ;
# password to connect to the database
$password = $User_Preferences{"AMPDBPASS"}; 


open EXTEN, ">$Backup_cron" or die "Cannot create\/overwrite cron file: $Backup_cron\n";

$dbh = DBI->connect("dbi:mysql:dbname=$database;host=$hostname", "$username", "$password");

$statement = "SELECT Command, ID from $table_name";

$result = $dbh->selectall_arrayref($statement);
unless ($result) {
  # check for errors after every single database call
  print "dbh->selectall_arrayref($statement) failed!\n";
  print "DBI::err=[$DBI::err]\n";
  print "DBI::errstr=[$DBI::errstr]\n";
}

@resultSet = @{$result};
if ( $#resultSet == -1 ) {
  print "No Backup Schedules defined in $table_name\n";
  #grab any other cronjobs that are running as asterisk and NOT associated with backups
	system ("/usr/bin/crontab -l | grep -v ampbackup.pl  >> $Backup_cron ");
	#issue the schedule to the cron scheduler
	system ("/usr/bin/crontab $Backup_cron");
  exit;
}

foreach my $row ( @{ $result } ) {
	my $Backup_Command = @{ $row }[0];
	my $Backup_ID = @{ $row }[1];
	print EXTEN "$Backup_Command $Backup_ID\n";
}
	#grab any other cronjobs that are running as asterisk and NOT associated with backups
	system ("/usr/bin/crontab -l | grep -v ampbackup.pl  >> $Backup_cron ");
	#issue the schedule to the cron scheduler
	system ("/usr/bin/crontab $Backup_cron");

exit 0;
