<?php
/**
 * Implementation of fulltext service
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2021-2023 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Implementation of fulltext service
 *
 * The fulltext service is wrapper around single services for a full text
 * search. Such a service can be based on Solr, SQlite, etc. It implements
 * three major methods:
 * IndexedDocument() for creating an instance of an indexed document
 * Indexer() for creating an instance of the index
 * Search() fro creating an instance of a search frontend
 *
 * Though this class can manage more than one service, it will only
 * use the first one.
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2021-2023 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_FulltextService {
	/**
	 * List of services for searching fulltext
	 */
	protected $services;

	/**
	 * List of converters
	 */
	protected $converters;

	/**
	 * @var object
	 */
	protected $conversionmgr;

	/**
	 * @var logger
	 */
	protected $logger;

	/**
	 * Max file size for imediate indexing
	 */
	protected $maxsize;

	private $index;

	private $search;

	public function __construct() {
		$this->services = array();
		$this->converters = array();
		$this->conversionmgr = null;
		$this->previewer = null;
		$this->logger = null;
		$this->maxsize = 0;
		$this->index = null;
		$this->search = null;
		$this->cmdtimeout = 5;
	}

	public function addService($name, $service) {
		$this->services[] = $service;
	}

	public function setConverters($converters) {
		$this->converters = $converters;
	}

	public function setLogger($logger) {
		$this->logger = $logger;
	}

	/**
	 * Set conversion service manager
	 *
	 * A conversion manager is a service for converting files from one format
	 * to another format.
	 *
	 * @param object $conversionmgr
	 */
	function setConversionMgr($conversionmgr) { /* {{{ */
		$this->conversionmgr = $conversionmgr;
	} /* }}} */

	public function setMaxSize($maxsize) {
		$this->maxsize = $maxsize;
	}

	public function setCmdTimeout($timeout) {
		$this->cmdtimeout = $timeout;
	}

	public function setPreviewer($previewer) {
		$this->previewer = $previewer;
	}

	/**
	 * Returns callback function to convert a document into plain text
	 *
	 * This variant just uses the conversion manager and does not
	 * cache the converted document
	 */
	public function getConversionCallback() { /* {{{ */
		$conversionmgr = $this->conversionmgr;
		return function($object) use ($conversionmgr) {
			$result = ['content'=>false, 'cmd'=>'', 'errormsg'=>''];
			if(!$conversionmgr)
				return $result;
			if($object->isType('document')) {
				$dms = $object->getDMS();
				$version = $object->getLatestContent();
				$mimetype = $version->getMimeType();
				$path = $dms->contentDir . $version->getPath();
				if(file_exists($path)) {
					if($service = $conversionmgr->getService($mimetype, 'text/plain')) {
						$content = $conversionmgr->convert($path, $mimetype, 'text/plain');
						if($content) {
							$result['content'] = $content;
						} elseif($content === false) {
							$result['errormsg'] = 'Conversion failed';
						}
						$result['cmd'] = get_class($service);
					} else {
						$result['cmd'] = 'No service to convert '.$mimetype.' to text/plain';
					}
				}
			}
			return $result;
		};
	} /* }}} */

	/**
	 * Returns callback function to convert a document into plain text
	 *
	 * This variant uses the text previewer which
	 * caches the converted document
	 */
	public function getConversionWithPreviewCallback() { /* {{{ */
		$previewer = $this->previewer;
		return function($object) use ($previewer) {
			$result = ['content'=>false, 'cmd'=>'', 'errormsg'=>''];
			if($object->isType('document')) {
				$dms = $object->getDMS();
				$version = $object->getLatestContent();
				if($previewer->createPreview($version)) {
					if($previewer->hasPreview($version)) {
						$filename = $previewer->getFileName($version).'.txt';
						$result['content'] = file_get_contents($filename);
						$result['cmd'] = 'previewer '.$previewer->getFileSize($version);
					}
				} else {
					$result['cmd'] = 'previewer';
					$result['errormsg'] = 'Creating preview failed';
				}
			}
			return $result;
		};
	} /* }}} */

	/**
	 * Return an indexable document based on the given document or folder
	 *
	 * @param SeedDMS_Core_Document|SeedDMS_Core_Folder $object document or folder
	 * to be indexed
	 * @param boolean $forceupdate set to true if the document shall be updated no
	 * matter how large the content is. Setting this to false will only update the
	 * document if its content is below the configured size.
	 * @return object indexed Document ready for passing to the indexer
	 */
	public function IndexedDocument($object, $forceupdate=false) { /* {{{ */
		if($object->isType('document'))
			$nocontent = $object->getLatestContent()->getFileSize() > $this->maxsize && $this->maxsize && !$forceupdate;
		else
			$nocontent = true;
		$convcallback = $this->getConversionWithPreviewCallback();
		return new $this->services[0]['IndexedDocument']($object->getDMS(), $object, $convcallback /*$this->conversionmgr ? $this->conversionmgr : $this->converters*/, $nocontent, $this->cmdtimeout);
	} /* }}} */

	/**
	 * Returns an instance of the indexer
	 *
	 * The indexer provides access to the fulltext index. It allows to add and
	 * get documents.
	 *
	 * @return object instance of class specified in 'Indexer'
	 */
	public function Indexer($recreate=false) { /* {{{ */
		if($this->index)
			return $this->index;

		if($this->services[0]) {
			if($recreate)
				$this->index = $this->services[0]['Indexer']::create($this->services[0]['Conf']);
			else
				$this->index = $this->services[0]['Indexer']::open($this->services[0]['Conf']);
			return $this->index;
		} else
			return null;
	} /* }}} */

	public function Search() { /* {{{ */
		if($this->search)
			return $this->search;
		if($this->services[0]) {
			$this->search = new $this->services[0]['Search']($this->index);
			return $this->search;
		} else {
			return null;
		}
	} /* }}} */
}


