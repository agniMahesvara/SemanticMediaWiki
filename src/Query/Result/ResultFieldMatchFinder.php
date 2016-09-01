<?php

namespace SMW\Query\Result;

use SMW\Query\PrintRequest;
use SMW\DataValueFactory;
use SMW\RequestOptions;
use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Store;
use SMWDataItem as DataItem;
use SMWDIBoolean as DIBoolean;

/**
 * Returns the result content (DI objects) for a single PrintRequest represented
 * as cell of the intersection between a subject row and a print column.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author Markus Krötzsch
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author mwjames
 */
class ResultFieldMatchFinder {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var PrintRequest
	 */
	private $printRequest;

	/**
	 * @var boolean|array
	 */
	private static $catCacheObj = false;

	/**
	 * @var boolean|array
	 */
	private static $catCache = false;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param PrintRequest $printRequest
	 */
	public function __construct( Store $store, PrintRequest $printRequest ) {
		$this->printRequest = $printRequest;
		$this->store = $store;
	}

	/**
	 * @since 2.5
	 *
	 * @param DataItem $dataItem
	 *
	 * @param DataItem[]|[]
	 */
	public function getResultsBy( DataItem $dataItem ) {

		$content = array();

		// Request the current element (page in result set).
		// The limit is ignored here.
		if ( $this->printRequest->isMode( PrintRequest::PRINT_THIS ) ) {
			return array( $dataItem );
		}

		// Request all direct categories of the current element
		// Always recompute cache here to ensure output format is respected.
		if ( $this->printRequest->isMode( PrintRequest::PRINT_CATS ) ) {
			self::$catCache = $this->store->getPropertyValues(
				$dataItem,
				new DIProperty( '_INST' ),
				$this->getRequestOptions( false )
			);

			self::$catCacheObj = $dataItem->getHash();

			$limit = $this->printRequest->getParameter( 'limit' );

			return ( $limit === false ) ? ( self::$catCache ) : array_slice( self::$catCache, 0, $limit );
		}

		// Request to whether current element is in given category (Boolean printout).
		// The limit is ignored here.
		if ( $this->printRequest->isMode( PrintRequest::PRINT_CCAT ) ) {
			if ( self::$catCacheObj !== $dataItem->getHash() ) {
				self::$catCache = $this->store->getPropertyValues(
					$dataItem,
					new DIProperty( '_INST' )
				);
				self::$catCacheObj = $dataItem->getHash();
			}

			$found = false;
			$prkey = $this->printRequest->getData()->getDBkey();

			foreach ( self::$catCache as $cat ) {
				if ( $cat->getDBkey() == $prkey ) {
					$found = true;
					break;
				}
			}

			return array( new DIBoolean( $found ) );
		}

		// Request all property values of a certain attribute of the current element.
		if ( $this->printRequest->isMode( PrintRequest::PRINT_PROP ) ) {

			$content = $this->getResultContent(
				$dataItem
			);

			if ( !$this->isMultiValueWithIndex() ) {
				return $content;
			}

			// Print one component of a multi-valued string.
			// Known limitation: the printrequest still is of type _rec, so if printers check
			// for this then they will not recognize that it returns some more concrete type.

			$propertyValue = $this->printRequest->getData();

			$index = $this->printRequest->getParameter( 'index' );
			$newcontent = array();

			foreach ( $content as $diContainer ) {

				/* SMWRecordValue */
				$recordValue = DataValueFactory::getInstance()->newDataValueByItem(
					$diContainer,
					$propertyValue->getDataItem()
				);

				if ( ( $dataItemByRecord = $recordValue->getDataItemByIndex( $index ) ) !== null ) {
					$newcontent[] = $dataItemByRecord;
				}
			}

			$content = $newcontent;
			unset( $newcontent );
		}

		return $content;
	}

	/**
	 * Make a request option object based on the given parameters, and
	 * return NULL if no such object is required. The parameter defines
	 * if the limit should be taken into account, which is not always desired
	 * (especially if results are to be cached for future use).
	 *
	 * @param boolean $useLimit
	 *
	 * @return RequestOptions|null
	 */
	public function getRequestOptions( $useLimit = true ) {
		$limit = $useLimit ? $this->printRequest->getParameter( 'limit' ) : false;
		$order = trim( $this->printRequest->getParameter( 'order' ) );

		// Important: use "!=" for order, since trim() above does never return "false", use "!==" for limit since "0" is meaningful here.
		if ( ( $limit !== false ) || ( $order != false ) ) {
			$options = new RequestOptions();

			if ( $limit !== false ) {
				$options->limit = trim( $limit );
			}

			if ( ( $order == 'descending' ) || ( $order == 'reverse' ) || ( $order == 'desc' ) ) {
				$options->sort = true;
				$options->ascending = false;
			} elseif ( ( $order == 'ascending' ) || ( $order == 'asc' ) ) {
				$options->sort = true;
				$options->ascending = true;
			}
		} else {
			$options = null;
		}

		return $options;
	}

	private function isMultiValueWithIndex() {
		return strpos( $this->printRequest->getTypeID(), '_rec' ) !== false && $this->printRequest->getParameter( 'index' ) !== false;
	}

	private function getResultContent( DataItem $dataItem ) {

		$dataValue = $this->printRequest->getData();
		$dataItems = array( $dataItem );

		if ( !$dataValue->isValid() ) {
			return array();
		}

		return $this->doFetchPropertyValues( $dataItems, $dataValue );
	}

	private function doFetchPropertyValues( $dataItems, $dataValue ) {

		$propertyValues = array();

		foreach ( $dataItems as $dataItem ) {

			if ( !$dataItem instanceof DIWikiPage ) {
				continue;
			}

			$pv = $this->store->getPropertyValues(
				$dataItem,
				$dataValue->getDataItem(),
				$this->getRequestOptions()
			);

			$propertyValues = array_merge( $propertyValues, $pv );
			unset( $pv );
		}

		return $propertyValues;
	}

}
