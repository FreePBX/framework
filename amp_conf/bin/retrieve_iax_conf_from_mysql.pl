#!/usr/bin/perl -w
# Retrieves the sip user/peer entries from the database
# Use these commands to create the appropriate tables in MySQL
#
#CREATE TABLE sip (id INT(11) DEFAULT -1 NOT NULL,keyword VARCHAR(20) NOT NULL,data VARCHAR(50) NOT NULL, flags INT(1) DEFAULT 0 NOT NULL,PRIMARY KEY (id,keyword));
#
# if flags = 1 then the records are not included in the output file

use FindBin;
push @INC, "$FindBin::Bin";

use DBI;
require "retrieve_parse_amportal_conf.pl";

################### BEGIN OF CONFIGURATION ####################

# the name of the iax table
$table_name = "iax";
# the path to the varous IAX conf files
$iax_conf = "/etc/asterisk/iax_additional.conf";
$iax_reg = "/etc/asterisk/iax_registrations.conf";

# cool hack by Julien BLACHE <jblache@debian.org>
$ampconf = parse_amportal_conf( "/etc/amportal.conf" );
# username to connect to the database
$username = $ampconf->{"AMPDBUSER"};
# password to connect to the database
$password = $ampconf->{"AMPDBPASS"};
# the name of the box the MySQL database is running on
$hostname = $ampconf->{"AMPDBHOST"};
# the name of the database our tables are kept
$database = $ampconf->{"AMPDBNAME"};

# the engine to be used for the SQL queries,
# if none supplied, backfall to mysql
$db_engine = "mysql";
if (exists($ampconf->{"AMPDBENGINE"})) {
	$db_engine = $ampconf->{"AMPDBENGINE"};
}

################### END OF CONFIGURATION #######################

if ( $db_engine eq "mysql" ) {
	$dbh = DBI->connect("dbi:mysql:dbname=$database;host=$hostname", "$username", "$password");
}
elsif ( $db_engine eq "pgsql" ) {
	$dbh = DBI->connect("dbi:pgsql:dbname=$database;host=$hostname", "$username", "$password");
}
elsif ( $db_engine eq "sqlite" ) {
	if (!exists($ampconf->{"AMPDBFILE"})) {
		print "No AMPDBFILE set in /etc/amportal.conf\n";
		exit;
	}
	
	my $db_file = $ampconf->{"AMPDBFILE"};
	$dbh = DBI->connect("dbi:SQLite2:dbname=$db_file","","");
}

# Load the 'register' lines into iax_registrations.conf


# items with id=-1 get added for all users
$statement = "SELECT keyword,data from $table_name where id=-1 and keyword <> 'account' and flags <> 1";
my $result = $dbh->selectall_arrayref($statement);
unless ($result) {
  # check for errors after every single database call
  print "dbh->selectall_arrayref($statement) failed!\n";
  print "DBI::err=[$DBI::err]\n";
  print "DBI::errstr=[$DBI::errstr]\n";
  exit;
}

open EXTEN, ">$iax_conf" or die "Cannot create/overwrite IAX file: $iax_conf\n";
open REG, ">$iax_reg" or die "Cannot create/overwrite IAX Registration file: $iax_reg\n";
$additional = "";
my @resultSet = @{$result};
if ( $#resultSet > -1 ) {
	foreach $row (@{ $result }) {
		my @result = @{ $row };
		$additional .= $result[0]."=".$result[1]."\n";
	}
}

# items with id like 9999999% get put at the top of the file - these are only registration strings, currently.
$statement = "SELECT keyword,data from $table_name where id LIKE '9999999%' and keyword <> 'account' and flags <> 1";
$result = $dbh->selectall_arrayref($statement);
unless ($result) {
  # check for errors after every single database call
  print "dbh->selectall_arrayref($statement) failed!\n";
  print "DBI::err=[$DBI::err]\n";
  print "DBI::errstr=[$DBI::errstr]\n";
  exit;
}
@resultSet = @{$result};
if ( $#resultSet > -1 ) {
	foreach $row (@{ $result }) {
		my @result = @{ $row };
		$top .= $result[0]."=".$result[1]."\n";
	}
	print REG "$top\n";
}


# select for unique accounts
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
  print "No iax accounts defined in $table_name\n";
  exit;
}

#get the details for each account found above
foreach my $row ( @{ $result } ) {
	my $account = @{ $row }[0];
	my $id = @{ $row }[1];
	print EXTEN "[$account]\n";
	$statement = "SELECT keyword,data from $table_name where id=$id and keyword <> 'account' and flags <> 1 order by keyword DESC";
	my $result = $dbh->selectall_arrayref($statement);
	unless ($result) {
		# check for errors after every single database call
		print "dbh->selectall_arrayref($statement) failed!\n";
		print "DBI::err=[$DBI::err]\n";
		print "DBI::errstr=[$DBI::errstr]\n";
		exit;
	}

	my @resSet = @{$result};
	if ( $#resSet != -1 ) {          
		foreach my $row ( @{ $result } ) {
			my @result = @{ $row };
			@opts=split("&",$result[1]);
			foreach $opt (@opts) {
				print EXTEN "$result[0]=$opt\n";
			}
		}
	}

	print EXTEN "$additional\n";
}

close EXTEN;
close REG;
exit 0;

