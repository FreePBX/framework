#!/bin/sh

DIR="../amp_conf/htdocs/admin/common"
cat $DIR/jquery-1.4.2.js $DIR/script.legacy.js $DIR/jquery.tabs-2.7.4.js $DIR/jquery.dimensions.js $DIR/jquery.cookie.js $DIR/jquery.toggleval.3.0.js $DIR/interface.dim.js | ./jsmin.rb > $DIR/libfreepbx.javascripts.js 
