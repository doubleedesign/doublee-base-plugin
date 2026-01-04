<?php
namespace Doubleedesign\BasePlugin\Tests;
use Brain\Monkey;
use Mockery;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Spies;

abstract class TestCase extends BaseTestCase {
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		Monkey\tearDown();
		Mockery::close();
		Spies\finish_spying();
	}
}
