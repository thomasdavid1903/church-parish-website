// Shows the post's language before publishing and warns when a Russian title
// is set to English. Reads Polylang's Language box. Plain JS, no build step.
( function ( wp ) {
	const { createElement: el } = wp.element;
	const { useSelect, select, dispatch, subscribe } = wp.data;
	const PrePublishPanel = wp.editor.PluginPrePublishPanel || ( wp.editPost && wp.editPost.PluginPrePublishPanel );
	const NOTICE = 'parish-language-mismatch';

	function languageBox() {
		return document.getElementById( 'post_lang_choice' ) || document.querySelector( 'select[name="post_lang_choice"]' );
	}

	function currentLanguage() {
		const box = languageBox();
		if ( ! box || box.selectedIndex < 0 ) {
			return null;
		}
		return { slug: box.value, name: box.options[ box.selectedIndex ].text.trim() };
	}

	function isMismatch( title, lang ) {
		return !! lang && /[Ѐ-ӿ]/.test( title || '' ) && 'ru' !== lang.slug;
	}

	function switchToRussian() {
		const box = languageBox();
		if ( box ) {
			box.value = 'ru';
			box.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
		dispatch( 'core/notices' ).removeNotice( NOTICE );
	}

	const message =
		'The title is in Russian, but the post’s language is English, so it would appear on the English site. ' +
		'Заголовок на русском, а язык записи — English: она появится на английской версии сайта.';

	// Warning while writing: re-checked when the title or the language changes.
	let shown = false;
	function check() {
		const title = select( 'core/editor' ).getEditedPostAttribute( 'title' );
		const wrong = isMismatch( title, currentLanguage() );
		if ( wrong && ! shown ) {
			dispatch( 'core/notices' ).createWarningNotice( message, {
				id: NOTICE,
				isDismissible: true,
				actions: [ { label: 'Switch to Русский', onClick: switchToRussian } ],
			} );
		} else if ( ! wrong && shown ) {
			dispatch( 'core/notices' ).removeNotice( NOTICE );
		}
		shown = wrong;
	}
	let lastTitle;
	subscribe( function () {
		const title = select( 'core/editor' ).getEditedPostAttribute( 'title' );
		if ( title !== lastTitle ) {
			lastTitle = title;
			check();
		}
	} );
	document.addEventListener( 'change', function ( event ) {
		if ( event.target === languageBox() ) {
			check();
		}
	} );

	// Pre-publish check: "Language: Русский", and the warning again if needed.
	wp.plugins.registerPlugin( 'parish-editor-language', {
		render() {
			const title = useSelect( ( s ) => s( 'core/editor' ).getEditedPostAttribute( 'title' ), [] );
			const [ lang, setLang ] = wp.element.useState( currentLanguage );
			wp.element.useEffect( function () {
				const update = () => setLang( currentLanguage() );
				document.addEventListener( 'change', update );
				return () => document.removeEventListener( 'change', update );
			}, [] );
			if ( ! lang ) {
				return null;
			}
			return el(
				PrePublishPanel,
				{ title: 'Language: ' + lang.name, initialOpen: true },
				el( 'p', null, 'This will be published on the ' + ( 'ru' === lang.slug ? 'Russian' : 'English' ) + ' version of the website. To change it, use the Languages box at the bottom of the Post sidebar.' ),
				isMismatch( title, lang ) ? el( 'p', { style: { color: '#b32d2e', fontWeight: 600 } }, message ) : null
			);
		},
	} );
} )( window.wp );
