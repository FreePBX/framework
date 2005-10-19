To create the .po (write your translations to this file):
cd ..
find *.php includes/*.inc modules/*.module | xargs xgettext -C -o ari.po --keyword=_ -

To create the .mo:  
msgfmt -v ari.po -o ari.mo
