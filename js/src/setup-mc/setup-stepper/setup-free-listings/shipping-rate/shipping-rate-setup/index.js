/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import AppInputPriceControl from '.~/components/app-input-price-control';
import VerticalGapLayout from '.~/components/vertical-gap-layout';
import AddRateButton from './add-rate-button';
import CountriesPriceInputForm from './countries-price-input-form';
import useStoreCurrency from '.~/hooks/useStoreCurrency';
import useShippingRates from '.~/hooks/useShippingRates';
import getCountriesPriceArray from './getCountriesPriceArray';
import AppSpinner from '.~/components/app-spinner';
import useTargetAudienceFinalCountryCodes from '.~/hooks/useTargetAudienceFinalCountryCodes';
import './index.scss';

const ShippingRateSetup = ( props ) => {
	const {
		formProps: { getInputProps, values },
	} = props;
	const { data: shippingRates } = useShippingRates();
	const { code: currencyCode } = useStoreCurrency();
	const { data: selectedCountryCodes } = useTargetAudienceFinalCountryCodes();

	if ( ! selectedCountryCodes ) {
		return <AppSpinner />;
	}

	const expectedCountryCount = selectedCountryCodes.length;
	const actualCountryCount = shippingRates.length;
	const remainingCount = expectedCountryCount - actualCountryCount;

	const countriesPriceArray = getCountriesPriceArray( shippingRates );

	// Prefill to-be-added price.
	if ( countriesPriceArray.length === 0 ) {
		countriesPriceArray.push( {
			countries: selectedCountryCodes,
			price: null,
			currency: currencyCode,
		} );
	}

	return (
		<div className="gla-shipping-rate-setup">
			<VerticalGapLayout>
				<div className="countries-price">
					<VerticalGapLayout>
						{ countriesPriceArray.map( ( el ) => {
							return (
								<div
									key={ el.countries.join( '-' ) }
									className="countries-price-input-form"
								>
									<CountriesPriceInputForm
										savedValue={ el }
									/>
								</div>
							);
						} ) }
						{ actualCountryCount >= 1 && remainingCount >= 1 && (
							<div className="add-rate-button">
								<AddRateButton />
							</div>
						) }
					</VerticalGapLayout>
				</div>
				<CheckboxControl
					label={ __(
						'I also offer free shipping for all countries for products over a certain price.',
						'google-listings-and-ads'
					) }
					{ ...getInputProps( 'offers_free_shipping' ) }
				/>
				{ values.offers_free_shipping && (
					<div className="price-over-input">
						<AppInputPriceControl
							label={ __(
								'I offer free shipping for products priced over',
								'google-listings-and-ads'
							) }
							{ ...getInputProps( 'free_shipping_threshold' ) }
						/>
					</div>
				) }
			</VerticalGapLayout>
		</div>
	);
};

export default ShippingRateSetup;
