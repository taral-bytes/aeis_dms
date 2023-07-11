<?php
/**
 * Implementation of lucene index
 *
 * @category   DMS
 * @package    SeedDMS_Lucene
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */


/**
 * Class for managing a lucene index.
 *
 * @category   DMS
 * @package    SeedDMS_Lucene
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Lucene_Indexer {
	/**
	 * @var string $indexname name of lucene index
	 * @access protected
	 */
	protected $indexname;

	/**
	 * @var string $index lucene index
	 * @access protected
	 */
	protected $index;

	public function __construct($index) {
		$this->index = $index;
	}

	static function open($conf) { /* {{{ */
		try {
			$index = Zend_Search_Lucene::open($conf['indexdir']);
			if($index)
				return new self($index);
			else
				return null;
		} catch (Exception $e) {
			return null;
		}
	} /* }}} */

	static function create($conf) { /* {{{ */
		try {
			$index = Zend_Search_Lucene::create($conf['indexdir']);
			if($index)
				return new self($index);
			else
				return null;
		} catch (Exception $e) {
			return null;
		}
	} /* }}} */

	/**
	 * Do some initialization
	 *
	 */
	public function init($stopWordsFile='') { /* {{{ */
		$analyzer = new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8_CaseInsensitive();
		if($stopWordsFile && file_exists($stopWordsFile)) {
			$stopWordsFilter = new Zend_Search_Lucene_Analysis_TokenFilter_StopWords();
			$stopWordsFilter->loadFromFile($stopWordsFile);
			$analyzer->addFilter($stopWordsFilter);
		}
		 
		Zend_Search_Lucene_Analysis_Analyzer::setDefault($analyzer);
	} /* }}} */

	/**
	 * Add document to index
	 *
	 * @param object $doc indexed document of class 
	 * SeedDMS_Lucene_IndexedDocument
	 * @return boolean false in case of an error, otherwise true
	 */
	function addDocument($doc) { /* {{{ */
		if(!$this->index)
			return false;

		/* addDocument() does not return anything */
		$this->index->addDocument($doc);
		return true;
	} /* }}} */

	/**
	 * Request reindexing a document
	 *
	 * Because Lucene does not any function to update the field of
	 * a document, this method just deleteѕ the whole document from
	 * the index.
	 *
	 * @param integer $id id of the document
	 * @return boolean false in case of an error, otherwise true
	 */
	public function reindexDocument($id) { /* {{{ */
		if(!$this->index)
			return false;

		return $this->index->delete($id);
	} /* }}} */

	/**
	 * Remove document from index
	 *
	 * @param object $id internal id of document
	 * @return boolean false in case of an error, otherwise true
	 */
	public function delete($id) { /* {{{ */
		if(!$this->index)
			return false;

		return $this->index->delete($id);
	} /* }}} */

	/**
	 * Check if document was deleted
	 *
	 * @param object $id internal id of document
	 * @return boolean true if document was deleted
	 */
	public function isDeleted($id) { /* {{{ */
		if(!$this->index)
			return false;

		return $this->index->isDeleted($id);
	} /* }}} */

	/**
	 * Search in index
	 *
	 * @param string $query
	 * @return array result
	 */
	public function find($query) { /* {{{ */
		if(!$this->index)
			return false;

		return $this->index->find($query);
	} /* }}} */

	/**
	 * Get a single document from index
	 *
	 * @param string $id id of document
	 * @return boolean false in case of an error, otherwise true
	 */
	public function findById($id) { /* {{{ */
		if(!$this->index)
			return false;

		return $this->index->findById($id);
	} /* }}} */

	/**
	 * Get a single document from index
	 *
	 * @param integer $id id of index record
	 * @return boolean false in case of an error, otherwise true
	 */
	public function getDocument($id, $content=true) { /* {{{ */
		if(!$this->index)
			return false;

		return $this->index->getDocument($id);
	} /* }}} */

	/**
	 * Return list of terms in index
	 *
	 * @return array list of Zend_Lucene_Term
	 */
	public function terms($prefix='', $col='') { /* {{{ */
		if(!$this->index)
			return false;

		return $this->index->terms();
	} /* }}} */

	/**
	 * Return number of documents in index
	 *
	 * @return interger number of documents
	 */
	public function count() { /* {{{ */
		if(!$this->index)
			return false;

		return $this->index->count();
	} /* }}} */

	/**
	 * Commit changes
	 *
	 * This function does nothing!
	 */
	function commit() { /* {{{ */
		if(!$this->index)
			return false;

		return $this->index->commit();
	} /* }}} */

	/**
	 * Optimize index
	 *
	 * This function does nothing!
	 */
	function optimize() { /* {{{ */
		if(!$this->index)
			return false;

		return $this->index->optimize();
	} /* }}} */
}
?>
