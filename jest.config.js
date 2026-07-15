const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...defaultConfig,
	testEnvironment: 'jsdom',
	setupFiles: [ 'core-js', '<rootDir>/js/src/tests/jest-unit.setup.js' ],
	transformIgnorePatterns: [
		// Fix that `is-plain-obj@4.1.0` doesn't provide the CommonJS build, so it needs to be transformed.
		// The pattern covers is-plain-obj nested under any package (e.g. @woocommerce/data, @wordpress/core-data).
		'<rootDir>/node_modules/(?!(?:@[^/]+/)?[^/]+/node_modules/is-plain-obj/|d3-.*/|internmap/)',
	],
	moduleNameMapper: {
		'\\.svg$': '<rootDir>/tests/mocks/assets/svgFileMock.js',
		'\\.scss$': '<rootDir>/tests/mocks/assets/styleMock.js',
		// Transform our `~/` alias.
		'^~/(.*)$': '<rootDir>/js/src/$1',
		'@woocommerce/settings':
			'<rootDir>/js/src/tests/dependencies/woocommerce/settings',
		// Fix `@woocommerce/components` still using incompatible `@woocommerce/currency`.
		'@woocommerce/currency': require.resolve( '@woocommerce/currency' ),
		// Fix the React versioning conflicts between @wordpress/* and @woocommerce/*.
		'^react$': require.resolve( 'react' ),
		// Force 'uuid' to resolve with the CommonJS entry point, because jest doesn't
		// support `package.json.exports`.
		'^uuid$': require.resolve( 'uuid' ),
	},
	testPathIgnorePatterns: [ '/node_modules/', '<rootDir>/tests/e2e/' ],
	coveragePathIgnorePatterns: [ '/node_modules/', '<rootDir>/tests/' ],
	watchPathIgnorePatterns: [
		'<rootDir>/.externalized.json',
		'<rootDir>/js/build/',
	],
	globals: {
		wcSettings: {
			currency: {
				code: 'USD',
				precision: 2,
				symbol: '$',
				symbolPosition: 'left',
				decimalSeparator: '.',
				priceFormat: '%1$s%2$s',
				thousandSeparator: ',',
			},
		},
	},
};
