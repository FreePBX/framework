// To create the .po (write your translations to this file):
$ cd ..
$ find *.php includes/*.inc modules/*.module misc/*.php | xargs xgettext -L PHP -o ari.po --keyword=_ -

// To create the .mo:  
$ msgfmt -v ari.po -o ari.mo

// To update
$ cp es_ES/LC_MESSAGES/ari.po ./old.po
$ msgmerge old.po ari.po --output-file=new.po
$ msgfmt new.po
