/**
 * @typedef {Object} JetpackAccount
 * @property {'yes'|'no'} active Whether jetpack is connected.
 * @property {'yes'|'no'} owner Whether the current admin user is the jetpack owner.
 * @property {string|''} email Owner email. Available for jetpack owner.
 * @property {string|''} displayName Owner name. Available for jetpack owner.
 */

/**
 * @typedef {Object} RedditAccount
 * @property {'connected'|'disconnected'} status The status of the Reddit account.
 */

/**
 * @typedef {Object} General
 * @property {string} version The version of the Reddit for WooCommerce plugin.
 */

/**
 * @typedef {Object} Setup
 * @property {'connected'|'disconnected'} status The setup status.
 * @property {string} step The current setup step.
 */

/**
 * @typedef {Object} RedditAccountDetails
 * @property {string} org_id The Reddit organization ID.
 * @property {string} org_name The name of the Reddit organization.
 * @property {string} ad_acc_id The Reddit ad account ID.
 * @property {string} ad_acc_name The name of the Reddit ad account.
 * @property {string} pixel_id The Reddit pixel ID.
 */

/**
 * Selector to retrieve the 'setup' property from the state.
 *
 * @param {Object} state - The Redux state object.
 * @return {*} The 'setup' property from the state.
 */
export const getSetup = ( state ) => {
	return state.setup;
};

/**
 * Select jetpack connection state.
 *
 * @param {Object} state The current store state will be injected by `wp.data`.
 * @return {JetpackAccount|null} The jetpack connection state. It would return `null` before the data is fetched.
 */
export const getJetpackAccount = ( state ) => {
	return state.accounts.jetpack;
};

/**
 * Retrieves the Reddit account information.
 * @param {Object} state - The Redux state object.
 * @return {RedditAccount | null} The Reddit account data from the state, or null if not set.
 */
export const getRedditAccount = ( state ) => {
	return state.accounts.reddit;
};

/**
 * Retrieves the general settings of the Reddit for WooCommerce plugin.
 *
 * @param {Object} state - The Redux state object.
 * @return {General} The general settings object containing version and other properties.
 */
export const getGeneral = ( state ) => {
	return state.general;
};

/**
 * Retrieves the Reddit account details from the state.
 *
 * @param {Object} state - The Redux state object.
 * @return {RedditAccountDetails|null} The Reddit details or null if not set.
 */
export const getRedditAccountDetails = ( state ) => {
	return state.reddit;
};

/**
 * Retrieves the status of conversions tracking.
 *
 * @param {Object} state - The Redux state object.
 * @return {boolean} The status of conversions tracking, true if enabled, false otherwise or null if not set.
 */
export const getTrackConversions = ( state ) => {
	return state.trackConversions;
};
