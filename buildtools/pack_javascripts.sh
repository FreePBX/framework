#!/bin/sh

DIR="../amp_conf/htdocs/admin/common"

cat `ls $DIR/*.js` | ./jsmin.rb > $DIR/libfreepbx.javascripts.js
