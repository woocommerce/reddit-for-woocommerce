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
 * @typedef {Object} RedditPixel
 * @property {string} pixel_id The Reddit pixel ID.
 * @property {string} pixel_name The name of the Reddit pixel.
 */

/**
 * @typedef {Object} RedditAdsAccount
 * @property {string} ad_account_id The Reddit ad account ID.
 * @property {string} ad_account_name The name of the Reddit ad account.
 */

/**
 * @typedef {Object} RedditBusinessAccount
 * @property {string} business_id The Reddit business ID.
 * @property {string} business_name The name of the Reddit business.
 */

/**
 * @typedef {Object} RedditAccountConfig
 * @property {string} business_id The Reddit business ID.
 * @property {string} business_name The name of the Reddit business.
 * @property {string} ad_account_id The Reddit ad account ID.
 * @property {string} ad_account_name The name of the Reddit ad account.
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
 * Retrieves the Reddit account config from the state.
 *
 * @param {Object} state - The Redux state object.
 * @return {RedditAccountConfig|null} The Reddit details or null if not set.
 */
export const getRedditAccountConfig = ( state ) => {
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

/**
 * Retrieves the settings state.
 *
 * @param {Object} state - The Redux state.
 * @return {{ trackConversions: boolean, triggerExport: boolean }} The settings object.
 */
export const getSettings = ( state ) => {
	return state.settings;
};

// /**
//  * Retrieves the Reddit Ads account information from the state.
//  *
//  * @param {Object} state - The Redux state object.
//  * @param {Object} state.reddit - The Reddit-related state.
//  * @param {string} [state.reddit.ad_account_id] - The Reddit Ads account ID (optional).
//  * @param {string} [state.reddit.ad_account_name] - The Reddit Ads account name (optional).
//  * @return {{ adAccountId: string | undefined, adAccountName: string | undefined }} An object containing the account ID and name.
//  */
// export const getRedditAdsAccount = ( state ) => {
// 	return {
// 		adAccountId: state.reddit?.ad_account_id,
// 		adAccountName: state.reddit?.ad_account_name,
// 	};
// };

// /**
//  * Retrieves the Reddit business account information from the state.
//  *
//  * @param {Object} state - The application state object.
//  * @param {Object} state.reddit - The Reddit-related state.
//  * @param {string} [state.reddit.business_id] - The ID of the Reddit business account.
//  * @param {string} [state.reddit.business_name] - The name of the Reddit business account.
//  * @return {{ businessId: string | undefined, businessName: string | undefined }} An object containing the business ID and name.
//  */
// export const getRedditBusinessAccount = ( state ) => {
// 	return {
// 		businessId: state.reddit?.business_id,
// 		businessName: state.reddit?.business_name,
// 	};
// };

/**
 * Retrieves the list of existing ads accounts from the Redux state.
 *
 * @param {Object} state - The Redux state object.
 * @return {Array<RedditAdsAccount>} The array of existing ads accounts.
 */
export const getExistingAdsAccounts = ( state ) => {
	return state.accounts.existingAdsAccounts || [];
};

/**
 * Retrieves the list of existing business accounts from the Redux state.
 *
 * @param {Object} state - The Redux state object.
 * @return {Array<RedditBusinessAccount>} The array of existing business accounts.
 */
export const getExistingBusinessAccounts = ( state ) => {
	return state.accounts.existingBusinessAccounts;
};

/**
 * Retrieves the list of existing pixel IDs from the Redux state.
 *
 * @param {Object} state - The Redux state object.
 * @return {Array<RedditPixel>} The array of existing pixel IDs.
 */
export const getExistingPixels = ( state ) => {
	return state.accounts.existingPixels;
};

/**
 * Selector to retrieve the 'adsBudgetMetrics' property from the state.
 *
 * @param {Object} state - The Redux state object.
 * @return {*} The 'adsBudgetMetrics' property from the state.
 */
export const getAdsBudgetMetrics = ( state ) => {
	return state.adsBudgetMetrics;
};
