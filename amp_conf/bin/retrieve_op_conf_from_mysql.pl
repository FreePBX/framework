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

if (scalar @ARGV == 2)
{
	$amportalconf = $ARGV[0];
	$zapataconf = $ARGV[1]."/zapata.conf";
	$zapataautoconf = $ARGV[1]."/zapata-auto.conf";
} else
{
	$amportalconf = "/etc/amportal.conf";
	$zapataconf="/etc/asterisk/zapata.conf";
	$zapataautoconf="/etc/asterisk/zapata-auto.conf";
}

######## LAYOUT INFO #########

# This layout info should really be in a "panel" table in the freepbx database

# structure is - Legend, startpos, stoppos, color1, color2
@rectangle1 = ("Trunks", 53, 80, "10ff10", "009900");
@rectangle2 = ("Extensions", 1, 40, "1010ff", "099cccc");
@rectangle3 = ("Parking lots", 49, 72, "ffff10", "cc9933");
@rectangle4 = ("Conferences", 45, 68, "006666", "a01000");
@rectangle5 = ("Queues", 41, 64, "ff1010", "a01000");
@rectangles = (\@rectangle1,\@rectangle2,\@rectangle3,\@rectangle4,\@rectangle5);

######## BUTTON INFO #########
$buttonsizex = 246; # 1+244+1 from information in op_style.cfg
$buttonsizey = 28; # 1+26+1 from information in op_style.cfg
$numbuttonsx = 4;
$numbuttonsy = 20;


######## STYLE INFO #########
$extenpos="2-40";
#$trunkpos="52-60,71-80";
#$confepos="";
#$queuepos="42-50,61-70";

## SME server changes
$trunkpos="53-60,72-80";
$parkingpos="50-51,69-71";
$confepos="46-48,65-68";
$queuepos="42-44,61-64";

# End of changes

#automated generation of style-info
$extenpos=styleinfo("Extensions");
$trunkpos=styleinfo("Trunks");
$parkingpos=styleinfo("Parking lots");
$confepos=styleinfo("Conferences");
$queuepos=styleinfo("Queues");


# Remove or add Zap trunks as needed
# Note: ZAP/* will match any ZAP channel that *is not referenced* in another button (ie: extensions)
@zaplines=(); # zap channel, description
#@zaplines=(@zaplines,[ "Zap/*","PSTN" ]);
#@zaplines=(@zaplines,[ "Zap/1","Zap 1" ]);
#@zaplines=(@zaplines,[ "Zap/2","Zap 2" ]);
#@zaplines=(@zaplines,[ "Zap/3","Zap 3" ]);
#@zaplines=(@zaplines,[ "Zap/4","Zap 4" ]);



if (-e $zapataconf) {
	@zaplines = parse_zapata($zapataconf);
} 
if (-e $zapataautoconf) {
	@zaplines = parse_zapata($zapataautoconf);
}

sub parse_zapata{
	# LETS PARSE zapata.conf
	# Allowed format options
	# %c Zap Channel number
	# %n Line number
	# %N Line number, but restart counter
	# Example:
	# ;AMPLABEL:Channel %c - Button %n
	my $conffile=shift;
	$ampwildcard=0;

	$zaplabel="Zap \%c";
	$lastlabelnum=0;
	open( ZAPATA, "<$conffile" ) or die "Cannot open config file: $zapataconf ($!)\n";
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

                # check if trunk or extension
                if($line =~ /^context=from-pstn/) {
                        $istrunk=1;
                        next;
                }
                if($line =~ /^context=from-zaptel/) {
                        $istrunk=1;
                        next;
                }
                if($line =~ /^context=from-internal/) {
                        $istrunk=0;
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

# only add if A) this is a trunk
# and B) we haven't already defined any zaplines at the top of the file
#        (I use this to customize it so instead of saying "Zap 1" it will
#         say something more useful -- like the phone # of the line

                                        if($istrunk) {
                                                $inzaplines=0;
                                                foreach my $row ( @zaplines ) {
                                                        $tempvalue=@{$row}[0];
                                                        if($tempvalue eq "Zap/$c") {
                                                                $inzaplines=1;
                                                        }
                                                }

                                                if ($inzaplines==0) {
                                                        @zaplines=(@zaplines,[ "Zap/$c","$newlabel" ]);
                                                }
                                        }

					
				}
				
			}
		}
	}
	return @zaplines;
}
#Finished parsing zapata.conf


# Conference Rooms not yet implemented in AMP config
@conferences=();   #### ext#, description
#@conferences=(@conferences,[ "810","Conf.10" ]);
#@conferences=(@conferences,[ "811","Conf.11" ]);

# cool hack by Julien BLACHE <jblache@debian.org>
$ampconf = parse_amportal_conf( $amportalconf );

# WARNING: this file will be substituted by the output of this program
$op_conf = $ampconf->{"AMPWEBROOT"}."/panel/op_buttons_additional.cfg";
# username to connect to the database
$username = $ampconf->{"AMPDBUSER"};
# password to connect to the database
$password = $ampconf->{"AMPDBPASS"};
# the name of the box the MySQL database is running on
$hostname = $ampconf->{"AMPDBHOST"};
# the name of the database our tables are kept
$database = $ampconf->{"AMPDBNAME"};
#sort option: extension or lastname
$sortoption = $ampconf->{"FOPSORT"};

# the engine to be used for the SQL queries,
# if none supplied, backfall to mysql
$db_engine = "mysql";
if (exists($ampconf->{"AMPDBENGINE"})){
	$db_engine = $ampconf->{"AMPDBENGINE"};
}
################### END OF CONFIGURATION #######################

$warning_banner =
"; do not edit this file, this is an auto-generated file by freepbx
; all modifications must be done from the web gui
";

if ( $db_engine eq "mysql" ) {
	$dbh = DBI->connect("dbi:mysql:dbname=$database;host=$hostname", "$username", "$password");
}
elsif ( $db_engine eq "pgsql" ) {
	$dbh = DBI->connect("dbi:pgsql:dbname=$database;host=$hostname", "$username", "$password");
}
elsif ( $db_engine eq "sqlite" ) {
	if (!exists($ampconf->{"AMPDBFILE"})) {
		print "No AMPDBFILE set in $amportalconf\n";
		exit;
	}
	
	my $db_file = $ampconf->{"AMPDBFILE"};
	$dbh = DBI->connect("dbi:SQLite2:dbname=$db_file","","");
}
elsif ( $db_engine eq "sqlite3" ) {
	if (!exists($ampconf->{"AMPDBFILE"})) {
		print "No AMPDBFILE set in $amportalconf\n";
		exit;
	}
	
	my $db_file = $ampconf->{"AMPDBFILE"};
	$dbh = DBI->connect("dbi:SQLite:dbname=$db_file","","");
}

open( EXTEN, ">$op_conf" ) or die "Cannot create/overwrite config file: $op_conf ($!)\n";
print EXTEN $warning_banner;

#First, populate extensions

@extensionlist=();

if (table_exists($dbh,"devices")) {
	$statement = "SELECT description,id,dial,tech from devices";
	$result = $dbh->selectall_arrayref($statement);
	@resultSet = @{$result};
	if ( $#resultSet == -1 ) {
		print "Notice: no devices defined\n";
	}
	push(@extensionlist, @{ $result });
}
else { print "Table does not exist: devices\n"; }

# sort the extensions
if  (defined($sortoption) && ($sortoption eq "lastname")) {
	@extensionlist=sort by_lastname @extensionlist;
} else {
	@extensionlist=sort {$a->[1] cmp $b->[1]}(@extensionlist);
}

#Next, populate queues
@queues=(); 
	if (table_exists($dbh,"queues_config")) {
		$statement = "SELECT extension,descr from queues_config order by extension";
		$result = $dbh->selectall_arrayref($statement);
		@resultSet = @{$result};
		if ( $#resultSet == -1 ) {
	  		print "Notice: no Queues defined\n";
		}
		push(@queues, @{ $result });
	}


## SME server chnges

#Next, populate conferences
@conferences=();
	if(table_exists($dbh,"meetme")) {
		$statement = "SELECT exten,description FROM meetme ORDER BY exten";
		$result = $dbh->selectall_arrayref($statement);
                @resultSet = @{$result};
                if ( $#resultSet == -1 ) {
                        print "Notice: no Conferences defined\n";
                }
                push(@conferences, @{ $result });
        }


#Next, populate parkings
@parkings=();
	if(table_exists($dbh,"parkinglot")) {
		$statement = "SELECT keyword,data FROM parkinglot";
		$result = $dbh->selectall_arrayref($statement);
                @resultSet = @{$result};
                if ( $#resultSet == -1 ) {
                        print "Notice: no Parkings defined\n";
                }
                push(@parkings, @{ $result });
	}

## End of changes
#Next, populate trunks (sip and iax)
@trunklist=();
foreach $table ("sip","iax") {
	if (table_exists($dbh,$table)) {
		$statement = "SELECT data,id,'$table' from $table where keyword='account' and flags <> 1 and id>99990 group by data order by id";
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
		@extensionrange = grep { @{ $_ }[1]+0 >= $exten_low && @{ $_ }[1]+0 <= $exten_high } @extensionlist;
	} else {
		@extensionrange = @extensionlist;
	}
	foreach my $row ( @extensionrange ) {
		my $description = @{ $row }[0];
		my $id = @{ $row }[1];
		my $dial = @{ $row }[2];
		
		
		# Support for real mailbox settings -	
		my $tech = @{ $row }[3];
		# some sensible defaults for voicemail ext and context
		my $vmext = @{ $row }[1];
		my $vmcontext = "default";
		# the device tech table should also have a dial context - if not assume from-internal
		my $context = "from-internal";
		# database table name for iax2 is just iax but sip and zap are ok
		if ($tech eq "iax2") {$tech = "iax";}
		# get mailbox setting from relevant tech table and split into ext and content
		if (table_exists($dbh,$tech)) {
			$statement = "SELECT data from $tech WHERE id = '$id' AND keyword = 'mailbox' ";
			$result = $dbh->selectall_arrayref($statement);
			@resultSet = @{$result};
			if ( $#resultSet == -1 ) {print "Notice: no mailbox defined\n";}
			my $mailbox = $resultSet[0][0]; 
			my @values = split('@', $mailbox);
			if (exists($values[0])) {$vmext = $values[0];}
			if (exists($values[1])) {$vmcontext = $values[1];}
			#while in this table lets get the dial context as well
			$statement = "SELECT data from $tech WHERE id = '$id' AND keyword = 'context' ";
			$result = $dbh->selectall_arrayref($statement);
			@resultSet = @{$result};
			if ( $#resultSet == -1 ) {print "Notice: no context defined\n";}
			if (exists($resultSet[0][0])) {$context = $resultSet[0][0];} 			
		} else { print "Table does not exist: $tech\n"; }
		# - Support for real mailbox settings
		

		# Support for real VM_PREFIX -
		my $vmprefix = "*";
		if (table_exists($dbh,"globals")) {
			$statement = "SELECT value from globals WHERE variable = 'VM_PREFIX' ";
			$result = $dbh->selectall_arrayref($statement);
			@resultSet = @{$result};
			if ( $#resultSet == -1 ) {print "Notice: no VM_PREFIX defined\n";}
			if (exists($resultSet[0][0])) {$vmprefix = $resultSet[0][0];} 			
		} else { print "Table does not exist: global\n"; }		
		# - Support for real VM_PREFIX
		
		
#	#	next if ($account eq "");
		$btn=get_next_btn($extenpos,$btn);
		$icon='4';
		print EXTEN "[$dial]\nPosition=$btn\nLabel=\"$id : $description\"\nExtension=$id\nContext=$context\nIcon=$icon\nVoicemail_Context=$vmcontext\nVoiceMailExt=$vmprefix$vmext\@$context\nPanel_Context=$panelcontext\n";
	}
	
	
	### NOW WRITE TRUNKS.. WE START WITH ZAP TRUNKS DEFINED ABOVE
	
	
	
	
	$btn=0; 
	foreach my $row ( @zaplines ) {
		$zapdef=@{$row}[0];
		$zapdesc=@{$row}[1];
		$icon='3';
		# zaplines and trunklist share the trunk positions so need to store previous btn on overflow from zaplines
		my $previousbtn = $btn;
		$btn=get_next_btn($trunkpos,$btn);
		if ($btn eq 0) {$btn = $previousbtn; last;}
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
		if ($btn eq 0) {last;}
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
	
	
	## SME server changes
	
		
        ### Write Parkings lots
	$btn=0;
	my $parken="" ;
	my $extpark ;
	my $parkcontext ;
	my $numberlots ;
	my $maxparkingslots ;
	
	foreach my $row ( @parkings ) {
		if (@{$row}[0] eq "parkingenabled") {
			$parken = @{$row}[1] ;
		}
		if (@{$row}[0] eq "parkext") {
			$extpark = @{$row}[1] ;
		}
		if (@{$row}[0] eq "parkingcontext") {
			$parkcontext = @{$row}[1] ;
		}
		if (@{$row}[0] eq "numslots") {
			$numberlots = @{$row}[1] ;
		}
	}
	if ($parken eq "s") {
		for (my $i = 1 ; $i <= $numberlots ; $i++ ) {
			$btn=get_next_btn($parkingpos,$btn);
			if ($btn eq 0) {last;}
			$parknum = $extpark + $i ;
			$icon='1';
			print EXTEN "[PARK$parknum]\nPosition=$btn\nLabel=\"Parked ($parknum)\"\nExtension=$parknum\nContext=$parkcontext\nIcon=$icon\nPanel_Context=$panelcontext\n";
		}
	}
	
	## End of chagnes
	### Write conferences (meetme)

	$btn=0; 
	if ($exten_low != 0 && $exten_high != 0) {  #display only allowed range of extensions for panel_contexts
		@confrange = grep { @{ $_ }[0]+0 >= $exten_low && @{ $_ }[0]+0 <= $exten_high } @conferences;
	} else {
		@confrange = @conferences;
	}
	foreach my $row ( @confrange ) {
		$btn=get_next_btn($confepos,$btn);
		if ($btn eq 0) {last;}
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
		if ($btn eq 0) {last;}
		$queuename=@{$row}[0];
		$queuedesc=@{$row}[1];
		$icon='5';
		print EXTEN "[QUEUE/$queuename]\nPosition=$btn\nLabel=\"$queuedesc\"\nExtension=-1\nContext=from-internal\nIcon=$icon\nPanel_Context=$panelcontext\n";
	}

	### Write rectangles

	foreach my $rect ( @rectangles ) {
		my $comment = @{$rect}[0];
		my $color1 = @{$rect}[3];
		my $color2 = @{$rect}[4];
		my $start = @{$rect}[1];
		my $stop = @{$rect}[2];
		
		my $xposition = $buttonsizex * int(($start-1)/$numbuttonsy);
		my $yposition = $buttonsizey * (($start-1)%$numbuttonsy);
		my $xsize = $buttonsizex * (1 + int(($stop-1)/$numbuttonsy) - int(($start-1)/$numbuttonsy));
		my $ysize = $buttonsizey * (1 + (($stop-1)%$numbuttonsy) - (($start-1)%$numbuttonsy));
		
		$xsize -= 2;
		$ysize -= 2;
		
		$yposition += 32;
	
		print EXTEN "\n; $comment\n[rectangle]\nx=$xposition\ny=$yposition\nwidth=$xsize\nheight=$ysize\nline_width=0\nline_color=$color1\nfade_color1=$color1\nfade_color2=$color2\nrnd_border=2\nalpha=20\nlayer=bottom\n";
	}

	### Write legends

	foreach my $legend ( @rectangles ) {
		my $text = @{$legend}[0];
		my $start = @{$legend}[1];
		
		my $xposition = $buttonsizex * int(($start-1)/$numbuttonsy);
		my $yposition = $buttonsizey * (($start-1)%$numbuttonsy);

		$xposition += 3;
		$yposition += 32;
	
		print EXTEN "\n[LEGEND]\nx=$xposition\ny=$yposition\ntext=$text\nfont_size=18\nfont_family=Arial\nuse_embed_fonts=1\n";
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
            $_ =~ s/(`.*`\.){0,1}`(.*)`/$2/g;
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

sub by_lastname {
	$a_var = $a->[0];
	$b_var = $b->[0];
	($a_firstname,$a_lastname)=$a_var=~/^\s*([0-9A-Za-z_\-\s.]*)\s+([^0-9][0-9A-Za-z_\-.]*).*$/;
	($b_firstname,$b_lastname)=$b_var=~/^\s*([0-9A-Za-z_\-\s.]*)\s+([^0-9][0-9A-Za-z_\-.]*).*$/;
	if (!$a_lastname) {$a_lastname=$a_var;}
	if (!$b_lastname) {$b_lastname=$b_var;}
	$sortResult=lc $a_lastname cmp lc $b_lastname;
	if ($sortResult == 0)
	{ $sortResult=lc $a_firstname cmp lc $b_firstname }
	return $sortResult;
}


sub styleinfo {
	my $legend = shift;
	foreach my $rect ( @rectangles ) {
		if ($legend  eq @{$rect}[0]) {

			my $start = @{$rect}[1];
			my $stop = @{$rect}[2];
			
			my $xposition = int(($start-1)/$numbuttonsy);
			my $yposition = (($start-1)%$numbuttonsy);
			my $xsize = int(($stop-1)/$numbuttonsy) - int(($start-1)/$numbuttonsy);
			my $ysize = (($stop-1)%$numbuttonsy) - (($start-1)%$numbuttonsy);
	
			$styleinfo = "";
			if ($ysize > 2) {
				$styleinfo .= ($start + 1) . "-" . ($start + $ysize) . ",";
			} 
			elsif ($ysize == 2) {
				$styleinfo .= ($start + 1) . ",";
			}
			
			for (my $i = 1 ; $i <= $xsize ; $i++ ) {
				if ($ysize > 1) {
					$styleinfo .= (($i + $xposition) * $numbuttonsy + $yposition + 1) . "-" . (($i + $xposition) * $numbuttonsy + $yposition + $ysize + 1) . ",";
				} 
				else {
					$styleinfo .= (($i + $xposition) * $numbuttonsy + $yposition + 1) . ",";		
				}	
			}
			last;
		}
	}
	return $styleinfo;
}
