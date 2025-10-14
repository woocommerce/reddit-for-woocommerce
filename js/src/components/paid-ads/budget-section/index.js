/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Tip } from '@wordpress/components';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import Subsection from '~/components/subsection';
import BudgetSetup from '../budget-setup';
import './index.scss';

/**
 * Renders a UI for setting up the campaign budget.
 *
 * @param {Object} props React props.
 * @param {JSX.Element} [props.children] Extra content to be rendered under the card of budget inputs.
 */
const BudgetSection = ( { children } ) => {
	return (
		<div className="rfw-budget-section">
			<Section
				verticalGap={ 4 }
				title={ __( 'Set your budget', 'reddit-for-woocommerce' ) }
			>
				<Section.Card>
					<Section.Card.Body className="rfw-budget-section__card-body">
						<div>
							<Subsection.Title>
								{ __(
									'Average daily budget',
									'reddit-for-woocommerce'
								) }
							</Subsection.Title>
							<Subsection.Subtitle>
								{ __(
									'These values are based on your settings and the budgets of similar advertisers.',
									'reddit-for-woocommerce'
								) }
							</Subsection.Subtitle>
						</div>

						<BudgetSetup />

						<Tip>
							{ __(
								'We recommend running campaigns at least 1 month so it can learn to optimize for your business.',
								'reddit-for-woocommerce'
							) }
						</Tip>
					</Section.Card.Body>
				</Section.Card>
				{ children }
			</Section>
		</div>
	);
};

export default BudgetSection;
