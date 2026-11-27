/**
 * Internal dependencies
 */
import clientSession from './clientSession';

describe( 'clientSession', () => {
	afterEach( () => {
		window.sessionStorage.clear();
	} );

	it( 'returns null when no campaign is stored', () => {
		expect( clientSession.getCampaign() ).toBeNull();
	} );

	it( 'restores the stored campaign, e.g. after a page refresh', () => {
		clientSession.setCampaign( { level: 'custom', amount: 42 } );

		expect( clientSession.getCampaign() ).toEqual( {
			level: 'custom',
			amount: 42,
		} );
	} );

	it( 'forgets the stored campaign once cleared', () => {
		clientSession.setCampaign( { level: 'custom', amount: 42 } );
		clientSession.clearCampaign();

		expect( clientSession.getCampaign() ).toBeNull();
	} );
} );
