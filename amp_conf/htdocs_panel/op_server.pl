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
use Fcntl;
use POSIX qw(setsid EWOULDBLOCK);

my $FOP_VERSION                = "021.001";
my %datos                      = ();
my %sesbot                     = ();
my %linkbot                    = ();
my %cache_hit                  = ();
my %estadoboton                = ();
my %statusboton                = ();
my %botonled                   = ();
my %botonalpha                 = ();
my %botonregistrado            = ();
my %botontext                  = ();
my %boton_ip                   = ();
my %botonlabel                 = ();
my %botontimer                 = ();
my %botonpark                  = ();
my %botonmeetme                = ();
my %botonclid                  = ();
my %botonqueue                 = ();
my %botonqueuemember           = ();
my %botonvoicemail             = ();
my %botonvoicemailcount        = ();
my %botonlinked                = ();
my %parked                     = ();
my %laststatus                 = ();
my %autenticado                = ();
my %auto_conference            = ();
my %buttons                    = ();
my %button_server              = ();
my %buttons_reverse            = ();
my %textos                     = ();
my %iconos                     = ();
my %remote_callerid            = ();
my %remote_callerid_name       = ();
my %extension_transfer         = ();
my %extension_transfer_reverse = ();
my %flash_contexto             = ();
my %saved_clidnum              = ();
my %saved_clidname             = ();
my %keys_socket                = ();
my %manager_socket             = ();
my %start_muted                = ();
my %timeouts                   = ();
my %no_rectangle               = ();
my %astdbcommands              = ();
my %client_queue               = ();
my %manager_queue              = ();
my %client_queue_nocrypt       = ();
my %ip_addy                    = ();
my $config                     = {};
my $global_verbose             = 1;
my $counter_servers            = -1;
my %bloque_completo;
my $bloque_final;
my $todo;
my @bloque;
my @respuestas;
my @all_flash_files;
my @masrespuestas;
my @fake_bloque;
my @flash_clients;
my @status_active;
my %mailbox;
my %instancias;
my %agents;
my %channel_to_agent;
my %reverse_agents;
my %agents_name;
my @p;
my $m;
my $O;
my @S;
my @key;
my @manager_host      = ();
my @manager_user      = ();
my @manager_secret    = ();
my @manager_conectado = ();
my $web_hostname;
my $listen_port;
my $security_code;
my $flash_dir;
my $restrict_channel = "";
my $socketpolicy;
my $poll_interval;
my $poll_voicemail;
my $kill_zombies;
my $ren_agentlogin;
my $ren_cbacklogin;
my $ren_agentname;
my $agent_status;
my $ren_queuemember;
my $ren_wildcard;
my $clid_privacy;
my $show_ip;
my $enable_restart;
my $change_led;
my $cdial_nosecure;
my $barge_muted;
my $debug       = -1;
my $debug_cache = "";
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
my $reverse_transfer;
my %shapes;
my %legends;
my %no_encryption = ();
my %total_shapes;
my %total_legends;
my %lastposition;
my @btninclude = ();
my $command    = "";
my $daemonized = 0;

my $PADDING = join(
                   '',
                   map(chr,
                       (
                        0x80, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0,    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0,    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        0,    0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
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
        $daemonized = 1;
    }
}

sub read_server_config()
{
    my $context = "";
    $counter_servers = -1;

    $/ = "\n";

    open(CONFIG, "<$directorio/op_server.cfg")
      or die("Could not open op_server.cfg. Aborting...");

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

                if ($variable_name eq "manager_host")
                {
                    $counter_servers++;
                    $manager_host[$counter_servers] = $value;
                }

                if ($variable_name eq "manager_user")
                {
                    $manager_user[$counter_servers] = $value;
                }

                if ($variable_name eq "manager_secret")
                {
                    $manager_secret[$counter_servers] = $value;
                }

            }
        }
    }
    close(CONFIG);

    $web_hostname     = $config->{"GENERAL"}{"web_hostname"};
    $listen_port      = $config->{"GENERAL"}{"listen_port"};
    $security_code    = $config->{"GENERAL"}{"security_code"};
    $flash_dir        = $config->{"GENERAL"}{"flash_dir"};
    $poll_interval    = $config->{"GENERAL"}{"poll_interval"};
    $poll_voicemail   = $config->{"GENERAL"}{"poll_voicemail"};
    $kill_zombies     = $config->{"GENERAL"}{"kill_zombies"};
    $reverse_transfer = $config->{"GENERAL"}{"reverse_transfer"};
    $debug            = $config->{"GENERAL"}{"debug"};
    $auth_md5         = $config->{"GENERAL"}{"auth_md5"};
    $ren_agentlogin   = $config->{"GENERAL"}{"rename_label_agentlogin"};
    $ren_cbacklogin   = $config->{"GENERAL"}{"rename_label_callbacklogin"};
    $ren_wildcard     = $config->{"GENERAL"}{"rename_label_wildcard"};
    $ren_agentname    = $config->{"GENERAL"}{"rename_to_agent_name"};
    $agent_status     = $config->{"GENERAL"}{"agent_status"};
    $ren_queuemember  = $config->{"GENERAL"}{"rename_queue_member"};
    $change_led       = $config->{"GENERAL"}{"change_led_agent"};
    $cdial_nosecure   = $config->{"GENERAL"}{"clicktodial_insecure"};
    $barge_muted      = $config->{"GENERAL"}{"barge_muted"};
    $clid_privacy     = $config->{"GENERAL"}{"clid_privacy"};
    $show_ip          = $config->{"GENERAL"}{"show_ip"};
    $enable_restart   = $config->{"GENERAL"}{"enable_restart"};

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
                    if (!defined($last_room))
                    {
                        $last_room = $first_room;
                    }
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
    push @all_flash_files, $flash_file;

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
    if (!defined $clid_privacy)
    {
        $clid_privacy = 0;
    }
    if (!defined $show_ip)
    {
        $show_ip = 0;
    }
    if (!defined $ren_wildcard)
    {
        $ren_wildcard = 1;
    }
    if (!defined $reverse_transfer)
    {
        $reverse_transfer = 0;
    }
    if (!defined $barge_muted)
    {
        $barge_muted = 0;
    }
    if (!defined $enable_restart)
    {
        $enable_restart = 0;
    }
    if (!defined $cdial_nosecure)
    {
        $cdial_nosecure = 0;
    }
    if (!defined $agent_status)
    {
        $agent_status = 1;
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
    if (!defined $debug)
    {
        $debug = 0;
    }
    else
    {
        if ($daemonized == 1)
        {
            $debug = 0;
        }
    }
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
            log_debug("** $filename already included", 16);
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
        log_debug("** $archivo not readable... skipping", 16);
    }
}

sub read_astdb_config()
{
    $/ = "\n";
    if (-e "$directorio/op_astdb.cfg")
    {
        open(ASTDB, "<$directorio/op_astdb.cfg")
          or die("Could not open op_astdb.cfg. Aborting...");
        my $contador = 0;
        my $key      = "";
        while (<ASTDB>)
        {
            chomp;
            $_ =~ s/^\s+//g;
            $_ =~ s/([^;]*)[;](.*)/$1/g;
            $_ =~ s/\s+$//g;

            if (/^#/ || /^;/ || /^$/)
            {
                next;
            }    # Ignores comments and empty lines

            if (/^\Q[\E/)
            {
                s/\[(.*)\]/$1/g;
                $key = $_;
            }
            else
            {
                push @{$astdbcommands{$key}}, $_;
            }
        }
    }

    $/ = "\0";
}

sub read_buttons_config()
{
    my @btn_cfg  = ();
    my $contador = -1;
    my @uniq;
    my $no_counter = 0;
    my @contextos  = ();

    $/ = "\n";

    my %seen = ();
    foreach my $item (@btninclude)
    {
        push(@uniq, $item) unless $seen{$item}++;
    }

    foreach my $archivo (@uniq)
    {
        open(CONFIG, "< $directorio/$archivo")
          or die("Could not open $directorio/$archivo. Aborting...");

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
                if (!/^Local/i && !/^_/)
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
                if (   $key ne "label"
                    && $key ne "font_family"
                    && $key ne "text"
                    && $key ne "mailbox"
                    && $key ne "voicemail_context")
                {
                    $val =~ s/^\s+//g;
                    $val =~ s/(.*)\s+/$1/g;
                }
                $btn_cfg[$contador]{"$key"} = $val;
                if ($key eq "panel_context")
                {
                    push @contextos, $val;
                }
            }
        }

        close(CONFIG);
    }

    my %seen2 = ();
    my @uniq2 = grep { !$seen2{$_}++ } @contextos;
    @contextos = @uniq2;
    @uniq2     = grep { !/\*/ } @contextos;
    @contextos = @uniq2;
    push @contextos, "DEFAULT";

    # Pass to replicate panel_context=* configuration
    my @copy_cfg = ();
    @copy_cfg = @btn_cfg;
    foreach (@copy_cfg)
    {
        my %tmphash = %$_;
        if (defined($tmphash{"panel_context"}))
        {
            if ($tmphash{"panel_context"} eq "*")
            {
                foreach my $contextoahora (@contextos)
                {
                    $contador++;
                    while (my ($key, $val) = each(%tmphash))
                    {
                        if ($key eq "panel_context")
                        {
                            $val = $contextoahora;
                        }
                        $btn_cfg[$contador]{"$key"} = $val;
                    }
                }
            }
        }
    }

    # We finished reading the file, now we populate our
    # structures with the relevant data
    my %rectangles_counter;
    my %legends_counter;

    foreach (@btn_cfg)
    {
        my @positions = ();
        my %tmphash   = %$_;

        if (defined($tmphash{"panel_context"}))
        {
            if ($tmphash{"panel_context"} eq "*")
            {

                # We skip the * panel_context because we already
                # expand them to every context possible before
                next;
            }
        }

        if ($tmphash{"channel"} eq "LEGEND")
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

            if (!defined($tmphash{"text"}))
            {
                $tmphash{"text"} = "LEGEND";
            }
            if (!defined($tmphash{"x"}))
            {
                $tmphash{"x"} = 1;
            }
            if (!defined($tmphash{"y"}))
            {
                $tmphash{"y"} = 1;
            }
            if (!defined($tmphash{"font_size"}))
            {
                $tmphash{"font_size"} = 16;
            }
            if (!defined($tmphash{"use_embed_fonts"}))
            {
                $tmphash{"use_embed_fonts"} = 1;
            }
            if (!defined($tmphash{"font_family"}))
            {
                $tmphash{"font_family"} = "Arial";
            }
            $tmphash{"text"} = encode_base64($tmphash{"text"});
            $legends_counter{$conttemp}++;
            if ($legends_counter{$conttemp} > 1)
            {
                $legends{$conttemp} .= "&";
            }
            $total_legends{$conttemp}++;
            $legends{$conttemp} .= "legend_$legends_counter{$conttemp}=" . $tmphash{"x"} . ",";
            $legends{$conttemp} .= $tmphash{"y"} . ",";
            $legends{$conttemp} .= $tmphash{"text"} . ",";
            $legends{$conttemp} .= $tmphash{"font_size"} . ",";
            $legends{$conttemp} .= $tmphash{"font_family"} . ",";
            $legends{$conttemp} .= $tmphash{"use_embed_fonts"};
            next;
        }

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

            $rectangles_counter{$conttemp}++;
            if ($rectangles_counter{$conttemp} > 1)
            {
                $shapes{$conttemp} .= "&";
            }
            $total_shapes{$conttemp}++;
            $shapes{$conttemp} .= "rect_$rectangles_counter{$conttemp}=" . $tmphash{"x"} . ",";
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
            log_debug("** Ignored button $tmphash{'channel'}, position?", 16);
            next;
        }

        if (!defined($tmphash{"server"}))
        {
            $tmphash{"server"} = 0;
        }
        else
        {
            $tmphash{"server"} = $tmphash{"server"} - 1;
        }

        if (!defined($tmphash{"label"}))
        {
            $tmphash{"label"} = $tmphash{"channel"};
        }

        if (!defined($tmphash{"icon"}))
        {
            $tmphash{"icon"} = "0";
        }

        my $canal_key = $tmphash{"channel"};

        if ($canal_key =~ m/^PARK\d/)
        {

            # Change the PARKXXX tu PARK/XXX
            $canal_key =~ s/PARK(.*)/PARK\/$1/g;
        }

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

        if (   ($tmphash{"position"} !~ /,/)
            && ($tmphash{"position"} !~ /-/)
            && (($canal_key =~ /\*/) || ($canal_key =~ /^_/)))
        {

            # If it's a wildcard button with just one position
            # we fake the same position number to populate
            # the array and make the button work anyways.
            my $pos = $tmphash{"position"};
            $pos =~ s/(\d+),(\d+)/$1/g;
            $tmphash{"position"} = "$pos,$pos";
            $no_counter = 1;
        }

        if ($tmphash{"position"} =~ /[,-]/)
        {

            my $canalidx = $tmphash{'server'} . "^" . $tmphash{'channel'};
            if (defined($tmphash{"panel_context"})
                && $tmphash{"panel_context"} ne "")
            {
                $canalidx .= "&" . $tmphash{"panel_context"};
            }

            $instancias{"$canalidx"}{""} = 0;

            my @ranges = split(/,/, $tmphash{"position"});
            foreach my $valu (@ranges)
            {
                if ($valu !~ m/-/)
                {
                    push @positions, $valu;
                }
                else
                {
                    my @range2 = split(/-/, $valu);
                    my $menor = $range2[0] < $range2[1] ? $range2[0] : $range2[1];
                    my $mayor = $range2[0] > $range2[1] ? $range2[0] : $range2[1];
                    my @newrange = $menor .. $mayor;
                    foreach my $valevale (@newrange)
                    {
                        push @positions, $valevale;
                    }
                }
            }

            #@positions = split(/,/, $tmphash{"position"});
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

                $buttons{"$tmphash{'server'}^$chan_trunk"} = $pos;
                $textos{"$indice_contexto"}                = $tmphash{"label"};
                if ($no_counter == 0)
                {
                    $textos{"$indice_contexto"} .= " " . $count;
                }
                $iconos{"$indice_contexto"} = $tmphash{"icon"};
                $button_server{"$pos"}      = $tmphash{"server"};

                # Saves last position for the button@context
                $lastposition{$tmphash{"panel_context"}} = $pos;
                log_debug("** " . $tmphash{"server"} . "^$chan_trunk in position " . $pos, 16);
            }
        }
        else
        {
            my $lastpos = 0;
            $lastpos = $lastposition{$tmphash{"panel_context"}}
              if defined($lastposition{$tmphash{"panel_context"}});
            if ($tmphash{"position"} eq "n")
            {
                if (is_number($lastpos))
                {
                    $lastpos++;
                    $lastposition{$tmphash{"panel_context"}} = $lastpos;
                }
            }
            else
            {
                $lastpos = $tmphash{"position"};
                $lastposition{$tmphash{"panel_context"}} = $lastpos;
            }

            log_debug("** " . $tmphash{"channel"} . " in position " . $lastpos, 16);

            if ($tmphash{"panel_context"} ne "")
            {

                $buttons{"$tmphash{'server'}^$canal_key"} = $lastpos . "\@" . $tmphash{'panel_context'};

                $textos{"$lastpos\@$tmphash{'panel_context'}"}            = $tmphash{"label"};
                $iconos{"$lastpos\@$tmphash{'panel_context'}"}            = $tmphash{"icon"};
                $button_server{$buttons{"$tmphash{'server'}^$canal_key"}} = $tmphash{"server"};
            }
            else
            {
                if ($canal_key =~ /(.*)\*$/)
                {
                    $canal_key .= "=1";
                }

                $buttons{"$tmphash{'server'}^$canal_key"}                 = $lastpos;
                $textos{$lastpos}                                         = $tmphash{"label"};
                $iconos{$lastpos}                                         = $tmphash{"icon"};
                $button_server{$buttons{"$tmphash{'server'}^$canal_key"}} = $tmphash{"server"};
            }
        }

        if (defined($tmphash{"no_rectangle"}))
        {
            my $count = @positions;
            if ($count == 0)
            {
                push @positions, $lastposition{$tmphash{'panel_context'}};
            }

            if ($tmphash{"no_rectangle"} eq "true")
            {
                my $pcont = $tmphash{"panel_context"};
                if ($pcont eq "") { $pcont = "GENERAL"; }
                foreach my $pos (@positions)
                {
                    $pos =~ s/\@$pcont//g;
                    $no_rectangle{$pcont}{$pos} = 1;
                }
            }
        }

        if (defined($tmphash{"extension"}))
        {
            if (defined($tmphash{"context"}))
            {
                $extension_transfer{"$tmphash{'server'}^$canal_key"} =
                  $tmphash{"extension"} . "@" . $tmphash{"context"};
            }
            else
            {
                $extension_transfer{"$tmphash{'server'}^$canal_key"} = $tmphash{"extension"};
            }
            if (defined($tmphash{"voicemail_context"}))
            {
                $mailbox{"$tmphash{'server'}^$canal_key"} = $tmphash{"extension"} . "@" . $tmphash{"voicemail_context"};
            }
        }
        if (defined($tmphash{"mailbox"}))
        {
            $mailbox{"$tmphash{'server'}^$canal_key"} = $tmphash{"mailbox"};
        }
        $/ = "\0";
    }
    %extension_transfer_reverse = reverse %extension_transfer;
    %buttons_reverse            = reverse %buttons;
}

sub genera_config
{

    # This sub generates the file variables.txt that is read by the
    # swf movie on load, with info about buttons, layout, etc.

    $/ = "\n";
    my %style_variables;
    my @contextos      = ();
    my @uniq           = ();
    my $contextoactual = "";

    open(STYLE, "<op_style.cfg")
      or die("Could not open op_style.cfg for reading");
    while (<STYLE>)
    {
        chop($_);
        $_ =~ s/^\s+//g;
        $_ =~ s/([^;]*)[;](.*)/$1/g;
        $_ =~ s/\s+$//g;
        next unless $_ ne "";

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
            if ($partes[1] ne "*")
            {
                push(@contextos, $partes[1]);
            }
        }
    }

    # Writes default context variables.txt
    open(VARIABLES, ">$flash_file")
      or die("Could not write configuration data $flash_file.\nCheck your file permissions\n");
    print VARIABLES "server=$web_hostname&port=$listen_port&restart=$enable_restart";

    if ($config->{"GENERAL"}{"security_code"} eq "")
    {
        print VARIABLES "&nosecurity=1";
    }

    if (defined($config->{"GENERAL"}{"transfer_timeout"}))
    {
        my @partes = split(/\|/, $config->{"GENERAL"}{"transfer_timeout"});
        my $cuantos = @partes;
        print VARIABLES "&totaltimes=$cuantos";
        my $contador = 1;
        foreach (@partes)
        {
            print VARIABLES "&timeout_$contador=$_";
            $contador++;
        }
    }
    else
    {
        print VARIABLES "&totaltimes=0";
    }

    if ($no_rectangle{"GENERAL"})
    {
        my $pos_no_dibujar = "";
        while (my ($key, $val) = each(%{$no_rectangle{'GENERAL'}}))
        {
            $pos_no_dibujar .= "$key,";
        }
        $pos_no_dibujar = substr($pos_no_dibujar, 0, -1);
        print VARIABLES "&nodraw=$pos_no_dibujar";
    }

    while (my ($key, $val) = each(%shapes))
    {
        if ($key eq "")    # DEFAULT PANEL CONTEXT
        {
            print VARIABLES "&$val";
        }
    }
    while (my ($key, $val) = each(%legends))
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
    if (!defined($total_legends{""}))
    {
        $total_legends{""} = 0;
    }
    print VARIABLES "&total_legends=" . $total_legends{""};
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
        push @all_flash_files, $flash_context_file;
        open(VARIABLES, ">$flash_context_file")
          or die("Could not write configuration data $flash_context_file.\nCheck your file permissions\n");
        print VARIABLES "server=$host_web&port=$listen_port&restart=$enable_restart";

        if (defined($config->{$_}{"security_code"}))
        {
            if ($config->{"$_"}{"security_code"} eq "")
            {
                print VARIABLES "&nosecurity=1";
            }
        }

        if (defined($config->{"$_"}{"transfer_timeout"}))
        {
            my @partes = split(/\|/, $config->{"$_"}{"transfer_timeout"});
            my $cuantos = @partes;
            print VARIABLES "&totaltimes=$cuantos";
            my $contador = 1;
            foreach (@partes)
            {
                print VARIABLES "&timeout_$contador=$_";
                $contador++;
            }
        }
        else
        {
            print VARIABLES "&totaltimes=0";
        }

        if ($no_rectangle{$_})
        {
            my $pos_no_dibujar = "";
            while (my ($key, $val) = each(%{$no_rectangle{$_}}))
            {
                $pos_no_dibujar .= "$key,";
            }
            $pos_no_dibujar = substr($pos_no_dibujar, 0, -1);
            print VARIABLES "&nodraw=$pos_no_dibujar";
        }

        while (my ($key, $val) = each(%shapes))
        {
            if ($key eq $_)    # OTHER CONTEXT
            {
                print VARIABLES "&$val";
            }
        }
        while (my ($key, $val) = each(%legends))
        {
            if ($key eq $_)    # OTHER CONTEXT
            {
                print VARIABLES "&$val";
            }
        }
        while (my ($key, $val) = each(%textos))
        {
            $val =~ s/\"(.*)\"/$1/g;
            my $contextoboton = $key;
            $contextoboton =~ s/(.*)\@(.*)/$2/g;
            $contextoboton =~ tr/a-z/A-Z/;
            if ($contextoboton eq $_)
            {
                $key =~ s/(\d+)\@.+/$1/g;
                print VARIABLES "&texto$key=$val";
            }
        }
        while (my ($key, $val) = each(%iconos))
        {
            $val =~ s/\"(.*)\"/$1/g;
            my $contextoboton = $key;
            $contextoboton =~ s/(.*)\@(.*)/$2/g;
            $contextoboton =~ tr/a-z/A-Z/;
            if ($contextoboton eq $_)
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
        if (!defined($total_legends{$_}))
        {
            $total_legends{$_} = 0;
        }
        print VARIABLES "&total_legends=" . $total_legends{$_};
        close(VARIABLES);
    }
    $/ = "\0";
}

sub dump_internal_hashes_to_stdout
{

    &print_botones(1);

    &print_instancias(1);

    if (keys(%datos))
    {
        &print_datos(1);
    }
    else
    {
        print "No data blocks in memory\n";
    }

    if (keys(%sesbot))
    {
        &print_sesbot(1);
    }
    else
    {
        print "No data sesiones botones\n";
    }

    if (keys(%linkbot))
    {
        &print_linkbot();
    }

    &print_cachehit();

    print "\n";
    while (my ($key, $val) = each(%timeouts))
    {
        print "Timer($key)=$val\n";
    }
    print "\n";

    &print_status();

    &print_clients();

    &print_cola_write();
}

sub generate_configs_onhup
{
    %buttons            = ();
    %textos             = ();
    %iconos             = ();
    %extension_transfer = ();
    %shapes             = ();
    %legends            = ();
    %total_shapes       = ();
    %total_legends      = ();
    &read_buttons_config();
    &read_server_config();
    &read_astdb_config();
    &genera_config();
    &send_initial_status();
}

sub get_next_trunk_button
{
    my $canalid        = shift;
    my $contexto       = shift;
    my $server         = shift;
    my $canalsesion    = shift;
    my $canal_tipo_fop = "";
    my $canal;
    my $sesion;
    my $return = "";
    my @uniq;
    my $trunk_pos;
    my $debugh = "** GET_NEXT_TRUNK";

    # This routine mantains and returns the position of each channel inside
    # a trunk button.

    log_debug("$debugh START SUB $canalid $contexto $server $canalsesion", 16);

    if ($canalid !~ /\^/)
    {
        $canal_tipo_fop = $server . "^" . $canalid;
    }
    else
    {
        $canal_tipo_fop = $canalid;
        $canalid =~ s/(.*)\^(.*)/$2/g;
    }

    if ($canal_tipo_fop =~ /\QCAPI[\E/)
    {
        $canal_tipo_fop =~ tr/a-z/A-Z/;
        $canalid        =~ tr/a-z/A-Z/;
    }
    $canal_tipo_fop =~ s/(.*)<(.*)>/$1/g;
    $canal_tipo_fop =~ s/\s+//g;
    $canal_tipo_fop =~ s/(.*)[-\/](.*)/$1/g;
    $sesion = $2;
    $sesion         =~ s/(.*)\&(.*)/$1/g;                              # removes context if it has any
    $canal_tipo_fop =~ s/(\d+\^IAX2\/)(.*)@?(.*)?/$1\U$2\E/g;
    $canal_tipo_fop =~ s/(\d+\^IAX2)\[(.*)@?(.*)?\]?/$1\[\U$2\E\]/g;
    log_debug("$debugh canal_tipo_fop $canal_tipo_fop", 64);

    if ($canalid =~ /^_.*/)
    {
        $canal_tipo_fop = $canalid;
        $canal_tipo_fop =~ /([^=].*)(=\d+)(.*)/;
        $canal_tipo_fop = $1;
        if (defined($3))
        {
            $contexto = $3;
        }
        log_debug("$debugh contexto $contexto", 32);
        my ($nada, $ses) = separate_session_from_channel($canalsesion);
        $sesion = $ses;
    }
    $canal_tipo_fop =~ tr/a-z/A-Z/;
    log_debug("$debugh canal_tipo_fop $canal_tipo_fop", 64);

    my $canalconcontexto = "";
    if ($contexto ne "")
    {
        $canalconcontexto = "$canal_tipo_fop$contexto";
    }
    else
    {
        $canalconcontexto = $canal_tipo_fop;
        $contexto         = "";
    }

    if ($sesion eq "XXXX")
    {

        # Si la sesion es XXXX devuelve siempre el 1er boton
        log_debug("$debugh return $canal_tipo_fop=1$contexto (1st one)", 64);
        return "$canal_tipo_fop=1$contexto";
    }

    my $canalconcontextosinserver = $canalconcontexto;
    $canalconcontextosinserver =~ s/(\d+)\^(.*)/$2/g;
    if ($canalconcontexto !~ /\^/)
    {
        $canalconcontexto = $server . "^" . $canalconcontexto;
    }
    &print_instancias(2);
    if (exists($instancias{"$canalconcontexto"}))
    {
        if (exists($instancias{"$canalconcontexto"}{"$server^$canalsesion"}))
        {
            log_debug(
                "$debugh Found instancias($canalconcontexto)($server^$canalsesion)=$instancias{\"$canalconcontexto\"}{\"$server^$canalsesion\"}",
                64
            );
            $trunk_pos = $instancias{"$canalconcontexto"}{"$server^$canalsesion"};
        }
        else
        {
            log_debug("$debugh Not Found instancias($canalconcontexto)($server^$canalsesion)", 64);
            my %busy_slots = ();
            foreach my $key1 (sort (keys(%instancias)))
            {
                if ($key1 eq $canalconcontexto)
                {
                    foreach my $key2 (sort (keys(%{$instancias{$key1}})))
                    {
                        my $indice = $instancias{$key1}{$key2};
                        $busy_slots{$indice} = 1;
                    }
                }
            }
            for ($trunk_pos = 1 ; ; $trunk_pos++)
            {
                last if (!exists($busy_slots{$trunk_pos}));
            }
            $instancias{"$canalconcontexto"}{"$server^$canalsesion"} = $trunk_pos;
        }
        $return = "$canal_tipo_fop=${trunk_pos}$contexto";
    }
    return $return;
}

sub separate_session_from_channel
{
    my $elemento = shift;
    my $debugh   = "** SEPARATE_SESSION_FROM_CHAN";
    log_debug("$debugh elemento1 $elemento", 32);
    if ($elemento !~ /.*[-\/].*[-\/].+$/)
    {
        if ($elemento =~ /^OH323/ || $elemento =~ /^\QMODEM[I4l]\E/i || $elemento =~ /^mISDN/i)
        {
            $elemento =~ s/(.*)\/(.*)/\U$1\E\/${2}-${2}/g;
        }
        else
        {
            $elemento .= "-XXXX";
        }
    }
    if ($elemento =~ /^mISDN/i)
    {
        $elemento =~ s/(.*)\/(.*)/\U$1\E\/${2}-${2}/g;
    }
    log_debug("$debugh elemento1 $elemento", 32);
    $elemento =~ s/(.*)[-\/](.*)/$1\t$2/g;
    log_debug("$debugh elemento2 $elemento", 32);
    my $canal  = $1;
    my $sesion = $2;
    log_debug("$debugh canal $canal sesion $sesion", 32);

    if (defined($canal) && defined($sesion))
    {
        $canal =~ tr/a-z/A-Z/;
        $elemento = $canal . "\t" . $sesion;
    }
    $elemento =~ s/IAX2\[(.*)@(.*)\]\t(.*)/IAX2\[$1\]\t$3/;
    $elemento =~ s/IAX2\/(.*)@(.*)\t(.*)/IAX2\/$1\t$3/;

    my @partes = split(/\t/, $elemento);
    return @partes;
}

sub peerinfo
{
    my $sock  = shift;
    my $short = shift;
    if ($sock eq "")
    {
        return "";
    }
    if (defined($sock->peeraddr))
    {
        if (defined($short))
        {
            return $sock->peerhost;
        }
        else
        {
            return sprintf("%s:%s", $sock->peerhost, $sock->peerport);
        }

    }
    else
    {
        return "";
    }
}

sub erase_instances_for_trunk_buttons
{
    my $canalid          = shift;
    my $canal            = shift;
    my $server           = shift;
    my $canalidsinserver = "";
    my $canalglobal;
    my $valor;
    my @new    = ();
    my $debugh = "** ERASE_INSTANCE_TRUNK";

    $canalidsinserver = $canalid;
    $canalid          = "$server^$canalid";
    $canalid =~ s/(.*)<(.*)>/$1/g;    #discards ZOMBIE or MASQ

    log_debug("$debugh canalid $canalid canal $canal", 1);

    $canalglobal = $canalid;
    $canalglobal =~ s/(.*)[-\/](.*)/$1/g;
    $canalglobal =~ s/IAX2\/(.*)@(.*)/IAX2\/$1/g;
    $canalglobal =~ s/IAX2\[(.*)@(.*)\]/IAX2\[$1\]/g;

    my ($nada, $contexto) = split(/\&/, $canal);
    if (!defined($contexto)) { $contexto = ""; }

    my $canalconcontexto = "";
    if ($contexto ne "")
    {
        $canalconcontexto = "$canalglobal&$contexto";
        $contexto         = "&$contexto";
    }
    else
    {
        $canalconcontexto = $canalglobal;
        $contexto         = "";
    }

    my $sesiontemp = $canalid;
    if ($canalid =~ /^Zap/i && $canal =~ /\*/)
    {

        # Si es un Zap y ademas wildcard, cambio el canalid para
        # que tenga la sesion modificada
        # $sesiontemp =~ s/Zap/ZAP/g;
        $sesiontemp =~ s/(.*)\/(.*)-(.*)/\U$1\/$2-${2}\E${3}/g;
    }
    if ($canalid =~ /^MGCP/i && $canal =~ /\*/)
    {

        # Si es un MGCP y ademas wildcard, cambio el canalid para
        # que tenga la sesion modificada
        my $sesiontemp2 = $sesiontemp;
        $sesiontemp2 =~ s/(.*)\@(.*)-(.*)/$2/g;
        $sesiontemp2 = substr($sesiontemp2, -3);
        $sesiontemp =~ s/(.*)\/(.*)-(.*)/\U$1\E\/${2}-${sesiontemp2}${3}/g;
    }

    log_debug("$debugh looking for $canalid on instancias to erase it", 128);

    foreach my $key1 (sort (keys(%instancias)))
    {
        foreach my $key2 (sort (keys(%{$instancias{$key1}})))
        {
            if ($key2 eq $canalid)
            {
                delete $instancias{$key1}{$key2};
                log_debug("$debugh Erasing $canalid from instanacias!", 128);
            }
        }
    }
}

sub generate_linked_buttons_list
{
    my $nroboton     = shift;
    my $server       = shift;
    my @botonas      = ();
    my $listabotones = "";
    my $debugh       = "** GEN_LINK_LIST ";

    log_debug("$debugh canal $nroboton server $server", 16);

    if ($nroboton !~ /\^/)
    {
        $nroboton = "$server^$nroboton";
    }

    my ($nada1, $contexto1) = split(/\&/, $nroboton);
    if (!defined($contexto1)) { $contexto1 = ""; }

    if (defined(@{$linkbot{"$nroboton"}}))
    {
        log_debug("$debugh Esta definido linkbot {$nroboton}", 32);
        foreach (@{$linkbot{"$nroboton"}})
        {
            log_debug("$debugh y contiene $_", 32);
            my ($canal1, $sesion1) = separate_session_from_channel($_);
            log_debug("$debugh luego de separate canal1 = $canal1 y sesion1 = $sesion1", 128);
            my $canalsesion = $_;
            if (!defined($sesion1))
            {
                $canalsesion = $canal1 . "-XXXX";
            }
            log_debug("$debugh canal1 = $canal1 y sesion1 = $sesion1 canalsesion=$canalsesion", 128);
            my @linkbotones = find_panel_buttons($canal1, $canalsesion, $server);
            foreach my $cual (@linkbotones)
            {
                my ($nada2, $contexto2) = split(/\&/, $cual);
                if (!defined($contexto2)) { $contexto2 = ""; }
                if ($contexto1 eq $contexto2)
                {
                    my $botinro = $buttons{"$server^$cual"};
                    push @botonas, $botinro;
                    log_debug("$debugh Agrego $botinro", 64);
                }
            }
        }

        my %seen2 = ();
        my @uniq2 = grep { !$seen2{$_}++ } @botonas;
        @botonas = \@uniq2;

        foreach my $val (@uniq2)
        {
            if (defined($val))
            {
                $listabotones .= "$val,";
                log_debug("$debugh devuelve $val", 128);
            }
        }
        $listabotones = substr($listabotones, 0, -1);
    }
    else
    {
        log_debug("$debugh NO ESTA DEFINIDO linkbot {$nroboton}", 32);
    }
    return $listabotones;
}

sub erase_all_sessions_from_channel
{
    my $canalid     = shift;
    my $canal       = shift;
    my $server      = shift;
    my $canalsesion = $canalid;
    my @final;
    my @return;
    my $debugh = "** ERASE_ALL_SESS_FROM";
    log_debug("$debugh canal $canal canalid $canalid", 16);

    my $indice_cache = $canalid . "-" . $canal . "-" . $server;
    log_debug("$debugh borro cache_hit($indice_cache)", 128);
    delete $cache_hit{$indice_cache};
    if (keys(%cache_hit))
    {
        for (keys %cache_hit)
        {
            if (defined(@{$cache_hit{$_}}))
            {
                foreach my $val (@{$cache_hit{$_}})
                {
                    if ($val eq $canal)
                    {
                        log_debug("$debugh borro cache $_", 128);
                        delete $cache_hit{$_};
                    }
                }
            }
        }
    }

    if ($canal =~ /=/)
    {

        # If its a trunk button, erase instances
        erase_instances_for_trunk_buttons($canalsesion, $canal, $server);
    }
    $canalsesion =~ s/\t/-/g;
    $canalid     =~ s/(.*)<(.*)>/$1/g;    # Removes <zombie><masq>

    for my $mnroboton (keys %sesbot)
    {
        @final = ();
        foreach my $msesion (@{$sesbot{$mnroboton}})
        {
            log_debug("$debugh $msesion ne $canalsesion?", 64);
            if ($msesion ne $canalsesion)
            {
                log_debug("$debugh sesbot es distinto dejo $msesion a \@final", 64);
                push @final, $msesion;
            }
        }
        $sesbot{$mnroboton} = [@final];
    }

    if (keys(%linkbot))
    {
        for (keys %linkbot)
        {
            if (defined(@{$linkbot{$_}}))
            {
                my @final = ();
                foreach my $val (@{$linkbot{$_}})
                {
                    log_debug("$debugh linkbot($_) ne $val ?", 64);
                    if ($val ne $canalsesion)
                    {
                        push @final, $val;
                        log_debug("$debugh No es igual lo dejo $_", 64);
                    }
                    else
                    {
                        push @return, $_;
                        log_debug("$debugh Es igual lo AGREGO RETURN $_", 64);
                    }
                }

                log_debug("$debugh delete linkbot($_)", 64);
                delete $linkbot{$_};
                $linkbot{$_} = [@final];
            }
        }
    }

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
                    log_debug("** Found a match $canalid=$val ($quehay) - Cleared!", 16);
                    delete $datos{$quehay};
                }
            }
        }
    }
    for my $valores (@return)
    {
        log_debug("$debugh devuleve $valores", 64);
    }
    return @return;
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
    my $debugh        = "** EXTRAER_TODAS ";
    log_debug("$debugh from the channel $canal", 16);

    # Removes the context if its set

    my @pedazos = split(/&/, $canal);
    $canal = $pedazos[0];

    # Checks if the channel name has an equal sign
    # (its a trunk button channel)

    if ($canal =~ /(.*)=(\d+)/)
    {
        ($canalbase, $sesion_numero) = split(/\=/, $canal);
        log_debug("** Its a trunk $canalbase button number $sesion_numero!", 16);

        foreach my $key1 (sort (keys(%instancias)))
        {
            foreach my $key2 (sort (keys(%{$instancias{$key1}})))
            {
                if ($key2 eq $canalbase)
                {
                    push @result, $key2;
                    log_debug("$debugh encontro sesion $canalbase", 16);
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
    my $server = "0";
    my @result = ();
    my $debugh = "** EXTRACT_LINKS_CHAN";

    my @pedazos = split(/&/, $canal);
    $canal = $pedazos[0];

    if ($canal =~ /\^/)
    {
        @pedazos = split(/\^/, $canal);
        $server  = $pedazos[0];
        $canal   = $pedazos[1];
    }

    # If the channel is not an agent, it will have a session
    # appended to it separated with a hypen
    if ($canal !~ m/^Agent/i)
    {
        $canal = $canal . "-";
    }

    log_debug("$debugh canal $canal server $server", 32);

    for $quehay (keys %datos)
    {
        my $canalaqui  = 0;
        my $serveraqui = 0;
        my $linkeado   = "";
        while (my ($key, $val) = each(%{$datos{$quehay}}))
        {
            log_debug("$debugh buscando $canal en $key=$val", 128);
            if ($val =~ /^$canal/i && $key =~ /^Chan/i)
            {
                $canalaqui = 1;
            }
            if ($key =~ /^Server/i && $val eq $server)
            {
                $serveraqui = 1;
            }
            if ($key =~ /^Link/i)
            {
                $linkeado = $val;
            }
        }
        if ($canalaqui == 1 && $linkeado ne "" && $serveraqui == 1)
        {
            push(@result, $linkeado);
            log_debug("$debugh Agrego $linkeado a la lista", 32);
        }
    }
    return @result;
}

sub find_panel_buttons
{

    # *****************************************************************
    # Based on a CHANNEL name returned by Asterisk, we try to match
    # one or more of our buttons to show status. Returns array with list
    # of channel names as set in op_buttons.cfg

    my $canal         = shift;
    my $canalsesion   = shift;
    my $server        = shift;
    my $pos           = 0;
    my $sesion        = "";
    my @canales       = ();
    my $quehay        = "";
    my $canalfinal    = "";
    my $contextoindex = "";
    my $server_boton  = 0;
    my $debugh        = "** FIND_PANEL_BUT";
    my $calleridnum   = "noexiste";
    log_debug("$debugh canal $canal canalsesion $canalsesion server $server", 32);

    my $uniqueid = find_uniqueid($canalsesion, $server);
    if ($uniqueid ne "")
    {
        if (defined($datos{$uniqueid}{"CallerID"}))
        {
            $calleridnum = $datos{$uniqueid}{"CallerID"};
        }
    }

    # XXXXX We have to try hard to find a match for the channel
    # There are several posibilities:
    #
    # Exact match:      SIP/jo       (no panel context, not trunk, no wildcard)
    # Panel Ctxt match: SIP/jo&SIP   (exact name, not trunk, no wildcard, panel context)
    # Trunk match:      SIP/jo=1     (exact name, trunk, no wildcard, no panel context)
    # Ctxt&Trunk match: SIP/jo=1&SIP (exact name, trunk, no wildcard, panel context)
    # Wildcard          SIP/*=1      (wildcard name, trunk)
    #
    # The key to match syntax is server^[chan_name|wildcard](=trunk_position)(&panel_context)
    #
    # Here I first will try to match any $buttons that might match the given channel name

    if ($canalsesion =~ /</)
    {

        # "<" Is an invalid character for a channel name, unless its a zombie
        # or masq, in that case we should discard them
        log_debug("$debugh canalsesion $canalsesion (Se supone que no debo tratar zombies?)", 16);
    }

    # Attemp to match a button from cache
    my $indice_cache = $canalsesion . "-" . $canal . "-" . $server;
    if (!defined($cache_hit{$indice_cache}))
    {
        log_debug("$debugh CACHE MISS $indice_cache", 16);
        for (keys %buttons)
        {
            $canalfinal = "";
            my ($nada, $contexto) = split("\&", $_);
            if (!defined($contexto)) { $contexto = ""; }
            if ($contexto ne "") { $contexto = "&" . $contexto; }
            if ($_ =~ /^\Q$server^$canal\E$/)
            {

                log_debug("$debugh exact match buttons ( $_ )  $canal $contexto", 32);
                $canalfinal = $canal;
            }
            elsif ($_ =~ /^\Q$server^$canal\E\&/)
            {

                log_debug("$debugh context match buttons ( $_ )  $canal $contexto", 32);
                $canalfinal = $canal;
            }
            elsif ($_ =~ /^\Q$server^$canal\E=/)
            {
                log_debug("$debugh trunk match ( $_ )  $canal $contexto", 32);
                $canalfinal = get_next_trunk_button($canalsesion, $contexto, $server, $canalsesion);
                print "canalfinal $canalfinal\n";
                $canalfinal =~ s/(.*)\^(.*)/$2/g;
                print "canalfinal $canalfinal\n";
            }
            elsif ($_ =~ /^$server\^CLID\/$calleridnum\&?/)
            {

                log_debug("$debugh clid match ( $_ )  $canal $contexto", 32);
                $canalfinal = "CLID/$calleridnum";
            }

            if ($canalfinal ne "")
            {
                my $indicefin             = "";
                my $canalfinalconcontexto = $canalfinal;

                if ($canalfinal =~ /\^/)
                {
                    $indicefin = "${canalfinal}";
                }
                else
                {
                    $indicefin = "$server^${canalfinal}";
                }
                if ($indicefin !~ /(.*)\&(.*)$/)
                {
                    $indicefin             = "$indicefin$contexto";
                    $canalfinalconcontexto = "${canalfinal}${contexto}";
                }

                my $posicion = $buttons{$indicefin};
                $server_boton = $button_server{$posicion};
                log_debug("$debugh server para $canalfinal = $server_boton", 64);

                push @canales, "${canalfinalconcontexto}";
            }
        }
        $canalfinal = "";

        my $nada1      = "";
        my $contextemp = "";
        my %contextosencontrados;
        for my $val (@canales)
        {
            ($nada1, $contextemp) = split("&", $val);
            if (!defined($contextemp)) { $contextemp = ""; }
            $contextosencontrados{"&$contextemp"} = 1;
        }

        # Attempt to have regexp buttons
        my $canal_sin_server_ni_contexto = $canal;
        $canal_sin_server_ni_contexto =~ s/(^\d+\^)(.*)(\&.*)?(=\d+)?/$2/;
        for (keys %buttons)
        {
            my $regexp = "";
            if ($_ =~ /^\d+\^_/)
            {
                $regexp = $_;
                $regexp =~ /^(\d+)\^_([^=&]*)(=[^&]*)?(\&.*)?/;
                my $serverb = $1;
                $regexp = $2;
                my $posicion = $3;
                my $contexto = "";
                if (defined($4))
                {
                    $contexto = $4;
                    if (defined($contextosencontrados{$contexto}))
                    {
                        next;
                    }
                }
                if ($canal_sin_server_ni_contexto =~ m/$regexp/i)
                {
                    if (defined($posicion))
                    {

                        # Es un trunk
                        $canalfinal = get_next_trunk_button($_, $contexto, $server, $canalsesion);
                    }
                    else
                    {

                        # No es un trunk
                        $canalfinal = $_;
                    }
                    if ($canalfinal ne "")
                    {
                        push @canales, $canalfinal;
                    }
                }
            }
        }

        my %count = ();
        my @unique = grep { ++$count{$_} < 2 } @canales;
        @canales = @unique;
        foreach my $val (@canales)
        {
            log_debug("$debugh returns canal $val", 128);
        }
    }
    else
    {

        # We have a cache match, retrieve the buttons from cache
        @canales = @{$cache_hit{$indice_cache}};
        log_debug("$debugh CACHE HIT Retrieving buttons from cache ($indice_cache)", 16);
        foreach (@canales)
        {
            log_debug("$debugh cache button $_", 128);
        }
    }
    my $cuantoscanales = @canales;
    if ($cuantoscanales > 0)
    {
        $cache_hit{$indice_cache} = [@canales];
    }
    foreach (@canales)
    {
        log_debug("$debugh cache button $_", 128);
    }
    return @canales;
}

sub procesa_bloque
{
    my $blaque = shift;
    my $socket = shift;
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
    my $clidnum       = "";
    my $clidname      = "";
    my $canalid       = "";
    my $key           = "";
    my $val           = "";
    my @return        = ();
    my $conquien      = "";
    my $enlazado      = "";
    my $viejo_nombre  = "";
    my $nuevo_nombre  = "";
    my $quehay        = "";
    my $elemento      = "";
    my $state         = "";
    my $exists        = 0;
    my $fakecounter   = 1;
    my $fill_datos    = 0;
    my $server        = 0;
    my $timeout       = 0;
    my $debugh        = "** PROCESA_BLOQUE";

    log_debug("$debugh START SUB", 16);

    $hash_temporal{"Event"} = "";

    while (my ($key, $val) = each(%bloque))
    {
        if (defined($val))
        {
            $val =~ s/(.*)\s+$/$1/g;
        }
        else
        {
            $val = "";
        }

        #			my ($lcan,$lses) = separate_session_from_channel($val);
        #			if(exists($channel_to_agent{$lcan}))
        #			{
        #			    print "Cambio $val por ".$channel_to_agent{$lcan}."\n";
        #				my $ori_val = $val;
        #				$val =~ s/(.*)-(.*)/$channel_to_agent{$lcan}-$2/g;
        #				($lcan,$lses) = separate_session_from_channel($val);
        #				$channel_to_agent{$lcan}=$lcan;
        #			}

        $hash_temporal{$key} = $val;

        log_debug("$debugh     HASH_TEMPORAL($key) = $val", 128);
    }

    if ($hash_temporal{"Event"} =~ /^UserEvent/)
    {

        # This blocks checks if we have an UserEvent
        # and splits every key value pair if it haves
        # a caret as a delimiter
        while (my ($key, $val) = each(%hash_temporal))
        {
            if ($val =~ /\^/)
            {
                my @partes = split(/\^/, $val, 2);
                $hash_temporal{$key} = $partes[0];
                my $resto_de_parametros = $partes[1];
                @partes = split(/\^/, $resto_de_parametros);
                foreach my $value (@partes)
                {
                    my @partes2 = split(/: /, $value);
                    if (!defined($partes2[0])) { next; }
                    $hash_temporal{$partes2[0]} = $partes2[1];
                }
            }
        }
    }

    $canalid = "";
    $canalid = $hash_temporal{"Channel"}
      if defined($hash_temporal{"Channel"});

    $server = 0;
    $server = $hash_temporal{"Server"}
      if defined($hash_temporal{"Server"});

    if (defined($hash_temporal{"Uniqueid"}))
    {
        $unico_id   = $hash_temporal{"Uniqueid"};
        $fill_datos = 1;
    }
    else
    {
        $unico_id = "YYYY";
    }

    $enlazado = "";
    if (exists($datos{$unico_id}))
    {

        if (exists($datos{$unico_id}{"Link"}))
        {
            $enlazado = $datos{$unico_id}{"Link"};
        }

        if (exists($datos{$unico_id}{"Application"}))
        {
            $enlazado .= " - " . $datos{$unico_id}{"Application"};
        }

        if (exists($datos{$unico_id}{"AppData"}))
        {
            $enlazado .= ":" . $datos{$unico_id}{"AppData"};
        }

    }

    if ($unico_id !~ /-\d+$/)
    {

        # Add the server at the end of the uniqueid
        # if its not already there
        $unico_id .= "-" . $server;
    }

    $evento = "";
    if (defined($hash_temporal{"Event"}))
    {
        $evento = $hash_temporal{"Event"};
    }

    if (defined($hash_temporal{"ActionID"}))
    {
        if ($hash_temporal{"ActionID"} =~ /^timeout/i)
        {
            my @partes = split(/\|/, $hash_temporal{"ActionID"});
            $canalid  = $partes[1];
            $timeout  = $partes[2];
            $evento   = "Timeout";
            $unico_id = "YYYY-$server";
        }
    }

    log_debug("$debugh canalid $canalid unico_id $unico_id evento $evento enlazado $enlazado", 128);

    # Populates a global hash to keep track of
    # 'active' channels, the ones that are not
    # state down.
    if (defined($unico_id))
    {
        if ($unico_id !~ /^YYYY/)
        {

            if ($fill_datos)
            {    # Ignores blocks without Uniqueid
                log_debug("$debugh LLENANDO el global datos $unico_id", 64);
                delete $datos{$unico_id}{"State"};
                while (my ($key, $val) = each(%hash_temporal))
                {
                    if ($key eq "Uniqueid")
                    {
                        if ($val !~ /-/)
                        {
                            $val .= "-" . $server;
                        }
                    }
                    if (!defined($val))
                    {
                        $val = "";
                    }
                    $datos{$unico_id}{"$key"} = $val;
                    log_debug("$debugh POPULATES datos($unico_id){ $key } = $val", 128);
                }
            }
        }
        else
        {
            log_debug("$debugh NO LLENO el global datos $unico_id", 64);
        }
    }

    $evento =~ s/UserEvent//g;
    if    ($evento =~ /Newchannel/)           { $evento = "newchannel"; }
    elsif ($evento =~ /Newcallerid/)          { $evento = "newcallerid"; }
    elsif ($evento =~ /^Status$/)             { $evento = "status"; }
    elsif ($evento =~ /^StatusComplete/)      { $evento = "statuscomplete"; }
    elsif ($evento =~ /Newexten/)             { $evento = "newexten"; }
    elsif ($evento =~ /^ParkedCall$/)         { $evento = "parkedcall"; }
    elsif ($evento =~ /Newstate/)             { $evento = "newstate"; }
    elsif ($evento =~ /Hangup/)               { $evento = "hangup"; }
    elsif ($evento =~ /Rename/)               { $evento = "rename"; }
    elsif ($evento =~ /MessageWaiting/)       { $evento = "voicemail"; }
    elsif ($evento =~ /Regstatus/)            { $evento = "regstatus"; }
    elsif ($evento =~ /^Unlink/)              { $evento = "unlink"; }
    elsif ($evento =~ /QueueParams/)          { $evento = "queueparams"; }
    elsif ($evento =~ /QueueMember/)          { $evento = "queuemember"; }
    elsif ($evento =~ /QueueStatus/)          { $evento = "queuestatus"; }
    elsif ($evento =~ /^Link/)                { $evento = "link"; }
    elsif ($evento =~ /^Join/)                { $evento = "join"; }
    elsif ($evento =~ /^MeetmeJoin/)          { $evento = "meetmejoin"; }
    elsif ($evento =~ /^MeetmeLeave/)         { $evento = "meetmeleave"; }
    elsif ($evento =~ /^meetmemute/)          { $evento = "meetmemute"; }
    elsif ($evento =~ /^meetmeunmute/)        { $evento = "meetmeunmute"; }
    elsif ($evento =~ /^Agentlogin/)          { $evento = "agentlogin"; }
    elsif ($evento =~ /^RefreshQueue/)        { $evento = "refreshqueue"; }
    elsif ($evento =~ /^Timeout/)             { $evento = "timeout"; }
    elsif ($evento =~ /^AgentCalled/)         { $evento = "agentcalled"; }
    elsif ($evento =~ /^AgentConnect/)        { $evento = "agentconnect"; }
    elsif ($evento =~ /^AgentCompleted/)      { $evento = "agentcompleted"; }
    elsif ($evento =~ /^Agentcallbacklogin/)  { $evento = "agentcblogin"; }
    elsif ($evento =~ /^Agentcallbacklogoff/) { $evento = "agentlogoff"; }
    elsif ($evento =~ /^Agentlogoff/)         { $evento = "agentlogoff"; }
    elsif ($evento =~ /^IsMeetmeMember/)      { $evento = "fakeismeetmemember"; }
    elsif ($evento =~ /^PeerStatus/)          { $evento = "peerstatus"; }
    elsif ($evento =~ /^Leave/)               { $evento = "leave"; }
    elsif ($evento =~ /^FOP_Popup/i)          { $evento = "foppopup"; }
    elsif ($evento =~ /^FOP_LedColor/i)       { $evento = "fopledcolor"; }
    elsif ($evento =~ /^Dial/)                { $evento = "dial"; }
    elsif ($evento =~ /^ASTDB/)               { $evento = "astdb"; }
    elsif ($evento =~ /^DNDState/)            { $evento = "zapdndstate"; }
    elsif ($evento =~ /^ZapShowChannels$/)    { $evento = "zapdndstate"; }
    else { log_debug("$debugh No event match ($evento)", 16); }

    if (defined($hash_temporal{"Link"}))
    {
        if (defined($hash_temporal{"Seconds"}))
        {
            my $unid = find_uniqueid($hash_temporal{"Link"}, $server);
            $fake_bloque[$fakecounter]{"Event"}    = "Newexten";
            $fake_bloque[$fakecounter]{"Channel"}  = $hash_temporal{"Link"};
            $fake_bloque[$fakecounter]{"State"}    = "Up";
            $fake_bloque[$fakecounter]{"Seconds"}  = $hash_temporal{"Seconds"};
            $fake_bloque[$fakecounter]{"CallerID"} = $hash_temporal{"CallerID"};
            $fake_bloque[$fakecounter]{"Uniqueid"} = $unid;
            $fakecounter++;
            log_debug("$debugh Fake bloque canal $hash_temporal{'Link'} con seconds $hash_temporal{'Seconds'}", 128);
        }
    }

    if ($evento eq "agentcalled")
    {

        # We use this event to send the ringing state for an Agent
        $estado_final = "ringing";
        $canal        = $hash_temporal{"AgentCalled"};
        $canal =~ tr/a-z/A-Z/;
        $canalid  = $canal . "-XXXX";
        $clidnum  = $hash_temporal{"CallerID"};
        $clidname = $hash_temporal{"CallerIDName"};
        $texto    = "Incoming call from [" . format_clid($clidnum, $clid_format) . "]";
        my $base64_clidnum  = encode_base64($clidnum . " ");
        my $base64_clidname = encode_base64($clidname . " ");
        push @return, "$canal|clidnum|$base64_clidnum|$canalid-$server|$canalid";
        push @return, "$canal|clidname|$base64_clidname|$canalid-$server|$canalid";
        push @return, "$canal|$estado_final|$texto|$canalid-$server|$canalid";
        $evento = "";
    }

    if ($evento eq "agentconnect")
    {

        # We use this event to fake the ringing state
        $estado_final = "ocupado";
        $texto        = "Taking call from $hash_temporal{\"Queue\"}";
        $canal        = $hash_temporal{"Channel"};
        $canal =~ tr/a-z/A-Z/;
        $canalid = $canal . "-XXXX";
        push @return, "$canal|$estado_final|$texto|$canalid-$server|$canalid";    #NEW
        $evento = "";
    }

    if ($evento eq "dial")
    {

        # We use this hashes to store the remote callerid for CVS-HEAD
        my $key      = "$server^$hash_temporal{'Destination'}";
        my $dorigen  = "";
        my $ddestino = "";
        my $dnada    = "";
        $remote_callerid{$key}      = $hash_temporal{"CallerID"};
        $remote_callerid_name{$key} = $hash_temporal{"CallerIDName"};

        # We also look for Dial from Local/XX@context to TECH/XX for
        # matching agentcallbacklogins exten@context to real channels
        # so we can map outgoing calls to Agent buttons
        # It will only work after the agent receives at least one call
        ($dorigen,  $dnada) = separate_session_from_channel($hash_temporal{'Source'});
        ($ddestino, $dnada) = separate_session_from_channel($hash_temporal{'Destination'});
        if (exists($channel_to_agent{$dorigen}))
        {
            my $agente = $channel_to_agent{$dorigen};

            # delete $channel_to_agent{$dorigen};
            $channel_to_agent{$ddestino} = $agente;
        }
    }

    if ($evento eq "zapdndstate")
    {
        $canal = $hash_temporal{"Channel"};
        my $zstatus = "";
        if ($canal !~ m/Zap/i)
        {
            $canal = "Zap/$canal";
        }
        if (defined($hash_temporal{"Status"}))
        {
            $zstatus = $hash_temporal{"Status"};
        }
        if (defined($hash_temporal{"DND"}))
        {
            $zstatus = $hash_temporal{"DND"};
        }
        if ($zstatus =~ /disabled/i)
        {
            $zstatus = "";
        }

        # If we receive a zap dnd state, we fake the ASTDB
        # event with family 'dnd'. So it will execute the
        # actions listed on op_astdb.cfg inside [dnd]
        $fake_bloque[$fakecounter]{"Event"}   = "ASTDB";
        $fake_bloque[$fakecounter]{"Channel"} = $canal;
        $fake_bloque[$fakecounter]{"Family"}  = "dnd";
        $fake_bloque[$fakecounter]{"Value"}   = $zstatus;
        $fakecounter++;

        $evento = "";
    }

    if ($evento eq "astdb")
    {
        my $valor = "";
        $estado_final = "astdb";
        ($canal, my $nada) = separate_session_from_channel($hash_temporal{"Channel"});
        $canalid = $hash_temporal{"Channel"} . "-XXXX";
        my $clave = $hash_temporal{"Family"};
        if (!defined($hash_temporal{"Value"}))
        {
            $valor = "";
        }
        else
        {
            $valor = $hash_temporal{"Value"};
        }

        foreach my $item (@{$astdbcommands{$clave}})
        {
            my $item_temp = $item;
            $item_temp =~ s/\${value}/$valor/g;
            my ($comando, $datos) = split(/=/, $item_temp);
            if ($valor ne "")
            {
                push @return, "$canal|$comando|$datos|$canalid-$server|$canalid";
            }
            else
            {
                push @return, "$canal|$comando||$canalid-$server|$canalid";
            }
        }
        $evento = "";
    }

    if ($evento eq "timeout")
    {
        $estado_final = "timeout";
        $texto        = $timeout;
        my $ahora = time();
        my $unique = find_uniqueid($canalid, $server);
        $datos{$unique}{"Timeout"} = $ahora + $timeout;
        $timeouts{$canalid} = $ahora + $timeout;
        push @return, "$canal|$estado_final|$texto|$unique|$canalid";    #NEW
        $evento = "";
    }

    if ($evento eq "regstatus")
    {

        # Sends the IP address of the peer to the flash client
        # XXXX It will have to store this value internally in future version
        # to avoid polling asterisk every time
        ($canal, my $nada) = separate_session_from_channel($hash_temporal{"Channel"});
        $texto = $hash_temporal{"IP"};
        my $serv = $hash_temporal{"Server"};
        if ($show_ip)
        {

            # $estado_final = "ip";
            $estado_final = "settext";
            $boton_ip{$canalid} = $texto;
            push @return, "$canal|$estado_final|$texto|$unico_id|$canalid";

            # $evento = "";
        }
    }

    if ($evento eq "fopledcolor")
    {
        my $color = "";
        my $state = "";
        ($canal, my $nada) = separate_session_from_channel($hash_temporal{"Channel"});
        $color        = $hash_temporal{"Color"};
        $state        = $hash_temporal{"State"};
        $estado_final = "fopledcolor";
        push @return, "$canal|$estado_final|$color^$state|$unico_id|$canalid";
        $evento = "";
    }

    if ($evento eq "foppopup")
    {
        ($canal, my $nada) = separate_session_from_channel($hash_temporal{"Channel"});
        my $url    = $hash_temporal{"URL"};
        my $target = $hash_temporal{"Target"};
        my $data   = "$url^$target";
        $estado_final = "foppopup";
        push @return, "$canal|$estado_final|$data|$unico_id|$canalid";
        $evento = "";
    }

    if ($evento eq "refreshqueue")
    {
        ($canal, my $nada) = separate_session_from_channel($hash_temporal{"Channel"});

        # Turns off led of the agent that generated the refresh
        if ($change_led == 1)
        {
            $estado_final = "changelabel" . $change_led;
            push @return, "$canal|$estado_final|original|$unico_id|$canalid";
        }
        request_queue_status($socket, $hash_temporal{"Channel"});
        $evento = "";
    }

    if ($evento eq "agentcblogin")
    {
        my $canalreal = "";
        my $labeltext = ".";
        my $texto     = $hash_temporal{"Agent"};

        if (defined($datos{$unico_id}{"Channel"}))
        {
            ($canalreal, my $nada) = separate_session_from_channel($datos{$unico_id}{"Channel"});
            $canalreal =~ tr/a-z/A-Z/;
            $channel_to_agent{"$canalreal"} = "AGENT/$texto";
            $channel_to_agent{"$canalreal"} =~ tr/a-z/A-Z/;
        }
        $canalreal = "Local/$hash_temporal{'Loginchan'}";
        $canalreal =~ tr/a-z/A-Z/;
        $channel_to_agent{"$canalreal"} = "AGENT/$texto";
        $channel_to_agent{"$canalreal"} =~ tr/a-z/A-Z/;

        if ($canalid eq "")
        {
            $canalid = "$texto-XXXX";
        }

        if (defined($agents{"$server^$texto"}))
        {

            # If it was already logged in, fake a logout event

            if (   $ren_agentlogin == 1
                || $ren_cbacklogin == 1
                || $change_led == 1)
            {
                $canal = $agents{"$server^$texto"};

                $estado_final = "changelabel" . $change_led;
                if ($canal =~ /^AGENT/i)
                {
                    push @return, "AGENT/$texto|setalpha|100|$unico_id|$canalid";
                }
                else
                {
                    push @return, "$canal|$estado_final|original|$unico_id|$canalid";
                }
                delete $agents{"$server^$texto"};
            }
        }

        if (defined($extension_transfer_reverse{$hash_temporal{"Loginchan"}}))
        {
            $canal = $extension_transfer_reverse{$hash_temporal{"Loginchan"}};
            $canal =~ s/(.*)&(.*)/$1/g;
            if ($canal =~ /\^/)
            {
                my @pedacete = split(/\^/, $canal);
                $canal = $pedacete[1];
            }
            $estado_final = "changelabel" . $change_led;
            if ($ren_cbacklogin == 1)
            {
                $labeltext = "AGENT/$texto";
                if ($ren_agentname == 1)
                {
                    if (defined($agents_name{"$server^$texto"}))
                    {
                        $labeltext = $agents_name{"$server^$texto"};
                    }
                }
            }
            if ($canal !~ /^agent/i)
            {
                push @return, "$canal|$estado_final|$labeltext|$unico_id|$canalid";
            }
            else
            {
                push @return, "AGENT/$texto|setalpha|100|$unico_id|$canalid";
            }
            if ($agent_status == 1)
            {
                push @return, "$canal|isagent|0|$unico_id|$canalid";
                push @return, "AGENT/$texto|isagent|0|$unico_id|$canalid";
            }
            $agents{"$server^$texto"} = $canal;
            $reverse_agents{$canal} = $texto;
        }
        $evento = "";
    }

    if ($evento eq "agentlogin")
    {

        while (($key, $val) = each(%hash_temporal))
        {

            # print "$key = $val\n";
        }

        my $labeltext = ".";

        $texto = $hash_temporal{"Agent"};
        ($canal, my $nada) = separate_session_from_channel($hash_temporal{"Channel"});
        $estado_final = "changelabel" . $change_led;
        if ($canalid eq "")
        {
            $canalid = "AGENT/$texto-XXXX";
        }

        if ($ren_agentlogin == 1 && !defined($hash_temporal{'Fake'}))
        {
            $labeltext = "Agent/$texto";
            if ($ren_agentname == 1)
            {
                if (defined($agents_name{"$server^$texto"}))
                {
                    $labeltext = $agents_name{"$server^$texto"};
                }
            }
        }
        if ($ren_queuemember == 1)
        {
            $labeltext = "Agent/$texto";
            if ($ren_agentname == 1)
            {
                if (defined($agents_name{"$server^$texto"}))
                {
                    $labeltext = $agents_name{"$server^$texto"};
                }
            }
        }

        if ($canal =~ m/^AGENT/i)
        {
            push @return, "$canal|setalpha|100|$unico_id|$canalid";
        }
        else
        {
            push @return, "$canal|$estado_final|$labeltext|$unico_id|$canalid";
        }

        if ($agent_status == 1)
        {
            push @return, "$canal|isagent|0|$unico_id|$canalid";
        }

        $agents{"$server^$texto"} = $canal;
        $evento = "";
    }

    if ($evento eq "agentlogoff")
    {
        my $agente                = "AGENT/" . $hash_temporal{'Agent'};
        my %temp_channel_to_agent = %channel_to_agent;
        while (($key, $val) = each(%temp_channel_to_agent))
        {
            if ($val eq $agente)
            {
                delete $channel_to_agent{$key};
            }
        }
        undef %temp_channel_to_agent;

        if ($ren_agentlogin == 1 || $ren_cbacklogin == 1 || $change_led == 1)
        {
            $texto        = $hash_temporal{"Agent"};
            $estado_final = "changelabel" . $change_led;

            if ($canalid eq "")
            {
                $canalid = "AGENT/$texto-XXXX";
            }

            push @return, "AGENT/$texto|setalpha|50|$unico_id|$canalid";

            if ($agent_status == 1)
            {
                push @return, "$canal|isagent|-1|$unico_id|$canalid";
                push @return, "AGENT/$texto|isagent|-1|$unico_id|$canalid";
            }

            if (defined($agents{"$server^$texto"}))
            {
                $canal = $agents{"$server^$texto"};
                push @return, "$canal|$estado_final|original|$unico_id|$canalid";
                delete $agents{"$server^$canal"};
                delete $agents{"$server^$texto"};
                delete $reverse_agents{$texto};
                delete $reverse_agents{$canal};
            }

            $evento = "";
        }
    }

    if ($evento eq "queuemember")
    {
        my $canalag = $hash_temporal{"Location"};
        $canalag =~ tr/a-z/A-Z/;
        my $canalagid  = $canalag . "-XXXX";
        my $unicoag_id = "$canalag-$server";
        $canal = $hash_temporal{"Location"};

        if ($canal =~ /^AGENT/i)
        {
            my $temp = $canal;
            $temp =~ s/^AGENT\///gi;
            if (defined($agents{"$server^$temp"}))
            {
                $canal = $agents{"$server^$temp"};
            }
        }
        $canal =~ tr/a-z/A-Z/;
        $estado_final = "info";
        $texto        = "";
        while (($key, $val) = each(%hash_temporal))
        {
            if (!defined($val)) { $val = " "; }
            $texto .= "$key = $val\n";
            if ($key eq "Queue")
            {
                $estado_final .= $val;
            }
        }
        $unico_id = "$canal-$server";
        $texto .= " ";
        $texto   = encode_base64($texto);
        $canalid = $canal . "-XXXX";
        push @return, "$canal|$estado_final|$texto|$unico_id|$canalid";
        push @return, "$canalag|$estado_final|$texto|$unicoag_id|$canalagid";
        $evento = "";

        if ($canal !~ /^AGENT/i)
        {

            # Generates Fake Agent Login to change led color and label renaming
            $fake_bloque[$fakecounter]{"Event"}   = "Agentlogin";
            $fake_bloque[$fakecounter]{"Channel"} = $canal . "-XXXX";
            $fake_bloque[$fakecounter]{"Agent"}   = $canal;
            $fake_bloque[$fakecounter]{"Fake"}    = "1";
            $fakecounter++;
        }

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
        $unico_id = "$canal-$server";
        $texto .= " ";
        $texto = encode_base64($texto);
        push @return, "$canal|$estado_final|$texto|$unico_id|$canalid";
        $evento = "";
    }

    if ($evento eq "meetmemute" || $evento eq "meetmeunmute")
    {
        my ($canal, $nada) = separate_session_from_channel($canalid);
        $estado_final = $evento;
        push @return, "$canal|$evento||$unico_id|$canalid";
        $evento = "";
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
            $texto    = $hash_temporal{"Calls"} . " member$plural waiting on queue...";
            $unico_id = "$canal-$server";
            push @return, "$canal|$estado_final|$texto|$unico_id|$canalid";
            $evento = "";
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
        $unico_id = "$canal-$server";
        push @return, "$canal|$estado_final|$texto|$unico_id|$canalid";
        $evento = "";
    }

    if ($evento eq "meetmejoin")
    {
        my $originate = "no";
        my $nada      = "";
        my $contexto  = "";

        $canal = $hash_temporal{"Meetme"};
        my $uni_id = $hash_temporal{"Uniqueid"} . "-" . $server;
        log_debug("$debugh MEETMEJOIN uni_id = $uni_id y canal = $canal", 128);
        $datos{$uni_id}{"Extension"} = $canal;
        log_debug("$debugh 2 BORRO datos $uni_id { link }", 128);
        delete $datos{$uni_id}{"Link"};

        $canal =~ tr/a-z/A-Z/;

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
            log_debug("$debugh origino a meetme en el contexto $contexto!", 128);
            my $comando = "Action: Originate\r\n";
            $comando .= "Channel: $originate\r\n";
            $comando .= "Exten: $canal\r\n";
            $comando .= "Context: " . $config->{$contexto}{'conference_context'} . "\r\n";
            $comando .= "Priority: 1\r\n";
            $comando .= "\r\n";
            send_command_to_manager($comando, $socket);

            if ($barge_muted)
            {
                $start_muted{"$server^$originate"} = 1;
            }
        }

        $estado_final = "ocupado9";    # 9 for conference
        my $plural = "";

        if (!defined($hash_temporal{"Fake"}))
        {
            if (!defined($datos{"$canal-$server"}{"Count"}))
            {
                $datos{"$canal-$server"}{"Count"} = 0;
                log_debug("$debugh POPULATES datos($canal-$server){ count } = 0", 64);
            }
            $datos{"$canal-$server"}{"Count"}++;
            log_debug("$debugh pongo DATOS ($canal-$server) {count} en $datos{\"$canal-$server\"}{'Count'}", 16);
        }
        else
        {
        }

        # Its a fake meetmejoin generated from the meetme status at startup
        my ($canalsinses, $pedses) = separate_session_from_channel($hash_temporal{'Channel'});
        push @return,
          "$hash_temporal{'Meetme'}|setlink|$hash_temporal{'Channel'}|YYYY-$server|$hash_temporal{'Channel'}";
        push @return,
          "$canalsinses|setlink|$hash_temporal{'Meetme'}|$hash_temporal{'Meetme'}-$server|$hash_temporal{'Channel'}";
        push @return,
          "$canalsinses|meetmeuser|$hash_temporal{'Usernum'},$hash_temporal{'Meetme'}|YYYY-$server|$hash_temporal{'Channel'}";

        if (defined($hash_temporal{"Total"}))
        {
            $datos{"$canal-$server"}{"Count"} = $hash_temporal{"Total"};
            log_debug("$debugh pongo DATOS de ($canal-$server) {count} en $hash_temporal{'Total'}", 64);
        }

        $barge_rooms{"$canal"} = $datos{"$canal-$server"}{"Count"};

        if (defined($datos{"$canal-$server"}{"Count"}))
        {
            if ($datos{"$canal-$server"}{"Count"} > 1) { $plural = "s"; }
            $texto =
                $datos{"$canal-$server"}{"Count"}
              . " member$plural on conference ["
              . $datos{"$canal-$server"}{"Count"}
              . " Member$plural].";
        }

        if (exists($start_muted{"$server^$canalsinses"}))
        {
            my $boton_con_contexto = $buttons{"$server^$canalsinses"};
            my $comando            = "Action: Command\r\n";
            $comando .= "ActionID: meetmemute$boton_con_contexto\r\n";
            $comando .= "Command: meetme mute $hash_temporal{'Meetme'} $hash_temporal{'Usernum'}\r\n\r\n";
            send_command_to_manager($comando, $socket);
            delete $start_muted{"$server^$canalsinses"};
        }

        $unico_id = $canal . "-" . $server;
        push @return, "$canal|$estado_final|$texto|$unico_id|$canalid";
        $evento = "";

    }

    if ($evento eq "meetmeleave")
    {
        $canal = $hash_temporal{"Meetme"};
        $canal =~ tr/a-z/A-Z/;
        $estado_final = "ocupado9";    # 9 for meetme
        my $plural = "";
        $datos{"$canal-$server"}{"Count"}--;
        log_debug("$debugh pongo DATOS ( $canal-$server) (count) en $datos{\"$canal-$server\"}{'Count'} leave", 64);
        $barge_rooms{"$canal"} = $datos{"$canal-$server"}{"Count"};
        if ($datos{"$canal-$server"}{"Count"} > 1)  { $plural       = "s"; }
        if ($datos{"$canal-$server"}{"Count"} <= 0) { $estado_final = "corto"; }
        $texto =
            $datos{"$canal-$server"}{"Count"}
          . " member$plural on conference ["
          . $datos{"$canal-$server"}{"Count"}
          . " member$plural].";
        $unico_id = $canal . "-" . $server;
        push @return, "$canal|$estado_final|$texto|$unico_id|$canalid";
        my $canaleja = $hash_temporal{"Channel"};
        delete $auto_conference{$canaleja};
        log_debug("$debugh Erasing auto_conference $canaleja", 16);

        for $quehay (keys %auto_conference)
        {
            log_debug("$debugh Remaining conferences: $quehay", 16);
        }

        my ($canal1, $nada1) = separate_session_from_channel($canaleja);
        push @return, "$canal1|unsetlink|$canal|$unico_id|$canalid";
        $evento = "";
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
        $unico_id = "$canal-$server";
        push @return, "$canal|$estado_final|$texto|$unico_id|$canalid";
        $evento = "";
    }

    if ($evento eq "voicemail")
    {
        my @canalesvoicemail = ();

        while (my ($ecanal, $eextension) = each(%mailbox))
        {
            if ($eextension eq $hash_temporal{"Mailbox"})
            {
                $canal = $ecanal;
                $canal =~ s/(.*)\&(.*)/$1/g;    # Remove &context
                $canal =~ s/(.*)\^(.*)/$2/g;    # Remove Server
                push @canalesvoicemail, $canal;
            }
        }
        foreach my $canal (@canalesvoicemail)
        {
            $unico_id = $canal;
            $canalid  = $canal . "-XXXX";
            if (defined($hash_temporal{"Waiting"}))
            {
                $estado_final = "voicemail";
                $texto        = $hash_temporal{"Waiting"};
                if ($texto eq "1")
                {

                    # If it has new voicemail, ask for mailboxcount
                    send_command_to_manager("Action: MailboxCount\r\nMailbox: $hash_temporal{'Mailbox'}\r\n\r\n",
                                            $socket);
                }
            }
            else
            {
                $estado_final = "voicemailcount";
                my $nuevos = $hash_temporal{"NewMessages"};
                my $viejos = $hash_temporal{"OldMessages"};
                $texto   = "New $nuevos, Old $viejos";
                $canalid = $canal . "-XXXX";
            }
            push @return, "$canal|$estado_final|$texto|$unico_id-$server|$canalid";
        }
        $evento = "";
    }

    if ($evento eq "link")
    {
        my $uniqueid1 = $hash_temporal{"Uniqueid1"};
        my $uniqueid2 = $hash_temporal{"Uniqueid2"};
        if ($uniqueid1 !~ /-\d+$/)
        {
            $uniqueid1 .= "-" . $server;
        }
        if ($uniqueid2 !~ /-\d+$/)
        {
            $uniqueid2 .= "-" . $server;
        }
        my $channel1 = $hash_temporal{"Channel1"};
        my $channel2 = $hash_temporal{"Channel2"};
        log_debug("$debugh DATOS de $uniqueid1 { link } en $channel2", 64);
        log_debug("$debugh DATOS de $uniqueid2 { link } en $channel1", 64);
        $datos{$uniqueid1}{"Link"} = $channel2;
        $datos{$uniqueid2}{"Link"} = $channel1;
        my ($canal1, $sesion1) = separate_session_from_channel($channel1);
        my ($canal2, $sesion2) = separate_session_from_channel($channel2);

        #        push @return, "$canal1|link|$canal2|$uniqueid2|${canal1}-XXXX";
        #        delete $datos{$unico_id};
        #	 	print "3 BORRO datos $unico_id \n";
        $evento       = "";
        $canal        = $canal1;
        $estado_final = "ocupado7";    # 7 for linked channel, start billing

        if (exists($parked{"$server^$channel1"}))
        {
            log_debug("$debugh EXISTE parked{$server^$channel1} ", 128);
            my $parkexten = $parked{"$server^$channel1"};
            delete $parked{"$server^$channel1"};
            push @return, "PARK/$parkexten|corto||YYYY-$server|$channel1";
            push @return, "$canal1|ocupado5||$uniqueid1|$channel1";
        }
        else
        {
            log_debug("$debugh NO EXISTE parked{$server^$channel1}", 128);
        }

        if (exists($parked{"$server^$channel2"}))
        {
            log_debug("$debugh EXISTE parked{$server^$channel2} ", 128);
            log_debug("$debugh SI EXISTE!",                        128);
            my $parkexten = $parked{"$server^$channel2"};
            delete $parked{"$server^$channel2"};
            push @return, "PARK/$parkexten|corto||YYYY-$server|$channel2";
            push @return, "$canal2|ocupado5||$uniqueid2|$channel2";
        }
        else
        {
            log_debug("$debugh NO EXISTE parked{$server^$channel2}", 128);
        }
        my $clid1 = "";
        my $clid2 = "";

        if (defined($datos{$uniqueid2}{"Callerid"}))
        {
            $clid1 = $datos{$uniqueid2}{"Callerid"};
        }
        if (defined($datos{$uniqueid2}{"CallerID"}))
        {
            $clid1 = $datos{$uniqueid2}{"CallerID"};
        }
        if (defined($datos{$uniqueid1}{"Callerid"}))
        {
            $clid2 = $datos{$uniqueid1}{"Callerid"};
        }
        if (defined($datos{$uniqueid1}{"CallerID"}))
        {
            $clid2 = $datos{$uniqueid1}{"CallerID"};
        }

        if ($clid1 ne "" && $channel2 ne "")
        {
            push @return, "$canal1|settext|$clid1|$uniqueid1|$channel1";
        }
        if ($clid2 ne "" && $channel1 ne "")
        {
            push @return, "$canal2|settext|$clid2|$uniqueid2|$channel2";
        }
        push @return, "$canal1|setlink|$channel2|$uniqueid1|$channel1";
        push @return, "$canal2|setlink|$channel1|$uniqueid2|$channel2";
        $evento = "";    #NEW
    }

    if ($evento eq "unlink")
    {
        my $uniqueid1 = $hash_temporal{"Uniqueid1"};
        my $uniqueid2 = $hash_temporal{"Uniqueid2"};
        my $channel1  = $hash_temporal{"Channel1"};
        my $channel2  = $hash_temporal{"Channel2"};
        my ($canal1, $sesion1) = separate_session_from_channel($channel1);
        my ($canal2, $sesion2) = separate_session_from_channel($channel2);

        log_debug("$debugh Unlink $canal1 and $canal2", 16);
        $evento = "";

        $estado_final = "unsetlink";
        $canal        = $canal1;

        my $boton1 = 0;
        my $boton2 = 0;

        for my $mnroboton (keys %sesbot)
        {
            foreach my $msesion (@{$sesbot{$mnroboton}})
            {
                if ($msesion eq $channel1)
                {
                    $boton1 = $mnroboton;
                }
                if ($msesion eq $channel2)
                {
                    $boton2 = $mnroboton;
                }
            }
        }

        #push @return, "$boton1|unsetlink|$boton2|$uniqueid1|$channel2";
        #push @return, "$boton2|unsetlink|$boton1|$uniqueid2|$channel1";
        push @return, "$canal1|unsetlink|$channel2|$uniqueid1-$server|$channel1";
        push @return, "$canal2|unsetlink|$channel1|$uniqueid2-$server|$channel2";
        $evento = "";    #NEW
    }

    if ($evento eq "rename")
    {
        my $nuevo_nombre = "";
        my $viejo_nombre = "";
        log_debug("$debugh RENAME Event", 16);
        $evento = "";
        while (($key, $val) = each(%hash_temporal))
        {
            if ($key =~ /newname/i)
            {
                $nuevo_nombre = $val;
            }
            if ($key =~ /oldname/i)
            {
                $viejo_nombre = $val;
            }
        }
        log_debug("$debugh RENAME $viejo_nombre por $nuevo_nombre (id $unico_id)", 16);

        # Directamente borra la sesion que se debe renombrar
        if (($nuevo_nombre !~ /</) && ($viejo_nombre !~ /</))
        {
            my @final = ();
            for my $mnroboton (keys %sesbot)
            {
                @final = ();
                foreach my $msesion (@{$sesbot{$mnroboton}})
                {
                    if ($msesion ne $viejo_nombre)
                    {
                        push @final, $msesion;
                    }
                    else
                    {
                        log_debug("$debugh RENAME quito de sesbot ($mnroboton) el valor $msesion", 16);

                        #push @final, $nuevo_nombre;
                        #print "RENAME agrego a sesbot ($mnroboton) el valor $nuevo_nombre\n";
                    }
                }
                $sesbot{$mnroboton} = [@final];
            }

            for my $mnroboton (keys %linkbot)
            {
                @final = ();
                foreach my $msesion (@{$linkbot{$mnroboton}})
                {
                    log_debug("$debugh RENAME iteracion cada linkbot($mnroboton)", 32);
                    if ($msesion ne $viejo_nombre)
                    {
                        push @final, $msesion;
                        log_debug("$debugh RENAME dejo $msesion en linkbot($mnroboton)", 32);
                    }
                    else
                    {
                        &print_sesbot(20);
                        log_debug("$debugh RENAME viejo $viejo_nombre no va, en realidad va $nuevo_nombre", 32);
                        push @final, $nuevo_nombre;
                        $estado_final = "setlink";
                        my $botoncambiado = $buttons{"$mnroboton"};
                        log_debug("$debugh RENAME buttons($mnroboton)",       32);
                        log_debug("$debugh RENAME sesbot($botoncambiado)[0]", 32);
                        my $canalcambiado = $sesbot{$botoncambiado}[0];
                        my ($canalito, $nada) = separate_session_from_channel($canalcambiado);
                        push @return, "$canalito|$estado_final|$nuevo_nombre|$unico_id|$canalcambiado";
                        ($canalito, $nada) = separate_session_from_channel($nuevo_nombre);
                        push @return, "$canalito|$estado_final|$canalcambiado|$unico_id|$nuevo_nombre";
                        $canal = $canalito;
                    }
                }
                $linkbot{$mnroboton} = [@final];
            }
        }

        for $quehay (keys %datos)
        {
            while (($key, $val) = each(%{$datos{$quehay}}))
            {
                if (($key eq "Channel") && ($val eq $viejo_nombre))
                {
                    $datos{"$quehay"}{"$key"} = $nuevo_nombre;
                    log_debug("$debugh POPULATES datos($quehay){ $key } = $nuevo_nombre", 32);
                }
            }
        }
        &print_sesbot(3);
        $evento = "";    #NEW
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
        $canalid = $canal . "-XXXX";
        push @return, "$canal|$estado_final|$texto|$unico_id|$canalid";
        $evento = "";
    }

    if ($evento eq "status")
    {
        $evento = "";
    }

    if ($evento eq "statuscomplete")
    {

        # When done with the status retrieval, generate events to send to
        # flash clients. Do it only when finishing receiving status from
        # all asterisk servers monitored

        my @ids = ();

        if (keys(%datos))
        {
            my $hay_activos = 0;

            for (keys %datos)
            {
                my @pedazote = split(/-/, $_);
                my $current_server = $pedazote[1];
                if ("$server" ne "$current_server")
                {
                    next;
                }

                log_debug("$debugh STATUSCOMPLETE datos { $_ }", 128);
                push @ids, $_;
                my $myevent = "Newexten";
                while (my ($key, $val) = each(%{$datos{$_}}))
                {
                    log_debug("$debugh STATUSCOMPLETE datos { $key } = $val", 128);

                    if (defined($val))
                    {
                        $hay_activos = 1;
                        $fake_bloque[$fakecounter]{$key} = $val;
                        if ($key eq "Extension")
                        {
                            $myevent = "Ring";
                            $fake_bloque[$fakecounter]{'Origin'} = "true";
                        }
                        if ($key eq "Channel")
                        {
                            if ($timeouts{$val})
                            {
                                $datos{$_}{"Timeout"} = $timeouts{$val};
                            }
                        }
                    }
                }
                if ($hay_activos == 1)
                {
                    $fake_bloque[$fakecounter]{"Event"} = $myevent;
                    log_debug("$debugh fake bloque de $fakecounter (evento) lo pongo en $myevent", 128);
                    $fakecounter++;
                }
            }
        }
        else
        {
            log_debug("$debugh En statuscomplete datos esta vacio!", 64);
        }

        foreach my $valor (@ids)
        {
            log_debug("$debugh foreach statuscomplete $valor", 32);
            if (exists($datos{"$valor"}))
            {
                if (exists($datos{"$valor"}{"Link"}))
                {
                    log_debug("$debugh datos de $valor tiene defined Link genero un Fake bloque", 128);

                    my $channel1 = $datos{$valor}{"Channel"};
                    my $channel2 = $datos{$valor}{"Link"};
                    my $unique1  = $datos{$valor}{"Uniqueid"};
                    my $unique2  = find_uniqueid($channel2, $server);

                    $fake_bloque[$fakecounter]{"Event"}     = "Link";
                    $fake_bloque[$fakecounter]{"Channel1"}  = $channel1;
                    $fake_bloque[$fakecounter]{"Channel2"}  = $channel2;
                    $fake_bloque[$fakecounter]{"Uniqueid1"} = $unique1;
                    $fake_bloque[$fakecounter]{"Uniqueid2"} = $unique2;

                    while (my ($quey, $vail) = each(%{$fake_bloque[$fakecounter]}))
                    {
                        log_debug("$debugh FAKEBLOQUE contiene $quey = $vail", 128);
                    }

                    $fakecounter++;
                }
                if (exists($datos{"$valor"}{"Timeout"}))
                {
                    my $calltimeout = $datos{"$valor"}{"Timeout"} - time();
                    $fake_bloque[$fakecounter]{"ActionID"} = "timeout|$datos{$valor}{'Channel'}|$calltimeout";
                    $fakecounter++;
                }
            }
        }

        $evento = "";    #NEW (estaba comentado)
    }

    if ($evento eq "fakeismeetmemember")
    {
        my @bot1 = ();
        my $bot2 = 0;
        $estado_final = "meetmeuser";
        $texto        = $hash_temporal{"Usernum"} . "," . $hash_temporal{"Meetme"};
        my ($chan1, $nada1) = separate_session_from_channel($hash_temporal{'Channel'});
        push @return, "$hash_temporal{'Meetme'}|setlink|$hash_temporal{'Channel'}||$hash_temporal{'Channel'}";
        push @return, "$chan1|setlink|$hash_temporal{'Meetme'}||$hash_temporal{'Channel'}";
        $evento = "";    #NEW
    }

    if ($evento eq "newexten")
    {

        # If its a new extension without state, defaults to 'Up'
        if (!defined($datos{$unico_id}{'State'}) && $fill_datos)
        {
            $datos{$unico_id}{'State'} = "Up";
            log_debug("$debugh POPULATES datos($unico_id){ State } = Up", 128);
        }

        # If its a parked channel, set the PARK button to 'Down'
        if (exists($parked{"$server^$canalid"}))
        {
            log_debug("$debugh EXISTE parked{$server^$canalid}", 128);
            my $parkexten = $parked{"$server^$canalid"};
            delete $parked{"$server^$canalid"};
            push @return, "PARK/$parkexten|corto||YYYY-$server|$canalid";
        }
        else
        {
            log_debug("$debugh NO EXISTE parked{$server^$canalid}", 128);
        }
    }

    if ($evento eq "hangup")
    {
        if (exists($datos{$unico_id}))
        {
            $datos{$unico_id}{'State'} = "Down";
            log_debug("$debugh POPULATES datos($unico_id){ State } = down", 128);
        }
        else
        {
            $hash_temporal{'State'} = "Down";
        }

        # Look if the channel was parked and clear that button too
        if (exists($parked{"$server^$canalid"}))
        {
            log_debug("$debugh 2 EXISTE parked{$server^$canalid}", 128);
            my $parkexten = $parked{"$server^$canalid"};
            delete $parked{"$server^$canalid"};
            push @return, "PARK/$parkexten|corto||YYYY-$server|$canalid";
        }
        else
        {
            log_debug("$debugh NO EXISTE parked{$server^$canalid}", 128);
        }
    }

    if ($evento eq "parkedcall")
    {
        $texto        = "[Parked on " . $hash_temporal{'Exten'} . "]";
        $estado_final = "ocupado3";
        my ($canal, $nada) = separate_session_from_channel($hash_temporal{'Channel'});
        my $textid   = "";
        my $timeout  = "";
        my $unidchan = find_uniqueid($hash_temporal{'Channel'}, $server);
        $textid = $datos{$unidchan}{'Callerid'}
          if (defined($datos{$unidchan}{'Callerid'}));
        $textid = $datos{$unidchan}{'CallerID'}
          if (defined($datos{$unidchan}{'CallerID'}));
        $timeout = "(" . $hash_temporal{'Timeout'} . ")";
        $textid =~ s/\"//g;
        $textid =~ s/\<//g;
        $textid =~ s/\>//g;
        push @return, "$canal|ocupado3|$texto|$unidchan|$canalid";
        push @return,
          "PARK/$hash_temporal{'Exten'}|park|[$textid]$timeout|$hash_temporal{'Timeout'}-$server|$hash_temporal{'Channel'}";

        log_debug("$debugh pongo parked($server^$hash_temporal{'Channel'}) en $hash_temporal{'Exten'}", 64);
        $parked{"$server^$hash_temporal{'Channel'}"} = $hash_temporal{'Exten'};
        $evento = "";    #NEW
    }

    if ($evento eq "newcallerid")
    {
        $estado_final = "ocupado";
        $state        = "Newcallerid";
        my $save_clidnum  = "";
        my $save_clidname = "";
        if (defined($hash_temporal{'CallerIDName'}))
        {
            $save_clidnum  = $hash_temporal{'CallerID'};
            $save_clidname = $hash_temporal{'CallerIDName'};
        }
        elsif (defined($hash_temporal{'CalleridName'}))
        {
            $save_clidnum  = $hash_temporal{'Callerid'};
            $save_clidname = $hash_temporal{'CalleridName'};
        }
        else
        {
            ($save_clidnum, $save_clidname) = split_callerid($hash_temporal{'CallerID'});
        }
        $saved_clidnum{"$server^$hash_temporal{'Channel'}"}  = $save_clidnum;
        $saved_clidname{"$server^$hash_temporal{'Channel'}"} = $save_clidname;
    }

    # From now on, we only look for the State of the datos block
    # Dont check for $evento bellow this line!

    if ($evento ne "")
    {
        log_debug("$debugh Event $evento, canal '$canal'", 16);

        # De acuerdo a los datos de la extension genera
        # la linea con info para el flash

        $elemento = $canalid;

        if (exists($datos{$unico_id}))
        {
            if (exists($datos{$unico_id}{'Channel'}))
            {
                $elemento = $datos{$unico_id}{'Channel'};

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
            }
        }
        ($canal, $sesion) = separate_session_from_channel($elemento);

        if (defined($canal))
        {
            $canal =~ tr/a-z/A-Z/;
        }
        else
        {
            log_debug("$debugh canal not defined!! END $elemento", 16);

            while (my ($key, $val) = each(%hash_temporal))
            {
                log_debug("$debugh hash_temporal $key = $val", 128);
            }
            return;
        }

        if (defined($sesion))
        {
            log_debug("$debugh canal $canal sesion $sesion", 128);
        }

        if (exists($datos{$unico_id}))
        {

            log_debug("$debugh EXISTE datos($unico_id) ", 32);

            if (exists($datos{$unico_id}{'Extension'}))
            {
                $exten = $datos{$unico_id}{'Extension'};
            }

            if (exists($datos{$unico_id}{'State'}))
            {
                log_debug("$debugh EXISTE datos($unico_id){state}", 32);
                $state = $datos{$unico_id}{'State'};
            }

            if (exists($datos{$unico_id}{'Callerid'}))
            {
                $clid = $datos{$unico_id}{'Callerid'};
            }

            if (exists($datos{$unico_id}{'CallerID'}))
            {
                $clid = $datos{$unico_id}{'CallerID'};
            }
            if ($clid ne "")
            {
                ($clidnum, $clidname) = split_callerid($clid);
            }
            if (exists($datos{$unico_id}{'CallerIDName'}))
            {
                $clidname = $datos{$unico_id}{'CallerIDName'};
            }

            # The ones below are for catching the callerid from the
            # Dial event on CVS-HEAD
            if (exists($remote_callerid{"$server^$canalid"}))
            {
                $clidnum = $remote_callerid{"$server^$canalid"};
            }
            if (exists($remote_callerid_name{"$server^$canalid"}))
            {
                $clidname = $remote_callerid_name{"$server^$canalid"};
            }
        }
        else
        {
            log_debug("$debugh NO EXISTE datos($unico_id)", 32);
        }

        if ($state eq "")
        {
            if (defined($hash_temporal{'State'}))
            {
                $state = $hash_temporal{'State'};
            }
            else
            {
                $state = "";
            }
        }

        my $clid_with_format = format_clid($clidnum, $clid_format);

        if ($state eq "Ringing")
        {
            if ($clidnum ne "")
            {
                my $base64_clidnum  = encode_base64($clidnum . " ");
                my $base64_clidname = encode_base64($clidname . " ");
                my $ret             = "$canal|clidnum|$base64_clidnum|$unico_id|$hash_temporal{'Channel'}";
                push @return, $ret;
                $ret = "$canal|clidname|$base64_clidname|$unico_id|$hash_temporal{'Channel'}";
                push @return, $ret;
            }
        }

        if ($state eq "Rsrvd")
        {
            $state = "Ring";
        }

        if ($state eq "Ring")
        {
            $texto                      = "Originating call ";
            $estado_final               = "ring";
            $datos{$unico_id}{'Origin'} = "true";
            log_debug("$debugh POPULATES datos($unico_id){ Origin } = true", 128);
        }

        if ($state eq "Dialing")
        {
            $texto        = "Dialing";
            $estado_final = "ring";
            if ($canal =~ /^OH323/)
            {

                # OH323 use a random channel name when Dialing
                # that later changes its name. So we just discard
                # this event/name to avoid getting Dialing channels
                # foerever (because we never receive a Down status)
                $estado_final = "";
            }
        }

        if ($state =~ /^UNK/)
        {
            $texto        = "No registrado";
            $estado_final = "noregistrado";
            $unico_id     = "YYYY-$server";
            if ($canalid !~ /(.*)-XXXX$/)
            {
                $canalid = $canalid .= "-XXXX";
            }
        }

        if ($state =~ /^UNR/)
        {
            $texto        = "No alcanzable";
            $estado_final = "unreachable";
            $unico_id     = "YYYY-$server";
            if ($canalid !~ /(.*)-XXXX$/)
            {
                $canalid = $canalid .= "-XXXX";
            }
        }

        if ($state =~ /^Unm/)
        {
            $texto        = "Registrado";
            $estado_final = "registrado";
            $unico_id     = "YYYY-$server";
            if ($canalid !~ /(.*)-XXXX$/)
            {
                $canalid = $canalid .= "-XXXX";
            }
        }

        if ($state =~ /^OK/)
        {
            $texto        = "Registrado";
            $estado_final = "registrado";
            $unico_id     = "YYYY-$server";
            if ($canalid !~ /(.*)-XXXX$/)
            {
                $canalid = $canalid .= "-XXXX";
            }
        }

        if ($state eq "Newcallerid")
        {
            $texto = "Incoming call from [" . $clid_with_format . "] " . $enlazado;
        }

        if ($state eq "Ringing")
        {
            $texto        = "Incoming call from [" . $clid_with_format . "] " . $enlazado;
            $estado_final = "ringing";
        }

        if ($state eq "Down")
        {

            if ($evento eq "hangup")
            {

                # Solo nos interesa mandar a flash los down por hangup
                # para evitar enviar eventos redundantes
                $estado_final = "corto";
                if ($agent_status == 1)
                {
                    if (exists($agents{"$server^$canal"}) || exists($reverse_agents{$canal}))
                    {
                        print
                          "Existe agents{$server^$canal) $agents{\"$server^$canal\"}, o reverse_agents($canal) $reverse_agents{$canal}\n";
                        push @return, "$canal|isagent|0|$unico_id|$canalid";
                    }
                }
            }
            delete $timeouts{$canalid};
            delete $remote_callerid{"$server^$canalid"};
            delete $remote_callerid_name{"$server^$canalid"};
            delete $saved_clidname{"$server^$canalid"};
            delete $saved_clidnum{"$server^$canalid"};

            #erase_instances_for_trunk_buttons($canalsesion);
        }

        my $exclude    = 0;
        my $exten_clid = "";
        if ($state eq "Up")
        {
            if ($exten ne "")
            {
                if (is_number($exten))
                {
                    $exten_clid = $exten;
                    if (defined($saved_clidnum{"$server^$canalid"}))
                    {
                        $exten_clid = format_clid($saved_clidnum{"$server^$canalid"}, $clid_format);
                    }
                    if ($clid_privacy)
                    {
                        $exten_clid = "n/a";
                    }
                    $conquien = "[" . $exten_clid . "]";
                }
                else
                {
                    if (length($exten) == 1)
                    {
                        $conquien = $exten;
                        $exclude  = 1;        # We ignore events that have letter extensions 's', 'h', etc
                        log_debug("$debugh CLID is not a number! $exten", 16);
                    }
                    else
                    {
                        $conquien = $exten;
                    }
                }
            }
            else
            {
                $conquien = $clid_with_format;
            }

            if (defined($hash_temporal{'Seconds'}))
            {

                # $conquien .= " (" . $hash_temporal{'Seconds'} . ")";
                push @return, "$canal|settimer|$hash_temporal{'Seconds'}|$unico_id|$canalid";
                push @return, "$canal|state|busy|$unico_id|$canalid";

            }

            if (defined($datos{$unico_id}{'Origin'}))
            {
                if ($datos{$unico_id}{'Origin'} eq "true")
                {
                    if ($exclude == 1)
                    {
                        $texto = "skip";
                    }
                    else
                    {

                        # $texto = "Calling $conquien - $enlazado";
                        $texto = "Calling [$exten_clid]";
                    }
                    $estado_final = "ocupado2";    # 2 for origin button
                }
            }
            else
            {

                # $texto = "Incoming call from $conquien - $enlazado";
                $texto        = "Incoming call from [$conquien]";
                $estado_final = "ocupado1";                         # 1 for destination button
            }
        }

        # Remove special character from Caller ID string
        $texto =~ s/\"/'/g;
        $texto =~ s/</[/g;
        $texto =~ s/>/]/g;
        $texto =~ s/\|/ /g;

        push @return, "$canal|$estado_final|$texto|$unico_id|$canalid";

    }

    my %seen = ();
    my @uniq =
      grep { !$seen{$_}++ } @return;
    @return = @uniq;

    foreach (@return)
    {
        log_debug("$debugh returns $_", 16);
    }
    return @return;

}

sub digest_event_block
{
    my $bleque     = shift;
    my $tipo       = shift;
    my $socket     = shift;
    my @blique     = @$bleque;
    my @respuestas = ();
    my $canal      = "";
    my $quehace    = "";
    my $dos        = "";
    my $uniqueid   = "";
    my $canalid    = "";
    my $quehay     = "";
    my @mensajes   = ();
    my $interno    = "";
    my $todo       = "";
    my @mensajefinal;
    my $cuantas;
    my $server = 0;
    my %cambiaron;
    my $debugh = "** DIGEST_EVENT:";

    log_debug("$debugh START SUB ($tipo)", 32);

    @fake_bloque = ();
    delete $datos{""};
    foreach my $blaque (@blique)
    {
        @mensajes = procesa_bloque($blaque, $socket);
        foreach my $mensaje (@mensajes)
        {
            if (defined($mensaje) && $mensaje ne "")
            {
                log_debug("$debugh GOT $mensaje", 32);
                delete $datos{""};    # Erase the hash with no uniqueid
                ($canal, $quehace, $dos, $uniqueid, $canalid) =
                  split(/\|/, $mensaje);
                if ($dos eq "skip")
                {
                    log_debug("$debugh SALTEO $mensaje", 32);
                    next;
                }
                log_debug("$debugh canal $canal quehace $quehace dos $dos", 16);
                log_debug("$debugh Uniqueid $uniqueid Canalid $canalid",    16);

                if (!defined($canal))   { $canal   = ""; }
                if (!defined($quehace)) { $quehace = ""; }
                if (!defined($dos))     { $dos     = ""; }
                $canalid =~ s/\s+//g;             # Removes whitespace from CHANNEL-ID
                $canalid =~ s/(.*)<(.*)>/$1/g;    # discards ZOMBIE or MASQ

                if ($canal =~ /^vpb\//i)
                {

                    # For vpb channels, we fake a session number
                    $canal = $canalid;
                    $canal =~ tr/a-z/A-Z/;
                    $canalid = $canalid .= "-VPB1";
                }

                $server = $uniqueid;
                $server =~ s/(.*)-(.*)/$2/g;

                log_debug("$debugh Quehace $quehace", 64);

                my $buttontext = $dos;

                if ($buttontext =~ /\Q[\E/)
                {
                    $buttontext =~ s/.*\Q[\E(.*)\Q]\E.*/$1/g;
                }
                else
                {
                    $buttontext = "";
                }

                my @canaleja = find_panel_buttons($canal, $canalid, $server);
                my $cuantos  = @canaleja;

                if ($quehace eq "corto" || $quehace eq "info")
                {

                    # We collect the last state of the channel on hangup
                    while (my ($key, $val) = each(%{$datos{$uniqueid}}))
                    {
                        $todo .= "$key = $val\n"
                          if ($key ne "E") && (defined($val));
                        log_debug("$debugh \tAgrego $key = $val", 128);
                    }
                    $todo .= " ";
                    $todo = encode_base64($todo);

                    delete $datos{$uniqueid};
                    log_debug("$debugh erasing datos{$uniqueid}", 128);
                }

                foreach $canal (@canaleja)
                {
                    log_debug("$debugh LOOP por canaleja es el turno de $canal", 16);

                    if (!defined($buttons{"$server^$canal"}))
                    {
                        log_debug("$debugh \tNo tengo botones para $server^$canal, END FUNCTION", 128);
                        for (keys %buttons)
                        {
                            log_debug("$debugh \t\tKey $_", 128);
                        }
                        next;
                    }

                    # If its a REGEXP button, we have to ignore all events
                    # except ocupado*, corto, setlink and unsetlink
                    if ($canal =~ /^_/)
                    {
                        log_debug("$debugh canal $canal es un WILD y quehace vale $quehace", 32);

                        if (   $quehace =~ /registr/
                            || $quehace =~ /reacha/
                            || $quehace =~ /^inf/)
                        {
                            log_debug("$debugh IGNORO $quehace porque es un wildcard", 16);
                            next;
                        }

                        if (   $quehace !~ /^ocupado/
                            && $quehace !~ /^corto/
                            && $quehace !~ /^state/
                            && $quehace !~ /^settext/
                            && $quehace !~ /^setlabel/
                            && $quehace !~ /^setlink/
                            && $quehace !~ /^meetme/
                            && $quehace !~ /^ring/
                            && $quehace !~ /^settimer/
                            && $quehace !~ /^unsetlink/)
                        {
                            my ($nada, $elcontexto) = split(/\&/, $canal);
                            if (!defined($elcontexto)) { $elcontexto = ""; }
                            if ($elcontexto ne "")
                            {
                                $elcontexto = "&" . $elcontexto;
                            }
                            my ($canalsolo, $nrotrunk) = split(/=/, $canal);
                            $canal = $canalsolo . "=1" . $elcontexto;
                            log_debug("$debugh quehace=$quehace, elijo el 1ero del trunk $canal", 16);

                            #next;
                        }

                        # If we have a wildcard button with changelabel
                        # and change led_color (the 1 after changelabel)
                        # change it so to not change the led color.
                        if ($quehace =~ /changelabel1/)
                        {
                            log_debug("$debugh el wildcard tiene changelabel1, lo cambio por changelabel0!", 16);
                            $quehace = "changelabel0";
                        }
                    }

                    if ($canal ne "")
                    {
                        if ($quehace eq 'corto' || $quehace eq 'info')
                        {

                            my @linked = erase_all_sessions_from_channel($canalid, $canal, $server);
                            push @linked, $canal;
                            my $btnorinum = "";
                            foreach my $canaleje (@linked)
                            {
                                if ($canaleje =~ /\^/)
                                {
                                    $btnorinum = $buttons{$canaleje};
                                }
                                else
                                {
                                    $btnorinum = $buttons{"$server^$canaleje"};
                                }
                                log_debug("$debugh call GEN_LINKED 1", 16);
                                my $listabotones = generate_linked_buttons_list($canaleje, $server);
                                push @respuestas, "$btnorinum|linked|$listabotones";
                            }

                            delete $datos{$uniqueid};
                            log_debug("$debugh REMOVING datos { $uniqueid }", 16);
                        }

                        if ($quehace eq "setlink")
                        {
                            log_debug("$debugh IF quehace = SETLINK", 16);
                            my ($nada1, $contexto1) = split(/\&/, $canal);
                            if (!defined($contexto1)) { $contexto1 = ""; }
                            my $listabotones = "";

                            if (!defined(@{$linkbot{"$server^$canal"}}))
                            {
                                push @{$linkbot{"$server^$canal"}}, "";
                                pop @{$linkbot{"$server^$canal"}};
                                log_debug("$debugh DEFINIENDO linkbot ($server^$canal)", 16);
                            }

                            my ($canal1, $sesion1) = separate_session_from_channel($dos);
                            my @linkbotones = find_panel_buttons($canal1, $dos, $server);
                            foreach (@linkbotones)
                            {
                                my ($nada2, $contexto2) = split(/\&/, $_);
                                if (!defined($contexto2)) { $contexto2 = ""; }
                                if ($contexto1 eq $contexto2)
                                {
                                    push @{$linkbot{"$server^$canal"}}, $dos;
                                    log_debug("$debugh AGREGO a linkbot{ $server^$canal} el valor $dos", 16);
                                }
                            }

                            my %seen = ();
                            my @uniq =
                              grep { !$seen{$_}++ } @{$linkbot{"$server^$canal"}};
                            $linkbot{"$server^$canal"} = \@uniq;
                            foreach my $valorad (@uniq)
                            {
                                log_debug("$debugh linkbot ($server^$canal) = $valorad", 16);
                            }
                            my $btnorinum = $buttons{"$server^$canal"};
                            log_debug("$debugh llamo a GENERATE_LINKED", 16);
                            $listabotones = generate_linked_buttons_list($canal, $server);
                            push @respuestas, "$btnorinum|linked|$listabotones";
                            $botonlinked{$btnorinum} = $listabotones;
                            log_debug("$debugh linkeado con $listabotones", 16);
                            log_debug("$debugh ENDIF quehace = SETLINK",    16);
                        }

                        if ($quehace eq "unsetlink")
                        {
                            log_debug("$debugh IF quehace = UNSETLINK", 16);
                            my @final = ();
                            foreach my $msesion (@{$linkbot{"$server^$canal"}})
                            {
                                if ($msesion ne $dos)
                                {
                                    push @final, $msesion;
                                }
                            }
                            $linkbot{"$server^$canal"} = [@final];
                            log_debug("$debugh ENDIF quehace = UNSETLINK", 16);
                        }

                        $interno = $buttons{"$server^$canal"};
                        $interno = "" if (!defined($interno));

                        if ($interno eq "")
                        {
                            log_debug("$debugh NO HAY INTERNO buttons($server^$canal), ABORTO", 16);
                            next;
                        }
                        log_debug("$debugh EL INTERNO es $interno", 16);

                        if (!defined($laststatus{$interno}))
                        {
                            $laststatus{$interno} = "";
                        }
                        if (!defined($estadoboton{$interno}))
                        {
                            $estadoboton{$interno} = "";
                            $statusboton{$interno} = "";
                        }

                        # Mantains hash of arrays with sessions for each button number
                        # %sesbot{key}=value where:
                        #
                        # key is the button number (anything after '@' is the panel context)
                        # value is an array containing the sessions Eg: SIP/mary-43xZ
                        #
                        # The rename manager event also modifies this hash
                        #
                        # There are other hashes to maintain a 'view' of the status:
                        #
                        # %estadoboton{key}   = shows busy, free or ringing
                        # %statusboton{key}   = text status
                        #
                        if (
                            $canalid ne ""
                            && (   $canalid !~ /zombie/i
                                && $canalid !~ /(.*)-XXXX$/)
                           )
                        {

                            if ($quehace eq "corto")
                            {

                                log_debug("$debugh CORTO interno $interno canal $canal", 16);

                                delete $botonpark{$interno};
                                delete $botonmeetme{$interno};
                                delete $botonclid{$interno};
                                delete $botonlinked{$interno};
                                delete $botontimer{$interno};

                                my $canalbotonreverse = $buttons_reverse{$interno};

                                if ($canal =~ /^_/ && $ren_wildcard == 1)
                                {
                                    push @respuestas, "$interno|changelabel0|labeloriginal";
                                }

                                delete $linkbot{$interno};
                                delete $linkbot{$canalbotonreverse};

                                if (!defined($sesbot{$interno}))
                                {
                                    push @{$sesbot{$interno}}, "";
                                    pop @{$sesbot{$interno}};
                                }
                                my $cuantos = @{$sesbot{$interno}};
                                if ($cuantos == 0)
                                {
                                    log_debug(
                                        "$debugh CORTO y SE DESOCUPO estadoboton($interno) = free, sesbot($interno) esta vacio",
                                        16
                                    );
                                    $cambiaron{$interno}   = 1;
                                    $estadoboton{$interno} = "free";
                                    $statusboton{$interno} = $dos;
                                }
                                else
                                {
                                    log_debug(
                                        "$debugh CORTO y SIGUE OCUPADO estadoboton($interno) = busy, sesbot($interno) tiene algo",
                                        16
                                    );
                                    &print_sesbot(3);
                                    if ($laststatus{$interno} ne "busy|${buttontext}")
                                    {
                                        $cambiaron{$interno} = 1;
                                        log_debug(
                                            "$debugh Y es distinto al ultimo estado $laststatus{$interno} ne $estadoboton{$interno}",
                                            16
                                        );
                                    }
                                    $estadoboton{$interno} = "busy|${buttontext}";
                                }

                            }
                            else
                            {

                                # quehace no es "corto"
                                #

                                if (!defined(@{$sesbot{$interno}}))
                                {
                                    push @{$sesbot{$interno}}, "";
                                    pop @{$sesbot{$interno}};
                                }

                                push @{$sesbot{$interno}}, "$canalid";
                                log_debug("$debugh AGREGO a sesbot($interno) el valor $canalid", 16);
                                foreach my $vavi (@{$sesbot{$interno}})
                                {
                                    log_debug("$debugh sesbot($interno) tiene $vavi", 16);
                                }

                                if ($canal =~ /^_/ && $quehace =~ /^ring/)
                                {
                                    log_debug("$debugh TENGO UN WILDCARD ORIGINANDO LLAMADO! $canal $quehace $canalid",
                                              16);
                                    if ($quehace eq "ring")
                                    {

                                        # $quehace = "ocupado1";
                                    }
                                    if ($ren_wildcard == 1)
                                    {
                                        push @respuestas, "$interno|changelabel0|$canalid";
                                        $botonlabel{$interno} = $canalid;
                                    }
                                }

                                my %seen = ();
                                my @uniq =
                                  grep { !$seen{$_}++ } @{$sesbot{$interno}};
                                $sesbot{$interno} = [@uniq];

                                if ($quehace eq "ringing")
                                {
                                    if ($laststatus{$interno} ne "ringing|${buttontext}")
                                    {
                                        $cambiaron{$interno} = 1;
                                    }
                                    $estadoboton{$interno} = "ringing|${buttontext}";
                                    if ($dos =~ m/(.*)?\[(.*)\].*?/)
                                    {
                                        my $clidtext = $2;
                                        $botonclid{$interno} = $clidtext;
                                    }

                                }
                                elsif ($quehace =~ /^ocupado/)
                                {
                                    if ($laststatus{$interno} ne "busy|${buttontext}")
                                    {
                                        $cambiaron{$interno} = 1;
                                    }
                                    $estadoboton{$interno} = "busy|${buttontext}";
                                    $statusboton{$interno} = $dos;
                                }
                            }
                        }
                        else
                        {
                            log_debug("$debugh ATENCION canalid es $canalid, NO PROCESAR?", 16);
                        }

                        if ($quehace =~ /changelabel/)
                        {

                            # Mantains state of label and led
                            my $cambia_el_led = $quehace;
                            $cambia_el_led =~ s/changelabel//g;
                            $botonled{$interno}   = $cambia_el_led;
                            $botonlabel{$interno} = $dos;
                        }
                        if ($quehace eq "park")
                        {
                            $dos =~ m/(.*)\((.*)\)/;
                            my $texto   = $1;
                            my $timeout = $2;
                            $timeout = time() + $timeout;
                            $botonpark{$interno} = "$texto|$timeout";
                        }
                        if ($quehace eq "meetmeuser")
                        {
                            $botonmeetme{$interno} = $dos;
                        }

                        if ($quehace =~ /infoqstat/)
                        {
                            $botonqueue{$interno} = $dos;
                        }
                        elsif ($quehace =~ /info/)
                        {
                            my $cola = $quehace;
                            $cola =~ s/^info//g;
                            push @{$botonqueuemember{$interno}}, "$cola|$dos";
                        }

                        if ($quehace eq "settext")
                        {
                            $botontext{$interno} = $dos;
                            push @respuestas, "$interno|settext|$dos";
                        }
                        if ($quehace eq "setalpha")
                        {
                            $botonalpha{$interno} = $dos;
                            push @respuestas, "$interno|setalpha|$dos";
                        }
                        if ($quehace eq "flip")
                        {
                            push @respuestas, "$interno|flip|$dos";
                        }
                        if ($quehace eq "setlabel")
                        {
                            if (   $dos ne "."
                                && $dos ne "original"
                                && $dos ne "labeloriginal")
                            {
                                $botonlabel{$interno} = $dos;
                                push @respuestas, "$interno|setlabel|$dos";
                            }
                        }
                        if ($quehace eq "voicemail")
                        {
                            $botonvoicemail{$interno} = $dos;
                        }
                        if ($quehace eq "voicemailcount")
                        {
                            $botonvoicemailcount{$interno} = $dos;
                        }

                        # linkbot{key} hash mantains the list of linked channels
                        # for a button. key is the button number, the value is the
                        # channel-session, like SIP/jose-AxiD

                        if (   ($quehace !~ /^corto/)
                            && ($quehace !~ /^ocupado/)
                            && ($quehace !~ /link/))
                        {
                            $cambiaron{$interno} = 1;
                        }

                        if (!defined($sesbot{$interno}))
                        {
                            push @{$sesbot{$interno}}, "";
                            pop @{$sesbot{$interno}};
                        }

                        if (@{$sesbot{$interno}} > 0 && $quehace eq 'corto')
                        {
                            log_debug("$debugh Still busy...sesbot($interno) is not empty,  ignoring hangup", 16);
                        }
                        else
                        {

                            # This block is for the voicemail client
                            if ($quehace =~ "^voicemail")
                            {
                                my $canalsincontexto = $canal;
                                $canalsincontexto =~ s/(.*)&(.*)/$1/g;
                                push @mensajefinal, "$canalsincontexto\@$canalsincontexto|$quehace|$dos";
                            }

                            # This block is for the voicemail client, popups
                            if ($quehace =~ "^ringing")
                            {
                                my $canalsincontexto = $canal;
                                $canalsincontexto =~ s/(.*)&(.*)/$1/g;
                                my $calleridpop = $dos;
                                $calleridpop =~ s/(.*)\Q[\E(.*)/$2/g;
                                $calleridpop =~ s/\]//g;
                                $calleridpop =~ s/\s+//g;
                                push @mensajefinal, "$canalsincontexto\@$canalsincontexto|$quehace|$calleridpop";
                            }

                            my $quehace2 = $quehace;

                            if ($quehace2 eq "ring")
                            {

                                #$quehace2 = "ocupado";
                            }

                            next unless ($quehace2 ne "setlink");
                            next unless ($quehace2 ne "unsetlink");

                            if ($quehace2 !~ /isagent/)
                            {

                                # Discard events that we dont want to send
                                # to flash clients
                                # "isagent"
                                push @mensajefinal, "$interno|$quehace2|$dos";
                            }

                            if ($quehace2 =~ /ocupado/)
                            {
                                if ($dos =~ m/(.*)?\[(.*)\].*?/)
                                {
                                    my $clidtext = $2;
                                    $botonclid{$interno} = $clidtext;
                                }

                                #push @mensajefinal, "$interno|state|busy";
                                push @mensajefinal, "$interno|settimer|0\@UP";
                            }
                            if ($quehace2 eq "settimer")
                            {
                                $botontimer{$interno} = time() - $dos;
                            }
                            if ($quehace eq "ocupado1" || $quehace eq "ocupado9")
                            {
                                push @mensajefinal, "$interno|settimer|0\@UP";
                                if (!defined($botontimer{$interno}))
                                {
                                    $botontimer{$interno} = time();
                                }
                            }
                            if ($quehace2 =~ /^ring$/)
                            {
                                push @mensajefinal, "$interno|state|busy";

                                #push @mensajefinal, "$interno|settext|$dos";
                            }
                            if ($quehace2 =~ /corto/)
                            {

                                #push @mensajefinal, "$interno|state|free";
                                #push @mensajefinal, "$interno|settext|";
                                if ((exists($agents{"$server^$canal"}) || exists($reverse_agents{$canal}))
                                    && $agent_status == 1)
                                {
                                    push @mensajefinal, "$interno|settimer|0\@IDLE";
                                    push @mensajefinal, "$interno|settext|Idle";
                                    if ($laststatus{$interno} =~ /busy/)
                                    {
                                        request_queue_status($socket, $canalid);
                                    }
                                }
                                else
                                {
                                    push @mensajefinal, "$interno|settimer|0\@STOP";
                                    if (defined($boton_ip{"$canal-XXXX"}))
                                    {
                                        my $valip = $boton_ip{"$canal-XXXX"};
                                        push @mensajefinal, "$interno|settext|$valip";
                                        $botontext{$interno} = $valip;
                                    }
                                    else
                                    {
                                        $botontext{$interno} = "";
                                    }

                                    if (defined($botonalpha{$interno}))
                                    {
                                        if ($botonalpha{$interno} ne "")
                                        {
                                            push @mensajefinal, "$interno|setalpha|$botonalpha{$interno}";
                                        }
                                    }
                                }
                            }

                            if ($quehace eq "registrado")
                            {
                                if (defined($botonalpha{$interno}))
                                {
                                    if ($botonalpha{$interno} ne "")
                                    {
                                        push @mensajefinal, "$interno|setalpha|50";
                                    }
                                    else
                                    {
                                        push @mensajefinal, "$interno|setalpha|100";
                                    }
                                }
                            }

                            if ($quehace eq "registrado" || $quehace eq "noregistrado" || $quehace eq "unreachable")
                            {
                                $botonregistrado{$interno} = "$quehace|$dos";
                            }

                            log_debug("$debugh Agrego mensaje final $interno|$quehace2|$dos", 16);

                            #if (defined($mensajefinal) && $interno ne "")
                            $cuantas = @mensajefinal;
                            if ($cuantas > 0 && $interno ne "")
                            {
                                if (exists $cambiaron{$interno})
                                {

                                    log_debug("$debugh Existe cambiaron($interno) = $cambiaron{$interno}", 16);

                                    #push(@respuestas, $mensajefinal);
                                    foreach (@mensajefinal)
                                    {
                                        push @respuestas, $_;
                                    }
                                }
                                else
                                {

                                    log_debug("$debugh No existe cambiaron($interno)", 16);
                                    foreach (@mensajefinal)
                                    {

                                        # If the last status was not modified, avoid sending info
                                        # push @respuestas, $_;
                                    }
                                }
                                if ($todo ne "")
                                {
                                    my $otromensajefinal = "$interno|info|$todo";
                                    push(@respuestas, $otromensajefinal);
                                }
                            }
                        }

                        $laststatus{$interno} = $estadoboton{$interno};
                    }
                    else
                    {    # endif canal distinto de nada
                        log_debug("$debugh There is no command defined", 16);
                    }
                }
            }
        }
    }
    my %seen = ();
    my @uniq = grep { !$seen{$_}++ } @respuestas;
    @respuestas = @uniq;
    $cuantas    = @respuestas;
    log_debug("$debugh There are $cuantas commands to send to flash clients", 16);
    foreach my $valor (@respuestas)
    {
        log_debug("$debugh RET: $valor", 32);
    }
    return @respuestas;
}

sub nonblock
{
    my $socket = shift;
    my $flags;

    $flags = fcntl($socket, F_GETFL, 0)
      or die "Can't get flags for socket: $!\n";
    fcntl($socket, F_SETFL, $flags | O_NONBLOCK)
      or die "Can't make socket nonblocking: $!\n";
}

sub manager_connection
{
    my $host       = "";
    my $user       = "";
    my $pass       = "";
    my $debugh     = "** MANAGER CONNECTION";
    my $temphandle = "";
    my $contador   = 0;

    foreach my $mhost (@manager_host)
    {
        if (defined($mhost))
        {
            $host = $mhost;
            $user = $manager_user[$contador];
            $pass = $manager_secret[$contador];

            if (defined($manager_conectado[$contador]))
            {
                if ($manager_conectado[$contador] == 1)
                {
                    $contador++;
                    next;
                }
            }

            log_debug("$debugh Connecting to $mhost $contador", 1);

            $p[$contador] =
              new IO::Socket::INET->new(
                                        PeerAddr => $manager_host[$contador],
                                        PeerPort => 5038,
                                        Proto    => "tcp",
                                        Type     => SOCK_STREAM
                                       );

            if (!$p[$contador])
            {
                log_debug("$debugh Couldn't connect to $mhost (Server $contador). Retry in $poll_interval seconds", 1);
                $p[$contador]                 = "";
                $manager_conectado[$contador] = 0;
                $contador++;
                next;
            }
            else
            {
                log_debug("$debugh Connected to $mhost (Server $contador)", 1);
                $manager_conectado[$contador] = 1;
                $p[$contador]->autoflush(1);
                $ip_addy{$p[$contador]} = peerinfo($p[$contador], 1);
            }

            $manager_socket{$p[$contador]} =
              $manager_host[$contador] . "|" . $manager_user[$contador] . "|" . $manager_secret[$contador];
            my $command = "";

            if ($auth_md5 == 1)
            {
                $command = "Action: Challenge\r\n";
                $command .= "AuthType: MD5\r\n\r\n";
            }
            else
            {
                $command = "Action: Login\r\n";
                $command .= "Username: $user\r\n";
                $command .= "Secret: $pass\r\n\r\n";
            }
            send_command_to_manager($command, $p[$contador],, 1);
        }
        $contador++;
    }

    # Adds AMI handles into IO::Select
    foreach (@p)
    {
        if (defined($_))
        {
            $O->add($_);
        }
    }

}

sub clean_socket
{
    my $socket = shift;
    my $debugh = "** CLEAN SOCKET ";
    log_debug("$debugh connection lost removing socket $socket", 1);

    $O->remove($socket);
    $socket->close;
    delete $ip_addy{$socket};
    if (exists($manager_socket{$socket}))
    {
        delete $manager_queue{$socket};

        # The closed connections belong to an asterisk manager port
        my @partes  = split(/\|/, $manager_socket{$socket});
        my @pp      = ();
        my $counter = 0;
        foreach my $cual (@p)
        {
            if (defined($cual))
            {
                if ($cual eq $_)
                {
                    log_debug("$debugh Connection lost to server $counter", 1);
                    $manager_conectado[$counter] = 0;
                    delete $autenticado{$_};
                    push @pp, "";
                }
                else
                {
                    log_debug("$debugh still connected to $ip_addy{$cual}", 16);
                    push @pp, $cual;
                }
            }
            $counter++;
        }
        @p = @pp;
    }
    else
    {

        # The closed socket was from a client
        log_debug("$debugh flash client connection lost", 1);
        delete $client_queue{$socket};
        delete $client_queue_nocrypt{$socket};
        my $cualborrar = $socket;
        my @temp = grep(!/\Q$cualborrar\E/, @flash_clients);
        @flash_clients = @temp;
        delete($keys_socket{$socket});
        &print_clients();
    }
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
read_astdb_config();
genera_config();

# Tries to open the listening socket
$m = new IO::Socket::INET(Listen => 1, LocalPort => $listen_port, ReuseAddr => 1, Blocking => 0)
  or die "\nCan't listen to port $listen_port\n";
$O = new IO::Select();
$O->add($m);

# Connects to the asterisk boxes
manager_connection();

$/ = "\0";

# Endless loop
while (1)
{
    my $debugh = "** MAIN";

    while (@S = $O->can_read(0.001))
    {
        foreach (@S)
        {
            my $handle = $_;

            if ($_ == $m)
            {

                # New client connection
                my $C = $m->accept;
                $ip_addy{$C} = peerinfo($C, 1);
                log_debug("$debugh New client connection $ip_addy{$C}", 1);
                push(@flash_clients, $C);
                $O->add($C);
                nonblock($C);
                &print_clients();
            }
            else
            {

                # Its not a new client connection
                my %i;
                my $R;
                $R = sysread($_, $i{$handle}, 1);
                if (defined($R) && $R == 0)
                {

                    # Could not read.. close the socket
                    log_debug("$debugh closing $ip_addy{$_}", 1);
                    clean_socket($_);
                }
                else
                {
                    $bloque_completo{$handle} = ""
                      if (!defined($bloque_completo{$handle}));
                    $bloque_completo{$handle} .= $i{$handle};

                    next
                      if (   $bloque_completo{$_} !~ /\r\n\r\n/
                          && $bloque_completo{$_} !~ /\0/);

                    # From here we have a complete Event block
                    # to process

                    if (exists($manager_socket{$_}))
                    {
                        my @part = split(/\|/, $manager_socket{$_});
                        log_debug("$debugh End of block from $part[0]", 16);
                    }

                    $bloque_final = $bloque_completo{$handle};
                    $bloque_final =~ s/([^\r])\n/$1\r\n/g;    # Reemplaza \n solo por \r\n
                    $bloque_final =~ s/\r\n\r\n/\r\n/g;
                    $bloque_completo{$handle} = "";

                    # Add the asterisk server number as a part of the event block
                    my $que_manager = 0;
                    foreach my $handle_manager_connected (@p)
                    {
                        if ($handle_manager_connected eq $handle)
                        {

                            $bloque_final = $bloque_final . "Server: $que_manager";
                        }
                        $que_manager++;
                    }

                    ####################################################
                    # This block is just for logging in the event
                    # to stdout
                    if ($debug & 1)
                    {
                        my @lineas = split("\r\n", $bloque_final);
                        foreach my $linea (@lineas)
                        {
                            if (exists($manager_socket{$handle}))
                            {
                                my $linea_formato = sprintf("%-15s <- %s", $ip_addy{$handle}, $linea);
                                log_debug($linea_formato, 1);
                            }
                        }
                        $global_verbose = 'separator';
                    }
                    ##################################################

                    foreach my $C ($O->handles)
                    {
                        if ($C == $handle)
                        {
                            log_debug("$debugh AST event received...", 16);

                            # Asterisk event received
                            # Read the info and arrange it into blocks
                            # for processing in 'procesa_bloque'
                            if (   $bloque_final =~ /Event:/
                                || $bloque_final =~ /Message: Mailbox/
                                || $bloque_final =~ /Message: Timeout/)
                            {
                                log_debug("$debugh There's an 'Event' in the event block", 32);
                                my @lineas = split(/\r\n/, $bloque_final);
                                @bloque = ();
                                my $contador = -1;
                                foreach my $p (@lineas)
                                {
                                    my $my_event = "";
                                    if ($p =~ /Event:/)
                                    {
                                        $contador++;
                                        log_debug("$debugh Event detected contador = $contador", 128);
                                    }
                                    elsif ($p =~ /Message: Mailbox/)
                                    {
                                        $my_event = "MessageWaiting";    # Fake event
                                        $contador++;
                                        log_debug("$debugh Event mailbox detected contador = $contador", 128);
                                    }
                                    my ($atributo, $valor) = split(/: /, $p, 2);
                                    if (defined $atributo && $atributo ne "")
                                    {
                                        if ($my_event ne "")
                                        {
                                            $atributo = "Event";
                                            $valor    = $my_event;
                                            log_debug("$debugh Fake event generated $atributo=$valor", 128);
                                        }
                                        if (length($atributo) >= 1)
                                        {
                                            if ($contador < 0)
                                            {
                                                $contador = 0;
                                            }
                                            $bloque[$contador]{"$atributo"} = $valor;
                                        }
                                    }
                                }
                                log_debug("$debugh There are $contador blocks for processing", 128);
                                @respuestas = ();
                                log_debug("$debugh Answer block cleared", 32);
                                @respuestas = digest_event_block(\@bloque, "real", $C);
                                @masrespuestas = ();
                                while (@fake_bloque)
                                {
                                    my @respi = digest_event_block(\@fake_bloque, "fake", $C);
                                    foreach (@respi)
                                    {
                                        push @masrespuestas, $_;
                                    }
                                }
                            }
                            elsif ($bloque_final =~ /--END COMMAND--/)
                            {
                                log_debug("$debugh There's an 'END' in the event block", 32);
                                $todo .= $bloque_final;
                                process_cli_command($todo);
                                my $cuantos = @bloque;
                                log_debug("$debugh There are $cuantos blocks for processing", 128);
                                @respuestas = digest_event_block(\@bloque, "real", $C);
                                @masrespuestas = ();
                                while (@fake_bloque)
                                {
                                    my @respi = digest_event_block(\@fake_bloque, "fake", $C);
                                    foreach (@respi)
                                    {
                                        push @masrespuestas, $_;
                                    }
                                }
                                $todo = "";
                            }
                            elsif ($bloque_final =~ /<msg/)
                            {
                                log_debug("$debugh Processing command received from flash clients...", 32);
                                process_flash_command($bloque_final, $_);
                                @respuestas   = ();
                                $bloque_final = "";
                                $todo         = "";
                            }
                            elsif ($bloque_final =~ /Challenge:/)
                            {
                                my @lineas = split(/\r\n/, $bloque_final);
                                foreach my $p (@lineas)
                                {
                                    if ($p =~ /Challenge:/)
                                    {
                                        $p =~ s/^Challenge: (.*)/$1/g;
                                        $md5challenge = $p;
                                    }
                                }
                                manager_login_md5($md5challenge, $C);
                            }
                            elsif ($bloque_final =~ /Message: Authentication ac/)
                            {

                                # Authentication Accepted, enable sending commands
                                $autenticado{$C} = 1;
                                send_initial_status($C);
                            }
                            else
                            {
                                log_debug("$debugh There is no 'Event' nor 'End' in the block. Erasing block...", 32);

                                # No Event in the block. Lets clear it up...
                                @bloque = ();
                                $todo .= $bloque_final;
                            }
                        }
                        else
                        {

                            # Send messages to Flash clients
                            @respuestas = (@respuestas, @masrespuestas);
                            @masrespuestas = ();
                            my %seen = ();
                            my @uniq = grep { !$seen{$_}++ } @respuestas;
                            @respuestas = @uniq;
                            foreach my $valor (@respuestas)
                            {
                                my ($contextores, $nada1, $nada2) = split(/\|/, $valor);
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
                                    send_status_to_flash($C, $valor, 0);
                                }
                            }    # end foreach respuestas
                        }
                    }    # end foreach handles
                }    # end else the handle is readable
            }    # end else for active connections
        }    # end foreach @S -> can read
    }    # while can read

    foreach my $sacket ($O->can_write(0.001))
    {
        if (defined($client_queue{$sacket}))
        {

            # Loop through command buffer to send to  clients
            while (my $comd = shift @{$client_queue{$sacket}})
            {
                my $tolog = shift @{$client_queue_nocrypt{$sacket}};
                my $ret = actual_syswrite($sacket, $comd, "isclient", $tolog);
                if ($ret == -1)
                {
                    log_debug("Partial syswrite, buffering for $ip_addy{$sacket}", 1);
                    last;
                }

            }
        }
        if (defined($manager_queue{$sacket}))
        {

            # Loop through command buffer to send to managers
            while (my $comd = shift @{$manager_queue{$sacket}})
            {
                my $cuantos = @{$manager_queue{$sacket}};

                my $ret = actual_syswrite($sacket, $comd, "ismanager", $comd);
                if ($ret == -1)
                {
                    log_debug("Partial syswrite, buffering for $ip_addy{$sacket}", 1);
                    last;
                }

            }
        }
    }
}    # endless loop

sub actual_syswrite
{
    my ($socket, $encriptadofinal, $whom, $log) = @_;
    my $largo = length($encriptadofinal);
    my $res   = syswrite($socket, $encriptadofinal, $largo);

    if (defined $res && $res > 0)
    {
        if ($res != $largo)
        {

            # Could not write the whole command, buffer
            # the rest for later
            my $offset = $largo - $res;
            $offset = $offset * -1;
            my $buf = substr($encriptadofinal, $offset);
            unshift(@{$client_queue{$socket}},         $buf);
            unshift(@{$client_queue_nocrypt{$socket}}, $log);
            log_debug("Partial syswrite, len $res but $largo written", 1);

            my $cuantos = @{$client_queue{$socket}};
            if ($cuantos > 200)
            {
                &clean_socket($socket);
            }
            return -1;
        }
        else
        {

            # Write succesfull, log to stdout
            $log = substr($log, 0, -1);
            if ($debug > 0)
            {
                if ($whom eq "isclient")
                {
                    my $linea_formato = sprintf("%-15s => %s", $ip_addy{$socket}, $log);
                    log_debug("$linea_formato", 8);
                    $global_verbose = "separador";
                }
                else
                {
                    $log =~ s/\r//g;
                    $log =~ s/\n//g;
                    if ($log ne "")
                    {
                        my $linea_formato = sprintf("%-15s -> %s", $ip_addy{$socket}, $log);
                        log_debug("$linea_formato", 2);
                    }
                    else
                    {
                        $global_verbose = "separador";
                    }
                }
            }
        }
    }
    elsif ($! == EWOULDBLOCK)
    {

        # would block: not an error
        # handle blocking, by trying again later
        log_debug("Write wouldblock, buffering for $ip_addy{$socket}", 1);
        push(@{$client_queue{$socket}},         $encriptadofinal);
        push(@{$client_queue_nocrypt{$socket}}, $log);
        my $cuantos = @{$client_queue{$socket}};
        if ($cuantos > 200)
        {
            &clean_socket($socket);
        }
    }
    else
    {
        log_debug("Write error on $ip_addy{$socket}", 1);
        &clean_socket($socket);
    }
    return 0;
}

sub process_flash_command
{

    # This function process a command received from a Flash client
    # Including request of transfers, hangups, etc
    my $comando        = shift;
    my $socket         = shift;
    my $datosflash     = "";
    my $accion         = "";
    my $password       = "";
    my $valor          = "";
    my $origin_channel = "";
    my $origin_server  = "";
    my $canal_destino  = "";
    my $destin_server  = "";
    my $contexto       = "";
    my $btn_destino;
    my $extension_destino;
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
    my $bargerooms;
    my $found_room;
    my $servidor_dial = "";
    my $debugh        = "-- PROCESS_FLASH_COMMAND";
    my $calltimeout   = 0;

    my $linea_formato = sprintf("%-15s <= %s", $ip_addy{$socket}, $comando);
    log_debug("$linea_formato", 4);

    log_debug("$debugh START SUB", 16);

    $comando =~ s/<msg data=\"(.*)\"\s?\/>/$1/g;    # Removes XML markup
    ($datosflash, $accion, $password) = split(/\|/, $comando);
    chop($password);

    log_debug("$debugh datosflash $datosflash accion $accion password $password", 16);

    if ($accion =~ /\+/)
    {

        # The command has a timeout for the call
        $accion =~ s/(.*)\+(.*)\+(.*)/$1$3/g;
        $calltimeout = $2;
    }

    if ($datosflash =~ /_level0\.casilla/)
    {
        $datosflash =~ s/_level0\.casilla(\d+)/$1/g;
    }
    if ($datosflash =~ /_level0\.rectangulo/)
    {
        $datosflash =~ s/_level0\.rectangulo(\d+).*/$1/g;
    }

    log_debug("$debugh datosflash before context $datosflash", 128);

    # Appends context if defined because my crappy regexp only extracts digits
    # FIXME make a regexp that extract digits and digits@context
    if (defined($flash_contexto{$socket}))
    {
        if ($flash_contexto{$socket} ne "")
        {
            if ($datosflash =~ /\@/)
            {

                # No need to append context
            }
            else
            {
                $datosflash .= "\@" . $flash_contexto{$socket};
            }
        }
    }
    log_debug("$debugh datosflash after context $datosflash", 128);

    undef $origin_channel;

    # Flash clients send a "contexto" command on connect indicating
    # the panel context they want to receive. We populate a hash with
    # sockets/contexts in order to send only the events they want
    # And because this is an initial connection, it triggers a status
    # request to Asterisk

    if ($accion =~ /^contexto\d+/)
    {

        my ($nada, $contextoenviado) = split(/\@/, $datosflash);

        if (defined($contextoenviado))
        {
            $flash_contexto{$socket} = $contextoenviado;
        }
        else
        {
            $flash_contexto{$socket} = "";
        }
        if ($datosflash =~ /^1/)
        {
            $no_encryption{"$socket"} = 1;
        }
        else
        {
            $no_encryption{"$socket"} = 0;
        }

        sends_key($socket);
        sends_version($socket);

        # send_initial_status();
        first_client_status($socket);
        return;
    }
    if (defined($flash_contexto{$socket}))
    {
        $panelcontext = $flash_contexto{$socket};
    }
    else
    {
        $panelcontext = "";
    }
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
        $bargerooms = $config->{$panelcontext}{"barge_rooms"};
        ($first_room, $last_room) = split(/-/, $bargerooms);
    }
    else
    {
        if (defined($config->{"GENERAL"}{"barge_rooms"}))
        {
            $bargerooms = $config->{"GENERAL"}{"barge_rooms"};
            ($first_room, $last_room) = split(/-/, $bargerooms);
        }
        else
        {
            $bargerooms = "";
        }
    }

    # We have the origin button number from the drag&drop in the 'datos'
    # variable. We need to traverse the %buttons hash in order to extract
    # the channel name and the panel context, used to find the destination
    # button of the command if any
    if (   $accion =~ /^meetmemute/
        || $accion =~ /^meetmeunmute/
        || $accion =~ /^bogus/
        || $accion =~ /^restart/)
    {
        $origin_channel = "bogus";
    }
    else
    {
        my $datosflash_sincontexto = $datosflash;
        $datosflash_sincontexto =~ s/(.*)\@(.*)/$1/g;

        if (is_number($datosflash_sincontexto))
        {

            # If the originator is a number, assume button position
            # on fop, search for the channel in the buttons hash
            while (($canal, $nroboton) = each(%buttons))
            {
                if ($nroboton eq $datosflash)
                {

                    # A button key with an & is for a context channel
                    # A button key with an = is for a trunk   channel
                    # This bit of code just cleans the channel name and context
                    @pedazos = split(/&/, $canal);
                    $origin_context = $pedazos[1];
                    my @pedazos2 = split(/\^/, $pedazos[0]);
                    $origin_server  = $pedazos2[0];
                    $origin_channel = $pedazos2[1];

                    $origin_channel =~ s/(.*)[=](.*)/$1/g;
                }

            }
        }
        else
        {

            # The origin has letters, assume its a channel name
            # with a possible extensions.conf context after '@'
            # (We have already removed the '@fop_context')

            if ($datosflash_sincontexto =~ /\@/)
            {
                $contexto = $datosflash_sincontexto;
                $datosflash_sincontexto =~ s/(.*)\@(.*)/$1/g;
                $contexto               =~ s/(.*)\@(.*)/$2/g;
            }

            $origin_channel = $datosflash_sincontexto;

            # If we receive a channel name for the dial command
            # we default to server number 1 to send the command
            $servidor_dial = "default";

        }
    }

    if ($accion =~ /^restrict/ && defined($origin_channel))
    {
        my $contextoaagregar = "";
        if ($panelcontext ne "GENERAL")
        {
            $contextoaagregar = "&$panelcontext";
        }
        $restrict_channel = $origin_channel;
        log_debug("$debugh RESTRICT commands to channel $restrict_channel", 32);
        my $indice = "0^$restrict_channel$contextoaagregar";
        log_debug("$debugh RESTRICT indice $indice", 32);
        my $btn_num = "0";
        if (defined($buttons{"$indice"}))
        {
            $btn_num = $buttons{"$indice"};
            $btn_num =~ s/(.*)\@(.*)/$1/g;
        }
        if ($btn_num ne "0")
        {
            log_debug("$debugh RESTRICT btn_num $btn_num", 32);
            my $manda = "$btn_num|restrict|0";
            send_status_to_flash($socket, $manda, 0);
        }
        else
        {
            log_debug("$debugh RESTRICT channel not found $indice!", 32);
        }
        return;
    }

    if (defined($origin_channel))
    {
        log_debug("$debugh origin_channel = $origin_channel", 64);

        if (defined($config->{$panelcontext}{"security_code"}))
        {
            $myclave = $config->{$panelcontext}{"security_code"} . $keys_socket{$socket};
        }
        else
        {
            $myclave = "";
            $myclave = $config->{"GENERAL"}{"security_code"} . $keys_socket{$socket};
        }

        if ($myclave ne "")
        {
            $md5clave = MD5HexDigest($myclave);
        }

        if (   ("$password" eq "$md5clave")
            || ($accion =~ /^dial/ && $cdial_nosecure == 1))
        {
            sends_correct($socket);
            log_debug("** The channel selected is $origin_channel and the security code matches", 16);
            sends_key($socket);
            if ($accion =~ /^restart/)
            {

                $comando = "Action: Command\r\n";
                $comando .= "Command: restart when convenient\r\n\r\n";
                log_debug("!! Command received: restart when convenient", 32);
                send_command_to_manager($comando, $p[0]);
                alarm(10);
                return;
            }

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
            if ($btn_destino eq "0")
            {
                log_debug("$debugh btn_destino es igual a cero!", 32);
            }
            else
            {

                log_debug("$debugh btn_destino = $btn_destino", 32);

                # Now assigns the channel name to destino variable
                # traversing the %buttons hash to find the key/channel
                while (($canal, $nroboton) = each(%buttons))
                {
                    log_debug("$debugh compara nroboton $nroboton con btn_destino $btn_destino", 32);

                    if ($nroboton eq $btn_destino)
                    {
                        $canal =~ s/(.*)=(.*)/$1/g;
                        $destino = $canal;
                        last;
                    }
                }
            }

            if (defined($destino))
            {
                if ($destino ne "0")
                {
                    log_debug("$debugh destino es igual a $destino", 32);
                    my @pedazos2 = split(/\^/, $destino);
                    $destin_server = $pedazos2[0];
                    $destino       = $pedazos2[1];
                    log_debug("$debugh El boton de destino es $destino en el server $destin_server", 64);
                }
            }

            if ($accion =~ /^voicemail/)
            {
                my $vext     = "";
                my $vcontext = "";

                if (defined($config->{$panelcontext}{"voicemail_extension"}))
                {
                    my $voicemailext = $config->{$panelcontext}{"voicemail_extension"};
                    ($vext, $vcontext) = split(/\@/, $voicemailext);
                }
                else
                {
                    log_debug("There is no voicemail_extension defined in op_server.cfg!", 32);
                    return;
                }

                my $keyext = "$origin_server^$origin_channel";
                if ($contexto ne "") { $keyext .= "\&$contexto"; }
                my $vclid = $extension_transfer{$keyext};
                $vclid =~ s/(.*)\@(.*)/$1/g;

                $comando = "Action: Originate\r\n";
                $comando .= "Channel: $origin_channel\r\n";
                $comando .= "Callerid: $vclid <$vclid>\r\n";
                $comando .= "Exten: $vext\r\n";
                if (defined($vcontext))
                {
                    $comando .= "Context: $vcontext\r\n";
                }
                $comando .= "Priority: 1\r\n";
                $comando .= "\r\n";
                send_command_to_manager($comando, $p[$button_server{$datosflash}]);
                return;
            }

            if (is_number($destino))
            {

                # If the selected channel name is only digits, its a
                # conference. So treat a conference command as a regular
                # transfer or redirect. (We do not want to send into a
                # meetme conference another ongoing meetme conference)
                my @sesiones_del_canal = extraer_todas_las_sesiones_de_un_canal($origin_channel);
                my $cuantos            = @sesiones_del_canal;

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
                log_debug("$debugh Will try to hangup channel", 16);
                my $buton_number = $datosflash;

                foreach (@{$sesbot{$buton_number}})
                {
                    $comando = "Action: Hangup\r\n";
                    $comando .= "Channel: $_\r\n\r\n";
                    log_debug("-- Command received: $accion chan $_", 32);
                    send_command_to_manager($comando, $p[$button_server{$datosflash}]);

                    #					$comando = "Action: Command\r\n";
                    #					$comando .= "Command: soft hangup $_\r\n\r\n";
                    #                    send_command_to_manager($comando, $p[$button_server{$datosflash}]);
                }
            }
            elsif ($accion =~ /^meetmemute/)
            {
                my $conference   = $btn_destino;
                my $meetmemember = $datosflash;
                $conference   =~ s/(.*)\@(.*)/$1/g;
                $meetmemember =~ s/(.*)\@(.*)/$1/g;
                my $boton_con_contexto = $clid;
                $boton_con_contexto =~ s/^meetmemute//g;
                $comando = "Action: Command\r\n";
                $comando .= "ActionID: meetmemute$boton_con_contexto\r\n";
                $comando .= "Command: meetme mute $conference $meetmemember\r\n\r\n";
                send_command_to_manager($comando, $p[$button_server{$boton_con_contexto}]);
            }
            elsif ($accion =~ /^meetmeunmute/)
            {
                my $conference   = $btn_destino;
                my $meetmemember = $datosflash;
                $conference   =~ s/(.*)\@(.*)/$1/g;
                $meetmemember =~ s/(.*)\@(.*)/$1/g;
                my $boton_con_contexto = $clid;
                $boton_con_contexto =~ s/^meetmeunmute//g;
                $comando = "Action: Command\r\n";
                $comando .= "ActionID: meetmeunmute$boton_con_contexto\r\n";
                $comando .= "Command: meetme unmute $conference $meetmemember\r\n\r\n";
                send_command_to_manager($comando, $p[$button_server{$boton_con_contexto}]);
            }
            elsif ($accion =~ /^conference/)
            {
                log_debug("$debugh CONFERENCE extension_transfer($origin_channel)", 32);
                my $indice    = $origin_server . "^" . $origin_channel;
                my $originate = $extension_transfer{$indice};

                foreach (keys(%buttons))
                {
                    log_debug("$debugh comparo $buttons{$_} con btn_destino $btn_destino", 64);
                    if ($buttons{$_} eq $btn_destino)
                    {
                        if ($canal =~ /\*/)
                        {
                            my @canalarray = @{$sesbot{$btn_destino}};
                            my $canalses   = $canalarray[0];
                            my ($newcanal, $newses) = separate_session_from_channel($canalses);
                            $canal = $newcanal;
                        }
                        $canal =~ s/(.*)=(.*)/$1/g;
                        log_debug("$debugh coincidencia para btn_destino $btn_destino el canal es $canal", 64);
                        my @links = extraer_todos_los_enlaces_de_un_canal($canal);

                        my @canal_transferir = @{$sesbot{$btn_destino}};

                        my $cuantos = @links;
                        if ($cuantos <= 0)
                        {
                            my @extensiondialed = extracts_exten_from_active_channel($canal);
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
                            for (my $at = $first_room ; $at <= $last_room ; $at++)
                            {
                                log_debug("room $at = " . $barge_rooms{"$at"}, 128);
                                if ($barge_rooms{"$at"} == 0)
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
                                $auto_conference{$canal_transferir[0]} = $origin_channel;
                            }
                            else
                            {
                                log_debug("$debugh No hay meetme vacio!", 64);
                                $comando = "";
                            }
                        }
                        send_command_to_manager($comando, $p[$button_server{$datosflash}]);
                        last;
                    }
                }
            }
            elsif ($accion =~ /^ctransferir/)
            {
                $comando = "Action: Command\r\n";
                $comando .= "Command: database put clid $destino ";
                $comando .= "\"$clid\"\r\n\r\n";
                send_command_to_manager($comando, $p[$button_server{$datosflash}]);

                $canal_destino = retrieve_extension($btn_destino);

                if ($origin_channel =~ /\*/)
                {
                    my @canalarray = @{$sesbot{$datosflash}};
                    my $canalses   = $canalarray[0];
                    my ($newcanal, $newses) = separate_session_from_channel($canalses);
                    $origin_channel = $newcanal;
                }

                if ($canal_destino ne "-1")
                {
                    if ($canal_destino =~ /\@/)
                    {
                        @pedazos       = split(/\@/, $canal_destino);
                        $canal_destino = $pedazos[0];
                        $contexto      = $pedazos[1];
                    }
                    my @cuales_transferir = ();
                    if ($reverse_transfer == 1)
                    {

                        # Transfer the session from the *other* button
                        @cuales_transferir = extraer_todos_los_enlaces_de_un_canal($origin_channel);
                    }
                    else
                    {

                        # Transfer the session from the same button
                        #                        @cuales_transferir =
                        #                          extraer_todas_las_sesiones_de_un_canal(
                        #                                                               $origin_channel);

                        @cuales_transferir = @{$sesbot{$datosflash}};

                    }
                    foreach my $valor (@cuales_transferir)
                    {
                        log_debug("** Will try to transfer $valor to extension number $canal_destino!", 16);
                        $comando = "Action: Redirect\r\n";
                        $comando .= "Channel: $valor\r\n";
                        $comando .= "Exten: $canal_destino\r\n";
                        if ($contexto ne "")
                        {
                            $comando .= "Context: $contexto\r\n";
                        }
                        $comando .= "Priority: 1\r\n\r\n";
                        send_command_to_manager($comando, $p[$button_server{$datosflash}]);

                        if ($calltimeout > 0)
                        {
                            $comando = "Action: AbsoluteTimeout\r\n";
                            $comando .= "Channel: $valor\r\n";
                            $comando .= "Timeout: $calltimeout\r\n";
                            $comando .= "ActionID: timeout|$valor|$calltimeout\r\n";
                            $comando .= "\r\n";
                            send_command_to_manager($comando, $p[$button_server{$datosflash}]);
                        }

                    }
                }
                else
                {
                    log_debug("** Untransferable destination!", 16);
                }
            }
            elsif ($accion =~ /^transferir/)
            {

                $canal_destino = retrieve_extension($btn_destino);

                if ($origin_channel =~ /\*/)
                {
                    my @canalarray = @{$sesbot{$datosflash}};
                    my $canalses   = $canalarray[0];
                    my ($newcanal, $newses) = separate_session_from_channel($canalses);
                    $origin_channel = $newcanal;
                }

                if ($canal_destino ne "-1")
                {
                    if ($canal_destino =~ /\@/)
                    {
                        @pedazos       = split(/\@/, $canal_destino);
                        $canal_destino = $pedazos[0];
                        $contexto      = $pedazos[1];
                    }

                    my @cuales_transferir = ();
                    if ($reverse_transfer == 1)
                    {
                        log_debug("$debugh REVERSE TRANSFER", 16);

                        # Transfer the session from the *other* button
                        @cuales_transferir = extraer_todos_los_enlaces_de_un_canal($origin_channel);
                    }
                    else
                    {
                        log_debug("$debugh NORMAL TRANSFER", 16);

                        # Transfer the session from the same button
                        #@cuales_transferir =
                        #  extraer_todas_las_sesiones_de_un_canal(
                        #                                           $origin_channel);
                        @cuales_transferir = @{$sesbot{$datosflash}};

                    }

                    foreach my $valor (@cuales_transferir)
                    {
                        log_debug("$debugh Will try to transfer $valor to extension number $canal_destino!", 16);
                        $comando = "Action: Redirect\r\n";
                        $comando .= "Channel: $valor\r\n";
                        $comando .= "Exten: $canal_destino\r\n";
                        if ($contexto ne "")
                        {
                            $comando .= "Context: $contexto\r\n";
                        }
                        $comando .= "Priority: 1\r\n\r\n";
                        send_command_to_manager($comando, $p[$button_server{$datosflash}]);

                        if ($calltimeout > 0)
                        {
                            $comando = "Action: AbsoluteTimeout\r\n";
                            $comando .= "Channel: $valor\r\n";
                            $comando .= "Timeout: $calltimeout\r\n";
                            $comando .= "ActionID: timeout|$valor|$calltimeout\r\n";
                            $comando .= "\r\n";
                            send_command_to_manager($comando, $p[$button_server{$datosflash}]);
                        }

                    }
                }
                else
                {
                    log_debug("** Untransferable destination! ($origin_channel)", 16);
                }
            }
            elsif ($accion =~ /^coriginate/)
            {
                if ($origin_channel =~ /\*/)
                {
                    log_debug("Cannot originate from wildcard buttons ($origin_channel)", 16);
                    return;
                }
                $comando = "Action: Command\r\n";
                $comando .= "Command: database put clid $destino ";
                $comando .= "\"$clid\"\r\n\r\n";
                send_command_to_manager($comando, $p[$button_server{$datosflash}]);

                $extension_destino = retrieve_extension($btn_destino);
                if ($extension_destino =~ /\@/)
                {
                    @pedazos           = split(/\@/, $extension_destino);
                    $extension_destino = $pedazos[0];
                    $contexto          = $pedazos[1];
                }

                log_debug("$debugh Originate from $origin_channel to extension $extension_destino!", 16);

                if ($origin_channel =~ /^IAX2\[/)
                {
                    $origin_channel =~ s/^IAX2\[(.*)\]/IAX2\/$1/g;
                }
                $comando = "Action: Originate\r\n";
                $comando .= "Channel: $origin_channel\r\n";
                $comando .= "Exten: $extension_destino\r\n";

                if ($contexto ne "")
                {
                    $comando .= "Context: $contexto\r\n";
                }
                $comando .= "Priority: 1\r\n";
                $comando .= "\r\n";
                send_command_to_manager($comando, $p[$button_server{$datosflash}]);
            }
            elsif ($accion =~ /^originate/)
            {
                if ($origin_channel =~ /\*/)
                {
                    log_debug("** Cannot originate from wildcard buttons ($origin_channel)", 16);
                    return;
                }
                $extension_destino = retrieve_extension($btn_destino);
                if ($extension_destino =~ /\@/)
                {
                    @pedazos           = split(/\@/, $extension_destino);
                    $extension_destino = $pedazos[0];
                    $contexto          = $pedazos[1];
                }

                log_debug("$debugh Originate from $origin_channel to extension $extension_destino!", 16);
                my $keyext = "$origin_server^$origin_channel";

                # if ($contexto ne "") { $keyext .= "\&$contexto"; }
                while (my ($key, $val) = each(%extension_transfer))
                {
                    print "k $key, v $val, keyext $keyext\n";
                }
                $clid = $textos{"$datosflash"} . " <" . $extension_transfer{$keyext} . ">";

                if ($origin_channel =~ /^IAX2\[/)
                {
                    $origin_channel =~ s/^IAX2\[(.*)\]/IAX2\/$1/g;
                }
                $comando = "Action: Originate\r\n";
                $comando .= "Channel: $origin_channel\r\n";
                $comando .= "Callerid: $clid\r\n";
                $comando .= "Exten: $extension_destino\r\n";

                if ($contexto ne "")
                {
                    $comando .= "Context: $contexto\r\n";
                }
                $comando .= "Priority: 1\r\n";
                $comando .= "\r\n";
                send_command_to_manager($comando, $p[$button_server{$datosflash}]);
            }
            elsif ($accion =~ /^dial/)
            {
                if ($servidor_dial eq "default")
                {
                    $servidor_dial = $p[0];
                }
                else
                {
                    $servidor_dial = $p[$button_server{$datosflash}];
                }
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
                send_command_to_manager($comando, $servidor_dial);
            }
        }
        else
        {
            log_debug("$debugh Password mismatch -$password-$md5clave-!", 1);
            sends_key($socket);
            sends_incorrect($socket);
        }
    }
    else
    {
        log_debug("$debugh There is no channel selected ?", 16);
        print("$debugh There is no channel selected ?\n");
    }
}

sub retrieve_extension
{
    my $param         = shift;
    my $canal         = "";
    my $canal_destino = "";
    my $debugh        = "** RETRIEVE_EXTEN";

    my $param_sin_contexto = $param;
    $param_sin_contexto =~ s/(.*)(\@.*)/$1/g;

    log_debug("$debugh param $param param_sin_con $param_sin_contexto", 32);

    if (is_number($param_sin_contexto))
    {
        log_debug("$debugh I guess its a button number", 32);

        # If the parameter is a number, assume button number
        foreach (keys(%buttons))
        {
            my $linealog = sprintf("%-20s %-10s", $_, $buttons{$_});

            #			log_debug("$debugh $linealog",64);

            if ($buttons{$_} eq $param)
            {
                log_debug("$debugh coincide con $param", 64);
                $canal = $_;
                $canal =~ s/(.*)=(.*)/$1/g;
                log_debug("$debugh canal $canal", 64);
                $canal_destino = $extension_transfer{"$canal"};
                last;
            }
        }
    }
    else
    {
        log_debug("$debugh I guess its a channel name", 32);

        # If its not a number, asume channel name (technology/name)
        foreach (keys(%buttons))
        {
            my $linealog = sprintf("%-20s %-10s", $_, $buttons{$_});

            #			log_debug("$debugh $linealog",64);
            if ($_ eq $param)
            {
                log_debug("$debugh coincide con $param", 64);
                $canal = $_;
                $canal =~ s/(.*)=(.*)/$1/g;
                $canal_destino = $extension_transfer{"$canal"};
                last;
            }
        }
    }
    log_debug("$debugh La extension para $param es $canal_destino", 32);
    return $canal_destino;
}

sub request_astdb_status
{

    for my $key (keys %astdbcommands)
    {
        my $nro_servidor = 0;
        foreach my $socket (@p)
        {
            if (defined($socket) && $socket ne "")
            {
                for (keys %buttons)
                {
                    my $canal = $_;
                    $canal =~ m/(\d+)\^([^&]*)(.*)/g;
                    my $servidor = $1;
                    my $canalito = $2;
                    if ($canalito !~ m/^_/ && $nro_servidor == $servidor && $canalito !~ m/=/)
                    {
                        my $comando = "Action: Command\r\n";
                        $comando .= "ActionID: astdb-$key-$canalito\r\n";
                        $comando .= "Command: database get $key $canalito\r\n\r\n";
                        if (defined($autenticado{$socket}))
                        {
                            if ($autenticado{$socket} == 1)
                            {
                                send_command_to_manager($comando, $socket);
                            }
                        }
                    }
                }
            }
            $nro_servidor++;
        }
    }
}

sub request_queue_status
{
    my $socket  = shift;
    my $canalid = shift;
    my $member  = "";
    my $nada    = "";

    if (defined($canalid))
    {
        ($member, $nada) = separate_session_from_channel($canalid);
    }

    my @todos = ();

    if ($socket eq "all")
    {
        @todos = @p;
    }
    else
    {
        push @todos, $socket;
    }

    foreach my $socket2 (@todos)
    {
        if (defined($socket2) && $socket2 ne "")
        {
            send_command_to_manager("Action: Command\r\nActionId: agents\r\nCommand: show agents\r\n\r\n", $socket2);
            if (defined($member))
            {
                my @agentes = ();
                push @agentes, $member;
                if (exists($reverse_agents{$member}))
                {
                    push @agentes, "Agent/" . $reverse_agents{$member};
                }
                foreach my $cual (@agentes)
                {
                    send_command_to_manager("Action: QueueStatus\r\nMember: $cual\r\n\r\n", $socket2);

                }
            }
            else
            {
                send_command_to_manager("Action: QueueStatus\r\n\r\n", $socket2);
            }
        }
    }
}

sub first_client_status
{

    # This functions traverses all FOP internal hashes and send the proper
    # commands to the flash client to reflect the status of each button.

    my $socket  = shift;
    my $interno = "";
    if (keys(%estadoboton))
    {
        for $interno (keys %botonled)
        {
            if ($botonled{$interno} == 1)
            {
                send_status_to_flash($socket, "$interno|changelabel1|$botonlabel{$interno}", 0);
            }
        }
        for $interno (keys %botonvoicemail)
        {
            if ($botonvoicemail{$interno} == 1)
            {
                send_status_to_flash($socket, "$interno|voicemail|1", 0);
            }
        }
        for $interno (keys %botonvoicemailcount)
        {
            send_status_to_flash($socket, "$interno|voicemailcount|$botonvoicemailcount{$interno}", 0);
        }
        for $interno (keys %botonalpha)
        {
            if ($botonalpha{$interno} ne "")
            {
                send_status_to_flash($socket, "$interno|setalpha|$botonalpha{$interno}", 0);
            }
        }
        for $interno (keys %botontext)
        {
            if ($botontext{$interno} ne "")
            {
                send_status_to_flash($socket, "$interno|settext|$botontext{$interno}", 0);
            }
        }
        for $interno (keys %botonqueue)
        {
            send_status_to_flash($socket, "$interno|infoqstat|$botonqueue{$interno}", 0);
        }
        if (keys(%botonqueuemember))
        {
            for $interno (keys %botonqueuemember)
            {
                if (defined(@{$botonqueuemember{$interno}}))
                {
                    my %temphash = ();
                    foreach my $val (@{$botonqueuemember{$interno}})
                    {
                        my @datos = split(/\|/, $val);
                        $temphash{$datos[0]} = $datos[1];

                        #                        send_status_to_flash($socket, "$interno|info$datos[0]|$datos[1]", 0);
                    }
                    while (my ($key, $val) = each(%temphash))
                    {
                        send_status_to_flash($socket, "$interno|info$key|$val", 0);
                    }
                }
            }
        }
        if (keys(%botonpark))
        {
            for $interno (keys %botonpark)
            {
                $botonpark{$interno} =~ m/(.*)\|(.*)/;
                my $texto   = $1;
                my $timeout = $2;
                my $diftime = $timeout - time();
                if ($diftime > 0)
                {
                    send_status_to_flash($socket, "$interno|park|$texto($diftime)", 0);
                }
            }
        }
        for $interno (keys %estadoboton)
        {
            if ($estadoboton{$interno} !~ /^free/)
            {
                if ($estadoboton{$interno} =~ /^busy/)
                {
                    send_status_to_flash($socket, "$interno|state|busy", 0);
                    if (defined($botonlabel{$interno}))
                    {
                        send_status_to_flash($socket, "$interno|changelabel0|$botonlabel{$interno}", 0);
                    }
                }
                elsif ($estadoboton{$interno} =~ /ringi/)
                {
                    send_status_to_flash($socket, "$interno|state|ringing", 0);
                }
                if (defined($botonclid{$interno}))
                {
                    if ($botonclid{$interno} ne "")
                    {
                        send_status_to_flash($socket, "$interno|settext|$botonclid{$interno}", 0);
                    }
                }
            }
        }
        if (keys(%botonlinked))
        {
            for $interno (keys %botonlinked)
            {
                if ($botonlinked{$interno} ne "")
                {
                    send_status_to_flash($socket, "$interno|linked|$botonlinked{$interno}", 0);
                }
            }
        }
        if (keys(%botonmeetme))
        {
            for $interno (keys %botonmeetme)
            {
                if ($botonmeetme{$interno} ne "")
                {
                    send_status_to_flash($socket, "$interno|meetmeuser|$botonmeetme{$interno}", 0);
                }
            }
        }
        if (keys(%botontimer))
        {
            for $interno (keys %botontimer)
            {
                if ($botontimer{$interno} ne "")
                {
                    my $diftime = time() - $botontimer{$interno};
                    send_status_to_flash($socket, "$interno|settimer|$diftime\@UP", 0);
                    send_status_to_flash($socket, "$interno|state|busy",            0);
                }
            }
        }
        if (keys(%botonlabel))
        {
            for $interno (keys %botonlabel)
            {
                if (   $botonlabel{$interno} ne ""
                    && $botonlabel{$interno} ne "."
                    && $botonlabel{$interno} ne "original"
                    && $botonlabel{$interno} ne "labeloriginal")
                {
                    send_status_to_flash($socket, "$interno|setlabel|$botonlabel{$interno}", 0);
                }
            }
        }
        if (keys(%botonregistrado))
        {
            for $interno (keys %botonregistrado)
            {
                if ($botonregistrado{$interno} ne "")
                {
                    my ($quehace, $dos) = split(/\|/, $botonregistrado{$interno});
                    send_status_to_flash($socket, "$interno|$quehace|$dos", 0);
                }
            }
        }
    }
}

sub send_initial_status
{
    %datos = ();
    my $nro_servidor = 0;
    my $debugh       = "** SEND INITIAL STATUS";
    my $cual         = shift;
    my @socket_manager;

    if (defined($cual))
    {
        push @socket_manager, $cual;
    }
    else
    {
        @socket_manager = @p;
    }

    log_debug("$debugh START SUB", 16);

    request_astdb_status();

    foreach my $socket (@socket_manager)
    {

        if (defined($socket) && $socket ne "")
        {
            my @pedazos = split(/\|/, $manager_socket{$socket});
            if ($pedazos[0] eq $ip_addy{$socket})
            {
                my $contador = 0;
                foreach my $valor (@manager_host)
                {
                    if ($valor eq $pedazos[0])
                    {
                        $nro_servidor = $contador;
                    }
                    $contador++;
                }
            }

            send_command_to_manager("Action: Status\r\n\r\n", $socket);

            send_command_to_manager("Action: ZapShowChannels\r\n\r\n", $socket);

            send_command_to_manager("Action: Command\r\nActionID: parkedcalls\r\nCommand: show parkedcalls\r\n\r\n",
                                    $socket);

            # Send commands to check the mailbox status for each mailbox defined
            while (my ($key, $val) = each(%mailbox))
            {
                my @pedacitos = split(/\^/, $key);
                my $servidormbox = $pedacitos[0];
                if ("$servidormbox" eq "$nro_servidor")
                {
                    log_debug("$debugh mailbox $ip_addy{$socket} $key $val", 32);
                    send_command_to_manager("Action: MailboxStatus\r\nMailbox: $val\r\n\r\n", $socket);
                }
            }
            my @all_meetme_rooms = ();

            # generates an array with all meetme rooms to check on init
            for my $valor (keys %barge_rooms)
            {
                push(@all_meetme_rooms, $valor);
            }

            for my $key (keys %buttons)
            {
                if ($key =~ /^\d+\^\d+$/)
                {
                    push(@all_meetme_rooms, $key);
                }
            }

            my %count               = ();
            my @unique_meetme_rooms =
              grep { ++$count{$_} < 2 } @all_meetme_rooms;

            foreach my $valor (@unique_meetme_rooms)
            {
                my $servidormeetme = 0;
                my $meetmeroom     = "";

                if ($valor =~ /\^/)
                {
                    my @pedacitos = split(/\^/, $valor);
                    $servidormeetme = $pedacitos[0];
                    $meetmeroom     = $pedacitos[1];
                }
                else
                {

                    # If there is no server defined (its a barge_room)
                    # we will query all servers - quick hack FIX IT or
                    # try to figure out a way to have barge-rooms separated
                    # in panel_contexts (as it is now) and also asterisk
                    # servers.
                    $servidormeetme = $nro_servidor;
                    $meetmeroom     = $valor;
                }

                if ("$servidormeetme" eq "$nro_servidor")
                {
                    send_command_to_manager(
                          "Action: Command\r\nActionID: meetme_$meetmeroom\r\nCommand: meetme list $meetmeroom\r\n\r\n",
                          $socket);
                }
            }
            request_queue_status($socket, "");
        }
    }
    alarm(2);
}

sub process_cli_command
{

    # This subroutine process the output for a manager "Command"
    # sent, as 'sip show peers'

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
    my $debugh     = "** PROCESS_CLI";
    my $server     = 0;

    log_debug("$debugh START SUB", 16);

    foreach my $valor (@lineas)
    {
        if ($valor =~ /^Server/)
        {
            $server = $valor;
            $server =~ s/Server: (.*)/$1/g;
        }
    }

    if ($texto =~ /ActionID: meetme_/)
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
                    $bloque[$contador]{"Server"}   = "$server";
                    $bloque[$contador]{"Uniqueid"} = "YYYY";
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
                $agent_number                         = $1;
                $agent_name                           = $2;
                $agent_state                          = $3;
                $agents_name{"$server^$agent_number"} = $agent_name;
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
                    $bloque[$contador]{"Server"}    = "$server";
                    $contador++;
                }

                if ($agent_state =~ /logged in on/)
                {

                    # Agent login
                    $agent_state =~ s/\s+/ /g;
                    $agent_state =~ s/logged in on //g;
                    $agent_state =~ s/([^ ]*).*/$1/g;

                    $bloque[$contador]{"Event"}   = "Agentlogin";
                    $bloque[$contador]{"Channel"} = $agent_state;
                    $bloque[$contador]{"Agent"}   = $agent_number;
                    $bloque[$contador]{"Server"}  = "$server";
                    $contador++;
                }
                if ($agent_state =~ /not logged in/)
                {
                    $bloque[$contador]{"Event"}  = "Agentlogoff";
                    $bloque[$contador]{"Agent"}  = "$agent_number";
                    $bloque[$contador]{"Server"} = "$server";
                    $contador++;
                }
            }
        }
    }
    elsif ($texto =~ /ActionID: astdb-/)
    {
        my $astdbk = "";
        my $canalk = "";
        my $valork = "";
        foreach (@lineas)
        {
            if (/^ActionID/)
            {
                $_ =~ m/ActionID: astdb-([^-]*)-(.*)/;
                $astdbk = $1;
                $canalk = $2;
            }
            if (/^Value:/)
            {
                $valork = $_;
                $valork =~ s/Value: //g;
            }
        }
        if ($valork ne "")
        {
            $bloque[$contador]{"Event"}   = "ASTDB";
            $bloque[$contador]{"Channel"} = $canalk;
            $bloque[$contador]{"Value"}   = $valork;
            $bloque[$contador]{"Family"}  = $astdbk;
            $contador++;
        }

    }
    elsif ($texto =~ /ActionID: meetmeun/ || $texto =~ /ActionID: meetmemute/)
    {
        my $quecomando = "";
        my $quecanal   = "";
        foreach my $valor (@lineas)
        {
            if ($valor =~ /^ActionID:/)
            {
                $quecomando = $valor;
                $quecomando =~ s/^ActionID: //g;
                if ($quecomando =~ /meetmemute/)
                {
                    $quecanal = $quecomando;
                    $quecanal =~ s/meetmemute//g;
                    $quecomando = "meetmemute";
                }
                else
                {
                    $quecanal = $quecomando;
                    $quecanal =~ s/meetmeunmute//g;
                    $quecomando = "meetmeunmute";
                }
            }
        }
        my $canal_a_mutear = $buttons_reverse{$quecanal};
        my @pedazos = split /\^/, $canal_a_mutear;
        $canal_a_mutear = $pedazos[1];
        $canal_a_mutear =~ s/(.*)\&(.*)/$1/g;
        $bloque[$contador]{"Event"}   = $quecomando;
        $bloque[$contador]{"Channel"} = $canal_a_mutear . "-XXXX";
        $bloque[$contador]{"Server"}  = "$server";
        $contador++;
    }
    elsif ($texto =~ "ActionID: iaxpeers")
    {
        my $info    = 0;
        my $statPos = 74;
        foreach my $valor (@lineas)
        {
            log_debug("$debugh Line iaxpeers: $valor", 32);
            if ($valor =~ /^Name\/User/i)
            {
                $statPos = index($valor, "Status");
                $info = 1;
                next;
            }
            last if $valor =~ /^--End/i;
            next unless $info;
            next unless (length($valor) > $statPos);
            my $estado = substr($valor, $statPos);
            $valor =~ s/\s+/ /g;
            my @parametros = split(" ", $valor);
            my $interno    = $parametros[0];

            if ($interno =~ /\//)
            {
                my @partecitas = split(/\//, $interno);
                $interno = $partecitas[0];
            }
            my $dirip = $parametros[1];

            if (defined($estado) && $estado ne "")
            {
                $interno = "IAX2/" . $interno . "-XXXX";
                log_debug("$debugh State: $estado Extension: $interno", 16);
                $bloque[$contador]{"Event"}   = "Regstatus";
                $bloque[$contador]{"Channel"} = $interno;
                $bloque[$contador]{"State"}   = $estado;
                $bloque[$contador]{"IP"}      = $dirip;
                $bloque[$contador]{"Server"}  = "$server";
                $contador++;
            }
        }
    }
    elsif ($texto =~ "ActionID: parkedcalls")
    {
        my $info = 0;
        foreach my $valor (@lineas)
        {
            log_debug("$debugh Line parkedcalls: $valor", 32);
            if ($valor =~ /Timeout/)
            {
                $info = 1;
                next;
            }
            last if $valor =~ /^--End/i;
            last if $valor =~ /^\d+ parked/i;
            next unless $info;
            $valor =~ s/\s+/ /g;
            my @parametros = split(" ", $valor);
            my $timeout = $parametros[6];
            $timeout =~ s/(\d+)s/$1/;

            $bloque[$contador]{"Event"}   = "ParkedCall";
            $bloque[$contador]{"Channel"} = $parametros[1];
            $bloque[$contador]{"Exten"}   = $parametros[0];
            $bloque[$contador]{"Timeout"} = $timeout;
            $bloque[$contador]{"Server"}  = "$server";
            $contador++;

        }
    }
    else
    {
        my $info    = 0;
        my $statPos = 74;

        # Its a sip show peers report
        foreach my $valor (@lineas)
        {
            if ($valor =~ /^Name\/User/i)
            {
                $statPos = index($valor, "Status");
                $info = 1;
                next;
            }
            last if $valor =~ /^--End/i;
            next unless $info;
            next unless (length($valor) > $statPos);
            log_debug("$debugh Line: $valor", 32);

            if (length($valor) < $statPos)
            {
                log_debug("$debugh SIP PEER line $valor does not match $statPos!", 16);
                next;
            }

            my $estado = substr($valor, $statPos);
            $valor =~ s/\s+/ /g;
            if ($valor eq "") { next; }
            my @parametros = split(" ", $valor);
            my $interno    = $parametros[0];
            my $dirip      = $parametros[1];
            $interno =~ s/(.*)\/(.*)/$1/g;

            if (defined($interno))
            {

                if ($interno =~ /(.*)\/(.*)/)
                {
                    if ($1 eq $2)
                    {
                        $interno = $1 . "-XXXX";
                    }
                    else
                    {
                        $interno .= "-XXXX";
                    }
                }
            }
            if (defined($estado)
                && $estado ne "")    # If set, is the status of 'sip show peers'
            {
                $interno = "SIP/" . $interno;
                log_debug("$debugh State: $estado Extension: $interno", 16);
                $bloque[$contador]{"Event"}   = "Regstatus";
                $bloque[$contador]{"Channel"} = $interno . "-XXXX";
                $bloque[$contador]{"State"}   = $estado;
                $bloque[$contador]{"IP"}      = $dirip;
                $bloque[$contador]{"Server"}  = "$server";
                $contador++;
            }
        }
    }
}

sub find_uniqueid
{

    # returns the uniqueid of a given channel
    my $canal  = shift;
    my $server = shift;
    my $uniqid = "";
    my $match  = 0;

    if (keys(%datos))
    {
        for (keys %datos)
        {
            $match = 0;
            while (my ($key, $val) = each(%{$datos{$_}}))
            {
                if ($key eq "Channel" && $val eq $canal)
                {
                    $match++;
                }
                if ($key eq "Server" && $val eq $server)
                {
                    $match++;
                }
            }
            if ($match > 1)
            {
                $uniqid = $_;
                last;
            }
        }
    }

    return $uniqid;
}

sub check_if_extension_is_busy
{
    my $interno = shift;
    my $return  = "no";
    my $quehay  = "";
    my $canal   = "";
    my $sesion  = "";
    my $comando = "";
    my $debugh  = "** CHECK_EXT_BUSY";

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
                        log_debug("$debugh ZOMBIE!! I will try to kill it!! $val", 16);
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
                        log_debug("$debugh Extension still busy $canal $interno", 16);
                    }
                    else
                    {
                        log_debug("$debugh $canal <> $interno", 32);
                    }
                }
            }
        }
    }
    return $return;
}

sub log_debug
{
    my $texto   = shift;
    my $nivel   = shift;
    my $verbose = "0";

    if (!defined($nivel)) { $nivel = 1; }

    if ($debug & $nivel)
    {
        $texto =~ s/\0//g;
        if ($texto !~ m/^\d+\.\d+\.\d+\.\d+/)
        {
            $verbose = $texto;
            $verbose =~ s/^\*\* ([^\s]*).*/$1/g;
        }
        else
        {
            my $parte = $texto;
            $parte =~ s/(\d+\.\d+\.\d+\.\d+)\s+(.*)/$1/g;
            $verbose = $parte;
        }
        if ($debug == -1)
        {

            # Debug log Cache
            $debug_cache .= "$texto\n";
        }
        else
        {
            if ($debug_cache ne "")
            {
                print $debug_cache. "\n";
                $debug_cache = "";
            }
            if ($verbose ne $global_verbose)
            {
                print "\n";
            }
            $global_verbose = $verbose;
            print "$texto\n";
        }
    }
}

sub alarma_al_minuto
{
    my $nro_servidor = 0;
    my $debugh       = "** ALARM ";
    manager_connection();

    #	%cache_hit = ();   # Clears button cache
    foreach (@p)
    {
        if (defined($_) && $_ ne "")
        {
            log_debug("Enviando status a " . $ip_addy{$_}, 16);
            my @pedazos = split(/\|/, $manager_socket{$_});

            if ($pedazos[0] eq $ip_addy{$_})
            {
                my $contador = 0;
                foreach my $valor (@manager_host)
                {
                    if ($valor eq $pedazos[0])
                    {
                        $nro_servidor = $contador;
                    }
                    $contador++;
                }
            }

            my $comando = "Action: Command\r\n";
            $comando .= "Command: sip show peers\r\n\r\n";
            send_command_to_manager($comando, $_);

            $comando = "Action: Command\r\n";
            $comando .= "ActionID: iaxpeers\r\n";
            $comando .= "Command: iax2 show peers\r\n\r\n";
            send_command_to_manager($comando, $_);

            if ($poll_voicemail == 1)
            {

                # Send commands to check the mailbox status for each mailbox defined
                while (my ($key, $val) = each(%mailbox))
                {
                    my @pedacitos = split(/\^/, $key);
                    my $servidormbox = $pedacitos[0];
                    if ("$servidormbox" eq "$nro_servidor")
                    {
                        log_debug("$debugh mailbox $ip_addy{$_} $key $val", 32);
                        send_command_to_manager("Action: MailboxStatus\r\nMailbox: $val\r\n\r\n", $_);
                    }
                }
            }
        }
    }
    alarm($poll_interval);
}

sub send_status_to_flash
{
    my $socket       = shift;
    my $status       = shift;
    my $nocrypt      = shift;
    my $encriptado   = $status;
    my $but_no       = 0;
    my $debugh       = "** SEND_STATUS_TO_FLASH ";
    my $contexto     = "";
    my $cmd          = "";
    my $cmd_crypt    = "";
    my $data         = "";
    my $data_crypt   = "";
    my $noencriptado = "";

    if (!defined($socket))
    {
        log_debug("$debugh socket $socket not open!!!", 64);
    }

    if ($encriptado =~ /key\|0/)
    {
        $but_no = '0';
        $encriptado =~ m/(.*)\|(.*)\|(.*)/;
        $cmd  = $2;
        $data = $1;

    }
    else
    {
        $but_no = $status;
        $but_no =~ s/(\d+)(.*)\|(.*)/$1/g;
        $contexto = $status;
        $contexto =~ s/([^\|]*).*/$1/g;
        $contexto =~ m/(.*)\@(.*)/;

        if (defined($2))
        {
            $contexto = $2;
            $but_no .= "\@$contexto";
        }
        else
        {
            $contexto = "";
        }
        $status =~ m/(.*)\|(.*)\|(.*)/;
        $cmd  = $2;
        $data = $3;
    }

    if ($flash_contexto{$socket} ne $contexto && $but_no ne "0")
    {

        # If the context does not match, exit without queueing anything
        return;
    }

    if (!defined($no_encryption{"$socket"}))
    {
        $no_encryption{"$socket"} = 0;
    }

    $noencriptado = "<response btn=\"$but_no\" cmd=\"$cmd\" data=\"$data\"/>\0";

    if (!defined($keys_socket{"$socket"}) || $nocrypt == 1 || $no_encryption{"$socket"} == 1)
    {
        $encriptado = "<response btn=\"$but_no\" cmd=\"$cmd\" data=\"$data\"/>\0";
    }
    else
    {
        $cmd_crypt  = &TEAencrypt($cmd,  $keys_socket{"$socket"});
        $data_crypt = &TEAencrypt($data, $keys_socket{"$socket"});
        $encriptado = "<response btn=\"$but_no\" cmd=\"$cmd_crypt\" data=\"$data_crypt\">\0";
    }
    if (!defined($ip_addy{$socket}))
    {
        log_debug("Skip actual_syswrite to $socket cause it does not exists!", 128);
    }
    else
    {
        actual_syswrite($socket, $encriptado, "isclient", $noencriptado);
    }

    #push @{$client_queue_nocrypt{$socket}}, $noencriptado;
    #push @{$client_queue{$socket}},         $encriptado;
}

sub manager_login_md5
{
    my $challenge = shift;
    my $handle    = shift;
    my @partes    = split(/\|/, $manager_socket{$handle});

    my $md5clave = MD5HexDigest($challenge . $partes[2]);

    $command = "Action: Login\r\n";
    $command .= "Username: $partes[1]\r\n";
    $command .= "AuthType: MD5\r\n";
    $command .= "Key: $md5clave\r\n\r\n";
    send_command_to_manager($command, $handle, 1);
}

sub send_command_to_manager
{
    my $comando       = shift;
    my $socket        = shift;
    my $noneedtoauth  = shift;
    my @todos_sockets = ();

    if (!defined($socket))
    {
        return;
    }

    if (!defined($autenticado{$socket}) && !defined($noneedtoauth))
    {
        log_debug("Cannot send command $comando, manager connection to " . $ip_addy{$socket} . " failed", 1);
        return;
    }

    my @partes = split(/\|/, $manager_socket{$socket});

    #    $comando = "";

    if ($comando eq "")
    {
        return;
    }

    if (!defined($socket))
    {
        @todos_sockets = @p;
    }
    else
    {
        push @todos_sockets, $socket;
    }

    foreach (@todos_sockets)
    {
        my $sockwrite = $_;
        if (!defined($sockwrite) || $sockwrite eq "") { next; }
        my @lineas = split("\r\n", $comando);
        foreach my $linea (@lineas)
        {

            #           syswrite($sockwrite, "$linea\r\n");
            push @{$manager_queue{$sockwrite}}, "$linea\r\n";

            # my $linea_formato = sprintf("%-15s -> %s", $partes[0], $linea);

            #            log_debug("$partes[0] -> $linea", 2);
            #log_debug("$linea_formato", 2);
        }
        $global_verbose = "separator";
        push @{$manager_queue{$sockwrite}}, "\r\n";

        #        syswrite($sockwrite, "\r\n");
    }
}

sub split_callerid
{
    my $clid         = shift;
    my @return       = ();
    my $calleridname = "";
    my $calleridnum  = "";

    if ($clid =~ /</)
    {
        $clid =~ /"?(.*)<(.*)>/;
        $calleridname = $1;
        $calleridnum  = $2;
    }
    else
    {
        $calleridnum  = $clid;
        $calleridname = "unknown";
    }
    push @return, $calleridnum;
    push @return, $calleridname;

    return @return;
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
    foreach my $file (@all_flash_files)
    {
        log_debug("Removing $file...", 1);
        unlink($file);
    }
    foreach my $hd ($O->handles)
    {
        my $peer_ip = $ip_addy{$hd};
        if (defined($peer_ip))
        {
            log_debug("Closing " . $peer_ip, 1);
        }

        $O->remove($hd);
        close($hd);
    }

    log_debug("Exiting...", 1);
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

    if ($clid_privacy)
    {
        return "n/a";
    }

    @chars_number = split(//, $numero);
    @chars_format = split(//, $format);

    @chars_format = reverse @chars_format;

    my $parate = 0;
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
            if ($parate) { last; }

            if ($_ eq "x" or $_ eq "X")
            {
                $parate = 1;
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
    my $randpassword = join '', map $alphanumeric[rand @alphanumeric], 0 .. $passwordsize;

    return $randpassword;
}

sub sends_incorrect
{
    my $socket = shift;
    my $manda  = "0|incorrect|0";
    my $T      = send_status_to_flash($socket, $manda, 0);
    print " Manda icorrect!\n";
}

sub sends_correct
{
    my $socket = shift;
    my $manda  = "0|correct|0";
    my $T      = send_status_to_flash($socket, $manda, 0);
}

sub sends_version
{
    my $socket   = shift;
    my $nocrypt  = 0;
    my $contexto = $flash_contexto{$socket};
    my $boton    = "0";
    if ($contexto ne "")
    {
        $boton .= "\@$contexto";
    }
    my $version_string = "$boton|version|$FOP_VERSION";
    if (!$keys_socket{"$socket"})
    {
        $nocrypt = 1;
    }
    send_status_to_flash($socket, $version_string, $nocrypt);
}

sub sends_key
{

    # Generate random key por padding the password
    # and write it to the client
    my $socket  = shift;
    my $keylen  = int(rand(22));
    my $nocrypt = 0;
    $keylen += 15;
    my $randomkey = generate_random_password($keylen);
    my $mandakey  = "$randomkey|key|0";
    if (!$keys_socket{"$socket"})
    {
        $nocrypt = 1;
    }
    $keys_socket{"$socket"} = $randomkey;
    send_status_to_flash($socket, $mandakey, $nocrypt);
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

        substr($context->{buffer}, $index, $partLen) = substr($input, 0, $partLen);

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
    substr($context->{buffer}, $index, $inputLen - $i) = substr($input, $i, $inputLen - $i);
}

# MD5 finalization. Ends an MD5 message-digest operation, writing the
# the message digest and zeroizing the context.

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
          (ord(substr($input, $j + 0, 1))) | (ord(substr($input, $j + 1, 1)) << 8) |
          (ord(substr($input, $j + 2, 1)) << 16) | (ord(substr($input, $j + 3, 1)) << 24);
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
        push @str, chr(0xFF & ($i >> 24)), chr(0xFF & ($i >> 16)), chr(0xFF & ($i >> 8)), chr(0xFF & $i);
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
        my $padding = pack 'CCCCCCC', &rand_byte, &rand_byte, &rand_byte, &rand_byte, &rand_byte, &rand_byte,
          &rand_byte;
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
        $sum += 0x9e3779b9;                                                              # TEA magic number delta
        $v0  += (($v1 << 4) + $k0) ^ ($v1 + $sum) ^ ((0x07FFFFFF & ($v1 >> 5)) + $k1);
        $v1  += (($v0 << 4) + $k2) ^ ($v0 + $sum) ^ ((0x07FFFFFF & ($v0 >> 5)) + $k3);
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
        $v1 -= (($v0 << 4) + $k2) ^ ($v0 + $sum) ^ ((0x07FFFFFF & ($v0 >> 5)) + $k3);
        $v0 -= (($v1 << 4) + $k0) ^ ($v1 + $sum) ^ ((0x07FFFFFF & ($v1 >> 5)) + $k1);
        $sum -= 0x9e3779b9;
    }
    return ($v0, $v1);
}

sub rand_byte
{
    if (!$rand_byte_already_called)
    {
        srand(time() ^ ($$ + ($$ << 15)));    # could do better, but its only padding
        $rand_byte_already_called = 1;
    }
    int(rand 256);
}

#
# End TEA

sub print_datos
{
    if ($debug & 1)
    {
        my $num = shift;

        if (keys(%datos))
        {
            print "---------------------------------------------------\n";
            print "DATOS $num\n";
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
            print "NO DATOS TO DISPLAY\n";
        }
    }
}

sub print_cachehit
{
    if ($debug & 1)
    {
        print "---------------------------------------------------\n";
        print "CACHE HIT\n";
        print "---------------------------------------------------\n";
        if (keys(%cache_hit))
        {
            for (keys %cache_hit)
            {
                print "key $_\n";

                if (defined(@{$cache_hit{$_}}))
                {
                    my @final = ();
                    foreach my $val (@{$cache_hit{$_}})
                    {
                        print "\tcache_hit($_) = $val\n";
                    }
                }
            }
        }
        else
        {
            print "NO CACHE HITS TO DISPLAY\n";
        }
        print "---------------------------------------------------\n";
    }
}

sub print_linkbot
{
    if ($debug & 1)
    {
        print "---------------------------------------------------\n";
        print "LINKS BOTONES\n";
        print "---------------------------------------------------\n";
        if (keys(%linkbot))
        {
            for (keys %linkbot)
            {
                if (defined(@{$linkbot{$_}}))
                {
                    my @final = ();
                    foreach my $val (@{$linkbot{$_}})
                    {
                        print "\tlinkbot($_) = $val\n";
                    }
                }
            }
        }
        else
        {
            print "NO DATOS TO DISPLAY\n";
        }
        print "---------------------------------------------------\n";
    }
}

sub print_sesbot
{
    my $quien = shift;
    if ($debug & 1)
    {
        print "---------------------------------------------------\n";
        print "SESIONES BOTONES $quien\n";
        print "---------------------------------------------------\n";
        if (keys(%sesbot))
        {
            for (keys %sesbot)
            {
                if (defined(@{$sesbot{$_}}))
                {
                    my @final = ();
                    foreach my $val (@{$sesbot{$_}})
                    {
                        print "\tsesbot($_) = $val\n";
                    }
                }
            }
        }
        else
        {
            print "NO DATOS TO DISPLAY\n";
        }
        print "---------------------------------------------------\n";
    }
}

sub print_instancias
{
    if ($debug & 1)
    {
        my $num = shift;
        print "---------------------------------------------------\n";
        print "Instancias 2 $num\n";
        print "---------------------------------------------------\n";
        foreach my $caca (sort (keys(%instancias)))
        {
            print $caca. "\n";
            foreach my $pipu (sort (keys(%{$instancias{$caca}})))
            {
                print "\t$pipu = $instancias{$caca}{$pipu}\n";
            }
        }
        print "---------------------------------------------------\n";
    }
}

sub print_botones
{
    if ($debug & 1)
    {
        my $num = shift;
        print "---------------------------------------------------\n";
        print "Botones $num\n";
        print "---------------------------------------------------\n";
        foreach (sort (keys(%buttons)))
        {
            printf("%-20s %-10s %-10s\n", $_, $buttons{$_}, $button_server{$buttons{$_}});
        }
    }
}

sub print_cola_write
{
    my $socket = shift;
    if (!defined($socket))
    {
        for (keys %client_queue)
        {
            my $contame = 0;
            foreach my $val (@{$client_queue{$_}})
            {
                $contame++;
                print "cola $contame $_ comando $val\n";
            }
        }
    }
    else
    {
        my $contame = 0;
        foreach my $val (@{$client_queue{$socket}})
        {
            $contame++;
            print "cola $contame $socket comando $val\n";
        }
    }
}

sub print_clients
{
    if ($debug & 1)
    {
        my $number_of_flash_clients_connected = @flash_clients;

        if ($number_of_flash_clients_connected > 0)
        {
            print "\nFlash clients connected: $number_of_flash_clients_connected\n";
            print "---------------------------------------------------\n";

            foreach my $C (@flash_clients)
            {
                print peerinfo($C) . " $C\n";
            }
            print "---------------------------------------------------\n";
        }
        else
        {
            print "No flash clients connected\n";
        }
    }
}

sub print_status
{
    if (keys(%estadoboton))
    {
        print "---------------------------------------------------\n";
        print "ESTADO BOTONES\n";
        print "---------------------------------------------------\n";
        for (keys %estadoboton)
        {
            my $separador = 0;
            my $nroboton  = $_;
            print "$nroboton\t $estadoboton{$nroboton}\t $statusboton{$nroboton}\n";
        }
        print "---------------------------------------------------\n";
    }
    else
    {
        print "No estadoboton populated\n";
    }

    if (keys(%botonled))
    {
        print "----- LEDS --------\n";
        for (keys %botonled)
        {
            print "$_ = $botonled{$_} $botonlabel{$_}\n";
        }
    }
    if (keys(%botonvoicemail))
    {
        print "----- VOICEMAIL --------\n";
        for (keys %botonvoicemail)
        {
            print "$_ = $botonvoicemail{$_}\n";
        }
    }
}
