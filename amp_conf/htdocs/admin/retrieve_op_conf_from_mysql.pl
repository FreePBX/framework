#!/usr/bin/perl -Tw
# Retrieves the sip user/peer entries from the database
# Use these commands to create the appropriate tables in MySQL
#
#CREATE TABLE sip (id INT(11) DEFAULT -1 NOT NULL,keyword VARCHAR(20) NOT NULL,data VARCHAR(50) NOT NULL, flags INT(1) DEFAULT 0 NOT NULL,PRIMARY KEY (id,keyword));
#
# if flags = 1 then the records are not included in the output file

use DBI;
################### BEGIN OF CONFIGURATION ####################

######## STYLE INFO #########
$extenpos="2-7,9-14";
$trunkpos="23-28";
$confepos="16-18";
$queuepos="19-21";

@zaplines=(); # zap channel, description
#@zaplines=(@zaplines,[ "Zap/1","Zap 1" ]);
#@zaplines=(@zaplines,[ "Zap/2","Zap 2" ]);
#@zaplines=(@zaplines,[ "Zap/3","Zap 3" ]);
@zaplines=(@zaplines,[ "Zap/4","PSTN" ]);

@conferences=();   #### ext#, description
#@conferences=(@conferences,[ "810","Conf.10" ]);
#@conferences=(@conferences,[ "811","Conf.11" ]);

@queues=();       ### queue name, queue description
#@queues=(@queues, [ "SALES", "Sales Queue" ]);
#@queues=(@queues, [ "TECH", "Tech Queue" ]);



# WARNING: this file will be substituted by the output of this program
$op_conf = "/var/www/html/panel/op_buttons_additional.cfg";
# the name of the box the MySQL database is running on
$hostname = "localhost";
# the name of the database our tables are kept
$database = "asterisk";
# username to connect to the database
$username = "asteriskuser";
# password to connect to the database
$password = "amp109";

# Zap Channels = remove or add to this list as necessary
#$additional = "[Zap/4]\nPosition=1\nLabel=\"Telefonica\"\nExtension=-1\nIcon=3\n";
#$additional .= "[Zap/2]\nPosition=2\nLabel=\"External 2\"\nExtension=-1\nIcon=3\n";
#$additional .= "[Zap/3]\nPosition=3\nLabel=\"External 3\"\nExtension=-1\nIcon=3\n";
#$additional .= "[Zap/4]\nPosition=4\nLabel=\"External 4\"\nExtension=-1\nIcon=3\n";
# Button position to start regular extensions at
$btn=10;

################### END OF CONFIGURATION #######################


open EXTEN, ">$op_conf" || die "Cannot create/overwrite config file: $op_conf\n";

#print EXTEN "$additional";

$dbh = DBI->connect("dbi:mysql:dbname=$database;host=$hostname", "$username", "$password");


#First, populate extensions

@extensionlist=();

foreach my $table ("sip","iax","zap") {
	if (table_exists($dbh,$table)) {
		$statement = "SELECT data,id,'$table' from $table where keyword='account' and flags <> 1 and id<9999 group by data order by id";
		$result = $dbh->selectall_arrayref($statement);
		@resultSet = @{$result};
		if ( $#resultSet == -1 ) {
	  		print "Notice: no $table accounts defined\n";
		}
		push(@extensionlist, @{ $result });
	}
	else { print "no existe $table\n"; }
}

@extensionlist=sort {$a->[1] cmp $b->[1]}(@extensionlist);

#Next, populate trunks (sip and iax)
@trunklist=();
foreach $table ("sip","iax") {
	if (table_exists($dbh,$table)) {
		$statement = "SELECT data,id,'$table' from $table where keyword='account' and flags <> 1 and id>9999 group by data order by id";
		$result = $dbh->selectall_arrayref($statement);
		@resultSet = @{$result};
		if ( $#resultSet == -1 ) {
	  		print "Notice: no $table trunks defined\n";
		}
		push(@trunklist, @{ $result });
	}
}


# WRITE EXTENSIONS

$btn=0; 
foreach my $row ( @extensionlist ) {
	my $account = @{ $row }[0];
	my $id = @{ $row }[1];
	my $table = @{ $row }[2];
#	next if ($account eq "");
	$btn=get_next_btn($extenpos,$btn);
	$statement = "SELECT keyword,data from $table where id=$id and keyword <> 'account' and flags <> 1 order by keyword";
	my $result = $dbh->selectall_arrayref($statement);
	unless ($result) {
		# check for errors after every single database call
		print "dbh->selectall_arrayref($statement) failed!\n";
		print "DBI::err=[$DBI::err]\n";
		print "DBI::errstr=[$DBI::errstr]\n";
		exit;
	}
	
	$tech="SIP" if $table eq "sip";
	$tech="IAX2" if $table eq "iax";
	$tech="ZAP" if $table eq "zap";

	#my @resSet = @{$result};
	
	$callerid = $account;  #default callerid to account
	$user=$account;

	foreach my $drow ( @{ $result } ) {
		my @result = @{ $drow };
		if ( $result[0] eq "callerid" ) {
			$callerid = $result[1];
			@fields=split(/</,$callerid);
			$callerid=$fields[1] ." ". $fields[0];
			$callerid =~ tr/\"<>//d;
		}
		if ( $result[0] eq "channel" ) {
			if ($tech eq "ZAP") { $user = $result[1]; }
		}
	}
	$icon='4';
	print EXTEN "[$tech/$user]\nPosition=$btn\nLabel=\"$callerid\"\nExtension=$account\nContext=from-internal\nIcon=$icon\nVoicemail_Context=default\n";
}


### NOW WRITE TRUNKS.. WE START WITH ZAP TRUNKS DEFINED ABOVE




$btn=0; 
foreach my $row ( @zaplines ) {
	$btn=get_next_btn($trunkpos,$btn);
	$zapdef=@{$row}[0];
	$zapdesc=@{$row}[1];
	$icon='3';
	print EXTEN "[$zapdef]\nPosition=$btn\nLabel=\"$zapdesc\"\nExtension=-1\nContext=from-internal\nIcon=$icon\nVoicemail_Context=default\n";
}


foreach my $row ( @trunklist ) {
	my $account = @{ $row }[0];
	my $id = @{ $row }[1];
	my $table = @{ $row }[2];
	next if ($account eq "");
	$btn=get_next_btn($trunkpos,$btn);
	$statement = "SELECT keyword,data from $table where id=$id and keyword <> 'account' and flags <> 1 order by keyword";
	my $result = $dbh->selectall_arrayref($statement);
	unless ($result) {
		# check for errors after every single database call
		print "dbh->selectall_arrayref($statement) failed!\n";
		print "DBI::err=[$DBI::err]\n";
		print "DBI::errstr=[$DBI::errstr]\n";
		exit;
	}
	
	$tech="SIP" if $table eq "sip";
	$tech="IAX2" if $table eq "iax";
	#$tech="ZAP" if $table eq "zap"; #no zap trunks in db

	#my @resSet = @{$result};
	
	$callerid = $account;  #default callerid to account

	foreach my $drow ( @{ $result } ) {
		my @result = @{ $drow };
		if ( $result[0] eq "callerid" ) {
			$callerid = $result[1];
			@fields=split(/</,$callerid);
			$callerid=$fields[1] ." ". $fields[0];
			$callerid =~ tr/\"<>//d;
		}
	}
	$icon='3';
	print EXTEN "[$tech/$account]\nPosition=$btn\nLabel=\"$callerid\"\nExtension=$account\nContext=from-internal\nIcon=$icon\nVoicemail_Context=default\n";
}


### Write conferences (meetme)


$btn=0; 
foreach my $row ( @conferences ) {
	$btn=get_next_btn($confepos,$btn);
	$confenum=@{$row}[0];
	$confedesc=@{$row}[1];
	$icon='6';
	print EXTEN "[$confenum]\nPosition=$btn\nLabel=\"$confedesc\"\nExtension=$confenum\nContext=from-internal\nIcon=$icon\nVoicemail_Context=default\n";
}

$btn=0; 
foreach my $row ( @queues ) {
	$btn=get_next_btn($queuepos,$btn);
	$queuename=@{$row}[0];
	$queuedesc=@{$row}[1];
	$icon='5';
	print EXTEN "[$queuename]\nPosition=$btn\nLabel=\"$queuedesc\"\nExtension=-1\nContext=from-internal\nIcon=$icon\nVoicemail_Context=default\n";
}

sub get_next_btn {
	my $data = shift;
	my $last = shift;

	@rangelist=split(",",$data);

	foreach $range (@rangelist) {
		@rangeval=split("-",$range);
		return $rangeval[0] if $last < $rangeval[0];
		return $last+1 if $last < $rangeval[1];
		#Need to try another range def...
	}
	#If we get here, we ran out of positions :(
	return 0; #?????
}
#this sub checks for the existance of a table
sub table_exists {
    my $db = shift;
    my $table = shift;
    my @tables = $db->tables('','','','TABLE');
    if (@tables) {
        for (@tables) {
            next unless $_;
            $_ =~ s/`//g;
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


