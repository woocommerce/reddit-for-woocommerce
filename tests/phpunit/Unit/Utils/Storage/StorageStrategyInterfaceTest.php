<?php
/**
 * Basic existence test for the StorageStrategyInterface interface.
 *
 * @package RedditForWooCommerce\Tests\Unit\Utils
 */

namespace RedditForWooCommerce\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;

/**
 * @covers \RedditForWooCommerce\Utils\Storage\StorageStrategyInterface
 */
class StorageStrategyInterfaceTest extends TestCase {

	public function test_interface_exists(): void {
		$this->assertTrue( interface_exists( \RedditForWooCommerce\Utils\Storage\StorageStrategyInterface::class ) );
	}
}
