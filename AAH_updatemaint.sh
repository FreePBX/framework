#!/bin/sh
#
# This script will restore the maintanance tab that was previoulsy included on
# AAH systems (Asterisk at Home).
#

mydir=/var/www/html/admin/modules
xmlfile=module.xml

# asteriskinfo
mysubdir=$mydir/asteriskinfo
if [ -d "$mysubdir" ]
then
    myfile=$mysubdir/$xmlfile
    if [ ! -e "$myfile" ]
    then
        touch $myfile
        echo "<module>" > $myfile
        echo "<rawname>asteriskinfo</rawname>" >> $myfile
        echo "<name>Asterisk Info</name>" >> $myfile
        echo "<version>1.0</version>" >> $myfile
        echo "<type>tool</type>" >> $myfile
        echo "<category>Maintenance</category>" >> $myfile
        echo "<menuitems>" >> $myfile
        echo "<asteriskinfo>Asterisk Info</asteriskinfo>" >> $myfile
        echo "</menuitems>" >> $myfile
        echo "</module>" >> $myfile
    else
        echo File $myfile Already Exists
    fi
    inffile=page.asteriskinfo.php
    myfile=$mysubdir/$inffile
    if [ ! -e "$myfile" ]
    then
        touch $myfile
        echo "<h2>Asterisk Info</h2>" > $myfile
        echo -e "<a href=\42modules/asteriskinfo/asterisk_info.php\42 target=\42_blank\42>Asterisk Info</a>" >> $myfile
    else
        echo File $myfile Already Exists
    fi
else
    echo Directory $mysubdir Does Not Exist
fi

# configedit
mysubdir=$mydir/configedit
if [ -d "$mysubdir" ]
then
    myfile=$mysubdir/$xmlfile
    if [ ! -e "$myfile" ]
    then
        echo "<module>" > $myfile
        echo "<rawname>configedit</rawname>" >> $myfile
        echo "<name>ConfigEdit</name>" >> $myfile
        echo "<version>1.0</version>" >> $myfile
        echo "<type>tool</type>" >> $myfile
        echo "<category>Maintenance</category>" >> $myfile
        echo "<menuitems>" >> $myfile
        echo "<configedit>Config Edit</configedit>" >> $myfile
        echo "</menuitems>" >> $myfile
        echo "</module>" >> $myfile
    else
        echo File $myfile Already Exists
    fi
    inffile=page.configedit.php
    myfile=$mysubdir/$inffile
    if [ ! -e "$myfile" ]
    then
        touch $myfile
        echo "<h2>Config Edit</h2>" > $myfile
        echo -e "<a href=\42modules/configedit/phpconfig.php\42 target=\42_blank\42>Config Edit</a>" >> $myfile
    else
        echo File $myfile Already Exists
    fi
else
    echo Directory $mysubdir Does Not Exist
fi

# ciscoconfig
mysubdir=$mydir/ciscoconfig
if [ -d "$mysubdir" ]
then
    myfile=$mysubdir/$xmlfile
    if [ ! -e "$myfile" ]
    then
        echo "<module>" > $myfile
        echo "<rawname>ciscoconfig</rawname>" >> $myfile
        echo "<name>Cisco Config</name>" >> $myfile
        echo "<version>1.0</version>" >> $myfile
        echo "<type>tool</type>" >> $myfile
        echo "<category>Maintenance</category>" >> $myfile
        echo "<menuitems>" >> $myfile
        echo "<ciscoconfig>CiscoConfig</ciscoconfig>" >> $myfile
        echo "</menuitems>" >> $myfile
        echo "</module>" >> $myfile
    else
        echo File $myfile Already Exists
    fi
    inffile=page.ciscoconfig.php
    myfile=$mysubdir/$inffile
    if [ ! -e "$myfile" ]
    then
        touch $myfile
        echo "<h2>Cisco Config</h2>" > $myfile
        echo -e "<a href=\42modules/ciscoconfig/cisco_cfg/phone.html\42 target=\42_blank\42>Cisco Config</a>" >> $myfile
    else
        echo File $myfile Already Exists
    fi
else
    echo Directory $mysubdir Does Not Exist
fi

# phpmyadmin
mysubdir=$mydir/phpmyadmin
if [ -d "$mysubdir" ]
then
    myfile=$mysubdir/$xmlfile
    if [ ! -e "$myfile" ]
    then
        echo "<module>" > $myfile
        echo "<rawname>phpmyadmin</rawname>" >> $myfile
        echo "<name>phpMyAdmin</name>" >> $myfile
        echo "<version>2.8.0.2</version>" >> $myfile
        echo "<type>tool</type>" >> $myfile
        echo "<category>Maintenance</category>" >> $myfile
        echo "<menuitems>" >> $myfile
        echo "<phpmyadmin>phpMyAdmin</phpmyadmin>" >> $myfile
        echo "</menuitems>" >> $myfile
        echo "</module>" >> $myfile
    else
        echo File $myfile Already Exists
    fi
    inffile=page.phpmyadmin.php
    myfile=$mysubdir/$inffile
    if [ ! -e "$myfile" ]
    then
        touch $myfile
        echo "<h2>phpMyaAmin</h2>" > $myfile
        echo -e "<a href=\42modules/phpmyadmin/phpMyAdmin\42 target=\42_blank\42>phpMyAdmin</a>" >> $myfile
    else
        echo File $myfile Already Exists
    fi
else
    echo Directory $mysubdir Does Not Exist
fi

# sysinfo
mysubdir=$mydir/sysinfo
if [ -d "$mysubdir" ]
then
    myfile=$mysubdir/$xmlfile
    if [ ! -e "$myfile" ]
    then
        echo "<module>" > $myfile
        echo "<rawname>sysinfo</rawname>" >> $myfile
        echo "<name>Sys Info</name>" >> $myfile
        echo "<version>1.0</version>" >> $myfile
        echo "<type>tool</type>" >> $myfile
        echo "<category>Maintenance</category>" >> $myfile
        echo "<menuitems>" >> $myfile
        echo "<sysinfo>Sys Info</sysinfo>" >> $myfile
        echo "</menuitems>" >> $myfile
        echo "</module>" >> $myfile
    else
        echo File $myfile Already Exists
    fi
    inffile=page.sysinfo.php
    myfile=$mysubdir/$inffile
    if [ ! -e "$myfile" ]
    then
        touch $myfile
        echo "<h2>Sys Info</h2>" > $myfile
        echo -e "<a href=\42modules/sysinfo/\42 target=\42_blank\42>Sys Info</a>" >> $myfile
    else
        echo File $myfile Already Exists
    fi
else
    echo Directory $mysubdir Does Not Exist
fi

# sysstatus
mysubdir=$mydir/sysstatus
if [ -d "$mysubdir" ]
then
    myfile=$mysubdir/$xmlfile
    if [ ! -e "$myfile" ]
    then
        echo "<module>" > $myfile
        echo "<rawname>sysstatus</rawname>" >> $myfile
        echo "<name>System Status</name>" >> $myfile
        echo "<version>1.0</version>" >> $myfile
        echo "<type>tool</type>" >> $myfile
        echo "<category>Maintenance</category>" >> $myfile
        echo "<menuitems>" >> $myfile
        echo "<sysstatus>System Status</sysstatus>" >> $myfile
        echo "</menuitems>" >> $myfile
        echo "</module>" >> $myfile
    else
        echo File $myfile Already Exists
    fi
else
    echo Directory $mysubdir Does Not Exist
fi
