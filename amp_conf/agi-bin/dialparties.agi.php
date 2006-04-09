#!/usr/bin/php -q
<?php 
#
# Copyright (C) 2003 Zac Sprackett <zsprackett-asterisk@sprackett.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
# 
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# Amended by Coalescent Systems Inc. Sept, 2004
# to include support for DND, Call Waiting, and CF to external trunk
# info@coalescentsystems.ca
# 
# This script has been ported to PHP by Diego Iastrubni <diego.iastrubni@xorcom.com>

$config = parse_amportal_conf( "/etc/amportal.conf" );

require_once "phpagi.php";
require_once "phpagi-asmanager.php";

# Minor modifications to assist with Dependancy checking, automatically
# parse required information from /etc/amportal.conf, and slightly more
# descriptive error reporting by Rob Thomas <xrobau@gmail.com> 
# 18th Sep 2005

$debug = 4;

$ext="";     # Hash that will contain our list of extensions to call
$ext_hunt="";# Hash that will contain our list of extensions to call used by huntgroup
$cidnum="";  # Caller ID Number for this call
$cidname=""; # Caller ID Name for this call
$timer="";   # Call timer for Dial command
$dialopts="";# options for dialing
$rc="";      # Catch return code
$priority="";# Next priority 
$rgmethod="";# If Ring Group what ringing method was chosen

$AGI = new AGI();
debug("Starting New Dialparties.agi", 0);

// $AGI->setcallback(\&mycallback);
// $input = $AGI->$request;


// printf( $AGI->$config . "\n" );

if ($debug >= 2) 
{
	foreach( $keys as $key=>$value)
	{
		debug("$key = $value" ,3);
		$AGI->verbose("$key = $value", 2);
	}
}

$priority = get_var( $AGI, "priority" ) + 1;
debug( "priority is $priority" );

$callerid = get_var( $AGI, "callerid" );
if (preg_match( $callerid, '/^\"(.*)\"\s+\<(\d+)-?(\d*)\>\s*$/', $matches)) 
{
	$cidname = $matches[1];
	$cidnum  = $matches[2].$matches[3];//$2.$3;
	debug("Caller ID name is '$cidname' number is '$cidnum'", 1);
} 
elseif (preg_match($callerid, '/^(\d+)*$/', $matches))
{
	$cidname = $matches[1];
	$cidnum  = $matches[1];
	debug("Caller ID name and number are '$cidnum'", 1);
}
else 
{
	$cidname = undef;
	$cidnum  = undef;
	debug("Caller ID is not set", 1);
}

$timer		= get_var( $AGI, "ARG1" );
$dialopts	= get_var( $AGI, "ARG2" );
$rgmethod	= get_var( $AGI, "RingGroupMethod" );
if (empty($timer))	$timer		= 0;
if (empty($dialopts))	$dialopts	= "";
if (empty($rgmethod))	$rgmethod	= "none";
debug("Methodology of ring is  '$rgmethod'", 1);

// Start with Arg Count set to 3 as two args are used
$arg_cnt = 3;
while($arg = get_var($AGI,"ARG". $arg_cnt) )
{
	if ($arg == '-') 
	{  #not sure why, dialparties will get stuck in a loop if noresponse
		debug("get_variable got a \"noresponse\"!  Exiting",3);
		exit($arg_cnt);
	}
	
	$extarray = split( '/-/', $arg );
	foreach ( $extarray as $k )
	{
		$ext[$k] = $k;
		debug("Added extension $k to extension map", 3);
	}
	
	$arg_cnt++;
}

# Check for call forwarding first
# If call forward is enabled, we use chan_local
foreach( $ext as $k)
{
	$cf  = $AGI->database_get('CF',$k);
	$cf  = $cf['data'];
	if (strlen($cf)) 
	{
		# append a hash sign so we can send out on chan_local below.
		$ext[$k] = $cf.'#';  
		debug("Extension $k has call forward set to $cf", 1);
	} 
	else 
	{
		debug("Extension $k cf is disabled", 3);
	}
}

# Now check for DND
foreach ( $ext as $k )
{
	if ( !preg_match($k, "/\#/", $matches) )
	{   
		// no point in doing if cf is enabled
		$dnd = $AGI->database_get('DND',$k);
		$dnd = $dnd['data'];
		if (strlen($dnd)) 
		{
			debug("Extension $k has do not disturb enabled", 1);
			//delete $ext{$k};
		} else {
			debug("Extension $k do not disturb is disabled", 3);
		}
	}
}

// Main calling loop
$ds = '';
foreach ( $ext as $k )
{
	$extnum    = $k;
	$exthascw  = $AGI->database_get('CW', $extnum) ? 1 : 0;
	$extcfb    = $AGI->database_get('CFB', $extnum)? 1 : 0;
	$exthascfb = (strlen($extcfb) > 0) ? 1 : 0;
	
	# Dump details in level 4
	debug("extnum: $extnum",4);
	debug("exthascw: $exthascw",4);
	debug("exthascfb: $exthascfb",4);
	debug("extcfb: $extcfb",4);
	
	# if CF is not in use; AND
	# CW is not in use or CFB is in use on this extension, then we need to check!
	//   if (($ext{$k} =~ /\#/)!=1 && (($exthascw == 0) || ($exthascfb == 1))) {
	if ( preg_match($ext{$k}, "/\#/", $matches) && (($exthascw == 0) || ($exthascfb == 1)) )
	{
		debug("Checking CW and CFB status for extension $extnum",3);
		$extstate = is_ext_avail($extnum);
		debug("extstate: $extstate",4);
		
		if ($extstate > 0) 
		{ # extension in use
			debug("Extension $extnum is not available to be called",1);
		
			if ($exthascfb == 1) 
			{	# CFB is in use
				debug("Extension $extnum has call forward on busy set to $extcfb",1);
				$extnum = $extcfb . '#';   # same method as the normal cf, i.e. send to Local
			} elseif ($exthascw == 0) 
			{	# CW not in use
				debug("Extension $extnum has call waiting disabled",1);
				$extnum = '';
			} else 
			{	# no reason why this will ever happen! but kept in for clarity
				debug("Extension $extnum has call waiting enabled",1);
			}
		}
		elseif ($extstate < 0) 
		{	# -1 means couldn't read status or chan unavailable
			debug("ExtensionState for $extnum could not be read...assuming ok",3);
		} 
		else 
		{
			debug("Extension $extnum is available...skipping checks",1);
		}
	}
	elseif ($exthascw == 1) 
	{	# just log the fact that CW enabled
		debug("Extension $extnum has call waiting enabled",1);
	}
	
	if ($extnum != '') 
	{ # Still got an extension to be called?
		$extds = get_dial_string($extnum);
		$ds = $ds . $extds . '&';
	
		# Update Caller ID for calltrace application
// 		if (($ext{$k} =~ /#/)!=1 && ($rgmethod ne "hunt") && ($rgmethod ne "memoryhunt")) {  
		if ( preg_match($k, "/\#/", $matches) && (($rgmethod != "hunt") && ($rgmethod != "memoryhunt")) )
		{
			if ($cidnum) 
			{
				$rc = $AGI->database_put('CALLTRACE', $ext{$k}, $cidnum);
				if ($rc == 1) 
				{
					debug("DbSet CALLTRACE/$ext{$k} to $cidnum", 3);
				} 
				else 
				{
					debug("Failed to DbSet CALLTRACE/$ext{$k} to $cidnum ($rc)", 1);
				}
			} 
			else 
			{
				# We don't care about retval, this key may not exist
				$AGI->database_del('CALLTRACE', $k);
				debug("DbDel CALLTRACE/$k - Caller ID is not defined", 3);
			}
		} else{
			$ext_hunt{$k}=$extds; # Need to have the extension HASH set with technology for hunt group ring 
		}
	}
} // endforeach

$dshunt ='';
$loops=0;
$myhuntmember="";
if (($rgmethod == "hunt") || ($rgmethod == "memoryhunt")) 
{
	if ($cidnum) 
		$AGI->set_variable(CALLTRACE_HUNT,$cidnum);
		
	foreach ($extarray as $k )
	{ 
		# we loop through the original array to get the extensions in order of importance
		if ($ext_hunt[$k]) 
		{
			#If the original array is included in the extension hash then set variables
			$myhuntmember="HuntMember"."$loops";
			if ($rgmethod == "hunt") 
			{
				$AGI->set_variable($myhuntmember,$ext_hunt[$k]);
			} 
			elseif ($rgmethod == "memoryhunt") 
			{
				if ($loops==0) 
				{
					$dshunt =$ext_hunt[$k];
				} 
				else 
				{
					$dshunt .='&'.$ext_hunt[$k];
				}
				$AGI->set_variable($myhuntmember,$dshunt);
			}
			$loops += 1;
		}
	}
}

// chop $ds if length($ds);
if (strlen($ds) )
	chop($ds);

if (!strlen($ds)) 
{
	$AGI->exec('NoOp');
} else {
	if (($rgmethod == "hunt") || ($rgmethod == "memoryhunt"))
	{
		$ds = '|';
		if ($timer)
			$ds .= $timer;
		$ds .= '|' . $dialopts; # pound to transfer, provide ringing
		$AGI->set_variable('ds',$ds);
		$AGI->set_variable("HuntMembers",$loops);
		$AGI->set_priority(20); #dial command is at priority 20 where dialplan handles calling a ringgroup with strategy of "hunt" or "MemoryHunt"
	} 
	else
	{
		$ds .= '|';
		if ($timer)
			$ds .= $timer;
		$ds .= '|' . $dialopts; # pound to transfer, provide ringing
		$AGI->set_variable('ds',$ds);
		$AGI->set_priority(10); #dial command is at priority 10
	}
}

exit( 0 );


function get_var( $agi, $value)
{
	$r = $agi->get_variable( $value );
	
// 	debug( "get_var" );
// 	foreach( $r as $rr )
// 		debug( "$rr" );
	
	if ($r['result'] == 1)
	{
		$result = $r['data'];
		return $result;
	}
	else
		return '';
}

function get_dial_string( $extnum )
{
	$dialstring = '';
	
// 	if ($extnum =~ s/#//) 
 	if (strpos($extnum,'#') == 0)
	{                       
		# "#" used to identify external numbers in forwards and callgourps
		$dialstring = 'Local/'.$extnum.'@from-internal';
	} 
	else 
	{
		$device = $AGI->database_get('AMPUSER',$extnum.'/device');
		# a user can be logged into multipe devices, append the dial string for each
		
		$device_array = split( '&', $device );
		foreach ($device_array as $adevice) 
		{
			$dds = $AGI->database_get('DEVICE',$adevice.'/dial');
			$dialstring .= $dds['data'];
			$dialstring .= '&';
		}
		
		chop($dialstring);
		return $dialstring;
	}
}

function debug($string, $level=3)
{
	global $AGI;
	$AGI->verbose($string, $level);
}

function mycallback( $rc )
{
	debug("User hung up. (rc=" . $rc . ")", 1);
	exit ($rc);
}

function is_ext_avail( $extnum )
{  
	global $config;
	
	#uses manager api to get ExtensionState info
	$server_ip = '127.0.0.1';
	
	
	$tn = AGI_AsteriskManager();
	
	# Load $config with /etc/amportal.conf..
	return $tn->ExtensionState( $extnum, 'from-internal' );
	
	/*
	$tn = new Net::Telnet (Port => 5038,
				Prompt => '/.*[\$%#>] $/',
				Output_record_separator => '',
				Errmode    => 'return'
				);
	#connect to manager and login
	$tn->open("$server_ip");
	$tn->waitfor('/0\n$/');
		$tn->print("Action: Login\n");
		$tn->print("Username: ".$config["AMPMGRUSER"]."\n");
		$tn->print("Secret: ".$config["AMPMGRPASS"]."\n\n");
		my ($pm, $m) = $tn->waitfor('/Authentication (.+)\n\n/');
		if ($m =~ /Authentication failed/) {
				debug ("/etc/amportal.conf contains incorrect AMPMGRUSER or AMPMGRPASS");
				exit;
		}
		debug ("Correct AMPMGRUSER and AMPMGRPASS", 3);
	#issue command
	$tn->print("Action: ExtensionState\nExten: $extnum\nContext: ext-local\nActionId: 8355\n\n");
	$tn->waitfor('/Response: Success\n/');
	$tn->waitfor('/ActionID: 8355\n/');
	
	#wait for status
	my $ok = 0; # 0 means ok to call
	my $extstatus = 0;
	($ok, $extstatus) = $tn->waitfor('/Status: .*\n/') or die "Could not get ExtensionState";
	
	#logoff
	$tn->print("Action: Logoff\n\n");
	
	if ($ok && $extstatus =~ /Status: (.*)/) 
	{
		$extstatus = $1;
	}
	else 
	{
		$extstatus = -1;	# Make -1 if couldn't read correctly
	}
	
	return $extstatus;
	*/
}

function parse_amportal_conf($filename) 
{
	$file = file($filename);
	foreach ($file as $line) 
	{
		if (preg_match("/^\s*([a-zA-Z0-9]+)\s*=\s*(.*)\s*([;#].*)?/",$line,$matches)) 
		{
			$conf[ $matches[1] ] = $matches[2];
		}
	}
	return $conf;
}

?>
