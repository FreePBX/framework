#!/usr/bin/perl -w 

#  Flash Operator Panel.    http://www.asternic.org
#
#  Copyright (c) 2004 Nicolás Gudiño.  All rights reserved.
#
#  Nicolás Gudiño <nicolas@house.com.ar>
#
#  This program is free software, distributed under the terms of
#  the GNU General Public License.
#
#  THIS SOFTWARE IS PROVIDED BY THE CONTRIBUTORS ``AS IS'' AND ANY EXPRESS OR
#  IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
#  MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO
#  EVENT SHALL THE CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
#  SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
#  PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS;
#  OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
#  WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
#  OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
#  ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

use strict;
use integer;

use IO::Socket;
use IO::Select;
use POSIX qw(setsid);

my %datos                      = ();
my %auto_conference            = ();
my %buttons                    = ();
my %textos                     = ();
my %iconos                     = ();
my %extension_transfer         = ();
my %extension_transfer_reverse = ();
my %flash_contexto             = ();
my %keys_socket                = ();
my $config                     = {};
my $bloque_completo;
my $bloque_final;
my $todo;
my @bloque;
my @respuestas;
my @masrespuestas;
my @fake_bloque;
my @flash_clients;
my @status_active;
my %mailbox;
my %instancias;
my %orden_instancias;
my %agents;
my %agents_name;
my $p;
my $m;
my $O;
my @S;
my @key;
my $manager_host;
my $manager_user;
my $manager_secret;
my $web_hostname;
my $listen_port;
my $security_code;
my $flash_dir;
my $mandapolicy = "";
my $socketpolicy;
my $poll_interval;
my $poll_voicemail;
my $kill_zombies;
my $ren_agentlogin;
my $ren_cbacklogin;
my $ren_agentname;
my $ren_queuemember;
my $change_led;
my $cdial_nosecure;
my $debug;
my $flash_file;
my %barge_rooms;
my %barge_context;
my $first_room;
my $last_room;
my $meetme_context;
my $clid_format;
my $directorio = $0;
my $papa;
my $auth_md5 = 1;
my $md5challenge;
my %shapes;
my %no_encryption = ();
my %total_shapes;
my @btninclude = ();

my $PADDING = join(
                   '',
                   map(chr,
                       (
                        0x80, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0,    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0,    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0,    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0,    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0,    0, 0, 0
                       ))
                  );
my %a2b = (
           A   => 000,
           B   => 001,
           C   => 002,
           D   => 003,
           E   => 004,
           F   => 005,
           G   => 006,
           H   => 007,
           I   => 010,
           J   => 011,
           K   => 012,
           L   => 013,
           M   => 014,
           N   => 015,
           O   => 016,
           P   => 017,
           Q   => 020,
           R   => 021,
           S   => 022,
           T   => 023,
           U   => 024,
           V   => 025,
           W   => 026,
           X   => 027,
           Y   => 030,
           Z   => 031,
           a   => 032,
           b   => 033,
           c   => 034,
           d   => 035,
           e   => 036,
           f   => 037,
           g   => 040,
           h   => 041,
           i   => 042,
           j   => 043,
           k   => 044,
           l   => 045,
           m   => 046,
           n   => 047,
           o   => 050,
           p   => 051,
           q   => 052,
           r   => 053,
           s   => 054,
           t   => 055,
           u   => 056,
           v   => 057,
           w   => 060,
           x   => 061,
           y   => 062,
           z   => 063,
           '0' => 064,
           '1' => 065,
           '2' => 066,
           '3' => 067,
           '4' => 070,
           '5' => 071,
           '6' => 072,
           '7' => 073,
           '8' => 074,
           '9' => 075,
           '+' => 076,
           '_' => 077,
          );
my %b2a                      = reverse %a2b;
my $rand_byte_already_called = 0;

$SIG{PIPE} = 'IGNORE';
$SIG{ALRM} = 'alarma_al_minuto';
$SIG{INT}  = 'close_all';
$SIG{HUP}  = 'generate_configs_onhup';
$SIG{USR1} = 'dump_internal_hashes_to_stdout';

if (defined($ARGV[0]))
{
    if ($ARGV[0] eq "-d")
    {
        defined(my $pid = fork) or die "Can't Fork: $!";
        exit if $pid;
        setsid or die "Can't start a new session: $!";
        open MYPIDFILE, ">/var/run/op_panel.pid";
        print MYPIDFILE $$;
        close MYPIDFILE;
    }
}

sub read_server_config()
{
    my $context = "";

    $/ = "\n";

    open(CONFIG, "<$directorio/op_server.cfg")
      or die("Could not open op_server.cfg. Aborting...");

    while (<CONFIG>)
    {
        chop;
        $_ =~ s/^\s+//g;
        $_ =~ s/([^;]*)[;](.*)/$1/g;
        $_ =~ s/\s+$//g;

        if (/^#/ || /^;/ || /^$/) { next; }   # Ignores comments and empty lines

        if (/^\Q[\E/)
        {
            s/\[(.*)\]/$1/g;
            tr/a-z/A-Z/;
            $context = $_;
        }
        else
        {
            if ($context ne "")
            {
                my ($variable_name, $value) = split(/=/, $_);
                $variable_name =~ tr/A-Z/a-z/;
                $variable_name =~ s/\s+//g;
                $value         =~ s/^\s+//g;
                $value         =~ s/\s+$//g;
                $value         =~ s/\"//g;
                $config->{$context}{$variable_name} = $value;
            }
        }
    }
    close(CONFIG);

    $manager_host    = $config->{"GENERAL"}{"manager_host"};
    $manager_user    = $config->{"GENERAL"}{"manager_user"};
    $manager_secret  = $config->{"GENERAL"}{"manager_secret"};
    $web_hostname    = $config->{"GENERAL"}{"web_hostname"};
    $listen_port     = $config->{"GENERAL"}{"listen_port"};
    $security_code   = $config->{"GENERAL"}{"security_code"};
    $flash_dir       = $config->{"GENERAL"}{"flash_dir"};
    $poll_interval   = $config->{"GENERAL"}{"poll_interval"};
    $poll_voicemail  = $config->{"GENERAL"}{"poll_voicemail"};
    $kill_zombies    = $config->{"GENERAL"}{"kill_zombies"};
    $debug           = $config->{"GENERAL"}{"debug"};
    $auth_md5        = $config->{"GENERAL"}{"auth_md5"};
    $ren_agentlogin  = $config->{"GENERAL"}{"rename_label_agentlogin"};
    $ren_cbacklogin  = $config->{"GENERAL"}{"rename_label_callbacklogin"};
    $ren_agentname   = $config->{"GENERAL"}{"rename_to_agent_name"};
    $ren_queuemember = $config->{"GENERAL"}{"rename_queue_member"};
    $change_led      = $config->{"GENERAL"}{"change_led_agent"};
    $cdial_nosecure  = $config->{"GENERAL"}{"clicktodial_insecure"};

    my @todos_los_rooms;
    foreach my $val ($config)
    {
        while (my ($aa, $bb) = each(%{$val}))
        {
            while (my ($cc, $dd) = each(%{$bb}))
            {
                if ($cc eq "barge_rooms")
                {
                    ($first_room, $last_room) = split(/-/, $dd);
                    my @arrayroom = $first_room .. $last_room;
                    foreach (@arrayroom)
                    {
                        $barge_context{"$_"} = $aa;
                    }
                    push(@todos_los_rooms, @arrayroom);
                }
            }
        }
    }
    %barge_rooms = map { $todos_los_rooms[$_], 0 } 0 .. $#todos_los_rooms;

    $meetme_context = $config->{"GENERAL"}{"conference_context"};
    $clid_format    = $config->{"GENERAL"}{"clid_format"};

    $flash_file = $flash_dir . "/variables.txt";

    if (!defined $manager_host)
    {
        die("Missing manager_host in op_server.cfg!");
    }
    if (!defined $manager_user)
    {
        die("Missing manager_user in op_server.cfg!");
    }
    if (!defined $manager_secret)
    {
        die("Missing manager_secret in op_server.cfg!");
    }
    if (!defined $web_hostname)
    {
        die("Missing web_hostname in op_server.cfg!");
    }
    if (!defined $listen_port)
    {
        die("Missing listen_port in op_server.cfg!");
    }
    if (!defined $security_code)
    {
        die("Missing security_code in op_server.cfg!");
    }
    if (!defined $flash_dir) { die("Missing flash_dir in op_server.cfg!"); }
    if (!defined $poll_interval)
    {
        die("Missing poll_interval in op_server.cfg!");
    }
    if (!defined $ren_agentlogin)
    {
        $ren_agentlogin = 0;
    }
    if (!defined $cdial_nosecure)
    {
        $cdial_nosecure = 0;
    }
    if (!defined $ren_agentname)
    {
        $ren_agentname = 0;
    }
    if (!defined $ren_cbacklogin)
    {
        $ren_cbacklogin = 0;
    }
    if (!defined $ren_queuemember)
    {
        $ren_queuemember = 0;
    }
    if (!defined $change_led)
    {
        $change_led = 0;
    }
    if (!defined $kill_zombies)
    {
        $kill_zombies = 0;
    }
    if (!defined $poll_voicemail)
    {
        $poll_voicemail = 0;
    }
    if (!defined $clid_format)
    {
        $clid_format = "(xxx) xxx-xxxx";
    }
    if (!defined $debug) { die("Missing debug in op_server.cfg!"); }
    $/ = "\0";
}

sub collect_includes
{
    my $filename = shift;
    my $archivo  = $directorio . "/" . $filename;

    if (-r $archivo)
    {

        if (!inArray($filename, @btninclude))
        {
            push(@btninclude, $filename);
        }
        else
        {
            log_debug("$filename already included", 16);
            return;
        }

        push(@btninclude, $filename);

        open(CONFIG, "< $archivo")
          or die("Could not open $filename. Aborting...\n\n");

        my @lineas  = <CONFIG>;
        my $cuantos = @lineas;
        foreach my $linea (@lineas)
        {
            $linea =~ s/^\s+//g;
            $linea =~ s/([^;]*)[;](.*)/$1/g;
            $linea =~ s/\s+$//g;
            if ($linea =~ /^include/)
            {

                # store include lines in an array so we can
                # process them later excluding duplicates
                $linea =~ s/^include//g;
                $linea =~ s/^\s+//g;
                $linea =~ s/^=>//g;
                $linea =~ s/^\s+//g;
                $linea =~ s/\s+$//g;
                collect_includes($linea);
            }
        }
        close CONFIG;
    }
    else
    {
        log_debug("$archivo not readable... skiping", 16);
    }
}

sub read_buttons_config()
{
    my @btn_cfg  = ();
    my $contador = -1;
    my @uniq;

    $/ = "\n";

    my %seen = ();
    foreach my $item (@btninclude)
    {
        push(@uniq, $item) unless $seen{$item}++;
    }

    foreach my $archivo (@uniq)
    {

        open(CONFIG, "< $directorio/$archivo")
          or die("Could not open op_buttons.cfg. Aborting...");

        # Read op_buttons.cfg loading it into a hash for easier processing

        while (<CONFIG>)
        {
            chop;
            $_ =~ s/^\s+//g;
            $_ =~ s/([^;]*)[;](.*)/$1/g;
            $_ =~ s/\s+$//g;
            if (/^#/ || /^;/ || /^$/)
            {
                next;
            }    # Ignores comments and empty lines

            if (/^\Q[\E/)
            {
                $contador++;
                s/\[(.*)\]/$1/g;
                if (!/^Local/i)
                {
                    tr/a-z/A-Z/;
                }
                $btn_cfg[$contador]{'channel'} = $_;
            }
            else
            {
                next unless ($contador >= 0);
                my ($key, $val) = split(/=/, $_);
                $key =~ tr/A-Z/a-z/;
                $key =~ s/^\s+//g;
                $key =~ s/(.*)\s+/$1/g;
                if ($key ne "label")
                {
                    $val =~ s/^\s+//g;
                    $val =~ s/(.*)\s+/$1/g;
                }
                $btn_cfg[$contador]{"$key"} = $val;
            }
        }

        close(CONFIG);
    }

    # We finished reading the file, now we populate our
    # structures with the relevant data
    my $rectangulos = 0;

    foreach (@btn_cfg)
    {
        my @positions = ();
        my %tmphash   = %$_;

        if ($tmphash{"channel"} eq "RECTANGLE")
        {
            if (defined($tmphash{"panel_context"}))
            {
                $tmphash{"panel_context"} =~ tr/a-z/A-Z/;
                $tmphash{"panel_context"} =~ s/^DEFAULT$//;
            }
            else
            {
                $tmphash{"panel_context"} = "";
            }
            my $conttemp = $tmphash{"panel_context"};

            if (!defined($tmphash{"x"}))
            {
                $tmphash{"x"} = 1;
            }
            if (!defined($tmphash{"y"}))
            {
                $tmphash{"y"} = 1;
            }
            if (!defined($tmphash{"width"}))
            {
                $tmphash{"width"} = 1;
            }
            if (!defined($tmphash{"height"}))
            {
                $tmphash{"height"} = 1;
            }
            if (!defined($tmphash{"line_width"}))
            {
                $tmphash{"line_width"} = 1;
            }
            if (!defined($tmphash{"line_color"}))
            {
                $tmphash{"line_color"} = "0x000000";
            }
            if (!defined($tmphash{"fade_color1"}))
            {
                $tmphash{"fade_color1"} = "0xd0d0d0";
            }
            if (!defined($tmphash{"fade_color2"}))
            {
                $tmphash{"fade_color2"} = "0xd0d000";
            }
            if (!defined($tmphash{"rnd_border"}))
            {
                $tmphash{"rnd_border"} = 3;
            }
            if (!defined($tmphash{"alpha"}))
            {
                $tmphash{"alpha"} = 100;
            }
            if (!defined($tmphash{"layer"}))
            {
                $tmphash{"layer"} = "bottom";
            }

            $rectangulos++;
            if ($rectangulos > 1)
            {
                $shapes{$conttemp} .= "&";
            }
            $total_shapes{$conttemp}++;
            $shapes{$conttemp} .= "rect_$rectangulos=" . $tmphash{"x"} . ",";
            $shapes{$conttemp} .= $tmphash{"y"} . ",";
            $shapes{$conttemp} .= $tmphash{"width"} . ",";
            $shapes{$conttemp} .= $tmphash{"height"} . ",";
            $shapes{$conttemp} .= $tmphash{"line_width"} . ",";
            $shapes{$conttemp} .= $tmphash{"line_color"} . ",";
            $shapes{$conttemp} .= $tmphash{"fade_color1"} . ",";
            $shapes{$conttemp} .= $tmphash{"fade_color2"} . ",";
            $shapes{$conttemp} .= $tmphash{"rnd_border"} . ",";
            $shapes{$conttemp} .= $tmphash{"alpha"} . ",";
            $shapes{$conttemp} .= $tmphash{"layer"};
            next;
        }

        if (!defined($tmphash{"position"}))
        {

            log_debug(
                "** Ignoring button configuration $tmphash{'channel'}, position?",
                16
            );
            next;
        }

        if (!defined($tmphash{"label"}))
        {
            $tmphash{"label"} = $tmphash{"channel"};
        }

        if (!defined($tmphash{"icon"}))
        {
            $tmphash{"icon"} = "1";
        }

        my $canal_key = $tmphash{"channel"};

        if (defined($tmphash{"panel_context"}))
        {
            $tmphash{"panel_context"} =~ tr/a-z/A-Z/;
            $tmphash{"panel_context"} =~ s/^DEFAULT$//;
        }
        else
        {
            $tmphash{"panel_context"} = "";
        }

        if ($tmphash{"panel_context"} ne "")
        {
            $canal_key .= "&" . $tmphash{"panel_context"};
        }

        if ($tmphash{"position"} =~ /,/)
        {
            $instancias{$tmphash{"channel"}} = [];
            @positions = split(/,/, $tmphash{"position"});
            my $count = 0;
            foreach my $pos (@positions)
            {
                $count++;
                my $indice_contexto = $pos;
                my $chan_trunk      = $tmphash{"channel"} . "=" . $count;
                if ($tmphash{"panel_context"} ne "")
                {
                    $chan_trunk      .= "&" . $tmphash{"panel_context"};
                    $indice_contexto .= "@" . $tmphash{"panel_context"};
                    $pos             .= "@" . $tmphash{"panel_context"};
                }
                $buttons{"$chan_trunk"}     = $pos;
                $textos{"$indice_contexto"} = $tmphash{"label"} . " " . $count;
                $iconos{"$indice_contexto"} = $tmphash{"icon"};
            }
        }
        else
        {
            if ($tmphash{"panel_context"} ne "")
            {
                $buttons{"$canal_key"} =
                  $tmphash{"position"} . "\@" . $tmphash{'panel_context'};
                $textos{"$tmphash{'position'}\@$tmphash{'panel_context'}"} =
                  $tmphash{"label"};
                $iconos{"$tmphash{'position'}\@$tmphash{'panel_context'}"} =
                  $tmphash{"icon"};
            }
            else
            {
                $buttons{"$canal_key"}        = $tmphash{"position"};
                $textos{$tmphash{"position"}} = $tmphash{"label"};
                $iconos{$tmphash{"position"}} = $tmphash{"icon"};
            }
        }

        if (defined($tmphash{"extension"}))
        {
            if (defined($tmphash{"context"}))
            {
                $extension_transfer{"$canal_key"} =
                  $tmphash{"extension"} . "@" . $tmphash{"context"};
            }
            else
            {
                $extension_transfer{"$canal_key"} = $tmphash{"extension"};
            }
            if (defined($tmphash{"voicemail_context"}))
            {
                $mailbox{$canal_key} =
                  $tmphash{"extension"} . "@" . $tmphash{"voicemail_context"};
            }
        }
        $/ = "\0";
    }
    %extension_transfer_reverse = reverse %extension_transfer;
}

sub genera_config
{

    # This sub generates the file variables.txt that is read by the
    # swf movie on load, with info about buttons, layout, etc.

    $/ = "\n";
    my %style_variables;
    my @contextos       = ();
    my @uniq            = ();
    my $contextoactual  = "";

    open(STYLE, "<op_style.cfg")
      or die("Could not open op_style.cfg for reading");
    while (<STYLE>)
    {
        chop($_);
        $_ =~ s/^\s+//g;
        $_ =~ s/([^;]*)[;](.*)/$1/g;
        $_ =~ s/\s+$//g;

        #        if (/^\Q[\E/) { next; }
        if (/^\Q[\E/)
        {
            s/\[(.*)\]/$1/g;
            $contextoactual = $_;
            $contextoactual =~ tr/A-Z/a-z/;
            next;
        }
        $style_variables{$contextoactual} .= $_ . "&";
    }
    close(STYLE);

    for (keys %textos)
    {
        if ($_ =~ /\@/)
        {
            my @partes = split(/\@/);
            push(@contextos, $partes[1]);
        }
    }

    # Writes default context variables.txt
    open(VARIABLES, ">$flash_file")
      or die(
        "Could not write configuration data $flash_file.\nCheck your file permissions\n"
      );
    print VARIABLES "server=$web_hostname&port=$listen_port";

    while (my ($key, $val) = each(%shapes))
    {
        if ($key eq "")    # DEFAULT PANEL CONTEXT
        {
            print VARIABLES "&$val";
        }
    }
    while (my ($key, $val) = each(%textos))
    {
        $val =~ s/\"(.*)\"/$1/g;
        if ($key !~ /\@/)
        {
            print VARIABLES "&texto$key=$val";
        }
    }
    while (my ($key, $val) = each(%iconos))
    {
        $val =~ s/\"(.*)\"/$1/g;
        if ($key !~ /\@/)
        {
            print VARIABLES "&icono$key=$val";
        }
    }
    print VARIABLES "&" . $style_variables{"general"};
    if (!defined($total_shapes{""}))
    {
        $total_shapes{""} = 0;
    }
    print VARIABLES "total_rectangles=" . $total_shapes{""};
    close(VARIABLES);

    my %seen = ();
    foreach my $item (@contextos)
    {
        push(@uniq, $item) unless $seen{$item}++;
    }

    # Writes variables.txt for each context defined
    foreach (@uniq)
    {
        my $directorio   = "";
        my $host_web     = "";
        my $contextlower = $_;
        $contextlower =~ tr/A-Z/a-z/;

        if (defined($config->{$_}{"flash_dir"}))
        {
            $directorio = $config->{$_}{"flash_dir"};
        }
        else
        {
            $directorio = $config->{"GENERAL"}{"flash_dir"};
        }

        if (defined($config->{$_}{"web_hostname"}))
        {
            $host_web = $config->{$_}{"web_hostname"};
        }
        else
        {
            $host_web = $config->{"GENERAL"}{"web_hostname"};
        }

        my $flash_context_file = $directorio . "/variables" . $_ . ".txt";
        open(VARIABLES, ">$flash_context_file")
          or die(
            "Could not write configuration data $flash_file.\nCheck your file permissions\n"
          );
        print VARIABLES "server=$host_web&port=$listen_port";
        while (my ($key, $val) = each(%shapes))
        {
            if ($key eq $_)    # OTHER CONTEXT
            {
                print VARIABLES "&$val";
            }
        }
        while (my ($key, $val) = each(%textos))
        {
            $val =~ s/\"(.*)\"/$1/g;
            if ($key =~ /\@$_$/)
            {
                $key =~ s/(\d+)\@.+/$1/g;
                print VARIABLES "&texto$key=$val";
            }
        }
        while (my ($key, $val) = each(%iconos))
        {
            $val =~ s/\"(.*)\"/$1/g;
            if ($key =~ /\@$_$/)
            {
                $key =~ s/(\d+)\@.+/$1/g;
                print VARIABLES "&icono$key=$val";
            }
        }
        if (!defined($style_variables{$contextlower}))
        {
            $style_variables{$contextlower} = $style_variables{"general"};
        }
        print VARIABLES "&" . $style_variables{$contextlower};
        if (!defined($total_shapes{$_}))
        {
            $total_shapes{$_} = 0;
        }
        print VARIABLES "total_rectangles=" . $total_shapes{$_};
        close(VARIABLES);
    }
    $/ = "\0";
}

sub dump_internal_hashes_to_stdout
{

    print "Botones\n";
    print "---------------------------------------------------\n";
    foreach (sort (keys(%buttons)))
    {
        printf("%-20s %-10s\n", $_, $buttons{$_});
    }
    print "---------------------------------------------------\n";
    print "Orden instancias\n";
    foreach (sort (keys(%orden_instancias)))
    {
        printf("%-20s %-10s\n", $_, $orden_instancias{$_});
    }
    print "---------------------------------------------------\n";
    print "Instancias\n";
    foreach (sort (keys(%instancias)))
    {
        printf("%-20s %-10s\n", $_, $instancias{$_});
    }

    my $number_of_data_blocks = %datos;

    if ($number_of_data_blocks ne "0")
    {
        print "---------------------------------------------------\n";
        print "DATOS\n";
        print "---------------------------------------------------\n";
        for (keys %datos)
        {
            print $_. "\n";
            while (my ($key, $val) = each(%{$datos{$_}}))
            {
                if (defined($val))
                {
                    print "\t$key = $val\n";
                }
            }
            print "---------------------------------------------------\n";
        }
    }
    else
    {
        print "No data blocks in memory\n";
    }

    my $number_of_flash_clients_connected = @flash_clients;

    if ($number_of_flash_clients_connected > 0)
    {
        print "\nFlash clients connected: $number_of_flash_clients_connected\n";
        print "---------------------------------------------------\n";

        foreach my $C (@flash_clients)
        {
            print "$C : ";
            my $sockaddr = $C->peername;
            my ($port, $inetaddr) = sockaddr_in($sockaddr);
            my $ip_address = inet_ntoa($inetaddr);
            print "$ip_address\n";

            #print "\n";
        }
        print "---------------------------------------------------\n";
    }
    else
    {
        print "\nNo flash clients connected\n\n";
    }
}

sub generate_configs_onhup
{
    %buttons            = ();
    %textos             = ();
    %iconos             = ();
    %extension_transfer = ();
    %shapes             = ();
    read_buttons_config();
    read_server_config();
    genera_config();
}

sub manager_reconnect()
{
    my $attempt        = 1;
    my $total_attempts = 60;
    my $command;
    %agents      = ();
    %agents_name = ();

    do
    {
        log_debug("** Attempt reconnection to manager port # $attempt", 16);
        $p =
          new IO::Socket::INET->new(
                                    PeerAddr => $manager_host,
                                    PeerPort => 5038,
                                    Proto    => "tcp",
                                    Type     => SOCK_STREAM
                                   );
        $attempt++;
        if ($attempt > $total_attempts)
        {
            die("!! Could not reconnect to Asterisk Manager port");
        }
        sleep(10);    # wait 10 seconds before trying to reconnect
    } until $p;
    $O->add($p);
    foreach my $hd ($O->handles)
    {
        if ($hd != $m && $hd != $p)
        {
            log_debug("Closing flash client $hd", 16);
            $O->remove($hd);
            close($hd);
        }
    }

    if ($auth_md5 == 1)
    {
        $command = "Action: Challenge\r\n";
        $command .= "AuthType: MD5\r\n\r\n";
    }
    else
    {
        $command = "Action: Login\r\n";
        $command .= "Username: $manager_user\r\n";
        $command .= "Secret: $manager_secret\r\n\r\n";
    }
    send_command_to_manager($command);

    #    send_initial_status();
}

# Checks file_name to find out the directory where the configuration
# files should reside

$directorio =~ s/(.*)\/(.*)/$1/g;
chdir($directorio);
$directorio = `pwd`;
chop($directorio);

collect_includes("op_buttons.cfg");
read_buttons_config();
read_server_config();
genera_config();

$p =
  new IO::Socket::INET->new(
                            PeerAddr => $manager_host,
                            PeerPort => 5038,
                            Proto    => "tcp",
                            Type     => SOCK_STREAM
                           )
  or die "\nCould not connect to Asterisk Manager Port\n";

$p->autoflush(1);

my $command = "";

if ($auth_md5 == 1)
{
    $command = "Action: Challenge\r\n";
    $command .= "AuthType: MD5\r\n\r\n";
}
else
{
    $command = "Action: Login\r\n";
    $command .= "Username: $manager_user\r\n";
    $command .= "Secret: $manager_secret\r\n\r\n";
}

send_command_to_manager($command);

$m =
  new IO::Socket::INET(Listen => 1, LocalPort => $listen_port, ReuseAddr => 1)
  or die "\nCan't listen to port $listen_port\n";
$O = new IO::Select();
$O->add($m);
$O->add($p);
$/ = "\0";

alarm(10);

while (1)
{
    while (@S = $O->can_read)
    {
        foreach (@S)
        {
            if ($_ == $m)
            {

                # New client connection, lets send a Status to Asterisk Manager
                log_debug("** New client connection", 16);
                my $C = $m->accept;
                push(@flash_clients, $C);

                $O->add($C);

                alarm(10);
            }
            else
            {

                # Its not a new client connection
                my $i;
                my $R = sysread($_, $i, 2);    # 2048
                if (defined($R) && $R == 0)
                {
                    my $T = syswrite($_, ' ', 2);    # 2048
                    if (!defined($T))
                    {
                        $O->remove($_);
                        $_->close;

                        # Removes handle from flash_clients array
                        my $cualborrar = $_;
                        my @temp = grep(!/\Q$cualborrar\E/, @flash_clients);
                        @flash_clients = @temp;
                        delete($keys_socket{$_});

                        if ($_ == $p)
                        {
                            log_debug(
                                     "** Asterisk Manager connection lost!!!!!",
                                     16);
                            manager_reconnect();
                        }
                    }
                }
                else
                {

                    # $i =~ s/([^\r])\n/$1\r\n/g;    # Reemplaza \n solo por \r\n
                    $bloque_completo = "" if (!defined($bloque_completo));
                    $bloque_completo .= $i;
                    next
                      if (   $bloque_completo !~ /\r\n\r\n/
                          && $bloque_completo !~ /\0/);
                    log_debug("** End of block", 16);
                    $bloque_final = $bloque_completo;
                    $bloque_final =~
                      s/([^\r])\n/$1\r\n/g;    # Reemplaza \n solo por \r\n
                    $bloque_completo = "";
                    my @lineas = split("\r\n", $bloque_final);

                    foreach my $linea (@lineas)
                    {
                        if (length($linea) < 2)
                        {
                            $bloque_completo = $linea;
                        }
                        else
                        {
                            if ($_ == $p) { log_debug("<- $linea", 1); }
                        }
                    }
                    if ($_ == $p) { log_debug(" ", 1); }

                    #    $i =~ s/([^\r])\n/$1\r\n/g;    # Reemplaza \n solo por \r\n

                    #    $bloque_completo = "" if (!defined($bloque_completo));
                    #    $papa            = $i;
                    #    $papa            = substr($bloque_completo, -5) . $papa;
                    #    if ($papa =~ "\r\n\r\n" || $i =~ /\0/)
                    #    {
                    #        log_debug("** End of block", 16);
                    #        $bloque_final    = $bloque_completo . $i;
                    #        $bloque_completo = "";
                    #        $bloque_final =~
                    #          s/([^\r])\n/$1\r\n/g;    # Reemplaza \n solo por \r\n
                    #        my @lineas = split("\r\n", $bloque_final);
                    #        foreach my $linea (@lineas)
                    #        {
                    #            if (length($linea) < 2)
                    #            {
                    #                $bloque_completo = $linea;
                    #            }
                    #            else
                    #            {
                    #                if ($_ == $p) { log_debug("<- $linea", 1); }
                    #            }
                    #        }
                    #        if ($_ == $p) { log_debug(" ", 1); }
                    #    }
                    #    else
                    #    {
                    #        my $quehay = substr($i, -2);
                    #        $bloque_completo .= $i;
                    #        next;
                    #    }

                    foreach my $C ($O->handles)
                    {
                        if ($C == $p)
                        {
                            log_debug(
                                 "** Asterisk event received, process block...",
                                 16
                            );

                            # Asterisk event received
                            # Read the info and arrange it into blocks
                            # for processing in 'procesa_bloque'
                            if (   $bloque_final =~ /Event:/
                                || $bloque_final =~ /Message: Mailbox/)
                            {
                                log_debug(
                                     "** There's an 'Event' in the event block",
                                     32
                                );
                                my @lineas = split(/\r\n/, $bloque_final);
                                @bloque = ();
                                my $contador = -1;
                                foreach $p (@lineas)
                                {
                                    log_debug("** Parse line: $p", 128);
                                    my $my_event = "";
                                    if ($p =~ /Event:/)
                                    {
                                        log_debug("** Event detected $p", 32);
                                        $contador++;
                                    }
                                    elsif ($p =~ /Message: Mailbox/)
                                    {
                                        log_debug(
                                                 "** Event mailbox detected $p",
                                                 32);
                                        $my_event =
                                          "MessageWaiting";    # Fake event
                                        $contador++;
                                    }
                                    my ($atributo, $valor) =
                                      split(/: /, $p);
                                    if (defined $atributo && $atributo ne "")
                                    {
                                        if ($my_event ne "")
                                        {
                                            $atributo = "Event";
                                            $valor    = $my_event;
                                            log_debug(
                                                "** Fake event generated $atributo=$valor",
                                                16
                                            );
                                        }
                                        if (length($atributo) > 1)
                                        {
                                            if ($contador < 0)
                                            {
                                                $contador = 0;
                                            }
                                            $bloque[$contador]{"$atributo"} =
                                              $valor;
                                        }
                                    }
                                }
                                log_debug(
                                    "** There are $contador blocks for processing",
                                    32
                                );
                                @respuestas = ();
                                log_debug("** Answer block cleared", 32);
                                @respuestas = digest_event_block(\@bloque);

                                if (@fake_bloque)
                                {
                                    @masrespuestas = ();
                                    @masrespuestas =
                                      digest_event_block(\@fake_bloque);
                                    @fake_bloque = ();
                                }
                            }
                            elsif ($bloque_final =~ /--END COMMAND--/)
                            {
                                log_debug(
                                       "** There's an 'END' in the event block",
                                       32);
                                $todo .= $bloque_final;
                                process_cli_command($todo);
                                my $cuantos = @bloque;
                                log_debug(
                                     "There are $cuantos blocks for processing",
                                     32
                                );
                                @respuestas = digest_event_block(\@bloque);
                                if (@fake_bloque)
                                {
                                    @masrespuestas =
                                      digest_event_block(\@fake_bloque);
                                    @fake_bloque = ();
                                }
                                $todo = "";
                            }
                            elsif ($bloque_final =~ /<msg/)
                            {
                                log_debug(
                                    "** Processing command received from flash clients...",
                                    32
                                );
                                process_flash_command($bloque_final, $_);
                                @respuestas   = ();
                                $bloque_final = "";
                                $todo         = "";
                            }
                            elsif ($bloque_final =~ /policy-file-request/)
                            {

                                # Flash policy request are not working
                                # But the code its here just in case
                                @respuestas   = ();
                                $bloque_final = "";
                                $todo         = "";
                                $mandapolicy  = "<?xml version=\"1.0\"?>\n";
                                $mandapolicy .=
                                  "<!DOCTYPE cross-domain-policy SYSTEM \"http://www.macromedia.com/xml/dtds/cross-domain-policy.dtd\">\n";
                                $mandapolicy .= "<cross-domain-policy>\n";
                                $mandapolicy .=
                                  "<allow-access-from domain=\"localhost\" to-ports=\"4445\" />\n";
                                $mandapolicy .=
                                  "<allow-access-from domain=\"127.0.0.1\" to-ports=\"4445\" />\n";
                                $mandapolicy .=
                                  "<allow-access-from domain=\"www.asternic.org\" to-ports=\"4445\" />\n";
                                $mandapolicy .= "</cross-domain-policy>\0";
                                $socketpolicy = $_;
                            }
                            elsif ($bloque_final =~ /Challenge:/)
                            {
                                my @lineas = split(/\r\n/, $bloque_final);
                                foreach $p (@lineas)
                                {
                                    if ($p =~ /Challenge:/)
                                    {
                                        $p =~ s/^Challenge: (.*)/$1/g;
                                        $md5challenge = $p;
                                    }
                                }
                                manager_login_md5($md5challenge);
                            }
                            else
                            {
                                log_debug(
                                    "** There is no 'Event' nor 'End' in the block. Erasing block...",
                                    32
                                );

                                # No Event in the block. Lets clear it up...
                                @bloque = ();
                                $todo .= $bloque_final;
                            }
                        }
                        else
                        {
                            if ($mandapolicy =~ /cross-domain/)
                            {

                                # Flash does not work with policy files
                                # But the code is here for future addition
                                my $T =
                                  syswrite($socketpolicy, $mandapolicy,
                                           length($mandapolicy));
                                my $sockaddr = $socketpolicy->peername;
                                my ($port, $inetaddr) = sockaddr_in($sockaddr);
                                my $ip_address = inet_ntoa($inetaddr);
                                $mandapolicy  = "";
                                $socketpolicy = "";
                            }
                            else
                            {
                                $mandapolicy  = "";
                                $socketpolicy = "";
                            }
                            my $contador = 0;

                            # Send messages to Flash clientes
                            @respuestas    = (@respuestas, @masrespuestas);
                            @masrespuestas = ();

                            foreach my $valor (@respuestas)
                            {
                                my ($contextores, $nada1, $nada2) =
                                  split(/\|/, $valor);

                                my @pedacitos = split(/\@/, $contextores);
                                if (defined($pedacitos[1]))
                                {
                                    $contextores = $pedacitos[1];
                                }
                                else
                                {
                                    $contextores = "";
                                }
                                if (defined($flash_contexto{$C}))
                                {
                                    if ($flash_contexto{$C} eq $contextores)
                                    {

                                        # Send messages only to the clients with
                                        # the specific context
                                        my $T =
                                          send_status_to_flash($C, $valor);
                                        $contador++;
                                    }
                                }
                            }    # end foreach respuestas
                        }
                    }    # end foreach handles
                }
            }
        }    # end foreach @S -> can read
    }    # while can read
}    # endless loop

sub digest_event_block
{
    log_debug("** START SUB digest_event_block", 16);

    my $bleque     = shift;
    my @blique     = @$bleque;
    my @respuestas = ();
    my $canal      = "";
    my $quehace    = "";
    my $dos        = "";
    my $uniqueid   = "";
    my $canalid    = "";
    my $quehay     = "";
    my $mensaje    = "";
    my $interno    = "";
    my $mensajefinal;
    my $cuantas;

    delete $datos{""};
    foreach my $blaque (@blique)
    {
        log_debug("** Processing one block", 16);
        $mensaje = procesa_bloque($blaque);

        if (defined($mensaje) && $mensaje ne "")
        {
            log_debug("** Got $mensaje", 128);
            delete $datos{""};    # Erase the hash with no uniqueid

            ($canal, $quehace, $dos, $uniqueid, $canalid) =
              split(/\|/, $mensaje);
            if (!defined($canal))   { $canal   = ""; }
            if (!defined($quehace)) { $quehace = ""; }
            if (!defined($dos))     { $dos     = ""; }
            if ($canal ne "")
            {

                my $todo = "";

                if ($quehace eq 'corto' || $quehace eq 'info')
                {
                    while (my ($key, $val) = each(%{$datos{$uniqueid}}))
                    {
                        $todo .= "$key = $val\n"
                          if ($key ne "E") && (defined($val));
                    }
                    erase_all_sessions_from_channel($canalid);
                    delete $datos{$uniqueid};
                    log_debug("** Deleting hash for uniqueid $uniqueid", 32);
                    $todo = encode_base64($todo);
                }

                my @posibles_internos = ();

                # Si el mismo canal esta en mas de un contexto, duplica
                # el mensaje a enviar
                for $quehay (keys %buttons)
                {
                    if ($quehay =~ /^\Q$canal&\E|^\Q$canal\E$/)
                    {
                        push(@posibles_internos, $quehay);
                    }
                }

                if (defined($instancias{$canal}))
                {

                    # Its a trunk button
                    # Add the first pseudo channel
                    log_debug("Its a trunk " . $instancias{$canal}, 128);
                    my $justincase = $canal . "=1";

                    # And then look for contexts
                    for $quehay (keys %buttons)
                    {
                        if ($quehay =~ /^\Q$justincase&\E|^\Q$justincase\E$/)
                        {
                            push(@posibles_internos, $quehay);
                        }
                    }
                }

                if ($quehace eq "link")
                {

                    # This block is for catching trunk buttons
                    # and remove the parked text in case of a link
                    my $segundocanal = "Z";
                    if (defined($datos{$uniqueid}{"Channel"}))
                    {
                        $segundocanal = $datos{$uniqueid}{"Channel"};
                        $segundocanal =~ tr/a-z/A-Z/;
                    }

                    if (defined($orden_instancias{$segundocanal}))
                    {
                        my @partes =
                          separate_session_from_channel($segundocanal);
                        my $canalglobal = $partes[0];
                        $canalglobal =
                          $canalglobal . "=" . $orden_instancias{$segundocanal};
                        for $quehay (keys %buttons)    # Busca contextos
                        {
                            if ($quehay =~
                                /^\Q$canalglobal&\E|^\Q$canalglobal\E$/)
                            {
                                push(@posibles_internos, $quehay);
                            }
                        }
                    }

                    for $quehay (keys %buttons)
                    {
                        if ($quehay =~ /^\Q$dos&\E|^\Q$dos\E$/)
                        {
                            push(@posibles_internos, $quehay);
                        }
                    }
                    $quehace = "ocupado";
                    $dos     = "";
                }

                foreach my $canal (@posibles_internos)
                {
                    $interno = $buttons{$canal};
                    $interno = "" if (!defined($interno));

                    $mensajefinal = "$interno|$quehace|$dos";

                    for $quehay (keys %datos)
                    {
                        log_debug("** Active: $quehay", 32);
                    }

                    if (check_if_extension_is_busy($canal) eq "si"
                        && $quehace eq 'corto')
                    {
                        log_debug("** Hangup but still busy", 16);
                    }
                    else
                    {
                        if (defined($mensajefinal) && $interno ne "")
                        {
                            push(@respuestas, $mensajefinal);
                            if ($todo ne "")
                            {
                                my $otromensajefinal = "$interno|info|$todo";
                                push(@respuestas, $otromensajefinal);
                            }
                        }
                    }
                }    # end foreach posibles_internos
            }
            else
            {        # endif canal distinto de nada
                log_debug("** There is no command defined", 16);
            }
        }
    }
    $cuantas = @respuestas;
    log_debug("** There are $cuantas commands to send to flash clients", 16);
    foreach my $valor (@respuestas) { log_debug("** R: $valor", 32); }
    return @respuestas;
}

sub process_flash_command
{

    # This function process a command received from a Flash client
    # Including request of transfers, hangups, etc
    my $comando        = shift;
    my $socket         = shift;
    my $datos          = "";
    my $accion         = "";
    my $password       = "";
    my $valor          = "";
    my $origin_channel = "";
    my $canal_destino  = "";
    my $contexto       = "";
    my $btn_destino;
    my $origin_context = "";
    my $canal;
    my $nroboton;
    my $destino;
    my $sesion;
    my @partes;
    my $ultimo;
    my $clid;
    my $myclave;
    my $md5clave;
    my @pedazos;
    my $panelcontext;
    my $auto_conf_exten;
    my $conference_context;
    my $barge_rooms;
    my $found_room;

    log_debug("<= $comando\n", 4);

    log_debug("** Incoming command from flash client", 16);

    $comando =~ s/<msg data=\"(.*)\"\s?\/>/$1/g;    # Removes XML markup
    ($datos, $accion, $password) = split(/\|/, $comando);
    chop($password);

    if ($datos =~ /_level0\.casilla/)
    {
        $datos =~ s/_level0\.casilla(\d+)/$1/g;
    }
    if ($datos =~ /_level0\.rectangulo/)
    {
        $datos =~ s/_level0\.rectangulo(\d+).*/$1/g;
    }

    # Appends context if defined because my crappy regexp only extracts digits
    # FIXME make a regexp that extract digits and digits@context
    if (defined($flash_contexto{$socket}))
    {
        if ($flash_contexto{$socket} ne "")
        {
            if ($datos =~ /\@/)
            {

                # No need to append context
            }
            else
            {
                $datos .= "\@" . $flash_contexto{$socket};
            }
        }
    }

    undef $origin_channel;

    # Flash clients send a "contexto" command on connect indicating
    # the panel context they want to receive. We populate a hash with
    # sockets/contexts in order to send only the events they want
    # And because this is an initial connection, it triggers a status
    # request to Asterisk

    if ($accion =~ /^contexto\d+/)
    {

        sends_key($socket);

        my ($nada, $contextoenviado) = split(/\@/, $datos);

        if (defined($contextoenviado))
        {
            $flash_contexto{$socket} = $contextoenviado;
        }
        else
        {
            $flash_contexto{$socket} = "";
        }
        if ($datos =~ /^1/)
        {
            $no_encryption{"$socket"} = 1;
        }
        else
        {
            $no_encryption{"$socket"} = 0;
        }

        send_initial_status();
        return;
    }

    $panelcontext = $flash_contexto{$socket};
    if ($panelcontext eq "") { $panelcontext = "GENERAL"; }

    if (defined($config->{$panelcontext}{"conference_context"}))
    {
        $conference_context = $config->{$panelcontext}{"conference_context"};
    }
    else
    {
        if (defined($config->{"GENERAL"}{"conference_context"}))
        {
            $conference_context = $config->{"GENERAL"}{"conference_context"};
        }
        else
        {
            $conference_context = "";
        }
    }

    if (defined($config->{$panelcontext}{"barge_rooms"}))
    {
        $barge_rooms = $config->{$panelcontext}{"barge_rooms"};
        ($first_room, $last_room) = split(/-/, $barge_rooms);
    }
    else
    {
        if (defined($config->{"GENERAL"}{"barge_rooms"}))
        {
            $barge_rooms = $config->{"GENERAL"}{"barge_rooms"};
            ($first_room, $last_room) = split(/-/, $barge_rooms);
        }
        else
        {
            $barge_rooms = "";
        }
    }

    # We have the origin button number from the drag&drop in the 'datos'
    # variable. We need to traverse the %buttons hash in order to extract
    # the channel name and the panel context, used to find the destination
    # button of the command if any
    if ($accion =~ /^meetmemute\d+/ || $accion =~ /^bogus/)
    {
        $origin_channel = "bogus";
    }
    else
    {
        while (($canal, $nroboton) = each(%buttons))
        {
            if ($nroboton eq $datos)
            {

                # A button key with an & is for a context channel
                # A button key with an = if for a trunk   channel
                # This bit of code just cleans the channel name and context
                @pedazos = split(/&/, $canal);
                $origin_channel = $pedazos[0];
                $origin_channel =~ s/(.*)[=](.*)/$1/g;
                $origin_context = $pedazos[1];
            }

        }
    }

    if (defined($origin_channel))
    {
        if (defined($config->{$panelcontext}{"security_code"}))
        {
            $myclave =
              $config->{$panelcontext}{"security_code"} . $keys_socket{$socket};
        }
        else
        {
            $myclave = "";
            $myclave =
              $config->{"GENERAL"}{"security_code"} . $keys_socket{$socket};
        }

        if ($myclave ne "")
        {
            $md5clave = MD5HexDigest($myclave);
        }

        if (   ("$password" eq "$md5clave")
            || ($accion =~ /^dial/ && $cdial_nosecure == 1))
        {
            sends_correct($socket);
            log_debug(
                "** The channel selected  is $origin_channel and the security code matches",
                16
            );
            sends_key($socket);

            if ($accion =~ /-/)
            {

                #if action has an "-" the command has clid text to pass
                @partes = split(/-/, $accion);
                $ultimo = @partes;
                $ultimo--;
                $btn_destino = $partes[$ultimo];
                $ultimo--;
                $clid = $partes[$ultimo];
                if (defined($origin_context))
                {

                    if (length($origin_context) > 0)
                    {
                        $btn_destino = $btn_destino . "@" . $origin_context;
                    }
                }
            }
            else
            {

                #strips the destination button (number at the end)
                $btn_destino = $accion;
                $btn_destino =~ s/[A-Za-z- ]//g;

                if (defined($origin_context))
                {
                    if (length($origin_context) > 0)
                    {
                        $btn_destino = $btn_destino . "@" . $origin_context;
                    }
                }
            }

            # Now assigns the channel name to destino variable
            # traversing the %buttons hash to find the key/channel
            while (($canal, $nroboton) = each(%buttons))
            {
                if ($nroboton eq $btn_destino)
                {
                    $canal =~ s/(.*)=(.*)/$1/g;
                    $destino = $canal;
                }
            }

            if (is_number($destino))
            {

                # If the selected channel name is only digits, its a
                # conference. So treat a conference command as a regular
                # transfer or redirect. (We do not want to send into a
                # meetme conference another ongoing meetme conference)
                my @sesiones_del_canal =
                  extraer_todas_las_sesiones_de_un_canal($origin_channel);
                my $cuantos = @sesiones_del_canal;

                if ($accion =~ /^conference/)
                {
                    if ($cuantos == 0)
                    {
                        $accion =~ s/conference/originate/g;
                    }
                    elsif ($cuantos > 0)
                    {
                        $accion =~ s/conference/transferir/g;
                    }
                }
            }

            if ($accion eq "cortar")
            {
                log_debug("** Will try tu hangup channel: ", 16);
                my @cuales_cortar =
                  extraer_todas_las_sesiones_de_un_canal($origin_channel);
                foreach $valor (@cuales_cortar)
                {
                    $comando = "Action: Hangup\r\n";
                    $comando .= "Channel: $valor\r\n\r\n";
                    log_debug("** Command received: $accion el $valor", 32);
                    send_command_to_manager($comando);
                }
            }
            elsif ($accion =~ /^meetmemute/)
            {
                my $conference   = $btn_destino;
                my $meetmemember = $datos;
                $comando = "Action: Command\r\n";
                $comando .=
                  "Command: meetme mute $conference $meetmemember\r\n\r\n";
                send_command_to_manager($comando);
            }
            elsif ($accion =~ /^meetmeunmute/)
            {
                my $conference   = $btn_destino;
                my $meetmemember = $datos;
                $comando = "Action: Command\r\n";
                $comando .=
                  "Command: meetme unmute $conference $meetmemember\r\n\r\n";
                send_command_to_manager($comando);
            }
            elsif ($accion =~ /^conference/)
            {

                my $originate = $extension_transfer{"$origin_channel"};

                while (($canal, $nroboton) = each(%buttons))
                {
                    if ($nroboton eq $btn_destino)
                    {
                        $canal =~ s/(.*)=(.*)/$1/g;
                        my @links =
                          extraer_todos_los_enlaces_de_un_canal($canal);
                        my @canal_transferir =
                          extraer_todas_las_sesiones_de_un_canal($canal);

                        my $cuantos = @links;
                        if ($cuantos <= 0)
                        {
                            my @extensiondialed =
                              extracts_exten_from_active_channel($canal);
                            $comando = "Action: Originate\r\n";
                            $comando .= "Channel: $origin_channel\r\n";
                            $comando .= "Exten: $extensiondialed[0]\r\n";
                            $comando .= "Priority: 1\r\n\r\n";
                        }
                        else
                        {

                            log_debug(
                                "** $canal_transferir[0] $links[0] will be conferenced together with $origin_channel ($originate)",
                                16
                            );

                            # Try to find an empty conference
                            my $empty_room = $first_room;
                            for (my $at = $first_room ;
                                 $at <= $last_room ;
                                 $at++)
                            {
                                if ($barge_rooms{$at} == 0)
                                {
                                    $found_room = 1;
                                    $empty_room = $at;
                                    last;
                                }
                            }

                            if ($found_room == 1)
                            {
                                $comando = "Action: Redirect\r\n";
                                $comando .= "Channel: $canal_transferir[0]\r\n";
                                $comando .= "ExtraChannel: $links[0]\r\n";
                                $comando .= "Exten: $empty_room\r\n";
                                $comando .= "ActionID: 1234\r\n";
                                $comando .= "Context: $conference_context\r\n";
                                $comando .= "Priority: 1\r\n\r\n";
                                $auto_conference{$canal_transferir[0]} =
                                  $origin_channel;
                            }
                            else
                            {
                                $comando = "";
                            }
                        }
                        send_command_to_manager($comando);
                    }
                }
            }
            elsif ($accion =~ /^ctransferir/)
            {
                $comando = "Action: Command\r\n";
                $comando .= "Command: database put clid $destino ";
                $comando .= "\"$clid\"\r\n\r\n";
                send_command_to_manager($comando);

                while (($canal, $nroboton) = each(%buttons))
                {
                    if ($nroboton eq $btn_destino)
                    {
                        $canal =~ s/(.*)=(.*)/$1/g;
                        $canal_destino = $extension_transfer{"$canal"};
                    }
                }

                if ($canal_destino ne "-1")
                {
                    if ($canal_destino =~ /\@/)
                    {
                        @pedazos       = split(/\@/, $canal_destino);
                        $canal_destino = $pedazos[0];
                        $contexto      = $pedazos[1];
                    }
                    my @cuales_transferir =
                      extraer_todas_las_sesiones_de_un_canal($origin_channel);
                    foreach my $valor (@cuales_transferir)
                    {
                        log_debug(
                            "** Will try to transfer $valor to extension number $canal_destino!",
                            16
                        );
                        $comando = "Action: Redirect\r\n";
                        $comando .= "Channel: $valor\r\n";
                        $comando .= "Exten: $canal_destino\r\n";
                        if ($contexto ne "")
                        {
                            $comando .= "Context: $contexto\r\n";
                        }
                        $comando .= "Priority: 1\r\n\r\n";
                        send_command_to_manager($comando);
                    }
                }
                else
                {
                    log_debug("** Untransferable destination!", 16);
                }
            }
            elsif ($accion =~ /^transferir/)
            {
                while (($canal, $nroboton) = each(%buttons))
                {
                    if ($nroboton eq $btn_destino)
                    {
                        $canal =~ s/(.*)=(.*)/$1/g;
                        $canal_destino = $extension_transfer{"$canal"};
                    }
                }

                if ($canal_destino ne "-1")
                {
                    if ($canal_destino =~ /\@/)
                    {
                        @pedazos       = split(/\@/, $canal_destino);
                        $canal_destino = $pedazos[0];
                        $contexto      = $pedazos[1];
                    }
                    my @cuales_transferir =
                      extraer_todas_las_sesiones_de_un_canal($origin_channel);
                    foreach my $valor (@cuales_transferir)
                    {
                        log_debug(
                            "** Will try to transfer $valor to extension number $canal_destino!",
                            16
                        );
                        $comando = "Action: Redirect\r\n";
                        $comando .= "Channel: $valor\r\n";
                        $comando .= "Exten: $canal_destino\r\n";
                        if ($contexto ne "")
                        {
                            $comando .= "Context: $contexto\r\n";
                        }
                        $comando .= "Priority: 1\r\n\r\n";
                        send_command_to_manager($comando);
                    }
                }
                else
                {
                    log_debug("** Untransferable destination!", 16);
                }
            }
            elsif ($accion =~ /^coriginate/)
            {
                $comando = "Action: Command\r\n";
                $comando .= "Command: database put clid $destino ";
                $comando .= "\"$clid\"\r\n\r\n";
                send_command_to_manager($comando);

                while (($canal, $nroboton) = each(%buttons))
                {
                    if ($nroboton eq $btn_destino)
                    {
                        $canal =~ s/(.*)=(.*)/$1/g;
                        $canal_destino = $extension_transfer{"$canal"};
                    }
                }
                if ($canal_destino =~ /\@/)
                {
                    @pedazos       = split(/\@/, $canal_destino);
                    $canal_destino = $pedazos[0];
                    $contexto      = $pedazos[1];
                }

                log_debug(
                    "** Will try to originate from $origin_channel to extension $canal_destino!",
                    16
                );

                if ($origin_channel =~ /^IAX2\[/)
                {
                    $origin_channel =~ s/^IAX2\[(.*)\]/IAX2\/$1/g;
                }
                $comando = "Action: Originate\r\n";
                $comando .= "Channel: $origin_channel\r\n";
                $comando .= "Exten: $canal_destino\r\n";

                if ($contexto ne "")
                {
                    $comando .= "Context: $contexto\r\n";
                }
                $comando .= "Priority: 1\r\n";
                $comando .= "\r\n";
                send_command_to_manager($comando);
            }
            elsif ($accion =~ /^originate/)
            {
                while (($canal, $nroboton) = each(%buttons))
                {
                    if ($nroboton eq $btn_destino)
                    {
                        $canal =~ s/(.*)=(.*)/$1/g;
                        $canal_destino = $extension_transfer{"$canal"};
                    }
                }
                if ($canal_destino =~ /\@/)
                {
                    @pedazos       = split(/\@/, $canal_destino);
                    $canal_destino = $pedazos[0];
                    $contexto      = $pedazos[1];
                }

                log_debug(
                    "** Will try to originate from $origin_channel to extension $canal_destino!",
                    16
                );
                $clid =
                    $textos{"$datos"} . " <"
                  . $extension_transfer{"$origin_channel"} . ">";
                if ($origin_channel =~ /^IAX2\[/)
                {
                    $origin_channel =~ s/^IAX2\[(.*)\]/IAX2\/$1/g;
                }
                $comando = "Action: Originate\r\n";
                $comando .= "Channel: $origin_channel\r\n";
                $comando .= "Callerid: $clid\r\n";
                $comando .= "Exten: $canal_destino\r\n";

                if ($contexto ne "")
                {
                    $comando .= "Context: $contexto\r\n";
                }
                $comando .= "Priority: 1\r\n";
                $comando .= "\r\n";
                send_command_to_manager($comando);
            }
            elsif ($accion =~ /^dial/)
            {
                my $numero_a_discar = $accion;
                $numero_a_discar =~ s/^dial//g;
                $comando = "Action: Originate\r\n";
                $comando .= "Channel: $origin_channel\r\n";
                $comando .= "Exten: $numero_a_discar\r\n";
                if ($contexto ne "")
                {
                    $comando .= "Context: $contexto\r\n";
                }
                $comando .= "Priority: 1\r\n";
                $comando .= "\r\n";
                send_command_to_manager($comando);
            }
        }
        else
        {
            log_debug("** Password mismatch -$password-$md5clave-!", 1);
            sends_key($socket);
            sends_incorrect($socket);
        }
    }
    else
    {
        log_debug("** There is no channel selected ?", 16);
    }
}

sub send_initial_status
{

    log_debug("** About to send Initial Status", 16);

    send_command_to_manager("Action: Status\r\n\r\n");

    # Send commands to check the mailbox stauts for each mailbox defined
    while (my ($key, $val) = each(%mailbox))
    {
        log_debug("mailbox $key $val", 32);

        send_command_to_manager(
                              "Action: MailboxStatus\r\nMailbox: $val\r\n\r\n");
    }
    my @all_meetme_rooms = ();

    # generates an array with all meetme rooms to check on init
    for my $valor (keys %barge_rooms)
    {
        push(@all_meetme_rooms, $valor);
    }

    for my $key (keys %buttons)
    {
        if ($key =~ /^\d+$/)
        {
            push(@all_meetme_rooms, $key);
        }
    }

    my %count               = ();
    my @unique_meetme_rooms = grep { ++$count{$_} < 2 } @all_meetme_rooms;

    foreach my $valor (@unique_meetme_rooms)
    {
        send_command_to_manager(
            "Action: Command\r\nActionID: meetme_$valor\r\nCommand: meetme list $valor\r\n\r\n"
        );
    }

    send_command_to_manager("Action: QueueStatus\r\n\r\n");
    send_command_to_manager(
         "Action: Command\r\nActionId: agents\r\nCommand: show agents\r\n\r\n");
}

sub process_cli_command
{

    # This subroutine process the output for a manager "Command"
    # sent, as 'sip show peers'

    log_debug("** START SUB process_cli_command\n", 16);

    my $texto = shift;
    @bloque = ();
    my @lineas     = split("\r\n", $texto);
    my $contador   = 0;
    my $interno    = "";
    my $estado     = "";
    my $nada       = "";
    my $conference = 0;
    my $usernum    = 0;
    my $canal      = "";
    my $sesion     = "";

    if ($texto =~ "ActionID: meetme")
    {

        # Its a meetme status report
        foreach my $valor (@lineas)
        {
            $valor =~ s/\s+/ /g;
            my ($key, $value) = split(/: /, $valor, 2);

            if (defined($key))
            {

                if ($key eq "ActionID")
                {
                    $value =~ s/meetme_(\d+)$/$1/g;
                    $conference = $value;
                }
                if ($key eq "User #")
                {
                    my @partes = split(/Channel:/, $value);
                    $usernum = $partes[0];
                    $usernum =~ s/\s+//g;
                    $canal = $partes[1];
                    $canal =~ s/^\s+//g;
                    $canal =~ s/(.*)\((.*)/$1/g;
                    $bloque[$contador]{"Event"}    = "MeetmeJoin";
                    $bloque[$contador]{"Meetme"}   = $conference;
                    $bloque[$contador]{"Count"}    = $contador;
                    $bloque[$contador]{"Channel"}  = $canal;
                    $bloque[$contador]{"Usernum"}  = $usernum;
                    $bloque[$contador]{"Fake"}     = "hola";
                    $bloque[$contador]{"Uniqueid"} = "";
                    $contador++;
                }
            }
        }
        my $cuentamenos = $contador - 1;
        if ($cuentamenos >= 0)
        {
            $bloque[$cuentamenos]{"Total"} = $contador;
        }
    }
    elsif ($texto =~ "ActionID: agents")
    {
        my $agent_number;
        my $agent_state;
        my $agent_name;

        # Show Agents CLI command, generates fake events

        foreach (@lineas)
        {
            $_ =~ s/\s+/ /g;
            /(\d+) \((.*)\) (.*) (\(.*\))/;
            if (defined($1))
            {
                $agent_number               = $1;
                $agent_name                 = $2;
                $agent_state                = $3;
                $agents_name{$agent_number} = $agent_name;
            }
            if (defined($3))
            {
                if ($agent_state =~ /available at/)
                {

                    # Agent callback login
                    $agent_state =~ s/.*'(.*)'.*/$1/g;
                    $bloque[$contador]{"Event"}     = "Agentcallbacklogin";
                    $bloque[$contador]{"Loginchan"} = $agent_state;
                    $bloque[$contador]{"Agent"}     = $agent_number;
                    $contador++;
                }

                if ($agent_state =~ /logged in on/)
                {

                    # Agent login
                    $agent_state =~ s/\s+/ /g;
                    $agent_state =~ s/logged in on //g;
                    $agent_state =~ s/(.*) (.*)/$1/g;

                    $bloque[$contador]{"Event"}   = "Agentlogin";
                    $bloque[$contador]{"Channel"} = $agent_state;
                    $bloque[$contador]{"Agent"}   = $agent_number;
                    $contador++;
                }
            }
        }
    }
    elsif ($texto =~ "ActionID: iaxpeers")
    {
        foreach my $valor (@lineas)
        {
            log_debug("** Line: $valor", 32);
            $valor =~ s/\s+/ /g;
            my @parametros = split(" ", $valor);
            my $interno    = $parametros[0];
            my $ultimo     = @parametros;
            if ($ultimo == 6)
            {
                $ultimo = $ultimo - 1;
                my $estado = $parametros[$ultimo];
                if (defined($estado)
                    && $estado ne
                    "")    # If set, is the status of 'sip show peers'
                {
                    $interno = "IAX2/" . $interno . "-" . $interno;
                    log_debug("** State: $estado Extension: $interno", 16);
                    $bloque[$contador]{"Event"}   = "Regstatus";
                    $bloque[$contador]{"Channel"} = $interno;
                    $bloque[$contador]{"State"}   = $estado;
                    $contador++;
                }
            }
        }
    }
    else
    {

        # Its a sip show peers report
        foreach my $valor (@lineas)
        {
            log_debug("** Line: $valor", 32);
            $valor =~ s/\s+/ /g;
            my @parametros = split(" ", $valor);
            my $interno    = $parametros[0];
            my $ultimo     = @parametros;
            $ultimo = $ultimo - 1;
            my $estado = $parametros[$ultimo];
            if (defined($estado)
                && $estado ne "")    # If set, is the status of 'sip show peers'
            {
                $interno = "SIP/" . $interno;
                log_debug("** State: $estado Extension: $interno", 16);
                $bloque[$contador]{"Event"}   = "Regstatus";
                $bloque[$contador]{"Channel"} = $interno;
                $bloque[$contador]{"State"}   = $estado;
                $contador++;
            }
        }
    }
}

sub procesa_bloque
{
    log_debug("** START SUB procesa_bloque", 16);

    my $blaque = shift;
    my %bloque = %$blaque if defined(%$blaque);

    my %hash_temporal = ();
    my $evento        = "";
    my $canal         = "";
    my $sesion        = "";
    my $texto         = "";
    my $estado_final  = "";
    my $unico_id      = "";
    my $exten         = "";
    my $clid          = "";
    my $canalid       = "";
    my $key           = "";
    my $val           = "";
    my $return        = "";
    my $conquien      = "";
    my $enlazado      = "";
    my $viejo_nombre  = "";
    my $nuevo_nombre  = "";
    my $quehay        = "";
    my $elemento      = "";
    my $state         = "";
    my $exists        = 0;
    my $fakecounter   = 1;

    undef $unico_id;

    while (my ($key, $val) = each(%bloque))
    {
        if ($key eq "Event")
        {
            $evento = "";
            $hash_temporal{$key} = $val;
            $val =~ s/UserEvent//g;
            if    ($val =~ /Newchannel/)      { $evento = "newchannel"; }
            elsif ($val =~ /Newcallerid/)     { $evento = "newcallerid"; }
            elsif ($val =~ /^Status$/)        { $evento = "status"; }
            elsif ($val =~ /^StatusComplete/) { $evento = "statuscomplete"; }
            elsif ($val =~ /Newexten/)        { $evento = "newexten"; }
            elsif ($val =~ /ParkedCall/)      { $evento = "parkedcall"; }
            elsif ($val =~ /Newstate/)        { $evento = "newstate"; }
            elsif ($val =~ /Hangup/)          { $evento = "hangup"; }
            elsif ($val =~ /Rename/)          { $evento = "rename"; }
            elsif ($val =~ /MessageWaiting/)  { $evento = "voicemail"; }
            elsif ($val =~ /Regstatus/)       { $evento = "regstatus"; }
            elsif ($val =~ /Unlink/)          { $evento = "unlink"; }
            elsif ($val =~ /QueueParams/)     { $evento = "queueparams"; }
            elsif ($val =~ /QueueMember/)     { $evento = "queuemember"; }
            elsif ($val =~ /QueueStatus/)     { $evento = "queuestatus"; }
            elsif ($val =~ /Link/)            { $evento = "link"; }
            elsif ($val =~ /^Join/)           { $evento = "join"; }
            elsif ($val =~ /^MeetmeJoin/)     { $evento = "meetmejoin"; }
            elsif ($val =~ /^MeetmeLeave/)    { $evento = "meetmeleave"; }
            elsif ($val =~ /^Agentlogin/)     { $evento = "agentlogin"; }
            elsif ($val =~ /^Agentcallbacklogin/)
            {
                $evento = "agentcblogin";
            }
            elsif ($val =~ /^Agentcallbacklogoff/)
            {
                $evento = "agentlogoff";
            }
            elsif ($val =~ /^Agentlogoff/) { $evento = "agentlogoff"; }
            elsif ($val =~ /^IsMeetmeMember/)
            {
                $evento = "fakeismeetmemember";
            }
            elsif ($val =~ /^PeerStatus/) { $evento = "peerstatus"; }
            elsif ($val =~ /^Leave/)      { $evento = "leave"; }
            else { log_debug("** No event match ($val)", 16); }
        }
        else
        {    # Guarda todos los otros datos en un hash nuevo
            $hash_temporal{$key} = $val;
        }
    }

    $unico_id = "";
    $unico_id = $hash_temporal{"Uniqueid"}
      if defined($hash_temporal{"Uniqueid"});

    $enlazado = "";
    $enlazado = $datos{$unico_id}{"Link"}
      if defined($datos{$unico_id}{"Link"});

    if (defined($hash_temporal{"Link"}))
    {
        if (defined($hash_temporal{"Seconds"}))
        {
            $fake_bloque[$fakecounter]{"Event"}    = "Status";
            $fake_bloque[$fakecounter]{"Channel"}  = $hash_temporal{"Link"};
            $fake_bloque[$fakecounter]{"State"}    = "Up";
            $fake_bloque[$fakecounter]{"Seconds"}  = $hash_temporal{"Seconds"};
            $fake_bloque[$fakecounter]{"CallerID"} = $hash_temporal{"CallerID"};
            $fakecounter++;

            # $fake_bloque[1]{"Extension"}=$hash_temporal{"Extension"};
        }
    }

    $enlazado .= " - " . $datos{$unico_id}{"Application"}
      if defined($datos{$unico_id}{"Application"});
    $enlazado .= ":" . $datos{$unico_id}{"AppData"}
      if defined($datos{$unico_id}{"AppData"});

    if ($evento eq "agentcblogin")
    {
        my $labeltext = ".";
        my $texto     = $hash_temporal{"Agent"};
        if (defined($extension_transfer_reverse{$hash_temporal{"Loginchan"}}))
        {
            $canal = $extension_transfer_reverse{$hash_temporal{"Loginchan"}};
            $estado_final = "changelabel" . $change_led;
            if ($ren_cbacklogin == 1)
            {
                $labeltext = "Agent/$texto";
                if ($ren_agentname == 1)
                {
                    if (defined($agents_name{$texto}))
                    {
                        $labeltext = $agents_name{$texto};
                    }
                }
            }
            $return = "$canal|$estado_final|$labeltext|$unico_id|$canalid";
            $agents{$texto} = $canal;
        }
        $evento = "";
    }

    if ($evento eq "agentlogin")
    {
        my $labeltext = ".";

        $texto = $hash_temporal{"Agent"};

        ($canal, my $nada) =
          separate_session_from_channel($hash_temporal{"Channel"});
        $estado_final = "changelabel" . $change_led;

        if ($ren_agentlogin == 1 && !defined($hash_temporal{'Fake'}))
        {
            $labeltext = "Agent/$texto";
            if ($ren_agentname == 1)
            {
                if (defined($agents_name{$texto}))
                {
                    $labeltext = $agents_name{$texto};
                }
            }
        }
        if ($ren_queuemember == 1)
        {
            $labeltext = "Agent/$texto";
            if ($ren_agentname == 1)
            {
                if (defined($agents_name{$texto}))
                {
                    $labeltext = $agents_name{$texto};
                }
            }
        }

        $return         = "$canal|$estado_final|$labeltext|$unico_id|$canalid";
        $agents{$texto} = $canal;
        $evento         = "";
    }

    if ($evento eq "agentlogoff"
        && ($ren_agentlogin == 1 || $ren_cbacklogin == 1 || $change_led == 1))
    {
        $texto = $hash_temporal{"Agent"};
        if (defined($agents{$texto}))
        {
            $canal        = $agents{$texto};
            $estado_final = "changelabel" . $change_led;
            $return       = "$canal|$estado_final|original|$unico_id|$canalid";
        }
        delete $agents{$texto};
        $evento = "";
    }

    if ($evento eq "queuemember")
    {
        $canal = $hash_temporal{"Location"};
        if (substr($canal, 0, 5) eq "Agent")
        {
            $canal =~ s/Agent\///g;

            if (defined($agents{"$canal"}))
            {
                $canal = $agents{$canal};
            }
        }

        $canal =~ tr/a-z/A-Z/;
        $estado_final = "info";
        $texto        = "";
        while (($key, $val) = each(%hash_temporal))
        {
            $texto .= "$key = $val\n";
            if ($key eq "Queue")
            {
                $estado_final .= $val;
            }
        }
        $unico_id = $canal;
        $texto    = encode_base64($texto);
        $return   = "$canal|$estado_final|$texto|$unico_id|$canalid";
        $evento   = "";

        #		if($ren_queuemember == 1) {
        # Generates Fake Agent Login to change led color and label renaming
        $fake_bloque[$fakecounter]{"Event"}   = "Agentlogin";
        $fake_bloque[$fakecounter]{"Channel"} = $canal . "-1234";
        $fake_bloque[$fakecounter]{"Agent"}   = $canal;
        $fake_bloque[$fakecounter]{"Fake"}    = "1";
        $fakecounter++;

        #		}
    }

    if ($evento eq "queuestatus")
    {
        $canal = $hash_temporal{"Queue"};
        $canal =~ tr/a-z/A-Z/;
        $estado_final = "infoqstat";
        $texto        = "";
        while (($key, $val) = each(%hash_temporal))
        {
            $texto .= "$key = $val\n";
        }
        $unico_id = $canal;
        $texto    = encode_base64($texto);
        $return   = "$canal|$estado_final|$texto|$unico_id|$canalid";
        $evento   = "";
    }

    if ($evento eq "queueparams")
    {
        $canal = $hash_temporal{"Queue"};
        $canal =~ tr/a-z/A-Z/;
        $estado_final = "ocupado";
        my $plural = "";
        if ($hash_temporal{"Calls"} > 0)
        {
            if ($hash_temporal{"Calls"} > 1) { $plural = "s"; }
            $texto =
              $hash_temporal{"Calls"} . " member$plural waiting on queue...";
            $unico_id = $canal;
            $return   = "$canal|$estado_final|$texto|$unico_id|$canalid";
            $evento   = "";
        }

        # Generates a Fake Block/Event for sending info status to queues
        while (($key, $val) = each(%hash_temporal))
        {
            $fake_bloque[$fakecounter]{$key} = $val;
        }
        $fake_bloque[$fakecounter]{"Event"} = "QueueStatus";
        $fakecounter++;
    }

    if ($evento eq "join")
    {
        $canal = $hash_temporal{"Queue"};
        $canal =~ tr/a-z/A-Z/;
        $estado_final = "ocupado";
        my $plural = "";
        if ($hash_temporal{"Count"} > 1) { $plural = "s"; }
        $texto    = "[" . $hash_temporal{"Count"} . " user$plural waiting.]";
        $unico_id = $canal;
        $return   = "$canal|$estado_final|$texto|$unico_id|$canalid";
        $evento   = "";
    }

    if ($evento eq "meetmejoin")
    {
        my $originate = "no";
        my $nada      = "";
        my $contexto  = "";

        $canal = $hash_temporal{"Meetme"};
        my $uni_id = $hash_temporal{"Uniqueid"};
        $datos{$uni_id}{"Extension"} = $canal;
        delete $datos{$uni_id}{"Link"};

        $canal =~ tr/a-z/A-Z/;

        # Fake event to signal flash that a button is member
        # of a meetme

        $fake_bloque[$fakecounter]{"Event"}   = "IsMeetmeMember";
        $fake_bloque[$fakecounter]{"Channel"} = $hash_temporal{"Channel"};
        $fake_bloque[$fakecounter]{"Usernum"} = $hash_temporal{"Usernum"};
        $fake_bloque[$fakecounter]{"Meetme"}  = $hash_temporal{"Meetme"};
        $fakecounter++;

        # Originates a call to the third extension if it finds
        # an auto created conference

        for $quehay (keys %auto_conference)
        {

            if ($quehay eq $hash_temporal{"Channel"})
            {
                $originate = $auto_conference{"$quehay"};
                $contexto  = $barge_context{$canal};
            }
        }

        if ($originate ne "no")
        {
            log_debug("origino a meetme en el contexto $contexto!", 128);
            my $comando = "Action: Originate\r\n";
            $comando .= "Channel: $originate\r\n";
            $comando .= "Exten: $canal\r\n";
            $comando .=
              "Context: " . $config->{$contexto}{'conference_context'} . "\r\n";
            $comando .= "Priority: 1\r\n";
            $comando .= "\r\n";
            send_command_to_manager($comando);
        }

        $estado_final = "ocupado9";    # 9 for conference
        my $plural = "";
        if (!defined($hash_temporal{"Fake"}))
        {
            if (!defined($datos{$canal}{"Count"}))
            {
                $datos{$canal}{"Count"} = 0;
            }
            $datos{$canal}{"Count"}++;
        }

        if (defined($hash_temporal{"Total"}))
        {
            $datos{$canal}{"Count"} = $hash_temporal{"Total"};
        }

        $barge_rooms{$canal} = $datos{$canal}{"Count"};

        if (defined($datos{$canal}{"Count"}))
        {
            if ($datos{$canal}{"Count"} > 1) { $plural = "s"; }
            $texto =
                $datos{$canal}{"Count"}
              . " member$plural on conference ["
              . $datos{$canal}{"Count"}
              . " Member$plural].";
        }
        $unico_id = $canal;
        $return   = "$canal|$estado_final|$texto|$unico_id|$canalid";
        $evento   = "";
    }

    if ($evento eq "meetmeleave")
    {
        $canal = $hash_temporal{"Meetme"};
        $canal =~ tr/a-z/A-Z/;
        $estado_final = "ocupado9";    # 9 for meetme
        my $plural = "";
        $datos{$canal}{"Count"}--;
        $barge_rooms{$canal} = $datos{$canal}{"Count"};
        if ($datos{$canal}{"Count"} > 1)  { $plural       = "s"; }
        if ($datos{$canal}{"Count"} <= 0) { $estado_final = "corto"; }
        $texto =
            $datos{$canal}{"Count"}
          . " member$plural on conference ["
          . $datos{$canal}{"Count"}
          . " member$plural].";
        $unico_id = $canal;
        $return   = "$canal|$estado_final|$texto|$unico_id|$canalid";
        $evento   = "";
        my $canaleja = $hash_temporal{"Channel"};
        delete $auto_conference{$canaleja};
        log_debug("** Erasing auto_conference $canaleja", 16);

        for $quehay (keys %auto_conference)
        {
            log_debug("** Remaining conferences: $quehay", 16);
        }
    }

    if ($evento eq "leave")
    {
        $canal = $hash_temporal{"Queue"};
        $canal =~ tr/a-z/A-Z/;
        $estado_final = "ocupado";
        my $plural = "";
        if ($hash_temporal{"Count"} > 1)  { $plural       = "s"; }
        if ($hash_temporal{"Count"} == 0) { $estado_final = "corto"; }
        $texto    = "[" . $hash_temporal{"Count"} . " member$plural on queue.]";
        $unico_id = $canal;
        $return   = "$canal|$estado_final|$texto|$unico_id|$canalid";
        $evento   = "";
    }

    if ($evento eq "voicemail")
    {
        while (my ($ecanal, $eextension) = each(%mailbox))
        {
            if ($eextension eq $hash_temporal{"Mailbox"})
            {
                $canal = $ecanal;
                $canal =~ s/(.*)\&(.*)/$1/g;    # Remove &context
            }
        }
        $unico_id = $canal;
        if (defined($hash_temporal{"Waiting"}))
        {
            $estado_final = "voicemail";
            $texto        = $hash_temporal{"Waiting"};
            if ($texto eq "1")
            {

                # If it has new voicemail, ask for mailboxcount
                send_command_to_manager(
                    "Action: MailboxCount\r\nMailbox: $hash_temporal{'Mailbox'}\r\n\r\n"
                );
            }
        }
        else
        {
            $estado_final = "voicemailcount";
            my $nuevos = $hash_temporal{"NewMessages"};
            my $viejos = $hash_temporal{"OldMessages"};
            $texto = "New: $nuevos, Old: $viejos";
        }
        $return = "$canal|$estado_final|$texto|$unico_id|$canalid";
        $evento = "";
    }

    if ($evento eq "link")
    {
        my $uniqueid1 = $hash_temporal{"Uniqueid1"};
        my $uniqueid2 = $hash_temporal{"Uniqueid2"};
        my $channel1  = $hash_temporal{"Channel1"};
        my $channel2  = $hash_temporal{"Channel2"};
        $datos{$uniqueid1}{"Link"} = $channel2;
        $datos{$uniqueid2}{"Link"} = $channel1;
        my ($canal1, $sesion1) = separate_session_from_channel($channel1);
        my ($canal2, $sesion2) = separate_session_from_channel($channel2);
        $return = "$canal1|link|$canal2|$uniqueid2|";
        delete $datos{$unico_id};
        $evento       = "";
        $canal        = $canal1;
        $estado_final = "ocupado";
    }

    if ($evento eq "newexten")
    {

        # If its a new extension without state, defaults to 'Up'
        $datos{$unico_id}{'State'} = "Up";
    }

    if ($evento eq "rename")
    {
        log_debug("** RENAME Event", 16);
        $evento = "";
        while (($key, $val) = each(%hash_temporal))
        {
            if ($key =~ /newname/i)
            {
                my $nuevo_nombre = $val;
            }
            if ($key =~ /oldname/i)
            {
                my $viejo_nombre = $val;
            }
        }

        for $quehay (keys %datos)
        {
            while (($key, $val) = each(%{$datos{$quehay}}))
            {
                if (($key eq "Channel") && ($val eq $viejo_nombre))
                {
                    $datos{"$quehay"}{"$key"} = $nuevo_nombre;
                }
            }
        }
    }

    if ($evento eq "unlink")
    {
        my $canal1 = $hash_temporal{"Channel1"};
        my $canal2 = $hash_temporal{"Channel2"};

        # erase_instances_for_trunk_buttons($canal1);
        # erase_instances_for_trunk_buttons($canal2);
        $canal1 =~ s/(.*)[-\/](.*)(<.*>)/$1\t$2/g;
        $canal2 =~ s/(.*)[-\/](.*)(<.*>)/$1\t$2/g;
        erase_all_sessions_from_channel($canal1);
        erase_all_sessions_from_channel($canal2);
        log_debug("** Unlink $canal1 and $canal2", 16);
        $evento = "";
    }

    if ($evento eq "peerstatus")
    {
        my $tiempo = 0;
        $canal = $hash_temporal{"Peer"};
        $canal =~ tr/a-z/A-Z/;
        $state = $hash_temporal{"PeerStatus"};

        if (defined $hash_temporal{"Time"})
        {
            $tiempo = $hash_temporal{"Time"};
        }

        if ($state eq "Registered")
        {
            $estado_final = "registrado";
            $texto        = "Registrado";
        }
        elsif ($state eq "Reachable")
        {
            $estado_final = "registrado";
            $texto        = "Reachable $tiempo";
        }
        elsif ($state eq "Unreachable")
        {
            $estado_final = "unreachable";
            $texto        = "No Reachable $tiempo";
        }
        elsif ($state eq "Lagged")
        {
            $estado_final = "noregistrado";
            $texto        = "Lagged $tiempo";
        }
        $evento = "";
        $return = "$canal|$estado_final|$texto|$unico_id|$canalid";
    }

    if ($evento ne "")
    {
        log_debug("** Event $evento", 16);

        # Populates a global hash to keep track of
        # 'active' channels, the ones that are not
        # state down.
        while (my ($key, $val) = each(%hash_temporal))
        {
            $datos{$unico_id}{"$key"} = $val;
        }

        if ($evento eq "hangup")
        {
            $datos{$unico_id}{'State'} = "Down";
        }

        if ($evento eq "fakeismeetmemember")
        {

            $estado_final = "meetmeuser";
            $texto = $hash_temporal{"Usernum"} . "," . $hash_temporal{"Meetme"};
            log_debug($return, 128);

            #        $evento="";
        }

        # De acuerdo a los datos de la extension genera
        # la linea con info para el flash

        $elemento = $datos{$unico_id}{'Channel'}
          if defined($datos{$unico_id}{'Channel'});
        my $canalsesion = $datos{$unico_id}{'Channel'}
          if defined($datos{$unico_id}{'Channel'});

        # Old IAX naming convention
        if ($elemento =~ /^IAX2\[/ && $elemento =~ /\@/)
        {

            # The channel is IAX2 and has the @context
            # I will remove the @context/host because it varies
            $elemento =~ s/IAX2\[(.*)@(.*)\](.*)/IAX2\[$1\]$3/g;
        }

        if ($elemento =~ /^IAX2\// && $elemento =~ /\@/)
        {
            $elemento =~ s/IAX2\/(.*)@(.*)\/(.*)/IAX2\/$1\/$3/g;
        }

        $elemento =~ s/(.*)[-\/](.*)/$1\t$2/g;
        $elemento =~ tr/a-z/A-Z/;
        ($canal, $sesion) = split(/\t/, $elemento);

        if (defined($canal) && defined($sesion))
        {
            log_debug("canal $canal sesion $sesion", 128);
        }

        if (defined($canal))
        {
            if (   defined($instancias{$canal})
                && $evento ne "regstatus"
                && $evento ne "")
            {

                #                $canal = count_instances_for_channel($canalsesion);
                $canal = get_next_trunk_button($canalsesion);
            }
        }

        $canal =~ tr/a-z/A-Z/ if defined($canal);
        if (!defined($canal)) { $canal = ""; }

        $exten = $datos{$unico_id}{'Extension'}
          if (defined($datos{$unico_id}{'Extension'}));
        $clid = $datos{$unico_id}{'Callerid'}
          if (defined($datos{$unico_id}{'Callerid'}));
        my $clid_with_format = format_clid($clid, $clid_format);
        $state = $datos{$unico_id}{'State'}
          if (defined($datos{$unico_id}{'State'}));

        if ($evento eq "parkedcall")
        {
            $texto        = "[Parked on " . $datos{$unico_id}{'Exten'} . "]";
            $estado_final = "ocupado3";
        }

        if ($state eq "Ring")
        {
            $texto                      = "Originating call ";
            $estado_final               = "ocupado";
            $datos{$unico_id}{'Origin'} = "true";
        }

        if ($state =~ /^UNK/)
        {
            $texto        = "No registrado " . $exten;
            $estado_final = "noregistrado";
        }

        if ($state =~ /^UNR/)
        {
            $texto        = "No alcanzable " . $exten;
            $estado_final = "unreachable";
        }

        if ($state =~ /^Unm/)
        {
            $texto        = "Registrado " . $exten;
            $estado_final = "registrado";
        }

        if ($state =~ /^OK/)
        {
            $texto        = "Registrado " . $exten;
            $estado_final = "registrado";
        }

        if ($state eq "Ringing")
        {
            $texto =
              "Incoming call from [" . $clid_with_format . "] " . $enlazado;
            $estado_final = "ringing";
        }

        if ($state eq "Down")
        {
            $canalid      = $elemento;
            $estado_final = "corto";
            erase_instances_for_trunk_buttons($canalsesion);
        }

        if ($state eq "Up")
        {
            if ($exten ne "")
            {
                if (is_number($exten))
                {
                    $conquien = "[" . $exten . "]";
                }
                else
                {
                    $conquien = $exten;
                }
            }
            else
            {
                $conquien = $clid_with_format;
            }
            if (defined($hash_temporal{'Seconds'}))
            {
                $conquien .= " (" . $hash_temporal{'Seconds'} . ")";
            }

            if (defined($datos{$unico_id}{'Origin'}))
            {
                if ($datos{$unico_id}{'Origin'} eq "true")
                {
                    $texto        = "Outgoing call to $conquien - $enlazado";
                    $estado_final = "ocupado2";
                }
            }
            else
            {
                $texto        = "Incoming call from $conquien - $enlazado";
                $estado_final = "ocupado1";
            }
        }

        # Remove special character from Caller ID string
        $texto =~ s/\"/'/g;
        $texto =~ s/</[/g;
        $texto =~ s/>/]/g;

        $return = "$canal|$estado_final|$texto|$unico_id|$canalid";

    }
    else
    {
        log_debug("** No 'event' in block ($evento)", 32);
    }

    if ($canal ne "" && $estado_final ne "")
    {
        log_debug("** Return $return", 16);
        return $return;
    }

}

# sub count_instances_for_channel
sub get_next_trunk_button
{
    my $canalid = shift;
    my $sesion;
    my $canalglobal;
    my @uniq;
    my $btn_num;

    my $canalsesion = $canalid;
    $canalsesion =~ tr/a-z/A-Z/;

    $canalid =~ s/(.*)<(.*)>/$1/g;
    $canalid =~ tr/a-z/A-Z/;
    $canalid =~ s/\s+//g;

    log_debug("** Count instances channel $canalid", 16);
    $canalglobal = $canalid;
    $canalglobal =~ s/(.*)[-\/](.*)/$1/g;
    $canalglobal =~ s/IAX2\/(.*)@(.*)/IAX2\/$1/g;
    $canalglobal =~ s/IAX2\[(.*)@(.*)\]/IAX2\[$1\]/g;
    $canalglobal =~ tr/a-z/A-Z/;

    #my $cuantos = @{$instancias{$canalglobal}};

    if (!defined($orden_instancias{$canalid}))
    {

        #        $cuantos++;
        #        $orden_instancias{$canalid} = $cuantos;
        for ($btn_num = 1 ; ; $btn_num++)
        {
            last
              if (
                  grep(($orden_instancias{$_} == $btn_num),
                       @{$instancias{$canalglobal}}) == 0
                 );
        }
        $orden_instancias{$canalid} = $btn_num;
    }

    if (defined($instancias{$canalglobal}))
    {
        push @{$instancias{$canalglobal}}, $canalid;
        my %seen = ();
        @uniq = grep { !$seen{$_}++ } @{$instancias{$canalglobal}};
        delete $instancias{$canalglobal};
        $instancias{$canalglobal} = [@uniq];
        my $contador = 0;
        foreach my $valor (@uniq)
        {
            $contador++;
            if ($valor eq $canalid)
            {
                my $queboton = $orden_instancias{$canalid};
                return "$canalglobal=$queboton";
            }
        }
    }
}

sub separate_session_from_channel()
{
    my $elemento = shift;
    $elemento =~ s/(.*)[-\/](.*)/$1\t$2/g;
    $elemento =~ tr/a-z/A-Z/;
    $elemento =~ s/IAX2\[(.*)@(.*)\]\t(.*)/IAX2\[$1\]\t$3/;
    $elemento =~ s/IAX2\/(.*)@(.*)\t(.*)/IAX2\/$1\t$3/;
    my @partes = split(/\t/, $elemento);
    return @partes;
}

sub erase_instances_for_trunk_buttons()
{
    my $canalid = shift;
    my $canalglobal;
    my $valor;
    my @new;

    $canalid =~ s/(.*)<(.*)>/$1/g;    #discards ZOMBIE or MASQ
    $canalid =~ tr/a-z/A-Z/;

    $canalglobal = $canalid;
    $canalglobal =~ s/(.*)[-\/](.*)/$1/g;
    $canalglobal =~ s/IAX2\/(.*)@(.*)/IAX2\/$1/g;
    $canalglobal =~ s/IAX2\[(.*)@(.*)\]/IAX2\[$1\]/g;
    $canalglobal =~ tr/a-z/A-Z/;

    if (defined($instancias{$canalglobal}))
    {
        log_debug("** Erase instance $canalid", 16);

        foreach $valor (@{$instancias{$canalglobal}})
        {
            if ($valor ne $canalid)
            {
                push(@new, $valor);
            }
        }
        delete $instancias{$canalglobal};
        delete $orden_instancias{$canalid};
        my $cuantos = @new;
        if ($cuantos == 0)
        {
            $instancias{$canalglobal} = [];
        }
        else
        {
            $instancias{$canalglobal} = [@new];
        }
        foreach $valor (@new)
        {
            log_debug("Session remaining: $valor", 64);
        }
    }

}

sub erase_all_sessions_from_channel
{
    my $canalid = shift;
    $canalid =~ s/(.*)<(.*)>/$1/g;    # Removes <zombie><masq>
    log_debug("** Erase all instances of channel $canalid", 16);
    my $quehay = "";
    for $quehay (keys %datos)
    {
        while (my ($key, $val) = each(%{$datos{$quehay}}))
        {
            if ($key eq "Channel")
            {
                $val =~ s/(.*)[-\/](.*)/$1\t$2/g;
                $val =~ tr/a-z/A-Z/;
                if ($canalid eq $val)
                {
                    log_debug(
                          "** Found a match $canalid=$val ($quehay) - Cleared!",
                          16);
                    delete $datos{$quehay};
                }
            }
        }
    }
}

sub extraer_todas_las_sesiones_de_un_canal
{
    my $canal         = shift;
    my $canalbase     = "";
    my $sesion_numero = "";
    my $sesion        = "";
    my $key           = "";
    my $val           = "";
    my $quehay        = "";
    my @result        = ();

    log_debug("** Extracts all sessions from the channel $canal", 16);

    # Removes the context if its set

    my @pedazos = split(/&/, $canal);
    $canal = $pedazos[0];

    # Checks if the channel name has an equal sign
    # (its a trunk button channel)

    if ($canal =~ /(.*)=(\d+)/)
    {
        ($canalbase, $sesion_numero) = split(/\=/, $canal);
        log_debug("** Its a trunk $canalbase button number $sesion_numero!", 8);

        # $sesion_numero--;
        # push(@result, $instancias{$canalbase}[$sesion_numero]);
        for $quehay (keys %orden_instancias)
        {
            my $vel = $quehay;
            $vel =~ s/(.*)[-\/](.*)/$1/g;
            $vel =~ s/IAX2\/(.*)@(.*)/IAX2\/$1/g;
            $vel =~ s/IAX2\[(.*)@(.*)\]/IAX2\/$1/g;
            if ($vel eq $canalbase)
            {
                my $orden = $orden_instancias{$quehay};
                $orden = $orden + 1 - 1;
                if ($sesion_numero == $orden)
                {
                    push(@result, $quehay);
                }
            }
        }
    }

    my $cuantos = @result;
    if ($cuantos == 0)
    {

        # If there is no results for a trunk button, look into the %datos
        # hash.

        for $quehay (keys %datos)
        {
            while (($key, $val) = each(%{$datos{$quehay}}))
            {
                if (defined($val))
                {
                    my $vel = $val;
                    if ($vel =~ /^IAX2/)
                    {
                        $vel =~ s/IAX2\/(.*)@(.*)\/(.*)/IAX2\/$1\/$3/g;
                        $vel =~ s/IAX2\[(.*)@(.*)\](.*)/IAX2\[$1\]$3/g;
                    }
                    if ($vel =~ /^\Q$canal\E[-\/]/i && $key eq "Channel")
                    {
                        push(@result, $val);
                        log_debug("** Sesion: $val", 16);
                    }
                }
            }
        }
    }
    return @result;
}

sub extracts_exten_from_active_channel
{
    my $canal  = shift;
    my $quehay = "";
    my @result = ();

    my @pedazos = split(/&/, $canal);
    $canal = $pedazos[0];

    for $quehay (keys %datos)
    {
        my $canalaqui = 0;
        my $linkeado  = "";
        while (my ($key, $val) = each(%{$datos{$quehay}}))
        {
            if ($val =~ /^$canal-/i && ($key =~ /^Chan/i || $key =~ /^Link/i))
            {
                $canalaqui = 1;
            }
            if ($key =~ /^Exten/i)
            {
                $linkeado = $val;
            }
        }
        if ($canalaqui == 1 && $linkeado ne "")
        {
            push(@result, $linkeado);
        }
    }
    return @result;
}

sub extraer_todos_los_enlaces_de_un_canal
{
    my $canal  = shift;
    my $quehay = "";
    my @result = ();

    my @pedazos = split(/&/, $canal);
    $canal = $pedazos[0];

    for $quehay (keys %datos)
    {
        my $canalaqui = 0;
        my $linkeado  = "";
        while (my ($key, $val) = each(%{$datos{$quehay}}))
        {

            if ($val =~ /^$canal-/i && $key =~ /^Chan/i)
            {
                $canalaqui = 1;
            }
            if ($key =~ /^Link/i)
            {
                $linkeado = $val;
            }
        }
        if ($canalaqui == 1 && $linkeado ne "")
        {
            push(@result, $linkeado);
        }
    }
    return @result;
}

sub check_if_extension_is_busy
{
    my $interno = shift;
    my $return  = "no";
    my $quehay  = "";
    my $canal   = "";
    my $sesion  = "";
    my $comando = "";

    for $quehay (keys %datos)
    {
        while (my ($key, $val) = each(%{$datos{$quehay}}))
        {
            if ($key eq "Channel")
            {
                if ($val =~ /ZOMBIE/)
                {
                    if ($kill_zombies == 1)
                    {

                        # If it finds a Zombie, try to hang it up
                        $comando = "Action: Hangup\r\n";
                        $comando .= "Channel: $val\r\n\r\n";
                        send_command_to_manager($comando);
                        log_debug("** ZOMBIE!! I will try to kill it!! $val",
                                  16);
                    }
                }
                else
                {
                    $val =~ s/(.*)[-\/](.*)/$1\t$2/g;
                    ($canal, $sesion) = split(/\t/, $val);
                    $canal =~ tr/a-z/A-Z/;
                    if ($canal eq $interno)
                    {
                        $return = "si";
                        log_debug("** Extension still busy $canal $interno",
                                  16);
                    }
                    else
                    {
                        log_debug("** $canal <> $interno", 32);
                    }
                }
            }
        }
    }
    return $return;
}

sub log_debug
{
    my $texto = shift;
    $texto =~ s/\0//g;
    my $nivel = shift;
    if (!defined($nivel)) { $nivel = 1; }
    if (!defined($debug)) { $debug = 1; }
    print "$texto\n" if $debug & $nivel;
}

sub alarma_al_minuto
{
    if (defined $p)
    {
        my $comando = "Action: Command\r\n";
        $comando .= "Command: sip show peers\r\n\r\n";
        send_command_to_manager($comando);

        $comando = "Action: Command\r\n";
        $comando .= "ActionID: iaxpeers\r\n";
        $comando .= "Command: iax2 show peers\r\n\r\n";
        send_command_to_manager($comando);

        if ($poll_voicemail == 1)
        {

            # Send commands to check the mailbox stauts for each mailbox defined
            while (my ($key, $val) = each(%mailbox))
            {
                log_debug("mailbox $key $val", 32);
                send_command_to_manager(
                              "Action: MailboxStatus\r\nMailbox: $val\r\n\r\n");
            }
        }
    }
    alarm($poll_interval);
}

sub send_status_to_flash
{
    my $socket       = shift;
    my $status       = shift;
    my $encriptado   = $status;
    my $boton_numero = 0;

    if ($encriptado =~ /key\|0/)
    {
        $boton_numero = '0';
    }
    else
    {
        $boton_numero = $status;
        $boton_numero =~ s/(\d+)\|(.*)/$1/g;
    }

    $encriptado = &TEAencrypt($encriptado, $keys_socket{"$socket"});
    my $encriptadofinal =
      "<response data=\"$encriptado\" btn=\"$boton_numero\"/>\0";

    if (!defined($keys_socket{"$socket"}))
    {
        $encriptadofinal =
          "<response data=\"$status\" btn=\"$boton_numero\"/>\0";
    }
    if (defined($no_encryption{"$socket"}))
    {
        if ($no_encryption{"$socket"} == 1)
        {
            $encriptadofinal =
              "<response data=\"$status\" btn=\"$boton_numero\"/>\0";
        }
    }
    my $T = syswrite($socket, $encriptadofinal, length($encriptadofinal));
    $encriptadofinal = substr($encriptadofinal, 0, -1);
    log_debug("=> $status\n", 8);
    return $T;
}

sub manager_login_md5
{
    my $challenge = shift;
    my $md5clave  = MD5HexDigest($challenge . $manager_secret);

    $command = "Action: Login\r\n";
    $command .= "Username: $manager_user\r\n";
    $command .= "AuthType: MD5\r\n";
    $command .= "Key: $md5clave\r\n\r\n";
    send_command_to_manager($command);
}

sub send_command_to_manager
{
    my $comando = shift;
    if ($comando eq "")
    {
        return;
    }
    if (defined $p)
    {
        my @lineas = split("\r\n", $comando);
        foreach my $linea (@lineas)
        {
            syswrite($p, "$linea\r\n");
            log_debug("-> $linea", 2);
        }
        log_debug(" ", 2);
        syswrite($p, "\r\n");
    }
}

sub is_number
{
    my $num = shift;
    if (!defined($num)) { return 1; }
    if ($num =~ /[^0-9]/)
    {
        return 0;
    }
    else
    {
        return 1;
    }
}

sub close_all
{
    log_debug("Exiting...", 1);

    foreach my $hd ($O->handles)
    {
        print "Closing $hd\n";
        $O->remove($hd);
        close($hd);
    }

    exit(0);
}

sub inArray
{
    my $val = shift;
    for my $elem (@_)
    {
        if ($val eq $elem)
        {
            return 1;
        }
    }
    return 0;
}

sub encode_base64
{
    my $res = "";
    my $eol = "\n";
    pos($_[0]) = 0;
    while ($_[0] =~ /(.{1,45})/gs)
    {
        $res .= substr(pack("u", $1), 1);
        chop($res);
    }
    $res =~ tr|` -_|AA-Za-z0-9+/|;    # `
    my $padding = (3 - length($_[0]) % 3) % 3;
    $res =~ s/.{$padding}$/"=" x $padding/e if $padding;

    return $res;
}

sub format_clid
{

    # Subroutine to format the caller id number
    # The format string is in the form "(xxx) xxx-xxxx"
    # Every x is counted as a digit, any other text is
    # displayed as is. The digits are replaced from right
    # to left. If there are digits left, they are discarded

    my $numero       = shift;
    my $format       = shift;
    my @chars_number = ();
    my @chars_format = ();
    my @result       = ();
    my $devuelve     = "";

    if (!is_number($numero))
    {
        return $numero;
    }

    @chars_number = split(//, $numero);
    @chars_format = split(//, $format);

    @chars_format = reverse @chars_format;

    foreach (@chars_format)
    {
        if (@chars_number)
        {
            if ($_ eq "x" or $_ eq "X")
            {
                push(@result, pop @chars_number);
            }
            else
            {
                push(@result, $_);
            }
        }
        else
        {
            if ($_ eq "x" or $_ eq "X")
            {
                next;
            }
            else
            {
                push(@result, $_);
                last;
            }
        }
    }

    @result = reverse @result;
    $devuelve = join("", @result);
    return $devuelve;
}

sub generate_random_password
{
    my $passwordsize = shift;
    my @alphanumeric = ('a' .. 'z', 'A' .. 'Z', 0 .. 9);
    my $randpassword = join '', map $alphanumeric[rand @alphanumeric],
      0 .. $passwordsize;

    return $randpassword;
}

sub sends_incorrect
{
    my $socket = shift;
    my $manda  = "0|incorrect|0";
    my $T      = send_status_to_flash($socket, $manda);
}

sub sends_correct
{
    my $socket = shift;
    my $manda  = "0|correct|0";
    my $T      = send_status_to_flash($socket, $manda);
}

sub sends_key
{

    # Generate random key por padding the password
    # and write it to the client
    my $socket = shift;
    my $keylen = int(rand(22));
    $keylen += 15;
    my $randomkey = generate_random_password($keylen);
    my $mandakey  = "$randomkey|key|0";
    my $T         = send_status_to_flash($socket, $mandakey);
    $keys_socket{"$socket"} = $randomkey;
}

sub MD5Digest
{
    my $context = &MD5Init();

    # security feature: uncomment and put your own "magic string"
    # note: MD5test.pl will not work with your magic string, of course
    # my $magicString = '!@#$%^';
    # &MD5Update($context, $magicString, length($magicString));

    # this should be done always
    &MD5Update($context, $_[0], length($_[0]));

    return &MD5Final($context);
}

#
# same as Digest but returns digest in a printable (hex) form
#

sub MD5HexDigest
{
    return unpack("H*", &MD5Digest(@_));
}

#
# MD5 implementation is below
#

# derived from the RSA Data Security, Inc. MD5 Message-Digest Algorithm

# Original context structure
# typedef struct {
#
#       UINT4 state[4];                                   /* state (ABCD) */
#       UINT4 count[2];        /* number of bits, modulo 2^64 (lsb first) */
#       unsigned char buffer[64];                         /* input buffer */
#
# } MD5_CTX;

# Constants for MD5Transform routine.

sub S11 { 7 }
sub S12 { 12 }
sub S13 { 17 }
sub S14 { 22 }
sub S21 { 5 }
sub S22 { 9 }
sub S23 { 14 }
sub S24 { 20 }
sub S31 { 4 }
sub S32 { 11 }
sub S33 { 16 }
sub S34 { 23 }
sub S41 { 6 }
sub S42 { 10 }
sub S43 { 15 }
sub S44 { 21 }

# F, G, H and I are basic MD5 functions.

sub F { my ($x, $y, $z) = @_; ((($x) & ($y)) | ((~$x) & ($z))); }
sub G { my ($x, $y, $z) = @_; ((($x) & ($z)) | (($y) & (~$z))); }
sub H { my ($x, $y, $z) = @_; (($x) ^ ($y) ^ ($z)); }
sub I { my ($x, $y, $z) = @_; (($y) ^ (($x) | (~$z))); }

# ROTATE_LEFT rotates x left n bits.
# Note: "& ~(-1 << $n)" is not in C version
#
sub ROTATE_LEFT
{
    my ($x, $n) = @_;
    ($x << $n) | (($x >> (32 - $n) & ~(-1 << $n)));
}

# FF, GG, HH, and II transformations for rounds 1, 2, 3, and 4.
# Rotation is separate from addition to prevent recomputation.

sub FF
{
    my ($a, $b, $c, $d, $x, $s, $ac) = @_;

    $a += &F($b, $c, $d) + $x + $ac;
    $a = &ROTATE_LEFT($a, $s);
    $a += $b;

    return $a;
}

sub GG
{
    my ($a, $b, $c, $d, $x, $s, $ac) = @_;

    $a += &G($b, $c, $d) + $x + $ac;
    $a = &ROTATE_LEFT($a, $s);
    $a += $b;

    return $a;
}

sub HH
{
    my ($a, $b, $c, $d, $x, $s, $ac) = @_;
    $a += &H($b, $c, $d) + $x + $ac;
    $a = &ROTATE_LEFT($a, $s);
    $a += $b;

    return $a;
}

sub II
{
    my ($a, $b, $c, $d, $x, $s, $ac) = @_;

    $a += &I($b, $c, $d) + $x + $ac;
    $a = &ROTATE_LEFT($a, $s);
    $a += $b;

    return $a;
}

# MD5 initialization. Begins an MD5 operation, writing a new context.

sub MD5Init
{
    my $context = {};

    @{$context->{count}} = 2;
    $context->{count}[0] = $context->{count}[1] = 0;
    $context->{buffer} = '';

    # Load magic initialization constants.

    @{$context->{state}} = 4;
    $context->{state}[0] = 0x67452301;
    $context->{state}[1] = 0xefcdab89;
    $context->{state}[2] = 0x98badcfe;
    $context->{state}[3] = 0x10325476;

    return $context;
}

# MD5 block update operation. Continues an MD5 message-digest
# operation, processing another message block, and updating the context.

sub MD5Update
{
    my ($context, $input, $inputLen) = @_;

    # Compute number of bytes mod 64
    my $index = (($context->{count}[0] >> 3) & 0x3F);

    # Update number of bits
    if (($context->{count}[0] += ($inputLen << 3)) < ($inputLen << 3))
    {
        $context->{count}[1]++;
        $context->{count}[1] += ($inputLen >> 29);
    }

    my $partLen = 64 - $index;

    # Transform as many times as possible.

    my $i;
    if ($inputLen >= $partLen)
    {

        substr($context->{buffer}, $index, $partLen) =
          substr($input, 0, $partLen);

        &MD5Transform(\@{$context->{state}}, $context->{buffer});

        for ($i = $partLen ; $i + 63 < $inputLen ; $i += 64)
        {
            &MD5Transform($context->{state}, substr($input, $i));
        }

        $index = 0;
    }
    else
    {
        $i = 0;
    }

    # Buffer remaining input
    substr($context->{buffer}, $index, $inputLen - $i) =
      substr($input, $i, $inputLen - $i);
}

# MD5 finalization. Ends an MD5 message-digest operation, writing the
#	the message digest and zeroizing the context.

sub MD5Final
{
    my $context = shift;

    # Save number of bits
    my $bits = &Encode(\@{$context->{count}}, 8);

    # Pad out to 56 mod 64.
    my ($index, $padLen);
    $index  = ($context->{count}[0] >> 3) & 0x3f;
    $padLen = ($index < 56) ? (56 - $index) : (120 - $index);

    &MD5Update($context, $PADDING, $padLen);

    # Append length (before padding)
    MD5Update($context, $bits, 8);

    # Store state in digest
    my $digest = &Encode(\@{$context->{state}}, 16);

    # &MD5_memset ($context, 0);

    return $digest;
}

# MD5 basic transformation. Transforms state based on block.

sub MD5Transform
{
    my ($state, $block) = @_;

    my ($a, $b, $c, $d) = @{$state};
    my @x = 16;

    &Decode(\@x, $block, 64);

    # Round 1
    $a = &FF($a, $b, $c, $d, $x[0],  S11, 0xd76aa478);    # 1
    $d = &FF($d, $a, $b, $c, $x[1],  S12, 0xe8c7b756);    # 2
    $c = &FF($c, $d, $a, $b, $x[2],  S13, 0x242070db);    # 3
    $b = &FF($b, $c, $d, $a, $x[3],  S14, 0xc1bdceee);    # 4
    $a = &FF($a, $b, $c, $d, $x[4],  S11, 0xf57c0faf);    # 5
    $d = &FF($d, $a, $b, $c, $x[5],  S12, 0x4787c62a);    # 6
    $c = &FF($c, $d, $a, $b, $x[6],  S13, 0xa8304613);    # 7
    $b = &FF($b, $c, $d, $a, $x[7],  S14, 0xfd469501);    # 8
    $a = &FF($a, $b, $c, $d, $x[8],  S11, 0x698098d8);    # 9
    $d = &FF($d, $a, $b, $c, $x[9],  S12, 0x8b44f7af);    # 10
    $c = &FF($c, $d, $a, $b, $x[10], S13, 0xffff5bb1);    # 11
    $b = &FF($b, $c, $d, $a, $x[11], S14, 0x895cd7be);    # 12
    $a = &FF($a, $b, $c, $d, $x[12], S11, 0x6b901122);    # 13
    $d = &FF($d, $a, $b, $c, $x[13], S12, 0xfd987193);    # 14
    $c = &FF($c, $d, $a, $b, $x[14], S13, 0xa679438e);    # 15
    $b = &FF($b, $c, $d, $a, $x[15], S14, 0x49b40821);    # 16

    # Round 2
    $a = &GG($a, $b, $c, $d, $x[1],  S21, 0xf61e2562);    # 17
    $d = &GG($d, $a, $b, $c, $x[6],  S22, 0xc040b340);    # 18
    $c = &GG($c, $d, $a, $b, $x[11], S23, 0x265e5a51);    # 19
    $b = &GG($b, $c, $d, $a, $x[0],  S24, 0xe9b6c7aa);    # 20
    $a = &GG($a, $b, $c, $d, $x[5],  S21, 0xd62f105d);    # 21
    $d = &GG($d, $a, $b, $c, $x[10], S22, 0x2441453);     # 22
    $c = &GG($c, $d, $a, $b, $x[15], S23, 0xd8a1e681);    # 23
    $b = &GG($b, $c, $d, $a, $x[4],  S24, 0xe7d3fbc8);    # 24
    $a = &GG($a, $b, $c, $d, $x[9],  S21, 0x21e1cde6);    # 25
    $d = &GG($d, $a, $b, $c, $x[14], S22, 0xc33707d6);    # 26
    $c = &GG($c, $d, $a, $b, $x[3],  S23, 0xf4d50d87);    # 27
    $b = &GG($b, $c, $d, $a, $x[8],  S24, 0x455a14ed);    # 28
    $a = &GG($a, $b, $c, $d, $x[13], S21, 0xa9e3e905);    # 29
    $d = &GG($d, $a, $b, $c, $x[2],  S22, 0xfcefa3f8);    # 30
    $c = &GG($c, $d, $a, $b, $x[7],  S23, 0x676f02d9);    # 31
    $b = &GG($b, $c, $d, $a, $x[12], S24, 0x8d2a4c8a);    # 32

    # Round 3
    $a = &HH($a, $b, $c, $d, $x[5],  S31, 0xfffa3942);    # 33
    $d = &HH($d, $a, $b, $c, $x[8],  S32, 0x8771f681);    # 34
    $c = &HH($c, $d, $a, $b, $x[11], S33, 0x6d9d6122);    # 35
    $b = &HH($b, $c, $d, $a, $x[14], S34, 0xfde5380c);    # 36
    $a = &HH($a, $b, $c, $d, $x[1],  S31, 0xa4beea44);    # 37
    $d = &HH($d, $a, $b, $c, $x[4],  S32, 0x4bdecfa9);    # 38
    $c = &HH($c, $d, $a, $b, $x[7],  S33, 0xf6bb4b60);    # 39
    $b = &HH($b, $c, $d, $a, $x[10], S34, 0xbebfbc70);    # 40
    $a = &HH($a, $b, $c, $d, $x[13], S31, 0x289b7ec6);    # 41
    $d = &HH($d, $a, $b, $c, $x[0],  S32, 0xeaa127fa);    # 42
    $c = &HH($c, $d, $a, $b, $x[3],  S33, 0xd4ef3085);    # 43
    $b = &HH($b, $c, $d, $a, $x[6],  S34, 0x4881d05);     # 44
    $a = &HH($a, $b, $c, $d, $x[9],  S31, 0xd9d4d039);    # 45
    $d = &HH($d, $a, $b, $c, $x[12], S32, 0xe6db99e5);    # 46
    $c = &HH($c, $d, $a, $b, $x[15], S33, 0x1fa27cf8);    # 47
    $b = &HH($b, $c, $d, $a, $x[2],  S34, 0xc4ac5665);    # 48

    # Round 4
    $a = &II($a, $b, $c, $d, $x[0],  S41, 0xf4292244);    # 49
    $d = &II($d, $a, $b, $c, $x[7],  S42, 0x432aff97);    # 50
    $c = &II($c, $d, $a, $b, $x[14], S43, 0xab9423a7);    # 51
    $b = &II($b, $c, $d, $a, $x[5],  S44, 0xfc93a039);    # 52
    $a = &II($a, $b, $c, $d, $x[12], S41, 0x655b59c3);    # 53
    $d = &II($d, $a, $b, $c, $x[3],  S42, 0x8f0ccc92);    # 54
    $c = &II($c, $d, $a, $b, $x[10], S43, 0xffeff47d);    # 55
    $b = &II($b, $c, $d, $a, $x[1],  S44, 0x85845dd1);    # 56
    $a = &II($a, $b, $c, $d, $x[8],  S41, 0x6fa87e4f);    # 57
    $d = &II($d, $a, $b, $c, $x[15], S42, 0xfe2ce6e0);    # 58
    $c = &II($c, $d, $a, $b, $x[6],  S43, 0xa3014314);    # 59
    $b = &II($b, $c, $d, $a, $x[13], S44, 0x4e0811a1);    # 60
    $a = &II($a, $b, $c, $d, $x[4],  S41, 0xf7537e82);    # 61
    $d = &II($d, $a, $b, $c, $x[11], S42, 0xbd3af235);    # 62
    $c = &II($c, $d, $a, $b, $x[2],  S43, 0x2ad7d2bb);    # 63
    $b = &II($b, $c, $d, $a, $x[9],  S44, 0xeb86d391);    # 64

    $state->[0] += $a;
    $state->[1] += $b;
    $state->[2] += $c;
    $state->[3] += $d;

    # Zeroize sensitive information.
    # MD5_memset ((POINTER)x, 0, sizeof (x));
}

# Encodes input (UINT4) into output (unsigned char). Assumes len is
# a multiple of 4.

sub Encode
{
    my ($input, $len) = @_;

    my $output = '';
    my ($i, $j);
    for ($i = 0, $j = 0 ; $j < $len ; $i++, $j += 4)
    {
        substr($output, $j + 0, 1) = chr($input->[$i] & 0xff);
        substr($output, $j + 1, 1) = chr(($input->[$i] >> 8) & 0xff);
        substr($output, $j + 2, 1) = chr(($input->[$i] >> 16) & 0xff);
        substr($output, $j + 3, 1) = chr(($input->[$i] >> 24) & 0xff);
    }

    return $output;
}

# Decodes input (unsigned char) into output (UINT4). Assumes len is
# a multiple of 4.

sub Decode
{
    my ($output, $input, $len) = @_;

    my ($i, $j);

    for ($i = 0, $j = 0 ; $j < $len ; $i++, $j += 4)
    {
        $output->[$i] =
          (ord(substr($input, $j + 0, 1))) |
          (ord(substr($input, $j + 1, 1)) << 8) |
          (ord(substr($input, $j + 2, 1)) << 16) |
          (ord(substr($input, $j + 3, 1)) << 24);
    }
}
#########################################################################
# TEA Encryption algorithm
#
#########################################################################
#        This Perl module is Copyright (c) 2000, Peter J Billam         #
#               c/o P J B Computing, www.pjb.com.au                     #
#########################################################################

sub binary2ascii
{
    return &str2ascii(&binary2str(@_));
}

sub ascii2binary
{
    return &str2binary(&ascii2str($_[$[]));
}

sub str2binary
{
    my @str      = split //, $_[$[];
    my @intarray = ();
    my $ii       = $[;
    while (1)
    {
        last unless @str;
        $intarray[$ii] = (0xFF & ord shift @str) << 24;
        last unless @str;
        $intarray[$ii] |= (0xFF & ord shift @str) << 16;
        last unless @str;
        $intarray[$ii] |= (0xFF & ord shift @str) << 8;
        last unless @str;
        $intarray[$ii] |= 0xFF & ord shift @str;
        $ii++;
    }
    return @intarray;
}

sub binary2str
{
    my @str = ();
    foreach my $i (@_)
    {
        push @str, chr(0xFF & ($i >> 24)), chr(0xFF & ($i >> 16)),
          chr(0xFF & ($i >> 8)), chr(0xFF & $i);
    }
    return join '', @str;
}

sub ascii2str
{
    my $a = $_[$[];    # converts pseudo-base64 to string of bytes
    $a =~ tr#A-Za-z0-9+_##cd;
    my $ia = $[ - 1;
    my $la = length $a;    # BUG not length, final!
    my $ib = $[;
    my @b  = ();
    my $carry;
    while (1)
    {                      # reads 4 ascii chars and produces 3 bytes
        $ia++;
        last if ($ia >= $la);
        $b[$ib] = $a2b{substr $a, $ia + $[, 1} << 2;
        $ia++;
        last if ($ia >= $la);
        $carry = $a2b{substr $a, $ia + $[, 1};
        $b[$ib] |= ($carry >> 4);
        $ib++;

        # if low 4 bits of $carry are 0 and its the last char, then break
        $carry = 0xF & $carry;
        last if ($carry == 0 && $ia == ($la - 1));
        $b[$ib] = $carry << 4;
        $ia++;
        last if ($ia >= $la);
        $carry = $a2b{substr $a, $ia + $[, 1};
        $b[$ib] |= ($carry >> 2);
        $ib++;

        # if low 2 bits of $carry are 0 and its the last char, then break
        $carry = 03 & $carry;
        last if ($carry == 0 && $ia == ($la - 1));
        $b[$ib] = $carry << 6;
        $ia++;
        last if ($ia >= $la);
        $b[$ib] |= $a2b{substr $a, $ia + $[, 1};
        $ib++;
    }
    return pack 'c*', @b;
}

sub str2ascii
{
    my $b  = $_[$[];      # converts string of bytes to pseudo-base64
    my $ib = $[;
    my $lb = length $b;
    my @s  = ();
    my $b1;
    my $b2;
    my $b3;
    my $carry;

    while (1)
    {                     # reads 3 bytes and produces 4 ascii chars
        if ($ib >= $lb) { last; }
        $b1 = ord substr $b, $ib + $[, 1;
        $ib++;
        push @s, $b2a{$b1 >> 2};
        $carry = 03 & $b1;
        if ($ib >= $lb) { push @s, $b2a{$carry << 4}; last; }
        $b2 = ord substr $b, $ib + $[, 1;
        $ib++;
        push @s, $b2a{($b2 >> 4) | ($carry << 4)};
        $carry = 0xF & $b2;
        if ($ib >= $lb) { push @s, $b2a{$carry << 2}; last; }
        $b3 = ord substr $b, $ib + $[, 1;
        $ib++;
        push @s, $b2a{($b3 >> 6) | ($carry << 2)}, $b2a{077 & $b3};
        if (!$ENV{REMOTE_ADDR} && (($ib % 36) == 0)) { push @s, "\n"; }
    }
    return join('', @s);
}

sub asciidigest
{    # returns 22-char ascii signature
    return &binary2ascii(&binarydigest($_[$[]));
}

sub binarydigest
{
    my $str = $_[$[];    # returns 4 32-bit-int binary signature
         # warning: mode of use invented by Peter Billam 1998, needs checking !
    return '' unless $str;

    # add 1 char ('0'..'15') at front to specify no of pad chars at end ...
    my $npads = 15 - ((length $str) % 16);
    $str = chr($npads) . $str;
    if ($npads) { $str .= "\0" x $npads; }
    my @str = &str2binary($str);
    my @key = (0x61626364, 0x62636465, 0x63646566, 0x64656667);

    my ($cswap, $v0, $v1, $v2, $v3);
    my $c0 = 0x61626364;
    my $c1 = 0x62636465;    # CBC Initial Value. Retain !
    my $c2 = 0x61626364;
    my $c3 = 0x62636465;    # likewise (abcdbcde).
    while (@str)
    {

        # shift 2 blocks off front of str ...
        $v0 = shift @str;
        $v1 = shift @str;
        $v2 = shift @str;
        $v3 = shift @str;

        # cipher them XOR'd with previous stage ...
        ($c0, $c1) = &tea_code($v0 ^ $c0, $v1 ^ $c1, @key);
        ($c2, $c3) = &tea_code($v2 ^ $c2, $v3 ^ $c3, @key);

        # mix up the two cipher blocks with a 4-byte left rotation ...
        $cswap = $c0;
        $c0    = $c1;
        $c1    = $c2;
        $c2    = $c3;
        $c3    = $cswap;
    }
    return ($c0, $c1, $c2, $c3);
}

sub TEAencrypt
{
    my ($str, $key) = @_;    # encodes with CBC (Cipher Block Chaining)
    use integer;
    return '' unless $str;
    return '' unless $key;
    @key = &binarydigest($key);

    # add 1 char ('0'..'7') at front to specify no of pad chars at end ...
    my $npads = 7 - ((length $str) % 8);
    $str = chr($npads | (0xF8 & &rand_byte)) . $str;
    if ($npads)
    {
        my $padding = pack 'CCCCCCC', &rand_byte, &rand_byte, &rand_byte,
          &rand_byte, &rand_byte, &rand_byte, &rand_byte;
        $str = $str . substr($padding, $[, $npads);
    }
    my @pblocks = &str2binary($str);
    my $v0;
    my $v1;
    my $c0 = 0x61626364;
    my $c1 = 0x62636465;    # CBC Initial Value. Retain !
    my @cblocks;
    while (1)
    {
        last unless @pblocks;
        $v0 = shift @pblocks;
        $v1 = shift @pblocks;
        ($c0, $c1) = &tea_code($v0 ^ $c0, $v1 ^ $c1, @key);
        push @cblocks, $c0, $c1;
    }
    my $btmp = &binary2str(@cblocks);
    return &str2ascii(&binary2str(@cblocks));
}

sub TEAdecrypt
{
    my ($acstr, $key) = @_;    # decodes with CBC
    use integer;
    return '' unless $acstr;
    return '' unless $key;
    @key = &binarydigest($key);
    my $v0;
    my $v1;
    my $c0;
    my $c1;
    my @pblocks = ();
    my $de0;
    my $de1;
    my $lastc0  = 0x61626364;
    my $lastc1  = 0x62636465;                        # CBC Init Val. Retain!
    my @cblocks = &str2binary(&ascii2str($acstr));

    while (1)
    {
        last unless @cblocks;
        $c0 = shift @cblocks;
        $c1 = shift @cblocks;
        ($de0, $de1) = &tea_decode($c0, $c1, @key);
        $v0 = $lastc0 ^ $de0;
        $v1 = $lastc1 ^ $de1;
        push @pblocks, $v0, $v1;
        $lastc0 = $c0;
        $lastc1 = $c1;
    }
    my $str = &binary2str(@pblocks);

    # remove no of pad chars at end specified by 1 char ('0'..'7') at front
    my $npads = 0x7 & ord $str;
    substr($str, $[, 1) = '';
    if ($npads) { substr($str, 0 - $npads) = ''; }
    return $str;
}

sub triple_encrypt
{
    my ($plaintext, $long_key) = @_;    # not yet ...
}

sub triple_decrypt
{
    my ($cyphertext, $long_key) = @_;    # not yet ...
}

sub tea_code
{
    my ($v0, $v1, $k0, $k1, $k2, $k3) = @_;

    # TEA. 64-bit cleartext block in $v0,$v1. 128-bit key in $k0..$k3.
    # &prn("tea_code: v0=$v0 v1=$v1");
    use integer;
    my $sum = 0;
    my $n   = 32;
    while ($n-- > 0)
    {
        $sum += 0x9e3779b9;    # TEA magic number delta
        $v0  +=
          (($v1 << 4) + $k0) ^ ($v1 + $sum) ^ ((0x07FFFFFF & ($v1 >> 5)) + $k1);
        $v1 +=
          (($v0 << 4) + $k2) ^ ($v0 + $sum) ^ ((0x07FFFFFF & ($v0 >> 5)) + $k3);
    }
    return ($v0, $v1);
}

sub tea_decode
{
    my ($v0, $v1, $k0, $k1, $k2, $k3) = @_;

    # TEA. 64-bit cyphertext block in $v0,$v1. 128-bit key in $k0..$k3.
    use integer;
    my $sum = 0;
    my $n   = 32;
    $sum = 0x9e3779b9 << 5;    # TEA magic number delta
    while ($n-- > 0)
    {
        $v1 -=
          (($v0 << 4) + $k2) ^ ($v0 + $sum) ^ ((0x07FFFFFF & ($v0 >> 5)) + $k3);
        $v0 -=
          (($v1 << 4) + $k0) ^ ($v1 + $sum) ^ ((0x07FFFFFF & ($v1 >> 5)) + $k1);
        $sum -= 0x9e3779b9;
    }
    return ($v0, $v1);
}

sub rand_byte
{
    if (!$rand_byte_already_called)
    {
        srand(time() ^ ($$ + ($$ << 15)))
          ;    # could do better, but its only padding
        $rand_byte_already_called = 1;
    }
    int(rand 256);
}

#
# End TEA
