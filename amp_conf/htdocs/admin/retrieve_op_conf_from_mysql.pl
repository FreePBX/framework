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
$extenpos="2-40";
$trunkpos="52-60,71-80";
$confepos="";
$queuepos="42-50,61-70";

# Remove or add Zap trunks as needed
# Note: ZAP/* will match any ZAP channel that *is not referenced* in another button (ie: extensions)
@zaplines=(); # zap channel, description
#@zaplines=(@zaplines,[ "Zap/*","PSTN" ]);
#@zaplines=(@zaplines,[ "Zap/1","Zap 1" ]);
#@zaplines=(@zaplines,[ "Zap/2","Zap 2" ]);
#@zaplines=(@zaplines,[ "Zap/3","Zap 3" ]);
#@zaplines=(@zaplines,[ "Zap/4","Zap 4" ]);


# LETS PARSE zapata.conf
# Allowed format options
# %c Zap Channel number
# %n Line number
# %N Line number, but restart counter
# Example:
# ;AMPLABEL:Channel %c - Button %n

$zapataconf="/etc/asterisk/zapata.conf";

$ampwildcard=0;

$zaplabel="Zap \%c";
$lastlabelnum=0;
open ZAPATA, "<$zapataconf" || die "Cannot open config file: $zapataconf\n";

while( $line = <ZAPATA> ) {
	next if $line =~ /^(\s)*$/;
	chomp($line);
	if($line =~ /^;AMPWILDCARDLABEL\((\d+)\)\s*:\s*([\S\s]+)\s*$/) {
		@zaplines=(@zaplines,[ "Zap/*","$2",$1 ]);
		$ampwildcard=1;
		next;	
	}

	if($line =~ /^;AMPLABEL:\s*(\S+[\s\S]*)$/) {
		$zaplabel=$1;
		$line=~/\%N/ and $lastlabelnum=0;
		$ampwildcard=0;
		next;
	}
	if($line =~ /^[b]?channel\s*=\s*[>]?\s*([\d\,-]+)\s*$/) {
		$ampwildcard and next;
		@ranges=split(/,/,$1);
		foreach $ran(@ranges) {
			@range=split(/-/,$ran);
			$start=$range[0];
			$end=$start;
			@range>1 and $end=$range[1];
			foreach $c($start .. $end) {
				$lastlabelnum++;
				$newlabel=$zaplabel;
				$newlabel=~s/\%c/$c/;
				$newlabel=~s/\%n/$lastlabelnum/;
				$newlabel=~s/\%N/$lastlabelnum/;
				
				@zaplines=(@zaplines,[ "Zap/$c","$newlabel" ]);
			}
			
		}
	}
}
#Finished parsing zapata.conf


# Conference Rooms not yet implemented in AMP config
@conferences=();   #### ext#, description
#@conferences=(@conferences,[ "810","Conf.10" ]);
#@conferences=(@conferences,[ "811","Conf.11" ]);


# WARNING: this file will be substituted by the output of this program
$op_conf = "AMPWEBROOT/panel/op_buttons_additional.cfg";
# the name of the box the MySQL database is running on
$hostname = "localhost";
# the name of the database our tables are kept
$database = "asterisk";
# username to connect to the database
$username = "AMPDBUSER";
# password to connect to the database
$password = "AMPDBPASS";


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

#Next, populate queues
@queues=(); 
	if (table_exists($dbh,"extensions")) {
		$statement = "SELECT extension,descr from extensions where application='Queue' and flags <> 1 order by extension";
		$result = $dbh->selectall_arrayref($statement);
		@resultSet = @{$result};
		if ( $#resultSet == -1 ) {
	  		print "Notice: no Queues defined\n";
		}
		push(@queues, @{ $result });
	}

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

#Determine AMP Users
@ampusers=(["default","0","0"]);
if (table_exists($dbh,"ampusers")) {
	$statement = 'SELECT deptname,extension_low,extension_high from ampusers WHERE NOT extension_low = "" AND NOT extension_high = ""';
	$result = $dbh->selectall_arrayref($statement);
	@resultSet = @{$result};
	if ( $#resultSet == -1 ) {
		print "Notice: no AMP Users defined\n";
	}
	push(@ampusers, @{ $result });
}

#Write a separate panel context from each AMP User's department
foreach my $pcontext ( @ampusers ) {
	my $exten_low = @{$pcontext}[1];
	my $exten_high = @{$pcontext}[2];
	my $panelcontext = @{$pcontext}[0];
	if ($panelcontext eq "") { $panelcontext = $exten_low."to".$exten_high; }
	
	
	# WRITE EXTENSIONS
	
	$btn=0; 
	if ($exten_low != 0 && $exten_high != 0) {  #display only allowed range of extensions for panel_contexts
		@extensionrange = grep { @{ $_ }[0]+0 >= $exten_low && @{ $_ }[0]+0 <= $exten_high } @extensionlist;
	} else {
		@extensionrange = @extensionlist;
	}
	foreach my $row ( @extensionrange ) {
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
			if ( $result[0] eq "mailbox" ) {
				$vmcontext = $result[1];
				if ($vmcontext =~ /@/) { $vmcontext =~ s/^.*@//; }
				else { $vmcontext="default"; }
			}
		}
		$icon='4';
		print EXTEN "[$tech/$user]\nPosition=$btn\nLabel=\"$callerid\"\nExtension=$account\nContext=from-internal\nIcon=$icon\nVoicemail_Context=$vmcontext\nPanel_Context=$panelcontext\n";
	}
	
	
	### NOW WRITE TRUNKS.. WE START WITH ZAP TRUNKS DEFINED ABOVE
	
	
	
	
	$btn=0; 
	foreach my $row ( @zaplines ) {
		$zapdef=@{$row}[0];
		$zapdesc=@{$row}[1];
		$icon='3';
		$btn=get_next_btn($trunkpos,$btn);
		if ($zapdef eq "Zap/*") {
			$numbuttons=@{$row}[2]-1;
			print EXTEN "[$zapdef]\nLabel=\"$zapdesc\"\nExtension=-1\nIcon=$icon\nPanel_Context=$panelcontext\nPosition=".$btn;
			while($numbuttons-->0) {
				$btn=get_next_btn($trunkpos,$btn);
				print EXTEN ",".$btn;
			}

			print EXTEN "\n";
		} else {
			print EXTEN "[$zapdef]\nPosition=$btn\nLabel=\"$zapdesc\"\nExtension=-1\nIcon=$icon\nPanel_Context=$panelcontext\n";
		}
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
		print EXTEN "[$tech/$account]\nPosition=$btn\nLabel=\"$callerid\"\nExtension=-1\nIcon=$icon\nPanel_Context=$panelcontext\n";
	}
	
	
	### Write conferences (meetme)

	$btn=0; 
	if ($exten_low != 0 && $exten_high != 0) {  #display only allowed range of extensions for panel_contexts
		@confrange = grep { @{ $_ }[0]+0 >= $exten_low && @{ $_ }[0]+0 <= $exten_high } @conferences;
	} else {
		@confrange = @conferences;
	}
	foreach my $row ( @confrange ) {
		$btn=get_next_btn($confepos,$btn);
		$confenum=@{$row}[0];
		$confedesc=@{$row}[1];
		$icon='6';
		print EXTEN "[$confenum]\nPosition=$btn\nLabel=\"$confedesc\"\nExtension=$confenum\nContext=from-internal\nIcon=$icon\nPanel_Context=$panelcontext\n";
	}

	### Write Queues
	
	$btn=0; 
	if ($exten_low != 0 && $exten_high != 0) {  #display only allowed range of extensions for panel_contexts
		@queuerange = grep { @{ $_ }[0]+0 >= $exten_low && @{ $_ }[0]+0 <= $exten_high } @queues;
	} else {
		@queuerange = @queues;
	}
	foreach my $row ( @queuerange ) {
		$btn=get_next_btn($queuepos,$btn);
		$queuename=@{$row}[0];
		$queuedesc=@{$row}[1];
		$icon='5';
		print EXTEN "[$queuename]\nPosition=$btn\nLabel=\"$queuedesc\"\nExtension=-1\nContext=from-internal\nIcon=$icon\nPanel_Context=$panelcontext\n";
	}
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

