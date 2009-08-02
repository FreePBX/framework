#!/bin/sh

DIR="../amp_conf/htdocs/admin/common"

cat $DIR/script.legacy.js $DIR/jquery-1.3.2.js $DIR/jquery.cookie.js $DIR/jquery.tabs-2.7.4.js $DIR/jquery.dimensions.js $DIR/interface.dim.js | ./jsmin.rb > $DIR/libfreepbx.javascripts.js
