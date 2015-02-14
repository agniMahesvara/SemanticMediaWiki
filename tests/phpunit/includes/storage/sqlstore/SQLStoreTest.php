<?php

namespace SMW\Test\SQLStore;

use SMWSQLStore3;
use SMW\Settings;
use SMW\ApplicationFactory;

/**
 * @covers \SMWSQLStore3
 *
 *
 * @group SMW
 * @group SMWExtension
 *
 * @licence GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class SQLStoreTest extends \PHPUnit_Framework_TestCase {

	private $applicationFactory;
	private $store;

	protected function setUp() {
		parent::setUp();

		$this->store = new SMWSQLStore3();
		$this->applicationFactory = ApplicationFactory::getInstance();

		// Default
		$this->applicationFactory->getSettings()->set( 'smwgFixedProperties', array() );
		$this->applicationFactory->getSettings()->set( 'smwgPageSpecialProperties', array() );
	}

	protected function tearDown() {
		$this->applicationFactory->clear();
		$this->store->clear();

		parent::tearDown();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMWSQLStore3',
			$this->store
		);

		$this->assertInstanceOf(
			'\SMW\SQLStore\SQLStore',
			$this->store
		);
	}

	/**
	 * @depends testCanConstruct
	 */
	public function testGetPropertyTables() {

		$defaultPropertyTableCount = count( $this->store->getPropertyTables() );

		$this->assertInternalType(
			'array',
			$this->store->getPropertyTables()
		);

		foreach ( $this->store->getPropertyTables() as $tid => $propTable ) {
			$this->assertInstanceOf(
				'\SMW\SQLStore\TableDefinition',
				$propTable
			);
		}
	}

	/**
	 * @depends testGetPropertyTables
	 */
	public function testPropertyTablesValidCustomizableProperty() {

		$defaultPropertyTableCount = count( $this->store->getPropertyTables() );

		$this->applicationFactory->getSettings()->set(
			'smwgPageSpecialProperties',
			array( '_MDAT' )
		);

		$this->store->clear();

		$this->assertCount(
			$defaultPropertyTableCount + 1,
			$this->store->getPropertyTables()
		);
	}

	/**
	 * @depends testGetPropertyTables
	 */
	public function testPropertyTablesWithInvalidCustomizableProperty() {

		$defaultPropertyTableCount = count( $this->store->getPropertyTables() );

		$this->applicationFactory->getSettings()->set(
			'smwgPageSpecialProperties',
			array( '_MDAT', 'Foo' )
		);

		$this->store->clear();

		$this->assertCount(
			$defaultPropertyTableCount + 1,
			$this->store->getPropertyTables()
		);
	}

	/**
	 * @depends testGetPropertyTables
	 */
	public function testPropertyTablesWithValidCustomizableProperties() {

		$defaultPropertyTableCount = count( $this->store->getPropertyTables() );

		$this->applicationFactory->getSettings()->set(
			'smwgPageSpecialProperties',
			array( '_MDAT', '_MEDIA' )
		);

		$this->store->clear();

		$this->assertCount(
			$defaultPropertyTableCount + 2,
			$this->store->getPropertyTables()
		);
	}

	public function testGetStatisticsTable() {

		$this->assertInternalType(
			'string',
			$this->store->getStatisticsTable()
		);
	}

	public function testGetObjectIds() {

		$this->assertInternalType(
			'object',
			$this->store->getObjectIds()
		);

		$this->assertInternalType(
			'string',
			$this->store->getObjectIds()->getIdTable()
		);
	}

}
