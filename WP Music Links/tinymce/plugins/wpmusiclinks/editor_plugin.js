(function() {
	tinymce.PluginManager.requireLangPack('wpmusiclinks');
	tinymce.create('tinymce.plugins.WPMusicLinksPlugin', {
		init : function(ed, url) {
			ed.addCommand('mceWPMusicLinksInsert', function() {
				ed.execCommand('mceInsertContent', 0, insertName('visual', ''));
			});
			ed.addButton('wpmusiclinks', {
				title : 'wpmusiclinks.insert_name',
				cmd : 'mceWPMusicLinksInsert',
				image : url + '/img/icon.gif'
			});
			ed.onNodeChange.add(function(ed, cm, n) {
				cm.setActive('wpmusiclinks', n.nodeName == 'IMG');
			});
		},

		createControl : function(n, cm) {
			return null;
		},
		getInfo : function() {
			return {
				longname : 'WP Music Links',
				author : 'Fernando Gómez',
				authorurl : 'http://fergomez.es/',
				infourl : 'http://github.com/fergomez',
				version : '0.1.4'
			};
		}
	});
	tinymce.PluginManager.add('wpmusiclinks', tinymce.plugins.WPMusicLinksPlugin);
})();

