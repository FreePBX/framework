/**
* FreePBX module based gettext class
*
* short translates based on a modules domain self initializing
*
* Much of this code was ported from modgettext.class.php in FreePBX
* the license from FreeePBX applies forward
*
* @author Philippe Lindheimer
* @author Andrew Nagy
*/

var languages = { locale_data : [] }, i18n = new Jed(languages);
function _(string) {
	try {
		return i18n.dgettext( UCP.domain, string );
	} catch (err) {
		return string;
	}
}

function sprintf() {
	try {
		return i18n.sprintf.apply(this, arguments);
	} catch (err) {
		return string;
	}
}
