/**
 * Internal dependencies
 */
import { API_NAMESPACE } from './constants';

/**
 * Parse the response to JSON if needed.
 *
 * @see https://github.com/WordPress/gutenberg/blob/%40wordpress/api-fetch%405.1.1/packages/api-fetch/src/utils/response.js#L20
 * @param {Response} response Response to be optionally parsed.
 * @param {boolean} shouldParseResponse `true` - if we should try to parse response to JSON.
 * @return {Response|Promise} Non-parsed `response`, or promise for parsed JSON. Rejects with `response`, if parsing fails.
 */
function responseOrJSON( response, shouldParseResponse ) {
	if ( shouldParseResponse ) {
		return response.json ? response.json() : Promise.reject( response );
	}
	return response;
}

/**
 * @callback onErrorResponseCallback
 * @param {Response} response The error response instance.
 * @return {Promise<any>|any} Reject or throw the response error if want to continue original error handling process.
 *                            Otherwise, resolve recovery result if the error could be processed here.
 */

/**
 * Create a middleware with a callback to watch and handle the error response instance globally.
 *
 * @param  {onErrorResponseCallback} onErrorResponse A callback to be invoked when receiving error response.
 * @return {import('@wordpress/api-fetch').APIFetchMiddleware} A middleware to watch and handle the error response instance globally.
 */
export function createErrorResponseCatcher( onErrorResponse ) {
	const regexApiNamespace = new RegExp( `^${ API_NAMESPACE }\/` );

	return function errorResponseCatcher( options, next ) {
		// Requests issued from other places return and call the next middlewares with original options directly.
		if ( ! regexApiNamespace.test( options.path ) ) {
			return next( options );
		}

		// To align with the original interface, set the default value of `parse` to `true`. Ref:
		// - https://github.com/WordPress/gutenberg/tree/%40wordpress/api-fetch%405.1.1/packages/api-fetch#parse-boolean-default-true
		// - https://github.com/WordPress/gutenberg/blob/%40wordpress/api-fetch%405.1.1/packages/api-fetch/src/index.js#L89
		const { parse: shouldParseResponse = true } = options;

		// Call onErrorResponse callback when receiving error response,
		// then parse its fulfillment value to JSON if needed (and possible).
		return next( { ...options, parse: false } )
			.catch( onErrorResponse )
			.catch( async ( response ) => {
				// Try parsing the rejection reason.
				// Reject with the response, parsed to JSON if required and possible.
				return Promise.reject(
					await responseOrJSON( response, shouldParseResponse )
				);
			} )
			.then( ( response ) => {
				// Handle the successful response or the "recovered" value resolved from onErrorResponse callback.
				// Ref: https://github.com/WordPress/gutenberg/blob/%40wordpress/api-fetch%405.1.1/packages/api-fetch/src/utils/response.js#L14-L24
				if ( shouldParseResponse && response.status === 204 ) {
					return null;
				}

				// Resolve with the response, parsed to JSON if required and possible.
				return responseOrJSON( response, shouldParseResponse );
			} );
	};
}
