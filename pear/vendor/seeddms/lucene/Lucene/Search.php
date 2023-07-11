<?php
/**
 * Implementation of search in lucene index
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
 * Class for searching in a lucene index.
 *
 * @category   DMS
 * @package    SeedDMS_Lucene
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Lucene_Search {
	/**
	 * @var object $index lucene index
	 * @access protected
	 */
	protected $index;

	/**
	 * Create a new instance of the search
	 *
	 * @param object $index lucene index
	 * @return object instance of SeedDMS_Lucene_Search
	 */
	function __construct($index) { /* {{{ */
		$this->index = $index;
		$this->version = '@package_version@';
		if($this->version[0] == '@')
			$this->version = '3.0.0';
	} /* }}} */

	/**
	 * Get document from index
	 *
	 * @param object $index lucene index
	 * @return object instance of SeedDMS_Lucene_Document of false
	 */
	function getDocument($id) { /* {{{ */
		$hits = $this->index->find('document_id:D'.$id);
		return $hits ? $hits[0] : false;
	} /* }}} */

	/**
	 * Get folder from index
	 *
	 * @param object $index lucene index
	 * @return object instance of SeedDMS_Lucene_Document of false
	 */
	function getFolder($id) { /* {{{ */
		$hits = $this->index->find('document_id:F'.$id);
		return $hits ? $hits[0] : false;
	} /* }}} */

	/**
	 * Search in index
	 *
	 * @param object $index lucene index
	 * @return object instance of SeedDMS_Lucene_Search
	 */
	function search($term, $fields=array(), $limit=array(), $order=array()) { /* {{{ */
		$querystr = '';
		$term = trim($term);
		if($term) {
			$querystr = substr($term, -1) != '*' ? $term.'*' : $term;
		}
		if(!empty($fields['owner'])) {
			if(is_string($fields['owner'])) {
				if($querystr)
					$querystr .= ' && ';
				$querystr .= 'owner:'.$fields['owner'];
			} elseif(is_array($fields['owner'])) {
				if($querystr)
					$querystr .= ' && ';
				$querystr .= '(owner:"';
				$querystr .= implode('" || owner:"', $fields['owner']);
				$querystr .= '")';
			}
		}
		if(!empty($fields['record_type'])) {
			if($querystr)
				$querystr .= ' && ';
			$querystr .= '(record_type:';
			$querystr .= implode(' || record_type:', $fields['record_type']);
			$querystr .= ')';
		}
		if(!empty($fields['category'])) {
			if($querystr)
				$querystr .= ' && ';
			$querystr .= '(category:"';
			$querystr .= implode('" && category:"', $fields['category']);
			$querystr .= '")';
		}
		if(!empty($fields['status'])) {
			if($querystr)
				$querystr .= ' && ';
			$querystr .= '(status:"';
			$querystr .= implode('" || status:"', $fields['status']);
			$querystr .= '")';
		}
		if(!empty($fields['user'])) {
			if($querystr)
				$querystr .= ' && ';
			$querystr .= '(users:"';
			$querystr .= implode('" || users:"', $fields['user']);
			$querystr .= '")';
		}
		if(!empty($fields['rootFolder']) && $fields['rootFolder']->getFolderList()) {
			if($querystr)
				$querystr .= ' && ';
			$querystr .= '(path:"';
			$tmp[] = $fields['rootFolder']->getID();
			$querystr .= implode('" && path:"', $tmp);
			//$querystr .= $fields['rootFolder']->getFolderList().$fields['rootFolder']->getID().':';
			$querystr .= '")';
		}
		if(!empty($fields['startFolder']) && $fields['startFolder']->getFolderList()) {
			if($querystr)
				$querystr .= ' && ';
			$querystr .= '(path:"';
//			$querystr .= str_replace(':', 'x', $fields['startFolder']->getFolderList().$fields['startFolder']->getID().':');
			$tmp = array();//explode(':', substr($fields['startFolder']->getFolderList(), 1, -1));
			$tmp[] = $fields['startFolder']->getID();
			$querystr .= implode('" && path:"', $tmp);
//			$querystr .= str_replace(':', ' ', $fields['startFolder']->getFolderList().$fields['startFolder']->getID());
			$querystr .= '")';
		}
		try {
			$query = Zend_Search_Lucene_Search_QueryParser::parse($querystr);
			try {
				$hits = $this->index->find($query);
				$recs = array();
				$c = 0;
				foreach($hits as $hit) {
					if($c >= $limit['offset'] && ($c-$limit['offset'] < $limit['limit']))
						$recs[] = array('id'=>$hit->id, 'document_id'=>$hit->document_id);
					$c++;
				}
				return array('count'=>count($hits), 'hits'=>$recs, 'facets'=>array());
			} catch (Zend_Search_Lucene_Exception $e) {
				return false;
			}
		} catch (Zend_Search_Lucene_Search_QueryParserException $e) {
			return false;
		}
	} /* }}} */
}
?>
