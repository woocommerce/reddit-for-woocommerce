const woocommerce = require( '@woocommerce/eslint-plugin' );
// Use the copy `@woocommerce/eslint-plugin` depends on, so the jsdoc types below
// match the rules it registers.
// eslint-disable-next-line import/no-extraneous-dependencies
const wordpress = require( '@wordpress/eslint-plugin' );
const importPlugin = require( 'eslint-plugin-import' );
const webpackConfig = require( './webpack.config' );

const webpackResolver = {
	config: {
		resolve: {
			...webpackConfig.resolve,
			/**
			 * Make eslint correctly resolve files that omit the .js extensions.
			 * The default value `'...'` doesn't work before the current eslint support for webpack v5.
			 * Ref: https://webpack.js.org/configuration/resolve/#resolveextensions
			 */
			extensions: [ '.js' ],
		},
	},
};

const { definedTypes: jsdocDefinedTypes } = wordpress.configs.jsdoc
	.map( ( config ) => config.rules?.[ 'jsdoc/no-undefined-types' ] )
	.find( ( rule ) => Array.isArray( rule ) && rule[ 1 ]?.definedTypes )[ 1 ];

module.exports = [
	{
		// Replaces `.eslintignore`, which flat config doesn't read.
		ignores: [
			'**/build/**',
			'**/build-module/**',
			'**/node_modules/**',
			'**/vendor/**',
			'coverage/**',
			'languages/**',
			'tests/e2e/test-results/**',
		],
	},
	...woocommerce.configs.recommended,
	{
		// `eslint-plugin-import` is registered by `@wordpress/eslint-plugin` through
		// `fixupPluginRules`, so only its recommended rules are applied here. Registering
		// the plugin again would fail with "Cannot redefine plugin".
		rules: importPlugin.flatConfigs.recommended.rules,
	},
	{
		languageOptions: {
			globals: {
				getComputedStyle: 'readonly',
				wp_has_consent: 'readonly',
				jQuery: 'readonly',
				ajaxurl: 'readonly',
				redditAdsAdminData: 'readonly',
			},
		},
		settings: {
			jsdoc: {
				mode: 'typescript',
			},
			'import/core-modules': [
				'webpack',
				'stylelint',
				'@woocommerce/product-editor',
				'@woocommerce/block-templates',
				'@wordpress/stylelint-config',
				'@pmmmwh/react-refresh-webpack-plugin',
				'react-transition-group',
				'jquery',
			],
			'import/resolver': { webpack: webpackResolver },
		},
		rules: {
			'@wordpress/i18n-text-domain': [
				'error',
				{ allowedTextDomain: 'reddit-for-woocommerce' },
			],
			'@wordpress/no-unsafe-wp-apis': 1,
			'react/react-in-jsx-scope': 'off',
			'react-hooks/exhaustive-deps': [
				'warn',
				{
					additionalHooks: 'useSelect',
				},
			],
			// compatibility-code "WC < 7.6"
			//
			// Turn it off because:
			// - `import { CurrencyFactory } from '@woocommerce/currency';`
			//   It's supported only since WC 7.6.0
			// - `import { userEvent } from '@testing-library/user-event';`
			//   It works but the official documentation also recommends using the default export
			'import/no-named-as-default': 'off',
			// Turn it off temporarily because it involves a lot of re-alignment. We can revisit it later.
			'jsdoc/check-line-alignment': 'off',
			// Originally, `@fires` tag indicates that when a method is called, it fires
			// a specified type of event that can be listened to, e.g. a native `CustomEvent`.
			// The JS package `tracking-jsdoc` changes the definition of the `@fires` tag to
			// be able to indicate a tracking event will be sent. Therefore, here we list
			// shared `@event` names to avoid false alarms.
			'jsdoc/no-undefined-types': [
				'error',
				{
					definedTypes: [
						...jsdocDefinedTypes,
						// The global TypeScript namespace used by `JSX.Element` in `mode: 'typescript'`.
						'JSX',
						'rfw_documentation_link_click',
					],
				},
			],
		},
	},
	{
		// `@woocommerce/eslint-plugin` allows `require()` in JS files by turning off
		// `@typescript-eslint/no-var-requires`, which typescript-eslint v8 replaced
		// with `no-require-imports`. Turn the new rule off too to keep that behavior.
		files: [ '**/*.js', '**/*.cjs' ],
		rules: {
			'@typescript-eslint/no-require-imports': 'off',
		},
	},
	{
		// `jest/*` rules only exist for the test files `@woocommerce/eslint-plugin` scopes them to.
		files: [
			'**/@(test|__tests__)/**/*.[jt]s?(x)',
			'**/?(*.)test.[jt]s?(x)',
			'**/tests/**/*.[jt]s?(x)',
		],
		rules: {
			'jest/expect-expect': [
				'warn',
				{ assertFunctionNames: [ 'expect', 'expect[A-Z]\\w*' ] },
			],
		},
	},
	{
		files: [ 'js/src/components/external/woocommerce/**' ],
		rules: {
			'@wordpress/i18n-text-domain': [
				'error',
				{ allowedTextDomain: 'woocommerce' },
			],
		},
	},
	{
		files: [ 'js/src/components/external/wordpress/**' ],
		rules: {
			'@wordpress/i18n-text-domain': [
				'error',
				{ allowedTextDomain: '' },
			],
		},
	},
	{
		files: [ 'tests/e2e/**/*.js' ],
		rules: {
			'jest/no-done-callback': [ 'off' ],
		},
	},
];
