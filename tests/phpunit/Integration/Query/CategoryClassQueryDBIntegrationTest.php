<?php

namespace SMW\Tests\Integration\Query;

use SMW\Tests\MwDBaseUnitTestCase;
use SMW\Tests\Util\SemanticDataFactory;
use SMW\Tests\Util\Validators\QueryResultValidator;

use SMW\DIWikiPage;
use SMW\DIProperty;
use SMW\DataValueFactory;

use SMWDIBlob as DIBlob;
use SMWQuery as Query;
use SMWQueryResult as QueryResult;
use SMWDataValue as DataValue;
use SMWDataItem as DataItem;
use SMW\Query\Language\SomeProperty as SomeProperty;
use SMWPrintRequest as PrintRequest;
use SMWPropertyValue as PropertyValue;
use SMW\Query\Language\ThingDescription as ThingDescription;
use SMW\Query\Language\ClassDescription as ClassDescription;

/**
 *
 * @group SMW
 * @group SMWExtension
 * @group semantic-mediawiki-integration
 * @group semantic-mediawiki-query
 * @group mediawiki-database
 * @group medium
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class CategoryClassQueryDBIntegrationTest extends MwDBaseUnitTestCase {

	protected $databaseToBeExcluded = array( 'sqlite' );

	private $subjectsToBeCleared = array();
	private $semanticDataFactory;
	private $dataValueFactory;
	private $queryResultValidator;

	protected function setUp() {
		parent::setUp();

		$this->dataValueFactory = DataValueFactory::getInstance();
		$this->semanticDataFactory = new SemanticDataFactory();
		$this->queryResultValidator = new QueryResultValidator();
	}

	protected function tearDown() {

		foreach ( $this->subjectsToBeCleared as $subject ) {
			$this->getStore()->deleteSubject( $subject->getTitle() );
		}

		parent::tearDown();
	}

	public function testSubjects_onCategoryCondition() {

		$property = new DIProperty( '_INST' );

		$dataValue = $this->dataValueFactory->newPropertyObjectValue( $property, 'SomeCategory' );

		$semanticData = $this->semanticDataFactory->newEmptySemanticData( __METHOD__ );

		$semanticData->addDataValue( $dataValue	);

		$this->getStore()->updateData( $semanticData );

		$this->assertArrayHasKey(
			$property->getKey(),
			$this->getStore()->getSemanticData( $semanticData->getSubject() )->getProperties()
		);

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( $property );

		$description = new SomeProperty(
			$property,
			new ThingDescription()
		);

		$description->addPrintRequest(
			new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue )
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		$queryResult = $this->getStore()->getQueryResult( $query );

		$this->queryResultValidator->assertThatQueryResultHasSubjects(
			$semanticData->getSubject(),
			$this->searchForResultsThatCompareEqualToClassOf( 'SomeCategory' )
		);

		$this->queryResultValidator->assertThatQueryResultContains(
			$dataValue,
			$this->searchForResultsThatCompareEqualToClassOf( 'SomeCategory' )
		);
	}

	private function searchForResultsThatCompareEqualToClassOf( $categoryName ) {

		$propertyValue = new PropertyValue( '__pro' );
		$propertyValue->setDataItem( new DIProperty( '_INST' ) );

		$description = new ClassDescription(
			new DIWikiPage( $categoryName, NS_CATEGORY, '' )
		);

		$description->addPrintRequest(
			new PrintRequest( PrintRequest::PRINT_PROP, null, $propertyValue )
		);

		$query = new Query(
			$description,
			false,
			false
		);

		$query->querymode = Query::MODE_INSTANCES;

		return $this->getStore()->getQueryResult( $query );
	}

}
