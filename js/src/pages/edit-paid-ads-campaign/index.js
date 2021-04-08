/**
 * External dependencies
 */
import { getQuery, getNewPath } from '@woocommerce/navigation';
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import FullContainer from '.~/components/full-container';
import TopBar from '.~/components/stepper/top-bar';
import AppSpinner from '.~/components/app-spinner';
import EditPaidAdsCampaignForm from './edit-paid-ads-campaign-form';
import { STORE_KEY } from '.~/data';

const dashboardURL = getNewPath( {}, '/google/dashboard', {} );

const EditPaidAdsCampaign = () => {
	const { programId } = getQuery();
	const campaignData = useSelect( ( select ) => {
		return select( STORE_KEY ).getAdsCampaign( Number( programId ) );
	} );
	const loading = campaignData === null;
	const error = campaignData === undefined;

	if ( loading ) {
		return (
			<FullContainer>
				<TopBar
					title={ __( 'Loading…', 'google-listings-and-ads' ) }
					backHref={ dashboardURL }
				/>
				<AppSpinner />
			</FullContainer>
		);
	}

	if ( error ) {
		return (
			<FullContainer>
				<TopBar
					title={ __( 'Edit Campaign', 'google-listings-and-ads' ) }
					backHref={ dashboardURL }
				/>
				<div>
					{ __(
						'Error in loading your paid ads campaign. Please try again later.',
						'google-listings-and-ads'
					) }
				</div>
			</FullContainer>
		);
	}

	return (
		<FullContainer>
			<TopBar
				title={ sprintf(
					// translators: %s: campaign's name.
					__( 'Edit %s', 'google-listings-and-ads' ),
					campaignData.name
				) }
				backHref={ dashboardURL }
			/>
			<EditPaidAdsCampaignForm campaign={ campaignData } />
		</FullContainer>
	);
};

export default EditPaidAdsCampaign;
