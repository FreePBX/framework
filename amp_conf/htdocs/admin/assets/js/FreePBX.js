var fpbx = Class.extend({
	params: {},
	init: function() {
		var self = this;
		var path = window.location.pathname.toString().split('/');
		path[path.length - 1] = 'ajax.php';
		if (typeof(window.location.origin) == 'undefined') {
			// Oh look, IE. Hur Dur, I'm a bwowsah.
			window.location.origin = window.location.protocol+'//'+window.location.host;
			if (window.location.port.length != 0) {
				window.location.origin = window.location.origin+':'+window.location.port;
			}
		}
		this.ajaxurl = window.location.origin + path.join('/');
		if (window.location.search.length) {
			var params = window.location.search.split(/\?|&/);
			// NOT using jquery here. This is a bit more annoying, yes, but it means we
			// can move it out of the way later. Note we break compat with IE8 and below
			// here.
			params.forEach(function(v) {
				if (res = v.match(/(.+)=(.+)/)) {
					self.params[res[1]] = res[2];
				}
			});
		}
	}
});
window.FreePBX = new fpbx();
