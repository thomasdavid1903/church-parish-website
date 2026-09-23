// Mobile menu toggle.
document.querySelectorAll( '.parish-nav__toggle' ).forEach( ( button ) => {
	button.addEventListener( 'click', () => {
		const nav = button.closest( '.parish-nav' );
		const open = nav.classList.toggle( 'is-open' );
		button.setAttribute( 'aria-expanded', String( open ) );
	} );
} );
