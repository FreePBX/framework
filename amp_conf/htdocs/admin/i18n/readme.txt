To create the .po (write your translations to this file):
xgettext -C -o amp.po --keyword=_ *.php

To create the .mo:  
msgfmt amp.po -o amp.mo
