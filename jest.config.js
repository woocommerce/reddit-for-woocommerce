module.exports = {
	// `@wordpress/scripts` 36 no longer ships a Jest config, so use the published
	// preset and Babel transform it used to provide.
	// Ref: https://github.com/WordPress/gutenberg/blob/%40wordpress/scripts%4036.0.0/packages/scripts/docs/vitest-migration.md#keep-an-existing-jest-suite
	preset: '@wordpress/jest-preset-default',
	transform: {
		// `.mjs` is included for `@wordpress/theme`, see `transformIgnorePatterns` below.
		'\\.(m?js|jsx|tsx?)$': [
			'babel-jest',
			{ presets: [ '@wordpress/babel-preset-default' ] },
		],
	},
	reporters: [ 'default', 'github-actions' ],
	testEnvironment: 'jsdom',
	setupFiles: [ 'core-js', '<rootDir>/js/src/tests/jest-unit.setup.js' ],
	transformIgnorePatterns: [
		// Fix that `is-plain-obj@4.1.0` doesn't provide the CommonJS build, so it needs to be transformed.
		// The pattern covers is-plain-obj nested under any package (e.g. @woocommerce/data, @wordpress/core-data).
		// `@wordpress/theme` (via @wordpress/ui) and `parsel-js` (via @wordpress/block-editor) are ESM-only,
		// with no CJS build, so they need to be transformed too.
		'<rootDir>/node_modules/(?!(?:@[^/]+/)?[^/]+/node_modules/is-plain-obj/|d3-.*/|internmap/|(?:.*/node_modules/)?(?:@wordpress/theme|parsel-js)/)',
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
		// support `package.json.exports`. Resolve it from `@wordpress/components`, which
		// imports it, because npm doesn't always hoist a copy to the top level.
		'^uuid$': require.resolve( 'uuid', {
			paths: [ require.resolve( '@wordpress/components' ) ],
		} ),
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
