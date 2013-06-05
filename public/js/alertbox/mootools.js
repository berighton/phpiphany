/*
 * Alertbox
 *
 * MooTools notification alerts immitating growl
 * Adapted for phpiphany styles by Paul Brighton 2012
 *
 */
(function($) {
	this.alertbox = new Class({
		initialize: function(opt) {
			if (!opt) opt = {};
			this.type = opt.type || 'notice';
			this.delay = opt.delay || 10000;
			this.pos = opt.pos || 'top';
			this.block = $('alertbox');
			if (this.block) return;
			this.block = new Element('div', {'id': 'alertbox'});
			this.block.inject(opt.el || document.body);
		},

		show: function(title, msg, img, callback) {
			this.block.set('class', this.pos);
			var i = "";
			if (img) i = '<img src="' + img + '" />';
			var b = new Element('div', {
				'html' : '<span class="alert_title">' + title + '</span><span class="alert_msg">' + msg + '</span>' + i,
				'class' : this.type
			});

			b.addEvent('click', function() {
				if (callback) callback.run();
				this.close(b);
			}.bind(this));

			b.setStyle('opacity', 0);
			b.inject(this.block, (this.pos == 'br' || this.pos == 'bl') ? 'top' : 'bottom');

			var fx = new Fx.Tween(b, {duration:600});
			b.store('fx', fx);
			fx.addEvent('complete', function() {
				b.set('style', '');
			});
			fx.start('opacity', 1);
			this.close.delay(this.delay, this, b);
		},

		close: function(b) {
			var fx = b.retrieve('fx');
			if (!fx) return;
			fx.addEvent('complete', function() {
				b.destroy();
			});
			fx.start('opacity', 0);
		}
	});
})(document.id);