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
$sip_conf = "AMPWEBROOT/panel/op_buttons_additional.cfg";
# the name of the box the MySQL database is running on
$hostname = "localhost";
# the name of the database our tables are kept
$database = "asterisk";
# username to connect to the database
$username = "AMPDBUSER";
# password to connect to the database
$password = "AMPDBPASS";

# Zap Channels = remove or add to this list as necessary
$additional = "[Zap/1]\nPosition=1\nLabel=\"External 1\"\nExtension=-1\nIcon=3\n";
$additional .= "[Zap/2]\nPosition=2\nLabel=\"External 2\"\nExtension=-1\nIcon=3\n";
$additional .= "[Zap/3]\nPosition=3\nLabel=\"External 3\"\nExtension=-1\nIcon=3\n";
$additional .= "[Zap/4]\nPosition=4\nLabel=\"External 4\"\nExtension=-1\nIcon=3\n";
# Button position to start regular extensions at
$btn=10;

################### END OF CONFIGURATION #######################


open EXTEN, ">$sip_conf" or die "Cannot create/overwrite config file: $sip_conf\n";

print EXTEN "$additional";

$dbh = DBI->connect("dbi:mysql:dbname=$database;host=$hostname", "$username", "$password");


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
  print "Notice: no sip accounts defined\n";
  #exit;
}

@total_result = @{ $result };



if (table_exists($dbh,"iax")) {
	$statement = "SELECT data,id from iax where keyword='account' and flags <> 1 group by data order by id";
	$result = $dbh->selectall_arrayref($statement);
	@resultSet = @{$result};
	if ( $#resultSet == -1 ) {
  		print "Notice: no iax accounts defined\n";
	}
	push(@total_result, @{ $result });
}

if (table_exists($dbh,"zap")) {
	$statement = "SELECT data,id FROM zap WHERE keyword='account' and flags <> 1 group by data order by id";
	$result = $dbh->selectall_arrayref($statement);
	@resultSet = @{$result};
	if ($#resultSet == -1 ) {
		print "Notice: no zap accounts defined\n";
	}
	push(@total_result, @{ $result });
}

foreach my $row ( @total_result ) {
	$btn++;
	my $account = @{ $row }[0];
	my $id = @{ $row }[1];
	#print EXTEN "[$account]\n";
	$statement = "SELECT keyword,data from sip where id=$id and keyword <> 'account' and flags <> 1 order by keyword";
	$tech="SIP";
	my $result = $dbh->selectall_arrayref($statement);
	unless ($result) {
		# check for errors after every single database call
		print "dbh->selectall_arrayref($statement) failed!\n";
		print "DBI::err=[$DBI::err]\n";
		print "DBI::errstr=[$DBI::errstr]\n";
		exit;
	}

	my @resSet = @{$result};
	if ( $#resSet == -1 ) {       	#if a result isn't in sip, look in iax   
		#print "no results\n";
			$statement = "SELECT keyword,data from iax where id=$id and keyword <> 'account' and flags <> 1 order by keyword";
			$tech="IAX2";
			$result = $dbh->selectall_arrayref($statement);
			my @resSetIax = @{$result};
			if ( $#resSetIax == -1 ) { #Maybe it's ZAP
				$statement = "SELECT keyword,data from zap where id=$id and keyword <> 'account' and flags<>1 order by keyword";
				$tech="ZAP";
				$result = $dbh->selectall_arrayref($statement);
			}

	}
	$zapchannel="PROBLEM"; #default to an invalid zap channel
	$callerid = $account;  #default callerid to account
	foreach my $row ( @{ $result } ) {
		my @result = @{ $row };
		if ( $result[0] eq "callerid" ) {
			$callerid = $result[1];
			$callerid =~ tr/\"<>//d;
		}
		if ( $result[0] eq "channel" ) {
			$zapchannel = $result[1];
		}
	}
	$icon='4';
	if ($callerid eq $account) {  #if this happens, we know it's a voip trunk, so change the icon
		$icon='3';
	}
	if ($tech eq "ZAP") {
		print EXTEN "[Zap/$zapchannel]\nPosition=$btn\nLabel=\"$callerid\"\nExtension=$account\nContext=from-internal\nIcon=$icon\nVoicemail_Context=default\n"
	} else {
		print EXTEN "[$tech/$account]\nPosition=$btn\nLabel=\"$callerid\"\nExtension=$account\nContext=from-internal\nIcon=$icon\nVoicemail_Context=default\n";
	}
}

exit 0;

#this sub checks for the existance of a table
sub table_exists {
    my $db = shift;
    my $table = shift;
    my @tables = $db->tables('','','','TABLE');
    if (@tables) {
        for (@tables) {
            next unless $_;
			$_ =~ s/`//g;     #on suse 9.2 we need to get rid of quotes
            return 1 if $_ eq $table
        }
    }
    else {
        eval {
            local $db->{PrintError} = 0;
            local $db->{RaiseError} = 1;
            $db->do(qq{SELECT * FROM $table WHERE 1 = 0 });
        };
        return 1 unless $@;
    }
    return 0;
}
