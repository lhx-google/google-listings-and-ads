/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { useDebouncedCallback } from 'use-debounce';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '.~/data';
import useShippingTimes from '.~/hooks/useShippingTimes';
import useShippingRates from '.~/hooks/useShippingRates';

const wait = 500;

// TODO: review the following effect (specifically the useShippingRates and useShippingTimes)
// after reducer deepClone issue (https://github.com/woocommerce/google-listings-and-ads/issues/270)
// has been addressed.
const useAutoClearShippingEffect = ( location, countries ) => {
	const { data: shippingRates } = useShippingRates();
	const { data: shippingTimes } = useShippingTimes();
	const { deleteShippingRates, deleteShippingTimes } = useAppDispatch();

	const debouncedDelete = useDebouncedCallback( async () => {
		if ( shippingRates.length ) {
			const countryCodes = shippingRates.map( ( el ) => el.countryCode );
			deleteShippingRates( countryCodes );
		}

		if ( shippingTimes.length ) {
			const countryCodes = shippingTimes.map( ( el ) => el.countryCode );
			deleteShippingTimes( countryCodes );
		}
	}, wait );

	const locationRef = useRef( null );
	const countriesRef = useRef( null );

	useEffect( () => {
		if ( locationRef.current === null && countriesRef.current === null ) {
			locationRef.current = location;
			countriesRef.current = countries;
			return;
		}

		if (
			( locationRef.current === 'all' && location === 'all' ) ||
			( locationRef.current === 'selected' &&
				location === 'selected' &&
				countriesRef.current.length === countries.length )
		) {
			return;
		}

		locationRef.current = location;
		countriesRef.current = countries;

		debouncedDelete.callback();
	}, [ debouncedDelete, location, countries ] );
};

export default useAutoClearShippingEffect;
