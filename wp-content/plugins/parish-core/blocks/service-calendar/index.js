// Editor UI for the Service schedule block. Plain JS, so no build step is needed.
( function ( wp ) {
	const { registerBlockType } = wp.blocks;
	const { createElement: el, Fragment } = wp.element;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, SelectControl, RangeControl, TextControl } = wp.components;
	const ServerSideRender = wp.serverSideRender;

	registerBlockType( 'parish/service-calendar', {
		edit( { attributes, setAttributes } ) {
			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Calendar' },
						el( SelectControl, {
							label: 'View',
							value: attributes.view,
							options: [
								{ label: 'List of upcoming services', value: 'AGENDA' },
								{ label: 'Week', value: 'WEEK' },
								{ label: 'Month', value: 'MONTH' },
							],
							onChange: ( view ) => setAttributes( { view } ),
						} ),
						el( RangeControl, {
							label: 'Height (px)',
							value: attributes.height,
							min: 300,
							max: 1400,
							step: 50,
							onChange: ( height ) => setAttributes( { height } ),
						} ),
						el( TextControl, {
							label: 'Different calendar ID (optional)',
							help: 'Leave empty to use the parish calendar from Settings → Parish.',
							value: attributes.calendarId,
							onChange: ( calendarId ) => setAttributes( { calendarId } ),
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					el( ServerSideRender, { block: 'parish/service-calendar', attributes } )
				)
			);
		},
		save: () => null,
	} );
} )( window.wp );
