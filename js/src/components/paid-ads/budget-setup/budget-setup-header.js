/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { forwardRef } from '@wordpress/element';
import { Tooltip } from '@wordpress/components';
import GridiconInfoOutline from 'gridicons/dist/info-outline';

/**
 * Internal dependencies
 */
import styles from './budget-setup.module.scss';

const InfoIcon = forwardRef( function ( props, ref ) {
	return (
		<span { ...props } ref={ ref }>
			<GridiconInfoOutline size={ 16 } />
		</span>
	);
} );

/**
 * Renders the header for the budget setup component.
 */
export default function BudgetSetupHeader() {
	return (
		<div className={ classnames( styles.header, styles.metricsGroup ) }>
			<span className={ styles.metricsItem }>
				{ __( 'Weekly conversions', 'reddit-for-woocommerce' ) }
				<Tooltip
					className={ styles.tooltip }
					text={
						<>
							{ __(
								'The estimated total value of all the conversions (sales volume) your campaign will generate in a week.',
								'reddit-for-woocommerce'
							) }
						</>
					}
				>
					<InfoIcon />
				</Tooltip>
			</span>
			<span className={ styles.metricsItem }>
				{ __( 'Weekly conv. value', 'reddit-for-woocommerce' ) }
				<Tooltip
					className={ styles.tooltip }
					text={ __(
						'The estimated number of conversions (unit sales) for a typical week. This number may vary based on change in your weekly spend.',
						'reddit-for-woocommerce'
					) }
				>
					<InfoIcon />
				</Tooltip>
			</span>
			<span className={ styles.metricsItem }>
				{ __( 'Weekly cost', 'reddit-for-woocommerce' ) }
				<Tooltip
					className={ styles.tooltip }
					text={ __(
						`This is the estimated average amount you'll spend weekly during the month. Some weeks you may spend less than this amount or up to 2 times this amount.`,
						'reddit-for-woocommerce'
					) }
				>
					<InfoIcon />
				</Tooltip>
			</span>
		</div>
	);
}
