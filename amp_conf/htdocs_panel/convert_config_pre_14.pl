#!/usr/bin/perl

sub copy
{
    $oldfile = shift;
    $newfile = shift;

    open(IN,  "< $oldfile") or die "can't open $oldfile: $!";
    open(OUT, "> $newfile") or die "can't open $newfile: $!";

    $blksize = (stat IN)[11] || 16384;    # preferred block size?
    while ($len = sysread IN, $buf, $blksize)
    {
        if (!defined $len)
        {
            next if $! =~ /^Interrupted/;    # ^Z and fg
            die "System read error: $!\n";
        }
        $offset = 0;
        while ($len)
        {                                    # Handle partial writes.
            defined($written = syswrite OUT, $buf, $len, $offset)
              or die "System write error: $!\n";
            $len -= $written;
            $offset += $written;
        }
    }

    close(IN);
    close(OUT);
}

copy("op_buttons.cfg", "op_buttons.cfg.bak");
copy("op_style.cfg",   "op_style.cfg.bak");
copy("op_server.cfg",  "op_server.cfg.bak");

open(NEWCONF, ">op_buttons.cfg.new");

open(CONFIG, "< op_buttons.cfg")
  or die("Could not open op_buttons.cfg. Aborting...");

while (<CONFIG>)
{
    chop;
    $_ =~ s/^\s+//g;
    if (/^#/ || /^;/ || /^$/) { next; }    # Ignores comments and empty lines

    ($channel, $position, $label, $extension, $icon, $vmail_context) =
      split(/,/, $_);
    $channel       =~ s/\s+//g;
    $position      =~ s/\s+//g;
    $position      =~ s/;/,/g;
    $extension     =~ s/\s+//g;
    $vmail_context =~ s/\s+//g;
    $icon          =~ s/\s+//g;
    $label         =~ s/^\s+//g;

    if ($position =~ /\@/)
    {
        ($parte1, $parte2) = split(/\@/, $position);
        $position      = $parte1;
        $panel_context = $parte2;
    }
    else
    {
        $panel_context = "";
    }

    if ($extension =~ /\@/)
    {
        ($parte1, $parte2) = split(/\@/, $extension);
        $extension = $parte1;
        $contexto  = $parte2;
    }
    else
    {
        $contexto = "";
    }

    print NEWCONF "[$channel]\n";
    print NEWCONF "Position=$position\n";
    print NEWCONF "Label=$label\n";
    print NEWCONF "Extension=$extension\n";
    print NEWCONF "Context=$contexto\n" if ($contexto ne "");
    print NEWCONF "Icon=$icon\n";
    print NEWCONF "Panel_Context=$panel_context\n" if ($panel_context ne "");
    print NEWCONF "Voicemail_Context=$vmail_context\n"
      if ($vmail_context ne "");
    print NEWCONF "\n";
}
close(CONFIG);
close(NEWCONF);

open(NEWCONF, ">op_server.cfg.new");
print NEWCONF "[general]\n";
open(CONFIG, "<op_server.cfg")
  or die("Could not open op_server.cfg. Aborting...");
while (<CONFIG>)
{
    $_ =~ s/\s+//g;
    $_ =~ s/(.*)#.*/$1/g;
    if (!/^$/)
    {
        my ($variable_name, $value) = split(/=/, $_);
        $variable_name =~ tr/A-Z/a-z/;
        $value         =~ s/\"//g;
        print NEWCONF "$variable_name=$value\n"
          unless ($variable_name =~ /auto_conference_extension/);
    }
}
print NEWCONF "auth_md5=1\n";
print NEWCONF "barge_rooms=900-910\n";
close(CONFIG);
close(NEWCONF);

open(NEWCONF, ">op_style.cfg.new");
print NEWCONF "[general]\n";
print NEWCONF "enable_animation=1\n";
open(CONFIG, "<op_style.cfg")
  or die("Could not open op_style.cfg. Aborting...");
while (<CONFIG>)
{
    $_ =~ s/(.*)#.*/$1/g;
    if (!/^$/)
    {
        my ($variable_name, $value) = split(/=/, $_);
        $variable_name =~ s/\s+//g;
        $variable_name =~ tr/A-Z/a-z/;
        $value         =~ s/\"//g;
        chomp($value);
        print NEWCONF "$variable_name=$value\n"
          unless (   $variable_name =~ /btn_help_label/
                  || $variable_name =~ /btn_reload_label/
                  || $variable_name =~ /btn_help_label/
                  || $variable_name =~ /security_label/);
    }
}
close(CONFIG);
close(NEWCONF);

copy("op_buttons.cfg.new", "op_buttons.cfg");
copy("op_style.cfg.new",   "op_style.cfg");
copy("op_server.cfg.new",  "op_server.cfg");

unlink("op_buttons.cfg.new");
unlink("op_style.cfg.new");
unlink("op_server.cfg.new");
