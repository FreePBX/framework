#!/usr/bin/perl -Tw
# Retrieves the sip user/peer entries from the database
# Use these commands to create the appropriate tables in MySQL
#
#CREATE TABLE sip (id INT(11) DEFAULT -1 NOT NULL,keyword VARCHAR(20) NOT NULL,data VARCHAR(50) NOT NULL, flags INT(1) DEFAULT 0 NOT NULL,PRIMARY KEY (id,keyword));
#
# if flags = 1 then the records are not included in the output file

use DBI;
################### BEGIN OF CONFIGURATION ####################

# the name of the extensions table
$table_name = "extensions";
# the path to the extensions.conf file
# WARNING: this file will be substituted by the output of this program
$meetme_conf = "/etc/asterisk/meetme_additional.conf";
# the name of the box the MySQL database is running on
$hostname = "localhost";
# the name of the database our tables are kept
$database = "asterisk";
# username to connect to the database
$username = "AMPDBUSER";
# password to connect to the database
$password = "AMPDBPASS";

################### END OF CONFIGURATION #######################
open EXTEN, ">$meetme_conf" or die "Cannot create/overwrite meetme file: $meetme_conf\n";

$dbh = DBI->connect("dbi:mysql:dbname=$database;host=$hostname", "$username", "$password");

$statement = "SELECT extension from $table_name WHERE context = 'ext-local' AND flags = 0";

$result = $dbh->selectall_arrayref($statement);
unless ($result) {
  # check for errors after every single database call
  print "dbh->selectall_arrayref($statement) failed!\n";
  print "DBI::err=[$DBI::err]\n";
  print "DBI::errstr=[$DBI::errstr]\n";
}

@resultSet = @{$result};
if ( $#resultSet == -1 ) {
  print "No extensions defined in $table_name\n";
  exit;
}

foreach my $row ( @{ $result } ) {
	my $meetme = @{ $row }[0];
	print EXTEN "conf => 8$meetme\n";
}

exit 0;
