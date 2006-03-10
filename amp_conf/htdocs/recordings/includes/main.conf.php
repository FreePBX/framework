<?php

/**
 * @file
 * site-specific configuration file.
 */

###############################
# AMP or standalone settings
###############################
#
# From AMP.  Used for logon to database.
#
$AMP_FUNCTIONS_FILES = "../admin/functions.php;../admin/functions.inc.php";
$AMPORTAL_CONF_FILE = "/etc/amportal.conf";

#
# Database host and name
#   Options: supported database types (others are supported, but not listed)
#     'mysql' - MySQL
#     'pgsql' - PostgreSQL
#     'oci8' - Oracle
#     'odbc' - ODBC
#
$ASTERISKMGR_DBHOST = "localhost";
$ASTERISK_DBHOST = "localhost";
$ASTERISK_DBNAME = "asterisk";
$ASTERISK_DBTYPE = "mysql";
$ASTERISKCDR_DBHOST = "localhost";
$ASTERISKCDR_DBNAME = "asteriskcdrdb";
$ASTERISKCDR_DBTYPE = "mysql";
$ASTERISKCDR_DBTABLE = "cdr";

#
# Standalone, for use without AMP
#   set use = true;
#   set asterisk_mgruser to Asterisk Call Manager username
#   set asterisk_mgrpass to Asterisk Call Manager password
#
$STANDALONE['use'] = false;
$STANDALONE['asterisk_mgruser'] = "";
$STANDALONE['asterisk_mgrpass'] = "";
$STANDALONE['asterisk_cdrdbuser'] = "";
$STANDALONE['asterisk_cdrdbpass'] = "";

###############################
# authentication settings
###############################
#
# For using the Call Monitor only
#   option: 0 - use Authentication, Voicemail, and Call Monitor
#           1 - use only the Call Monitor
#
$ARI_NO_LOGIN = 0;

#
# Admin only account
#
$ARI_ADMIN_USERNAME = "admin";
$ARI_ADMIN_PASSWORD = "ari_password";

#
# Admin extensions
#   option: Comma delimited list of extensions
#
$ARI_ADMIN_EXTENSIONS = "";

#
# Authentication password to unlock cookie password
#   This must be all continuous and only letters and numbers
#
$ARI_CRYPT_PASSWORD = "z1Mc6KRxA7Nw90dGjY5qLXhtrPgJOfeCaUmHvQT3yW8nDsI2VkEpiS4blFoBuZ";

###############################
# modules settings
###############################
#
# modules with admin only status (they will not be displayed for regular users)
#   option: Comma delimited list of module names (ie voicemail,callmonitor,help,settings)
#
$ARI_ADMIN_MODULES = "";

#
# disable modules (you can also just delete them from /recordings/modules without problems)
#   option: Comma delimited list of module names (ie voicemail,callmonitor,help,settings)
#
$ARI_DISABLED_MODULES = "";

#
# sets the default admin page
#   option: Comma delimited list of module names (ie voicemail,callmonitor,help,settings)
#
$ARI_DEFAULT_ADMIN_PAGE = "callmonitor";

#
# sets the default user page
#   option: Comma delimited list of module names (ie voicemail,callmonitor,help,settings)
#
$ARI_DEFAULT_USER_PAGE = "voicemail";

#
# enables ajax page refresh
#   option: 0 - disable ajax page refresh
#           1 - enable ajax page refresh
#
$AJAX_PAGE_REFRESH_ENABLE = 1;

#
# sets the default user page
#   option: refresh time in 'minutes:seconds' (0 to inifinity) : (0 to 59)
#
$AJAX_PAGE_REFRESH_TIME = '0:10';

###############################
# voicemail settings
###############################
#
# voicemail config.
#
$ASTERISK_VOICEMAIL_CONF = "/etc/asterisk/voicemail.conf";

#
# To set to a specific context.  
#   If using default or more than one context then leave blank
#
$ASTERISK_VOICEMAIL_CONTEXT = "";

#
# Location of asterisk voicemail recordings on server
#    Use semi-colon for multiple paths
#
$ASTERISK_VOICEMAIL_PATH = "/var/spool/asterisk/voicemail";

#
# valid mailbox folders
#
$ASTERISK_VOICEMAIL_FOLDERS = array();
$ASTERISK_VOICEMAIL_FOLDERS[0]['folder'] = "INBOX";
$ASTERISK_VOICEMAIL_FOLDERS[0]['name'] = _("INBOX");
$ASTERISK_VOICEMAIL_FOLDERS[1]['folder'] = "Family";
$ASTERISK_VOICEMAIL_FOLDERS[1]['name'] = _("Family");
$ASTERISK_VOICEMAIL_FOLDERS[2]['folder'] = "Friends";
$ASTERISK_VOICEMAIL_FOLDERS[2]['name'] = _("Friends");
$ASTERISK_VOICEMAIL_FOLDERS[3]['folder'] = "Old";
$ASTERISK_VOICEMAIL_FOLDERS[3]['name'] = _("Old");
$ASTERISK_VOICEMAIL_FOLDERS[4]['folder'] = "Work";
$ASTERISK_VOICEMAIL_FOLDERS[4]['name'] = _("Work");

###############################
# call monitor settings
###############################
#
# Location of asterisk call monitor recordings on server
#
$ASTERISK_CALLMONITOR_PATH = "/var/spool/asterisk/monitor";

#
# Extensions with access to all call monitor recordings
#   option: Comma delimited list of extensions or "all"
#
$CALLMONITOR_ADMIN_EXTENSIONS = "1";

#
# Allow call monitor users to delete monitored calls
#   option: 0 - do not show controls
#           1 - show controls
#
$CALLMONITOR_ALLOW_DELETE = 1;

#
# Allow for aggressive matching of recording files to database records
#     will match recordings that are marked several seconds off
#   option: 0 - do not aggressively match
#           1 - aggressively match
#
$CALLMONITOR_AGGRESSIVE_MATCHING = 1;

#
# Limits log/recording file matching to exact matching
#     will not try to look through all the recordings and make a best match
#     even if there is not uniqueid
#     requires that the MYSQL_UNIQUEID flag be compiled in asterisk-addons
#     (in the asterisk-addon Makefile add the following "CFLAGS+=-DMYSQL_LOGUNIQUEID")
#
#     * use if there are or will be more than 2500 recording files
#
#   option: 0 - do not exact match 
#           1 - only exact match 
#
$CALLMONITOR_ONLY_EXACT_MATCHING = 0;

###############################
# help page settings
###############################
#
# help feature codes
#   list of handset options and their function
#
$ARI_HELP_FEATURE_CODES = array();
$ARI_HELP_FEATURE_CODES['*411'] = _("Directory");
$ARI_HELP_FEATURE_CODES['*43'] = _("Echo Test");
$ARI_HELP_FEATURE_CODES['*60'] = _("Time");
$ARI_HELP_FEATURE_CODES['*61'] = _("Weather");
$ARI_HELP_FEATURE_CODES['*62'] = _("Schedule wakeup call");
$ARI_HELP_FEATURE_CODES['*65'] = _("festival test (your extension is XXX)");
$ARI_HELP_FEATURE_CODES['*70'] = _("Activate Call Waiting (deactivated by default)");
$ARI_HELP_FEATURE_CODES['*71'] = _("Deactivate Call Waiting");
$ARI_HELP_FEATURE_CODES['*72'] = _("Call Forwarding System");
$ARI_HELP_FEATURE_CODES['*73'] = _("Disable Call Forwarding");
$ARI_HELP_FEATURE_CODES['*77'] = _("IVR Recording");
$ARI_HELP_FEATURE_CODES['*78'] = _("Enable Do-Not-Disturb");
$ARI_HELP_FEATURE_CODES['*79'] = _("Disable Do-Not-Disturb");
$ARI_HELP_FEATURE_CODES['*90'] = _("Call Forward on Busy");
$ARI_HELP_FEATURE_CODES['*91'] = _("Disable Call Forward on Busy");
$ARI_HELP_FEATURE_CODES['*97'] = _("Message Center (does no ask for extension)");
$ARI_HELP_FEATURE_CODES['*98'] = _("Enter Message Center");
$ARI_HELP_FEATURE_CODES['*99'] = _("Playback IVR Recording");
$ARI_HELP_FEATURE_CODES['666'] = _("Test Fax");
$ARI_HELP_FEATURE_CODES['7777'] = _("Simulate incoming call");

###############################
# settings page settings
###############################
#
# protocol config.
#   config_file options: semi-colon delimited list of extensions
#
$ASTERISK_PROTOCOLS = array();
$ASTERISK_PROTOCOLS['iax']['table'] = "iax";
$ASTERISK_PROTOCOLS['iax']['config_files'] = "/etc/asterisk/iax.conf;/etc/asterisk/iax_additional.conf";
$ASTERISK_PROTOCOLS['sip']['table'] = "sip";
$ASTERISK_PROTOCOLS['sip']['config_files'] = "/etc/asterisk/sip.conf;/etc/asterisk/sip_additional.conf";
$ASTERISK_PROTOCOLS['zap']['table'] = "zap";
$ASTERISK_PROTOCOLS['zap']['config_files'] = "/etc/asterisk/zapata.conf;/etc/asterisk/zapata_additional.conf";

#
# For setting 
#   option: 0 - do not show controls
#           1 - show controls
#
$SETTINGS_ALLOW_VOICEMAIL_PASSWORD_SET = 1;

#
# password length 
#
$SETTINGS_VOICEMAIL_PASSWORD_LENGTH = 4;

#
# Default
#   option: ".wav" - wav format
#           ".gsm" - gsm format
#
$ARI_VOICEMAIL_AUDIO_FORMAT_DEFAULT = ".wav";

#
# For setting 
#   option: 0 - do not show controls
#           1 - show controls
#
$SETTINGS_ALLOW_CALL_RECORDING_SET = 1;


?>