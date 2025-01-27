/**
 * External dependencies
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Call API to create campaign.
 *
 * @param {number} amount Daily average cost of the paid ads campaign.
 * @param {string} country Country code of the paid ads campaign audience country.
 * @return {Promise} Result from apiFetch.
 */
const createCampaign = ( amount, country ) => {
	const options = {
		path: '/wc/gla/ads/campaigns',
		method: 'POST',
		data: {
			amount: Number( amount ),
			country,
		},
	};

	return apiFetch( options );
};

export default createCampaign;
