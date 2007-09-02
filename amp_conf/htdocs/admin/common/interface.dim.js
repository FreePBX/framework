	// hack to hold the 'real' page height, because due to the freepbx layout $(document).height() doesn't actually get the real height
	var realHeight;

	/** Dim the screen, and show a modal box (a div) 
	*/
	function freepbx_modal_show(divID,callback) {
		// hack: find the "real" height: the height of the header, plus either nav or wrapper, whichever is longer
		obj = $('#'+divID);
		navHeight = $('#nav').height();
		wrapperHeight = $('#wrapper').height()
		realHeight = $('#header').height() + (wrapperHeight > navHeight ? wrapperHeight : navHeight);
		
		// handle when content is smaller than window height (http://freepbx.org/trac/ticket/2161)
		if ($(window).height() > realHeight) {
			realHeight = $(window).height();
		}
		
		// center the box
		obj.css({
			top: ( Math.floor( $(window).height()/2 - ($('#'+divID).height()/2) ) ),
			left: ( Math.floor( $(window).width()/2 - ($('#'+divID).width()/2) ) )
		});
		
		
		if ($.browser.msie) {
			// hide select boxes, IE shows them overtop of everything
			hideSelects(true);
		}
		
		// dim screen
		$.dimScreen(50, 0.7, function() {
			// show box
			
			// workaround for safari/jquery 1.1.3 bug: http://dev.jquery.com/ticket/1369
			// see http://freepbx.org/trac/ticket/2132
			if ($.browser.safari) { 
				obj[0].style.display = "block";
			}
			
			obj.fadeIn(50);
			if (callback) {
				callback();
			}
		});
	}
	
	/** Hide the modal box, but don't get rid of the dimmed screen */
	function freepbx_modal_hide(divID, callback) {
		$('#__dimScreen').css('cursor','wait');
		$('#'+divID).fadeOut(50, callback);
	}
	
	/** Close the modal box, undim the screen */
	function freepbx_modal_close(divID, callback) {
		obj = $('#'+divID);
		if (obj.css('display') != 'block') {
			// already closed, don't show fadeout
			return;
		}
		
		obj.fadeOut(50, function() {
			$.dimScreenStop(50);
			
			if ($.browser.msie) {
				// show select boxes, IE shows them overtop of everything
				hideSelects(false);
			}

			if (callback) {
				callback();
			}
		});
		
	}


//dimScreen()
//by Brandon Goldman
jQuery.extend({
    //dims the screen
    dimScreen: function(speed, opacity, callback) {
        if(jQuery('#__dimScreen').size() > 0) return;
        
        if(typeof speed == 'function') {
            callback = speed;
            speed = null;
        }

        if(typeof opacity == 'function') {
            callback = opacity;
            opacity = null;
        }

        if(speed < 1) {
            var placeholder = opacity;
            opacity = speed;
            speed = placeholder;
        }
        
        if(opacity >= 1) {
            var placeholder = speed;
            speed = opacity;
            opacity = placeholder;
        }
		
		/* freepbx hack to get "real" height */
		dimHeight = (realHeight > 0) ? realHeight : $(window).height();

        speed = (speed > 0) ? speed : 50;
        opacity = (opacity > 0) ? opacity : 0.5;
        return jQuery('<div></div>').attr({
                id: '__dimScreen'
                ,fade_opacity: opacity
                ,speed: speed
            }).css({
            background: '#000'
            ,height: dimHeight + 'px'
            ,left: '0px'
            ,opacity: 0
            ,position: 'absolute'
            ,top: '0px'
            ,width: $(window).width() + 'px'
            ,zIndex: 999
        }).appendTo(document.body).fadeTo(speed, 0.7, callback);
    },
    
    //stops current dimming of the screen
    dimScreenStop: function(callback) {
        var x = jQuery('#__dimScreen');
        var opacity = x.attr('fade_opacity');
        var speed = x.attr('speed');
        x.fadeOut(speed, function() {
            x.remove();
            if(typeof callback == 'function') callback();
        });
    }
});
