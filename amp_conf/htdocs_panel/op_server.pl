#!/usr/bin/perl -w 

#  Flash Operator Panel.    http://www.asternic.org
#
#  Copyright (c) 2004 Nicolás Gudiño.  All rights reserved.
#
#  Nicolás Gudiño <nicolas@house.com.ar>
#
#  Redistribution and use in source and binary forms, with or without
#  modification, are permitted provided that the following conditions
#  are met:
#
#  1. Redistributions of source code must retain the above copyright
#     notice, this list of conditions and the following disclaimer.
#  2. Redistributions in binary form must reproduce the above copyright
#     notice, this list of conditions and the following disclaimer in the
#     documentation and/or other materials provided with the distribution.
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
#

use strict;
use IO::Socket;
use IO::Select;
use POSIX qw(setsid);

my %datos              = ();
my %auto_conference    = ();
my %buttons            = ();
my %textos             = ();
my %iconos             = ();
my %extension_transfer = ();
my %flash_contexto     = ();
my $bloque_completo;
my $bloque_final;
my $todo;
my @bloque;
my @respuestas;
my @flash_clients;
my @status_active;
my %mailbox;
my %instancias;
my %orden_instancias;
my $p;
my $O;
my @S;
my $manager_host;
my $manager_user;
my $manager_secret;
my $web_hostname;
my $listen_port;
my $security_code;
my $flash_dir;
my $poll_interval;
my $kill_zombies;
my $debug;
my $flash_file;
my $auto_conf_exten;
my $meetme_context;
my $clid_format;
my $directorio = $0;
my $papa;

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
    $/ = "\n";
    my %config = ();
    open(CONFIG, "<$directorio/op_server.cfg")
      or die("Could not open op_server.cfg. Aborting...");
    while (<CONFIG>)
    {
        $_ =~ s/\s+//g;
        $_ =~ s/(.*)#(.*)$/$1/g;
        if (!/^$/)
        {
            my ($variable_name, $value) = split(/=/, $_);
            $variable_name =~ tr/A-Z/a-z/;
            $value         =~ s/\"//g;
            $config{"$variable_name"} = $value;
        }
    }
    close(CONFIG);

    $manager_host    = $config{"manager_host"};
    $manager_user    = $config{"manager_user"};
    $manager_secret  = $config{"manager_secret"};
    $web_hostname    = $config{"web_hostname"};
    $listen_port     = $config{"listen_port"};
    $security_code   = $config{"security_code"};
    $flash_dir       = $config{"flash_dir"};
    $poll_interval   = $config{"poll_interval"};
    $kill_zombies    = $config{"kill_zombies"};
    $debug           = $config{"debug"};
    $auto_conf_exten = $config{"auto_conference_extension"};
    $meetme_context  = $config{"conference_context"};
    $clid_format     = $config{"clid_format"};

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
    if (!defined $kill_zombies)
    {
        die("Missing kill_zombies in op_server.cfg!");
    }
    if (!defined $clid_format)
    {
        $clid_format = "(xxx) xxx-xxxx";
    }
    if (!defined $debug) { die("Missing debug in op_server.cfg!"); }
    $/ = "\0";

}

sub read_buttons_config()
{
    $/ = "\n";

    open(CONFIG, "< $directorio/op_buttons.cfg")
      or die("Could not open op_buttons.cfg. Aborting...");

    while (<CONFIG>)
    {
        my $campo1 = "";
        my $campo3 = "";
        my $campo4 = "";
        my $campo5 = "";
        my $contexto;
        my $indice;
        my $canal;
        my $canel;
        my @campos = ();
        chop($_);
        $_ =~ s/^\s+(.*)/$1/g;
        next if ($_ =~ /^#/);

        while ($_ =~ m/"([^"\\]*(\\.[^"\\]*)*)",?|([^,]+),?|,/g)
        {
            $campo1 = $1;
            $campo3 = $3;
            $campo1 =~ s/^\s+//g if (defined($1));
            $campo3 =~ s/^\s+//g if (defined($3));
            push(@campos, defined($campo1) ? $campo1 : $campo3);
        }
        push(@campos, undef) if $_ =~ m/,$/;
        $campos[0] =~ tr/a-z/A-Z/;
        $campos[1] =~ tr/a-z/A-Z/;
        my @partes = split(/\@/, $campos[1]);
        if (defined($partes[1]) && length($partes[1]) > 0)
        {
            $campos[1] = $partes[0];
            $contexto = $partes[1];
            $contexto =~ s/^DEFAULT$//g;
            $canal = $campos[0] . "&" . $contexto;
        }
        else
        {
            $contexto = "";
            $canal    = $campos[0];
        }
        my @repetido = split(/;/, $partes[0]);
        my $cuantos = @repetido;
        if ($cuantos > 1)
        {
            $instancias{$canal} = [];
            my $contador = 0;
            foreach my $numeroboton (@repetido)
            {
                $contador++;
                if (length($contexto) > 0)
                {
                    $indice = $numeroboton . "\@" . $contexto;
                    $canel  = $campos[0] . "=" . $contador . "\&" . $contexto;
                }
                else
                {
                    $indice = $numeroboton;
                    $canel  = $canal . "=" . $contador;
                }

                # $buttons{"${canal}=${contador}"} = $indice;
                $buttons{"$canel"} = $indice;
                $textos{"$indice"} = $campos[2] . " " . $contador;
                $iconos{"$indice"} = $campos[4];
            }
        }
        else
        {
            if (length($contexto) > 0)
            {
                $indice = $partes[0] . "\@" . $contexto;
            }
            else
            {
                $indice = $partes[0];
            }
            $buttons{"$canal"} = $indice;

            # El contexto para nombre de canal se adjunta con &
            # El contexto como indice se adjunto con @
            $textos{$indice} = $campos[2];
            $iconos{$indice} = $campos[4];
        }
        $extension_transfer{"$canal"} = $campos[3];
        if (defined($campos[5]))
        {
            my @partes = split(/@/, $campos[3]);
            $mailbox{"$canal"} = $partes[0] . "@" . $campos[5];
        }
    }
    close(CONFIG);
    $/ = "\0";
}

sub genera_config
{
    $/ = "\n";
    my $style_variables = "";
    my @contextos       = ();

    open(STYLE, "<op_style.cfg")
      or die("Could not open op_style.cfg for reading");
    while (<STYLE>)
    {
        chop($_);
        $_ =~ s/^\s+//g;
        $style_variables .= $_ . "&";
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
    print VARIABLES "&" . $style_variables;
    print VARIABLES "CheckDone=1";
    close(VARIABLES);

    # Writes variables.txt for each context defined
    foreach (@contextos)
    {
        my $flash_context_file = $flash_dir . "/variables" . $_ . ".txt";
        open(VARIABLES, ">$flash_context_file")
          or die(
            "Could not write configuration data $flash_file.\nCheck your file permissions\n"
          );
        print VARIABLES "server=$web_hostname&port=$listen_port";
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
        print VARIABLES "&" . $style_variables;
        print VARIABLES "CheckDone=1";
        close(VARIABLES);
    }
    $/ = "\0";
}

sub dump_internal_hashes_to_stdout
{

    print "---------------------------------------------------\n";
    foreach (sort (keys(%buttons)))
    {
        printf("%-20s %-10s\n", $_, $buttons{$_});
    }
    print "---------------------------------------------------\n";
    foreach (sort (keys(%orden_instancias)))
    {
        printf("%-20s %-10s\n", $_, $orden_instancias{$_});
    }
    print "---------------------------------------------------\n";

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
    read_buttons_config();
    read_server_config();
    genera_config();
}

sub manager_reconnect()
{
    my $attempt        = 1;
    my $total_attempts = 60;

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
    send_command_to_manager(
        "Action: Login\r\nUsername: $manager_user\r\nSecret: $manager_secret\r\n\r\n"
    );
}

# Checks file_name to find out the directory where the configuration
# files should reside

$directorio =~ s/(.*)\/(.*)/$1/g;
chdir($directorio);
$directorio = `pwd`;
chop($directorio);

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

send_command_to_manager(
    "Action: Login\r\nUsername: $manager_user\r\nSecret: $manager_secret\r\n\r\n"
);

my $m =
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
                    $i =~ s/([^\r])\n/$1\r\n/g;    # Reemplaza \n solo por \r\n

                    $bloque_completo = "" if (!defined($bloque_completo));
                    $papa            = $i;
                    $papa            = substr($bloque_completo, -5) . $papa;
                    if ($papa =~ "\r\n\r\n" || $i =~ /\0/)
                    {
                        log_debug("** End of block", 16);
                        $bloque_final    = $bloque_completo . $i;
                        $bloque_completo = "";
                        $bloque_final =~
                          s/([^\r])\n/$1\r\n/g;    # Reemplaza \n solo por \r\n
                        my @lineas = split("\r\n", $bloque_final);
                        foreach my $linea (@lineas)
                        {
                            if (length($linea) < 2)
                            {
                                $bloque_completo = $linea;

                                #my $largo = length($linea);
                                #$largo = $largo * -1;
                                #$bloque_final=substr($bloque_final,$largo);
                            }
                            else
                            {
                                if ($_ == $p) { log_debug("<- $linea", 1); }
                            }
                        }
                        if ($_ == $p) { log_debug(" ", 1); }
                    }
                    else
                    {
                        my $quehay = substr($i, -2);
                        $bloque_completo .= $i;
                        next;
                    }

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
                                my $contador = 0;
                                foreach $p (@lineas)
                                {
                                    log_debug("** Parse line: $p", 64);
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
                                        $bloque[$contador]{"$atributo"} =
                                          $valor;
                                    }
                                }
                                log_debug(
                                    "** There are $contador blocks for processing",
                                    32
                                );
                                @respuestas = ();
                                log_debug("** Answer block cleared", 32);
                                @respuestas =
                                  digiere_el_bloque_y_devuelve_array_de_respuestas
                                  (@bloque);
                            }
                            elsif ($bloque_final =~ /--END COMMAND--/)
                            {
                                log_debug(
                                       "** There's an 'END' in the event block",
                                       32);
                                $todo .= $bloque_final;
                                procesa_comando($todo);
                                my $cuantos = @bloque;
                                log_debug(
                                     "There are $cuantos blocks for processing",
                                     32
                                );
                                @respuestas =
                                  digiere_el_bloque_y_devuelve_array_de_respuestas
                                  (@bloque);
                                $todo = "";
                            }
                            elsif ($bloque_final =~ /<msg/)
                            {
                                log_debug(
                                    "** Processing command received from flash clients...",
                                    32
                                );
                                procesa_comando_cliente($bloque_final, $_);
                                @respuestas   = ();
                                $bloque_final = "";
                                $todo         = "";
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
                            my $contador = 0;

                            # Send messages to Flash clientes
                            foreach my $valor (@respuestas)
                            {
                                my $contextores = $valor;
                                $contextores =~
                                  s/<response data=\"(.*)\|.*\|.*/$1/g;
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
                                          syswrite($C, $valor, length($valor));
                                        log_debug("=> $valor", 8);
                                        $contador++;
                                    }
                                }
                            }    # end foreach respuestas
                            if ($contador > 0)
                            {
                                log_debug(" ", 8);
                            }    # Cosmetic separator line in debug
                        }
                    }    # end foreach handles
                }
            }
        }    # end foreach @S -> can read
    }    # while can read
}    # endless loop

sub digiere_el_bloque_y_devuelve_array_de_respuestas
{
    log_debug("** ---- Start sub digiere_el_bloque... ----", 16);
    my $bloque     = shift;
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
    foreach my $blaque (@bloque)
    {
        log_debug("** I will process one block", 16);
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

                # There is a channel defined, erase the %hash
                if ($quehace eq 'corto')
                {
                    while (my ($key, $val) = each(%{$datos{$uniqueid}}))
                    {
                        $todo .= "$key = $val\n" if ($key ne "E");
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

                if ($quehace eq "link")
                {

                    # This block is for catching trunk buttons
                    # and remove the parked text in case of a link
                    my $segundocanal = $datos{$uniqueid}{"Channel"};
                    $segundocanal =~ tr/a-z/A-Z/;

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

                        # push(@posibles_internos, $canalglobal);
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
                    $interno      = $buttons{$canal};
                    $interno      = "" if (!defined($interno));
                    $mensajefinal =
                      "<response data=\"$interno|$quehace|$dos\"/>\0";

                    for $quehay (keys %datos)
                    {
                        log_debug("** Active: $quehay", 16);
                    }

                    if (check_if_extension_is_busy($canal) eq "si"
                        && $quehace eq 'corto')
                    {
                        log_debug("** Hangup but still busy", 16);

                        # Como SIP puede transferir por si mismo, quedan zombies en Asterisk
                        # Y no detecta bien el estado del cliente, forzamos un status
                        #### log_debug("** Force a status command", 16);
                        #### send_command_to_manager("Action: Status\r\n\r\n");
                    }
                    else
                    {
                        if (defined($mensajefinal) && $interno ne "")
                        {
                            push(@respuestas, $mensajefinal);
                            if ($todo ne "")
                            {
                                my $otromensajefinal =
                                  "<response data=\"$interno|info|$todo\"/>\0";
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
    log_debug("** ---- End sub digiere_el_bloque ----", 16);
    return @respuestas;
}

sub procesa_comando_cliente
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
    my @pedazos;

    log_debug("<= $comando\n",                         4);
    log_debug("** Incoming command from flash client", 16);

    $comando =~ s/<msg data=\"(.*)\"\s?\/>/$1/g;    # Removes XML markup
    ($datos, $accion, $password) = split(/\|/, $comando);
    chop($password);

    $datos =~ s/_level0\.casilla(\d+)/$1/g;
    undef $origin_channel;

    # Flash clients send a "contexto" command on connect, indicating
    # the panel context they want to receive. We populate a hash with
    # sockets/contexts in order to send only the events they want
    # And because this is an initial connection, it triggers a status
    # request to Asterisk
    if ($accion =~ /^contexto\d+/)
    {

        my ($nada, $contextoenviado) = split(/\@/, $datos);

        if (defined($contextoenviado))
        {
            $flash_contexto{$socket} = $contextoenviado;
        }
        else
        {
            $flash_contexto{$socket} = "";
        }

        send_initial_status();
    }

    # We have the origin button number from the drag&drop in the 'datos'
    # variable. We need to traverse the %buttons hash in order to extract
    # the channel name and the panel context, used to find the destination
    # button of the command if any
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

    if (defined($origin_channel))
    {
        if ("$password" eq "$security_code")
        {
            log_debug(
                "** The channel selected  is $origin_channel and the security code matches",
                16
            );

            if ($accion =~ /-/)
            {

                #if action has an "-" the command has clid text to pass
                @partes = split(/-/, $accion);
                $ultimo = @partes;
                $ultimo--;
                $btn_destino = $partes[$ultimo];
                $ultimo--;
                $clid = $partes[$ultimo];
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

                        log_debug(
                            "** !! $canal_transferir[0] $links[0] will be conferenced together with $origin_channel ($originate)",
                            16
                        );
                        $comando = "Action: Redirect\r\n";
                        $comando .= "Channel: $canal_transferir[0]\r\n";
                        $comando .= "ExtraChannel: $links[0]\r\n";
                        $comando .= "Exten: $auto_conf_exten\r\n";
                        $comando .= "ActionID: 1234\r\n";
                        $comando .= "Context: $meetme_context\r\n";
                        $comando .= "Priority: 1\r\n\r\n";
                        $auto_conference{$canal_transferir[0]} =
                          $origin_channel;
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

                #$clid =
                #  "$clid" . " <" . $extension_transfer{"$origin_channel"} . ">";
                if ($origin_channel =~ /^IAX2\[/)
                {
                    $origin_channel =~ s/^IAX2\[(.*)\]/IAX2\/$1/g;
                }
                $comando = "Action: Originate\r\n";
                $comando .= "Channel: $origin_channel\r\n";

                #$comando .= "Callerid: $clid\r\n";
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
        }
        else
        {
            log_debug("** Password mismatch -$password-$security_code-!", 1);
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

    for my $key (keys %buttons)
    {
        if ($key =~ /^\d+$/)
        {

            # If there is a meetme defined, query for status
            send_command_to_manager(
                "Action: Command\r\nActionID: meetme_$key\r\nCommand: meetme list $key\r\n\r\n"
            );
        }
    }

    send_command_to_manager("Action: QueueStatus\r\n\r\n");
}

sub procesa_comando
{

    # This subroutine process the output for a manager "Command"
    # sent, as 'sip show peers'

    log_debug("** --- Start sub procesa_comando -----\n", 16);

    my $texto = shift;
    @bloque = ();
    my @lineas     = split("\r\n", $texto);
    my $contador   = 0;
    my $interno    = "";
    my $estado     = "";
    my $nada       = "";
    my $conference = 0;

    if ($texto =~ "ActionID: meetme")
    {

        # Its a meetme status report
        foreach my $valor (@lineas)
        {
            $valor =~ s/\s+/ /g;
            my ($key, $value) = split(/: /, $valor);

            if (defined($key))
            {

                if ($key eq "ActionID")
                {
                    $value =~ s/meetme_(\d+)$/$1/g;
                    $conference = $value;
                }
                if ($key eq "User #")
                {
                    $contador++;
                }
            }
        }

        if ($contador > 0)
        {
            $bloque[0]{"Event"}  = "MeetmeJoin";
            $bloque[0]{"Meetme"} = $conference;
            $bloque[0]{"Count"}  = $contador;
            $bloque[0]{"Fake"}   = "hola";
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
    log_debug("** --- End sub procesa_comando ---", 16);
}

sub procesa_bloque
{

    log_debug("** --- Start sub procesa_bloque ---", 16);

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

    undef $unico_id;

    while (my ($key, $val) = each(%bloque))
    {
        if ($key eq "Event")
        {
            $evento = "";
            $hash_temporal{$key} = $val;
            if    ($val =~ /Newchannel/)      { $evento = "newchannel"; }
            if    ($val =~ /Newcallerid/)     { $evento = "newcallerid"; }
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
            elsif ($val =~ /Link/)            { $evento = "link"; }
            elsif ($val =~ /^Join/)           { $evento = "join"; }
            elsif ($val =~ /^MeetmeJoin/)     { $evento = "meetmejoin"; }
            elsif ($val =~ /^MeetmeLeave/)    { $evento = "meetmeleave"; }
            elsif ($val =~ /^SIPPeerRegistration/)
            {
                $evento = "sipregistration";
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
    $enlazado .= " - " . $datos{$unico_id}{"Context"}
      if defined($datos{$unico_id}{"Context"});
    $enlazado .= ":" . $datos{$unico_id}{"Priority"}
      if defined($datos{$unico_id}{"Priority"});

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

        $canal = $hash_temporal{"Meetme"};
        $canal =~ tr/a-z/A-Z/;

        # Originates a call to the third extension if it finds
        # an auto created conference

        for $quehay (keys %auto_conference)
        {
            if ($quehay eq $hash_temporal{"Channel"})
            {
                $originate = $auto_conference{"$quehay"};
            }
        }

        if ($originate ne "no")
        {
            my $comando = "Action: Originate\r\n";
            $comando .= "Channel: $originate\r\n";
            $comando .= "Exten: $canal\r\n";
            $comando .= "Context: $meetme_context\r\n";
            $comando .= "Priority: 1\r\n";
            $comando .= "\r\n";
            send_command_to_manager($comando);
        }

        $estado_final = "ocupado";
        my $plural = "";
        if (!defined($hash_temporal{"Fake"}))
        {
            if (!defined($datos{$canal}{"Count"}))
            {
                $datos{$canal}{"Count"} = 0;
            }
            $datos{$canal}{"Count"}++;
        }

        if ($datos{$canal}{"Count"} > 1) { $plural = "s"; }
        $texto =
            $datos{$canal}{"Count"}
          . " member$plural on conference ["
          . $datos{$canal}{"Count"}
          . " Member$plural].";
        $unico_id = $canal;
        $return   = "$canal|$estado_final|$texto|$unico_id|$canalid";
        $evento   = "";
    }

    if ($evento eq "meetmeleave")
    {
        $canal = $hash_temporal{"Meetme"};
        $canal =~ tr/a-z/A-Z/;
        $estado_final = "ocupado";
        my $plural = "";
        $datos{$canal}{"Count"}--;
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

        # Si es una extension nueva sin state, por defecto lo pone en UP
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

    if ($evento eq "sipregistration")
    {
        if (defined $hash_temporal{"Peername"})
        {
            $canal = $hash_temporal{"Peername"};
        }
        if (defined $hash_temporal{"Peer"})
        {
            $canal = $hash_temporal{"Peer"};
        }
        $state = $hash_temporal{"Status"};
        log_debug(
              "** Event sipregistration|peerstatus chan: $canal, state: $state",
              16);
        if ($state eq "Registred")
        {
            $estado_final = "registrado";
            $texto        = "Registrado";
        }
        elsif ($state eq "reachable")
        {
            $estado_final = "registrado";
            $texto        = "Registrado";
        }
        elsif ($state eq "unreachable")
        {
            $estado_final = "unreachable";
            $texto        = "No Reachable";
        }
        elsif ($state eq "lagged")
        {
            $estado_final = "noregistrado";
            $texto        = "Lagged";
        }
        $evento = "";
        $return = "SIP/$canal|$estado_final|$texto|$unico_id|$canalid";
        log_debug("** $return", 16);
    }

    if ($evento ne "")
    {
        log_debug("** Event $evento", 16);

        while (my ($key, $val) = each(%hash_temporal))
        {
            $datos{$unico_id}{"$key"} = $val;
        }

        if ($evento eq "hangup")
        {
            $datos{$unico_id}{'State'} = "Down";
        }
        log_debug("** Event " . $datos{$unico_id}{'Event'}, 32);

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

        if (defined($canal))
        {
            if (defined($instancias{$canal}) && $evento ne "regstatus")
            {
                $canal = count_instances_for_channel($canalsesion);
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
            $estado_final = "ringing";
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
    log_debug("** --- End sub procesa_bloque -----", 16);

    if ($canal ne "" && $estado_final ne "")
    {
        log_debug("** Return $return", 16);
        return $return;
    }

}

sub count_instances_for_channel
{
    my $canalid = shift;
    my $sesion;
    my $canalglobal;
    my @uniq;

    $canalid =~ s/(.*)<(.*)>/$1/g;
    $canalid =~ tr/a-z/A-Z/;

    log_debug("** Count instances channel $canalid", 16);
    $canalglobal = $canalid;
    $canalglobal =~ s/(.*)[-\/](.*)/$1/g;
    $canalglobal =~ s/IAX2\/(.*)@(.*)/IAX2\/$1/g;
    $canalglobal =~ s/IAX2\[(.*)@(.*)\]/IAX2\[$1\]/g;
    $canalglobal =~ tr/a-z/A-Z/;

    my $cuantos = @{$instancias{$canalglobal}};

    if (!defined($orden_instancias{$canalid}))
    {
        $cuantos++;
        $orden_instancias{$canalid} = $cuantos;
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

sub extraer_todos_los_enlaces_de_un_canal
{
    my $canal  = shift;
    my $quehay = "";
    my @result = ();
    for $quehay (keys %datos)
    {
        my $canalaqui = 0;
        my $linkeado  = "";
        while (my ($key, $val) = each(%{$datos{$quehay}}))
        {
            if ($val =~ /^$canal/i && $key =~ /^Chan/i)
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
                        log_debug(
                                "** Extension still busy $canal $interno!!!!!!",
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
    }

    alarm($poll_interval);
}

sub send_command_to_manager
{
    my $comando = shift;
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
    log_debug("Exiting...", 0);

    foreach my $hd ($O->handles)
    {
        $O->remove($hd);
        close($hd);
    }

    exit(0);
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

sub format_clid()
{

    # Horrible subroutine to format the caller id number
    # The format string is in the form "(xxx) xxx-xxxx"
    # Every x is counted as a digit, any other text is
    # displayed as is. The digits are replaced from right
    # to left. If there are digits left, they are discarded

    my $numero          = shift;
    my $format          = shift;
    my $contandonumeros = 0;
    my $campo           = 0;
    my @largo           = ();
    my @cempo           = ();
    my $finalconformato = "";
    my $letra           = "";
    my $parte           = "";
    my $queparte        = "";
    my $regexp          = "";
    my $cuantasletras;
    my $largonumero;
    my $largonumerofinal;
    my $a = 0;

    if (!is_number($numero))
    {
        return $numero;
    }

    for (my $a = 0 ; $a < length($format) ; $a++)
    {
        my $letra = substr($format, $a, 1);

        if ($letra eq "x")
        {
            if ($contandonumeros == 1)
            {
                $largo[$campo]++;
                next;
            }
            else
            {
                $contandonumeros = 1;
                $campo++;
                next;
            }
        }
        else
        {
            if ($contandonumeros == 1)
            {
                $largo[$campo]++;
                $regexp .= "\\" . $campo;
            }
            $contandonumeros = 0;
            $regexp .= $letra;
        }
    }

    if ($contandonumeros == 1)
    {
        $largo[$campo]++;
        $regexp .= "\\" . $campo;
    }

    for ($a = $campo ; $a > 0 ; $a--)
    {
        $largonumero      = length($numero);
        $cuantasletras    = $largo[$a] * -1;
        $largonumerofinal = $largonumero + $cuantasletras;
        $parte            = substr($numero, $cuantasletras);
        $numero           = substr($numero, 0, $largonumerofinal);
        $cempo[$a]        = $parte;
    }

    for ($a = 0 ; $a < length($regexp) ; $a++)
    {
        $letra = substr($regexp, $a, 1);
        if ($letra eq "\\")
        {
            $queparte = substr($regexp, $a + 1, 1);
            $finalconformato .= $cempo[$queparte];
            $a++;
        }
        else
        {
            $finalconformato .= $letra;
        }
    }
    return $finalconformato;
}
