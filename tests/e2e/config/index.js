const config = require( './default.json' );
const { billing } = config.addresses.customer;

const admin = config.users.admin;

const customer = {
	...config.users.customer,
	billing: {
		firstName: billing.firstname,
		lastName: billing.lastname,
		company: billing.company,
		country: 'US',
		countryName: 'United States',
		address: billing.addressfirstline,
		addressSecondLine: billing.addresssecondline,
		city: billing.city,
		state: billing.state,
		stateName: billing.statename,
		zip: billing.postcode,
		phone: billing.phone,
		email: billing.email,
	},
};

module.exports = {
	admin,
	customer,
};
