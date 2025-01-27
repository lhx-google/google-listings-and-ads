/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Internal dependencies
 */
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import createCampaign from '.~/apis/createCampaign';

export default function useSetupCompleteCallback() {
	const { createNotice } = useDispatchCoreNotices();
	const [ loading, setLoading ] = useState( false );

	const createCampaignCallback = useCallback( ( amount, country ) => {
		return createCampaign( amount, country ).catch( () => {
			return Promise.reject(
				__(
					'Unable to launch your ads campaign. Please try again later.',
					'google-listings-and-ads'
				)
			);
		} );
	}, [] );

	const completeAdsSetup = useCallback( () => {
		const options = {
			path: '/wc/gla/ads/setup/complete',
			method: 'POST',
		};
		return apiFetch( options ).catch( () => {
			return Promise.reject(
				__(
					'Unable to complete your ads setup. Please try again later.',
					'google-listings-and-ads'
				)
			);
		} );
	}, [] );

	const handleFinishSetup = useCallback(
		( amount, country, onCompleted ) => {
			setLoading( true );
			createCampaignCallback( amount, country )
				.then( completeAdsSetup )
				.then( onCompleted )
				.catch( ( errorMessage ) => {
					createNotice( 'error', errorMessage );
				} )
				.then( () => setLoading( false ) );
		},
		[ createCampaignCallback, completeAdsSetup, createNotice ]
	);

	return [ handleFinishSetup, loading ];
}
