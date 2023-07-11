<?php
/**
 * Implementation of SQLiteFTS index
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
 * Class for managing a SQLiteFTS index.
 *
 * @category   DMS
 * @package    SeedDMS_Lucene
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_SQLiteFTS_Indexer {

	/**
	 * @var string $_ftstype
	 * @access protected
	 */
	protected $_ftstype;

	/**
	 * @var object $_conn sqlite index
	 * @access protected
	 */
	protected $_conn;

	/**
	 * @var array $_stop_words array of stop words
	 * @access protected
	 */
	protected $_stop_words;

	/**
	 * @var array $extracols list of extra columns in fts table
	 * @access protected
	 */
	protected $_extracols;

	const ftstype = 'fts5';

	/**
	 * Remove stopwords from string
	 */
  protected function strip_stopwords($str = "") { /* {{{ */
    // 1.) break string into words
    // [^-\w\'] matches characters, that are not [0-9a-zA-Z_-']
    // if input is unicode/utf-8, the u flag is needed: /pattern/u
    $words = preg_split('/[^-\w\']+/u', $str, -1, PREG_SPLIT_NO_EMPTY);

    // 2.) if we have at least 2 words, remove stopwords
    if(!empty($words)) {
      $stopwords = $this->_stop_words;
      $words = array_filter($words, function ($w) use (&$stopwords) {
        return ((mb_strlen($w, 'utf-8') > 2) && !isset($stopwords[mb_strtolower($w, "utf-8")]));
      });
    }

    // check if not too much was removed such as "the the" would return empty
    if(!empty($words))
      return implode(" ", $words);
    return $str;
  } /* }}} */

	/**
	 * Constructor
	 *
	 */
	function __construct($indexerDir) { /* {{{ */
		$this->_conn = new PDO('sqlite:'.$indexerDir.'/index.db');
		$this->_ftstype = self::ftstype;
		if($this->_ftstype == 'fts5')
			$this->_rawid = 'rowid';
		else
			$this->_rawid = 'docid';
		$this->_stop_words = [];
	} /* }}} */

	/**
	 * Open an existing index
	 *
	 * @param string $indexerDir directory on disk containing the index
	 */
	static function open($conf) { /* {{{ */
		if(file_exists($conf['indexdir'].'/index.db')) {
			$index = new SeedDMS_SQLiteFTS_Indexer($conf['indexdir']);
			$index->_extracols = [];
			if(!empty($conf['attrcallback']) && is_callable($conf['attrcallback'])) {
				$attrdefs = $conf['attrcallback']();
				foreach($attrdefs as $attrdef) {
					$fname = strtolower('attr_'.$attrdef->getId());
					/* Only document attributes are used */
					$isdoc = true; //in_array($attrdef->getObjType(), [SeedDMS_Core_AttributeDefinition::objtype_document, SeedDMS_Core_AttributeDefinition::objtype_folder, SeedDMS_Core_AttributeDefinition::objtype_all]);
					/* sqlitefts cannot handle multiple values propperly */
					$isvalueset = ($attrdef->getValueSet() && $attrdef->getMultipleValues());
					if($isdoc && !$isvalueset) {
						$index->_extracols['attr_'.$attrdef->getId()] = 'attr_'.$attrdef->getId();
					}
				}
			}
			return $index;
		} else
			return static::create($conf);
	} /* }}} */

	/**
	 * Create a new index
	 *
	 * @param array $conf $conf['indexdir'] is the directory on disk containing the index
	 */
	static function create($conf) { /* {{{ */
		if(file_exists($conf['indexdir'].'/index.db'))
			unlink($conf['indexdir'].'/index.db');
		$index = new SeedDMS_SQLiteFTS_Indexer($conf['indexdir']);
		$index->_extracols = [];
		if(!empty($conf['attrcallback']) && is_callable($conf['attrcallback'])) {
			$attrdefs = $conf['attrcallback']();
			foreach($attrdefs as $attrdef) {
				$index->_extracols[] = 'attr_'.$attrdef->getId(); //preg_replace('/[^a-z0-9_]/', '', strtolower($attrdef->getName()));
			}
		}
		/* Make sure the sequence of fields is identical to the field list
		 * in SeedDMS_SQLiteFTS_Term
		 */
		$version = SQLite3::version();
		if(self::ftstype == 'fts4') {
			if($version['versionNumber'] >= 3008000)
				$sql = 'CREATE VIRTUAL TABLE docs USING fts4(document_id, title, comment, keywords, category, record_type, mimetype, origfilename, owner, content, created, modified, indexed, user, status, path, notindexed=created, notindexed=modified, notindexed=indexed, matchinfo=fts3)';
			else
				$sql = 'CREATE VIRTUAL TABLE docs USING fts4(document_id, title, comment, keywords, category, record_type, mimetype, origfilename, owner, content, created, modified, indexed, user, status, path, matchinfo=fts3)';
			$res = $index->_conn->exec($sql);
			if($res === false) {
				return null;
			}
			$sql = 'CREATE VIRTUAL TABLE docs_terms USING fts4aux(docs);';
			$res = $index->_conn->exec($sql);
			if($res === false) {
				return null;
			}
		} elseif(self::ftstype == 'fts5') {
			$sql = 'CREATE VIRTUAL TABLE docs USING fts5(document_id, title, comment, keywords, category, record_type, mimetype, origfilename, owner, content, created unindexed, modified unindexed, indexed unindexed, user, status, path';
			if($index->_extracols)
				$sql .= ', '.implode(', ', $index->_extracols);
			$sql .= ')';
			$res = $index->_conn->exec($sql);
			if($res === false) {
				unlink($conf['indexdir'].'/index.db');
				var_dump($index->_conn->errorInfo());
				exit;
				return null;
			}
			$sql = 'CREATE VIRTUAL TABLE docs_terms USING fts5vocab(docs, \'col\');';
			$res = $index->_conn->exec($sql);
			if($res === false) {
				return null;
			}
		} else
			return null;
		return($index);
	} /* }}} */

	/**
	 * Do some initialization
	 *
	 */
	public function init($stopWordsFile='') { /* {{{ */
		if($stopWordsFile)
			$this->_stop_words = array_flip(preg_split("/[\s,]+/", file_get_contents($stopWordsFile)));
	} /* }}} */

	/**
	 * Add document to index
	 *
	 * @param object $doc indexed document of class 
	 * SeedDMS_SQLiteFTS_IndexedDocument
	 * @return boolean false in case of an error, otherwise true
	 */
	function addDocument($doc) { /* {{{ */
		if(!$this->_conn)
			return false;

		$fields = array('comment', 'keywords', 'category', 'content', 'mimetype', 'origfilename', 'status', 'created', 'modified', 'indexed');
		$fields = array_merge($fields, $this->_extracols);
		foreach($fields as $kk) {
			try {
				${$kk} = $doc->getFieldValue($kk);
			} catch (Exception $e) {
				${$kk} = '';
			}
		}
		$sql = "DELETE FROM docs WHERE document_id=".$this->_conn->quote($doc->getFieldValue('document_id'));
		$res = $this->_conn->exec($sql);
		if($res === false) {
			return false;
		}
		if($this->_stop_words)
			$content = $this->strip_stopwords($content);

		$sql = "INSERT INTO docs (document_id, record_type, title, comment, keywords, category, owner, content, mimetype, origfilename, created, modified, indexed, user, status, path";
		if($this->_extracols)
			$sql .= ', '.implode(', ', $this->_extracols);
		$sql .= ") VALUES (".$this->_conn->quote($doc->getFieldValue('document_id')).", ".$this->_conn->quote($doc->getFieldValue('record_type')).", ".$this->_conn->quote($doc->getFieldValue('title')).", ".$this->_conn->quote($comment).", ".$this->_conn->quote($keywords).", ".$this->_conn->quote($category).", ".$this->_conn->quote($doc->getFieldValue('owner')).", ".$this->_conn->quote($content).", ".$this->_conn->quote($mimetype).", ".$this->_conn->quote($origfilename).", ".(int)$created.", ".(int)$modified.", ".(int)$indexed.", ".$this->_conn->quote($doc->getFieldValue('user')).", ".$this->_conn->quote($status).", ".$this->_conn->quote($doc->getFieldValue('path'));
		if($this->_extracols) {
			foreach($this->_extracols as $extracol)
				$sql .= ', '.$this->_conn->quote(${$extracol});
		}
		$sql .= ")";
		$res = $this->_conn->exec($sql);
		if($res === false) {
			return false;
			var_dump($this->_conn->errorInfo());
		}
		return $res;
	} /* }}} */

	/**
	 * Remove document from index
	 *
	 * @param object $id internal id of document
	 * @return boolean false in case of an error, otherwise true
	 */
	public function delete($id) { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "DELETE FROM docs WHERE ".$this->_rawid."=".(int) $id;
		$res = $this->_conn->exec($sql);
		return $res;
	} /* }}} */

	/**
	 * Check if document was deleted
	 *
	 * Just for compatibility with lucene.
	 *
	 * @return boolean always false
	 */
	public function isDeleted($id) { /* {{{ */
		return false;
	} /* }}} */

	/**
	 * Find documents in index
	 *
	 * @param string $query 
	 * @param array $limit array with elements 'limit' and 'offset'
	 * @return boolean false in case of an error, otherwise array with elements
	 * 'count', 'hits', 'facets'. 'hits' is an array of SeedDMS_SQLiteFTS_QueryHit
	 */
	public function find($query, $filter='', $limit=array(), $order=array()) { /* {{{ */
		if(!$this->_conn)
			return false;

		/* First count some records for facets */
//		$facetlist = array('owner', 'mimetype', 'category', 'status');
		$facetlist = array_merge(array('created', 'modified', 'owner', 'mimetype', 'category', 'status'), $this->_extracols);
		foreach($facetlist as $facetname) {
			$sql = "SELECT `".$facetname."`, count(*) AS `c` FROM `docs`";
			if($query) {
				$sql .= " WHERE docs MATCH ".$this->_conn->quote($query);
			}
			if($filter) {
				if($query)
					$sql .= " AND ".$filter;
				else
					$sql .= " WHERE ".$filter;
			}
			$res = $this->_conn->query($sql." GROUP BY `".$facetname."`");
			if(!$res)
				throw new SeedDMS_SQLiteFTS_Exception("Counting records in facet \"$facetname\" failed.");
//				return false;
			$facets[$facetname] = array();
			foreach($res as $row) {
				if($row[$facetname] && $row['c']) {
					if($facetname == 'category') {
						$tmp = explode('#', $row[$facetname]);
						if(count($tmp) > 1) {
							foreach($tmp as $t) {
								if(!isset($facets[$facetname][$t]))
									$facets[$facetname][$t] = $row['c'];
								else
									$facets[$facetname][$t] += $row['c'];
							}
						} else {
							if(!isset($facets[$facetname][$row[$facetname]]))
								$facets[$facetname][$row[$facetname]] = $row['c'];
							else
								$facets[$facetname][$row[$facetname]] += $row['c'];
						}
					} elseif($facetname == 'status') {
						$facets[$facetname][($row[$facetname]-10).''] = $row['c'];
					} else
						$facets[$facetname][$row[$facetname]] = $row['c'];
				}
			}
			/* Do no return emtpy facets (same behavious like solr( */
			if(!$facets[$facetname])
				unset($facets[$facetname]);
		}

		$sql = "SELECT `record_type`, count(*) AS `c` FROM `docs`";
		if($query)
			$sql .= " WHERE docs MATCH ".$this->_conn->quote($query);
		if($filter) {
			if($query)
				$sql .= " AND ".$filter;
			else
				$sql .= " WHERE ".$filter;
		}
		$res = $this->_conn->query($sql." GROUP BY `record_type`");
		if(!$res)
			throw new SeedDMS_SQLiteFTS_Exception("Counting records in facet \"record_type\" failed.");
//			return false;
		$facets['record_type'] = array(); //'document'=>0, 'folder'=>0);
		$total = 0;
		foreach($res as $row) {
			$total += $row['c'];
			if($row['c'])
				$facets['record_type'][$row['record_type']] = $row['c'];
		}

		$sql = "SELECT ".$this->_rawid.", document_id, rank FROM docs";
		if($query)
			$sql .= " WHERE docs MATCH ".$this->_conn->quote($query);
		if($filter) {
			if($query)
				$sql .= " AND ".$filter;
			else
				$sql .= " WHERE ".$filter;
		}
		if($this->_ftstype == 'fts5') {
			if(empty($order['by'])) {
				$order['by'] = '';
			}
			switch($order['by']) {
			case "title":
				$sql .= " ORDER BY title";
				break;
			case "created":
				$sql .= " ORDER BY created";
				break;
			case "modified":
				$sql .= " ORDER BY modified";
				break;
			case "id":
				$sql .= " ORDER BY document_id";
				break;
			default:
				if($query)
				/* The boost factors must match with the sequence of fields
				 * when the table 'docs' is created.
				 * document_id, title, comment, keywords, category
				 */
					$sql .= " ORDER BY bm25(docs, 10.0, 10.0, 5.0, 5.0, 10.0), created";
				else
					$sql .= " ORDER BY created";
			}
			if(!empty($order['dir'])) {
				if($order['dir'] == 'desc')
					$sql .= " DESC";
				else
					$sql .= " ASC";
			} else {
				$sql .= " ASC";
			}
		}
		if(!empty($limit['limit']))
			$sql .= " LIMIT ".(int) $limit['limit'];
		if(!empty($limit['offset']))
			$sql .= " OFFSET ".(int) $limit['offset'];
		$res = $this->_conn->query($sql);
		if(!$res)
			throw new SeedDMS_SQLiteFTS_Exception("Searching for documents failed.");
		$hits = array();
		if($res) {
			foreach($res as $rec) {
				$hit = new SeedDMS_SQLiteFTS_QueryHit($this);
				$hit->id = $rec[$this->_rawid];
				$hit->documentid = $rec['document_id'];
				$hit->score = $rec['rank'];
				$hits[] = $hit;
			}
		}
		return array('count'=>$total, 'hits'=>$hits, 'facets'=>$facets);
	} /* }}} */

	/**
	 * Get a single document from index
	 *
	 * @param string $id id of document
	 * @return boolean false in case of an error, otherwise true
	 */
	public function findById($id) { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "SELECT ".$this->_rawid.", document_id FROM docs WHERE document_id=".$this->_conn->quote($id);
		$res = $this->_conn->query($sql);
		$hits = array();
		if($res) {
			while($rec = $res->fetch(PDO::FETCH_ASSOC)) {
				$hit = new SeedDMS_SQLiteFTS_QueryHit($this);
				$hit->id = $rec[$this->_rawid];
				$hit->documentid = $rec['document_id'];
				$hits[] = $hit;
			}
		}
		return $hits;
	} /* }}} */

	/**
	 * Get a single document from index
	 *
	 * @param integer $id id of index record
	 * @return boolean false in case of an error, otherwise true
	 */
	public function getDocument($id, $content=true) { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "SELECT ".$this->_rawid." as docid, * FROM docs WHERE ".$this->_rawid."='".$id."'";
		$res = $this->_conn->query($sql);
		$doc = false;
		if($res) {
			if(!($rec = $res->fetch(PDO::FETCH_ASSOC)))
				return false;
			$doc = new SeedDMS_SQLiteFTS_Document();
			foreach(array_keys($rec) as $key) {
				switch($key) {
				case 'path':
					$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('path', explode('x', substr($rec['path'], 1, -1))));
					break;
				case 'status':
					if($rec[$key] !== '') /* Folders don't have a status */
						$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword($key, $rec[$key]-10));
					break;
				default:
					if($rec[$key])
						$doc->addField(SeedDMS_SQLiteFTS_Field::Text($key, $rec[$key]));
				}
			}
			/*
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('docid', $rec['docid']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('document_id', $rec['document_id']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Text('title', $rec['title']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Text('comment', $rec['comment']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Text('keywords', $rec['keywords']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Text('category', $rec['category']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('mimetype', $rec['mimetype']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('origfilename', $rec['origfilename']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Text('owner', $rec['owner']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('created', $rec['created']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('indexed', $rec['indexed']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Text('user', $rec['user']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('status', $rec['status']));
			$doc->addField(SeedDMS_SQLiteFTS_Field::Keyword('path', explode('x', substr($rec['path'], 1, -1))));
			if($content)
				$doc->addField(SeedDMS_SQLiteFTS_Field::UnStored('content', $rec['content']));
			 */
		}
		return $doc;
	} /* }}} */

	/**
	 * Request reindexing a document
	 *
	 * This method request reindexing a document by set the field
	 * 'indexed' to null. The document will remain in the index, but
	 * the next time the indexing is run, the document will be reindexed
	 * because the field 'indexed' has a value older (null) than the creation
	 * date of the document.
	 *
	 * @param integer $id id of the document
	 * @return boolean false in case of an error, otherwise true
	 */
	public function reindexDocument($id) { /* {{{ */
		if(!$this->_conn)
			return false;

		$sql = "UPDATE docs SET indexed = NULL WHERE ".$this->_rawid."=".$this->_conn->quote($id);
		$res = $this->_conn->exec($sql);
		if($res === false) {
			return false;
		}
	} /* }}} */

	/**
	 * Return list of suggestions for a given query
	 *
	 * @return array list of SeedDMS_SQLiteFTS_Term
	 */
	public function suggestions($prefix='', $col='') { /* {{{ */
		return $this->terms($prefix);
	} /* }}} */
	/**
	 * Return list of terms in index
	 *
	 * @return array list of SeedDMS_SQLiteFTS_Term
	 */
	public function terms($prefix='', $col=null) { /* {{{ */
		if(!$this->_conn)
			return false;

		if(is_string($col) && $col)
			$cols = [$col];
		elseif(is_array($col))
			$cols = $col;
		else
			$cols = [];
		if($this->_ftstype == 'fts5') {
			$sql = "SELECT term, col, doc as occurrences FROM docs_terms";
			if($prefix || $col) {
				$sql .= " WHERE";
				if($prefix) {
					$sql .= " term like '".$prefix."%'";
					if($col)
						$sql .= " AND";
				}
				if($cols)
					$sql .= " col IN ('".implode("','", $cols)."')";
			}
			$sql .= " ORDER BY col, occurrences desc";
		} else {
			$sql = "SELECT term, col, occurrences FROM docs_terms WHERE col!='*'";
			if($prefix)
				$sql .= " AND term like '".$prefix."%'";
			if($col)
				$sql .= " col IN ('".implode("','", $cols)."')";
			$sql .=	" ORDER BY col, occurrences desc";
		}
		$res = $this->_conn->query($sql);
		$terms = array();
		if($res) {
			while($rec = $res->fetch(PDO::FETCH_ASSOC)) {
				$term = new SeedDMS_SQLiteFTS_Term($rec['term'], $rec['col'], $rec['occurrences']);
				$terms[] = $term;
			}
		}
		return $terms;
	} /* }}} */

	/**
	 * Return number of documents in index
	 *
	 * @return interger number of documents
	 */
	public function count() { /* {{{ */
		$sql = "SELECT count(*) c FROM docs";
		$res = $this->_conn->query($sql);
		if($res) {
			$rec = $res->fetch(PDO::FETCH_ASSOC);
			return $rec['c'];
		}
		return 0;
	} /* }}} */

	/**
	 * Commit changes
	 *
	 * This function does nothing!
	 */
	function commit() { /* {{{ */
	} /* }}} */

	/**
	 * Optimize index
	 *
	 * This function does nothing!
	 */
	function optimize() { /* {{{ */
	} /* }}} */
}
?>
