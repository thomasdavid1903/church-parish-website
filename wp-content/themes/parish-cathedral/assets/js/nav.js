// Mobile menu toggle. The button sits in the masthead and controls the nav below it.
document.querySelectorAll( '.parish-nav__toggle' ).forEach( ( button ) => {
	button.addEventListener( 'click', () => {
		const nav = document.getElementById( button.getAttribute( 'aria-controls' ) );
		const open = nav.classList.toggle( 'is-open' );
		button.setAttribute( 'aria-expanded', String( open ) );
	} );
} );
