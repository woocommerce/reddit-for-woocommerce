<?php
/**
 * Interface definition for final Conversion Event payload builders.
 *
 * This interface defines the contract for classes that assemble the complete
 * payload structure required to transmit a Conversion Event to an Ad Partner’s
 * Conversions API. While other interfaces (e.g., ConversionCommonPayloadInterface
 * and ConversionProductsPayloadInterface) define specific portions of the payload,
 * this interface ensures that a fully buildable and dispatch-ready payload is
 * produced.
 *
 * Implementing classes are responsible for:
 * - Combining common fields (partner, event timestamp, source, type)
 * - Merging product-level or order-level metadata
 * - Incorporating optional user data (e.g., IP address, user agent, click IDs)
 * - Producing the final payload array in the schema expected by the Ad Partner
 *
 * ---
 * 🔗 Related Interfaces:
 * - ConversionCommonPayloadInterface — defines shared core fields.
 * - ConversionProductsPayloadInterface — defines product-specific details.
 * ---
 *
 * @package RedditForWooCommerce\Tracking\ConversionEvent\Contract
 * @since 0.1.0
 */

namespace RedditForWooCommerce\Tracking\ConversionEvent\Contract;

/**
 * Interface for final Conversion Event payload builders.
 *
 * Enforces that implementing event classes can construct a complete
 * associative array payload suitable for dispatch to an Ad Partner’s
 * Conversions API.
 *
 * @since 0.1.0
 */
interface ConversionFinalPayloadInterface {
	/**
	 * Builds the raw Conversion Event payload.
	 *
	 * This method should combine all relevant metadata — including common
	 * event fields, product details, contextual information, and deduplication
	 * identifiers — into a fully structured payload that matches the Ad Partner’s
	 * specification.
	 *
	 * Implementations should allow selective overrides via the `$args` parameter,
	 * enabling flexibility for advanced use cases (e.g., injecting a pre-generated
	 * conversion ID or custom user data).
	 *
	 * @since 0.1.0
	 *
	 * @param array<string,mixed> $args Optional arguments to override or extend
	 *                                  the final payload data.
	 * @return array<string,mixed> Fully structured Conversion Event payload
	 *                             ready for transmission.
	 */
	public function build_payload( array $args ): array;
}
