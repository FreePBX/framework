#!/usr/bin/perl -w
# amportal config parser for retrieve_*.pl
#	License for all code of this FreePBX module can be found in the license file inside the module directory
#	Copyright 2013 Schmooze Com Inc.
#

sub parse_amportal_conf
{
	my $filename = $_[0];
	my %ampconf = (
		AMPDBENGINE => "mysql",
		AMPDBNAME => "asterisk",
		AMPENGINE => "asterisk",
	);

	open(AMPCONF, $filename) or die "Cannot open $filename ($!)";

	while (<AMPCONF>)
	{
		if ($_ =~ /^\s*([a-zA-Z0-9_]+)\s*=\s*(.*)\s*([;#].*)?/)
		{
			$ampconf{$1} = $2;
		}
	}
	close(AMPCONF);

	return \%ampconf;
}

# perl depends on this
1;
