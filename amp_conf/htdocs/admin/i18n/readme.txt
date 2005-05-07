To create the .po (write your translations to this file):
cd ..
find *.php common/*.php | xargs xgettext -C -o amp.po --keyword=_ -

To create the .mo:  
msgfmt -v amp.po -o amp.mo
