/**
 * External dependencies
 */
import { noop } from 'lodash';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AccountCard from '~/components/account-card';
import ConfirmCreateModal from './confirm-create-modal';
import ConnectExistingAccount from './connect-existing-account';
import UpsertingAccount from './upserting-account';

/**
 * ConnectBusiness component renders an account card to connect to an existing Reddit Business account.
 *
 * @param {Object} props Component props.
 * @param {Function} props.onRequestCreate A callback to fire when creating a new account.
 * @param {string|null} props.upsertingAction The action the user is performing. Possible values are 'create', 'update', or null.
 * @return {JSX.Element} {@link AccountCard} filled with content.
 */
const ConnectBusiness = ( { onRequestCreate = noop, upsertingAction } ) => {
	const [ showCreateNewModal, setShowCreateNewModal ] = useState( false );

	if ( upsertingAction ) {
		return <UpsertingAccount upsertingAction={ upsertingAction } />;
	}

	const handleCreateClick = () => {
		setShowCreateNewModal( true );
	};

	const handleRequestClose = () => {
		setShowCreateNewModal( false );
	};

	const handleContinue = () => {
		onRequestCreate();
		handleRequestClose();
	};

	return (
		<>
			<ConnectExistingAccount onCreateClick={ handleCreateClick } />

			{ showCreateNewModal && (
				<ConfirmCreateModal
					onContinue={ handleContinue }
					onRequestClose={ handleRequestClose }
				/>
			) }
		</>
	);
};

export default ConnectBusiness;
