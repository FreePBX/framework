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
$table_name = "sip";
# the path to the extensions.conf file
# WARNING: this file will be substituted by the output of this program
$sip_conf = "/var/www/html/panel/op_buttons.cfg";
# the name of the box the MySQL database is running on
$hostname = "localhost";
# the name of the database our tables are kept
$database = "asterisk";
# username to connect to the database
$username = "asteriskuser";
# password to connect to the database
$password = "amp109";

################### END OF CONFIGURATION #######################

$additional = "Zap/1, 1, \"External 1\", -1, 3\n";
$additional .= "Zap/2, 2, \"External 2\", -1, 3\n";
$additional .= "Zap/3, 3, \"External 3\", -1, 3\n";
$additional .= "Zap/4, 4, \"External 4\", -1, 3\n";
$btn=10;

open EXTEN, ">$sip_conf" || die "Cannot create/overwrite config file: $sip_conf\n";

print EXTEN "$additional";

$dbh = DBI->connect("dbi:mysql:dbname=$database;host=$hostname", "$username", "$password");
$statement = "SELECT keyword,data from $table_name where id=0 and keyword <> 'account' and flags <> 1";
my $result = $dbh->selectall_arrayref($statement);
unless ($result) {
  # check for errors after every single database call
  print "dbh->selectall_arrayref($statement) failed!\n";
  print "DBI::err=[$DBI::err]\n";
  print "DBI::errstr=[$DBI::errstr]\n";
  exit;
}
my @resultSet = @{$result};
if ( $#resultSet > -1 ) {
	foreach $row (@{ $result }) {
		my @result = @{ $row };
		$additional .= $result[0]."=".$result[1]."\n";
	}
}

$statement = "SELECT data,id from $table_name where keyword='account' and flags <> 1 group by data";

$result = $dbh->selectall_arrayref($statement);
unless ($result) {
  # check for errors after every single database call
  print "dbh->selectall_arrayref($statement) failed!\n";
  print "DBI::err=[$DBI::err]\n";
  print "DBI::errstr=[$DBI::errstr]\n";
}

@resultSet = @{$result};
if ( $#resultSet == -1 ) {
  print "No sip accounts defined in $table_name\n";
  exit;
}

foreach my $row ( @{ $result } ) {
	$btn++;
	my $account = @{ $row }[0];
	my $id = @{ $row }[1];
#	print EXTEN "[$account]\n";
	$statement = "SELECT keyword,data from $table_name where id=$id and keyword <> 'account' and flags <> 1 order by keyword";
	my $result = $dbh->selectall_arrayref($statement);
	unless ($result) {
		# check for errors after every single database call
		print "dbh->selectall_arrayref($statement) failed!\n";
		print "DBI::err=[$DBI::err]\n";
		print "DBI::errstr=[$DBI::errstr]\n";
		exit;
	}

	my @resSet = @{$result};
	if ( $#resSet == -1 ) {          
		print "no results\n";
		exit;
	}
	foreach my $row ( @{ $result } ) {
		my @result = @{ $row };
		if ( $result[0] eq "callerid" ) {
			$callerid = $result[1];
			$callerid =~ tr/\"<>//d;
		}
	}
	print EXTEN "SIP/$account, $btn, \"$callerid\", $account\@from-internal, 4, default\n";
}

exit 0;

