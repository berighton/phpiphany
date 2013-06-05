/*
 * Alertbox
 *
 * jQuery notice extention to immitate growl
 * Adapted for phpiphany styles by Paul Brighton 2012
 *
 */
(function(jQuery) {
	jQuery.extend({
		alertbox: function(options) {
			var defaults = {
				inEffect: {opacity: 'show'}, // in effect
				inEffectDuration: 600,       // in effect duration in miliseconds
				delay: 10000,                // time in miliseconds before the alertbox disappears
				text: '',                    // content of the alertbox
				stay: false,                 // should the alertbox stay or not?
				type: 'notice',              // could also be error, warning, success or notice
				pos: 'top'                   // position of the alertbox (default: top center)
			}

			// declare varaibles
			var options, alertbox, content;

			options = jQuery.extend({}, defaults, options);
			alertbox = (!jQuery('#alertbox').length) ? jQuery('<div></div>').attr('id', 'alertbox').addClass(options.pos).appendTo('body') : jQuery('#alertbox');
			content = jQuery('<div></div>').hide().addClass(options.type).appendTo(alertbox).html('<span class="alert_title">' + options.text + '</span><span class="alert_msg"></span>' +
					'').animate(options.inEffect, options.inEffectDuration).click(function() { jQuery.alertbox_remove(content) });

			if (navigator.userAgent.match(/MSIE 6/i)) {
				alertbox.css({top: document.documentElement.scrollTop});
			}

			if (!options.stay) {
				setTimeout(function() {
					jQuery.alertbox_remove(content);
				},
				options.delay);
			}
		},

		alertbox_remove: function(obj) {
			obj.animate({opacity: '0'}, 600, function() {
				obj.parent().animate({height: '0px'}, 300, function() {
					obj.parent().remove();
				});
			});
		}
	});
})(jQuery);