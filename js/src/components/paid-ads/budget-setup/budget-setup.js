/**
 * External dependencies
 */
import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useDebounce } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import useBudgetMetrics from '~/hooks/useBudgetMetrics';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import AppInputPriceControl from '~/components/app-input-price-control';
import BudgetBadge from './budget-badge';
import BudgetRadioControl from './budget-radio-control';
import DailyBudgetLabel from './daily-budget-label';
import LowBudgetNotice from './low-budget-notice';
import round from '~/utils/round';
import styles from './budget-setup.module.scss';

const i18nLevel = {
	low: __( 'Low', 'reddit-for-woocommerce' ),
	high: __( 'High', 'reddit-for-woocommerce' ),
	current: __( 'Current', 'reddit-for-woocommerce' ),
	recommended: __( 'Recommended', 'reddit-for-woocommerce' ),
};

function isBelowLowRecommendation( amount, recommendation, precision ) {
	if (
		! recommendation?.low?.dailyBudget ||
		! Number.isInteger( precision )
	) {
		return false;
	}

	return amount < round( recommendation.low.dailyBudget, precision );
}

function BudgetMetrics( { formatAmount, metrics } ) {
	const renderUplift = metrics?.uplift && Number( metrics?.uplift ) !== 0;

	return (
		<div className={ styles.metricsGroup }>
			<span className={ styles.metricsItem }>
				{ metrics ? metrics.conversions : null }
			</span>
			<span className={ styles.metricsItem }>
				{ metrics ? formatAmount( metrics.conversionsValue ) : null }
				{ !! renderUplift && <BudgetBadge amount={ metrics.uplift } /> }
			</span>
			<span className={ styles.metricsItem }>
				{ metrics ? formatAmount( metrics.cost ) : null }
			</span>
		</div>
	);
}

/**
 * Renders a UI for selecting a campaign budget from recommendations or
 * entering a custom campaign budget.
 *
 * Please note that this component relies on a CampaignAssetsForm's context and custom adapter,
 * so it expects a `CampaignAssetsForm` to exist in its parents.
 *
 * @param {Object} props React props.
 * @param {boolean} [props.hideRecommendations=false]
 */
export default function BudgetSetup( { hideRecommendations = false } ) {
	const formContext = useAdaptiveFormContext();
	const { adapter, getInputProps, setValue, values } = formContext;
	const { budgetRecommendation } = adapter;
	const { amount } = values;
	const { adsCurrencyConfig, formatAmount } = useAdsCurrency();
	const currencyPrecision = Number.isInteger( adsCurrencyConfig?.precision )
		? adsCurrencyConfig.precision
		: undefined;

	const [ budget, setBudget ] = useState( amount );
	const debouncedSetBudget = useDebounce( setBudget, 1000 );
	const { data } = useBudgetMetrics( budget );

	useEffect( () => {
		debouncedSetBudget( amount );
	}, [ debouncedSetBudget, amount ] );

	useEffect( () => {
		const { level } = values;

		if ( ! level || level === 'custom' ) {
			return;
		}

		let nextAmount;

		if ( level === 'current' ) {
			nextAmount = values.currentAmount;
		} else {
			nextAmount = budgetRecommendation?.[ level ]?.dailyBudget;
		}

		const normalizedNextAmount = Number( nextAmount );

		if ( ! Number.isFinite( normalizedNextAmount ) ) {
			return;
		}

		const normalizedCurrentAmount = Number( values.amount );

		let amountsMatch;

		if ( Number.isFinite( normalizedCurrentAmount ) ) {
			if ( currencyPrecision !== undefined ) {
				amountsMatch =
					round( normalizedCurrentAmount, currencyPrecision ) ===
					round( normalizedNextAmount, currencyPrecision );
			} else {
				amountsMatch = normalizedCurrentAmount === normalizedNextAmount;
			}
		} else {
			amountsMatch = false;
		}

		if ( amountsMatch ) {
			return;
		}

		setValue( 'amount', normalizedNextAmount );
	}, [
		budgetRecommendation,
		currencyPrecision,
		setValue,
		values.amount,
		values.currentAmount,
		values.level,
	] );

	const options = [ 'high', 'recommended', 'low' ].reduce( ( acc, level ) => {
		const item = hideRecommendations
			? null
			: budgetRecommendation?.[ level ];

		if ( item ) {
			const dailyBudget = formatAmount( item.dailyBudget );

			acc.push( {
				level,
				metrics: item.metrics,
				radioProps: {
					...getInputProps( 'level' ),
					value: level,
					label: <DailyBudgetLabel amount={ dailyBudget } />,
				},
			} );
		}
		return acc;
	}, [] );

	const { help, ...amountInputProps } = getInputProps( 'amount' );
	const shouldNoticeRecommendedBudget =
		! help &&
		! hideRecommendations &&
		budgetRecommendation?.recommended &&
		isBelowLowRecommendation(
			amount,
			budgetRecommendation,
			adsCurrencyConfig.precision
		);

	const getRowClassName = ( level ) => {
		const selected = level === values.level;
		return classnames(
			styles.row,
			level === 'custom' && styles.customRow,
			hideRecommendations && styles.hideRecommendations,
			selected && styles.rowSelected
		);
	};

	return (
		<div className={ styles.container }>
			{ options.map( ( { level, radioProps } ) => {
				const helperContentClassName =
					level === 'recommended'
						? styles.highlightRecommended
						: null;

				return (
					<div key={ level } className={ getRowClassName( level ) }>
						<BudgetRadioControl { ...radioProps } />
						<div className={ styles.helper }>
							<span className={ helperContentClassName }>
								{ i18nLevel[ level ] }
							</span>
						</div>
					</div>
				);
			} ) }

			<div className={ getRowClassName( 'custom' ) }>
				<BudgetRadioControl
					{ ...getInputProps( 'level' ) }
					value="custom"
					label={ __(
						'Set custom budget',
						'reddit-for-woocommerce'
					) }
				/>
				{ values.level === 'custom' && (
					<>
						<AppInputPriceControl
							suffix={ data?.currency }
							{ ...amountInputProps }
							className={ classnames(
								amountInputProps.className,
								styles.customInput
							) }
						/>
						<BudgetMetrics
							formatAmount={ formatAmount }
							metrics={ data?.metrics }
						/>
						{ help && (
							<div className={ styles.customHelp } role="alert">
								{ help }
							</div>
						) }
						{ shouldNoticeRecommendedBudget && (
							<LowBudgetNotice
								className={ styles.customNotice }
								recommendedAmount={ formatAmount(
									budgetRecommendation.recommended.dailyBudget
								) }
							/>
						) }
					</>
				) }
			</div>
		</div>
	);
}
