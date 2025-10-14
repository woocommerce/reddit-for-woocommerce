/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';
import { Flex, FlexItem, FlexBlock } from '@wordpress/components';
import { megaphone, tag, external, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { OPTIONS } from './constants';
import { recordRfwEvent } from '~/utils/tracks';
import AdaptiveForm from '~/components/adaptive-form';
import AppModal from '~/components/app-modal';
import AppButton from '~/components/app-button';
import AppDocumentationLink from '~/components/app-documentation-link';
import Survey from './survey';
import './survey-modal.scss';

/**
 * Send survey responses when the user skips the paid ads setup.
 *
 * @event rfw_skip_campaign_creation_survey
 * @property {string} context Name of the context where the survey was triggered (e.g. 'skip-paid-ads-survey-modal').
 * @property {boolean} i_already_have_ads_on_reddit Indicates if the user already has ads on Reddit.
 * @property {boolean} i_dont_have_the_budget_to_create_ads_now Indicates if the user doesn't have the budget to create ads now.
 * @property {boolean} ive_tried_reddit_ads_before_without_success Indicates if the user has tried Reddit ads before without success.
 * @property {boolean} i_dont_want_ads_on_reddit Indicates if the user doesn't want ads on Reddit.
 * @property {string} i_dont_want_ads_on_reddit_text Text input for the reason why the user doesn't want ads on Reddit.
 * @property {boolean} ill_create_ads_later Indicates if the user will create ads later.
 * @property {string} ill_create_ads_later_text Text input for the reason why the user will create ads later.
 * @property {boolean} other Indicates if the user has another reason.
 * @property {string} other_text Text input for the user's other reason.
 */

/**
 * Renders a modal dialog that confirms whether the user wants to skip setting up paid ads along with a survey to understand their reasons.
 * It provides information about the benefits of enabling Performance Max and includes a link to learn more.
 *
 * @param {Object} props React props.
 * @param {Function} props.onRequestClose Function to be called when the modal should be closed.
 * @param {Function} props.onSkipCreatePaidAds Function to be called when the user confirms skipping the paid ads setup.
 * @fires rfw_documentation_link_click with `{ context: 'skip-paid-ads-survey-modal', link_id: 'paid-ads-learn-more', href: 'https://www.business.reddit.com/learn' }`
 * @fires rfw_skip_campaign_creation_survey with the survey responses and context 'skip-paid-ads-survey-modal'.
 */
const SurveyModal = ( { onRequestClose, onSkipCreatePaidAds } ) => {
	const formRef = useRef();

	const initialFormValues = OPTIONS.reduce( ( accumulator, option ) => {
		if ( option.hasTextInput ) {
			return {
				...accumulator,
				[ option.value ]: false,
				[ `${ option.value }__text` ]: '', // This is to handle the text input for options that require additional explanation.
			};
		}

		return {
			...accumulator,
			[ option.value ]: false,
		};
	}, {} );

	return (
		<AdaptiveForm ref={ formRef } initialValues={ initialFormValues }>
			{ ( formContext ) => {
				const handleSendAndCompleteSetupClick = async () => {
					const { values, isDirty } = formContext;

					if ( isDirty ) {
						recordRfwEvent( 'rfw_skip_campaign_creation_survey', {
							...values,
							context: 'skip-paid-ads-survey-modal',
						} );
					}

					onSkipCreatePaidAds();
				};

				return (
					<AppModal
						className="rfw-skip-paid-ads-survey-modal"
						title={ __(
							'Skip setting up ads?',
							'reddit-for-woocommerce'
						) }
						buttons={ [
							<AppButton
								key="cancel"
								isSecondary
								onClick={ onRequestClose }
							>
								{ __( 'Cancel', 'reddit-for-woocommerce' ) }
							</AppButton>,
							<AppButton
								key="complete-setup"
								onClick={ handleSendAndCompleteSetupClick }
								isPrimary
							>
								{ __(
									'Send and complete setup',
									'reddit-for-woocommerce'
								) }
							</AppButton>,
						] }
						onRequestClose={ onRequestClose }
					>
						<Flex
							gap={ 6 }
							align="flex-start"
							direction={ [ 'column', 'row' ] }
							wrap
						>
							<FlexBlock>
								<div className="rfw-skip-paid-ads-survey-modal__benefits">
									<h3 className="rfw-skip-paid-ads-survey-modal__benefits-title">
										{ __(
											'Reach Reddit users with engaging ads',
											'reddit-for-woocommerce'
										) }
									</h3>

									<ul className="rfw-skip-paid-ads-survey-modal__benefits-list">
										<li>
											<Flex
												gap={ 4 }
												align="flex-start"
												justify="flex-start"
											>
												<FlexItem>
													<Icon
														icon={ tag }
														width={ 24 }
													/>
												</FlexItem>

												<FlexBlock>
													<h4 className="rfw-skip-paid-ads-survey-modal__benefits-item-title">
														{ __(
															'Grow your audience',
															'reddit-for-woocommerce'
														) }
													</h4>
													<p>
														{ __(
															'Run targeted ads on Reddit and reach users within relevant communities and conversations.',
															'reddit-for-woocommerce'
														) }
													</p>
												</FlexBlock>
											</Flex>
										</li>
										<li>
											<Flex
												gap={ 4 }
												align="flex-start"
												justify="flex-start"
											>
												<FlexItem>
													<Icon
														icon={ megaphone }
														width={ 24 }
													/>
												</FlexItem>
												<FlexBlock>
													<h4 className="rfw-skip-paid-ads-survey-modal__benefits-item-title">
														{ __(
															'Use advanced tracking and optimization',
															'reddit-for-woocommerce'
														) }
													</h4>
													<p>
														{ __(
															'With the Reddit Pixel and Conversions API, track conversions and improve performance with real-time data.',
															'reddit-for-woocommerce'
														) }
													</p>
													<p>
														<AppDocumentationLink
															href="https://www.business.reddit.com/learn"
															context="skip-paid-ads-survey-modal"
															linkId="paid-ads-learn-more"
														>
															{ __(
																'Learn more about advertising on Reddit',
																'reddit-for-woocommerce'
															) }{ ' ' }
															<Icon
																icon={
																	external
																}
																size={ 16 }
															/>
														</AppDocumentationLink>
													</p>
												</FlexBlock>
											</Flex>
										</li>
									</ul>
								</div>
							</FlexBlock>
							<FlexBlock>
								<Survey />
							</FlexBlock>
						</Flex>
					</AppModal>
				);
			} }
		</AdaptiveForm>
	);
};

export default SurveyModal;
