( function () {
	const settings = ( window.wc && window.wc.wcSettings && window.wc.wcSettings.getSetting )
		? window.wc.wcSettings.getSetting( 'mrkv_novapay_data', {} )
		: {};

	const title = settings.title || 'NovaPay';
	const description = settings.description || '';

	const Label = () => window.wp.element.createElement(
		'span',
		{ className: 'wc-block-components-payment-method-label' },
		title
	);

	const Content = () => window.wp.element.createElement(
		'div',
		{ className: 'wc-block-components-payment-method-content' },
		window.wp.htmlEntities
			? window.wp.htmlEntities.decodeEntities( description )
			: description
	);

	window.wc.wcBlocksRegistry.registerPaymentMethod( {
		name: 'mrkv_novapay',
		label: window.wp.element.createElement( Label ),
		content: window.wp.element.createElement( Content ),
		edit: window.wp.element.createElement( Content ),
		canMakePayment: () => true,
		ariaLabel: title,
		supports: { features: settings.supports || [ 'products' ] },
	} );
} )();
