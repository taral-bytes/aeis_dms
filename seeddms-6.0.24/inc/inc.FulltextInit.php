<?php

function getAttributesCallback($dms) {
	return function () use ($dms) {
		return $dms->getAllAttributeDefinitions();
	};
}

function reindexDocumentOrFolderCallback($fulltextservice, $object) {
	if($fulltextservice && ($index = $fulltextservice->Indexer())) {
		$lucenesearch = $fulltextservice->Search();
		if($object->isType('document'))
			$hit = $lucenesearch->getDocument($object->getId());
		elseif($object->isType('folder'))
			$hit = $lucenesearch->getFolder($object->getId());
		elseif($object->isType('documentcontent'))
			$hit = $lucenesearch->getDocument($object->getDocument()->getId());
		if($hit) {
			$index->reindexDocument($hit->id);
			$index->commit();
		}
	}
}

$fulltextservice = null;
if($settings->_enableFullSearch) {
	require_once("inc.ClassFulltextService.php");
	$fulltextservice = new SeedDMS_FulltextService();
	$fulltextservice->setLogger($logger);

	if($settings->_fullSearchEngine == 'sqlitefts') {
		$indexconf = array(
			'Indexer' => 'SeedDMS_SQLiteFTS_Indexer',
			'Search' => 'SeedDMS_SQLiteFTS_Search',
			'IndexedDocument' => 'SeedDMS_SQLiteFTS_IndexedDocument',
			'Conf' => array(
				'indexdir' => $settings->_luceneDir,
				'attrcallback' => getAttributesCallback($dms)
			)
		);
		$fulltextservice->addService('sqlitefts', $indexconf);

		require_once('vendor/seeddms/sqlitefts/SQLiteFTS.php');
	} elseif($settings->_fullSearchEngine == 'lucene') {
		$indexconf = array(
			'Indexer' => 'SeedDMS_Lucene_Indexer',
			'Search' => 'SeedDMS_Lucene_Search',
			'IndexedDocument' => 'SeedDMS_Lucene_IndexedDocument',
			'Conf' => array('indexdir' => $settings->_luceneDir)
		);
		$fulltextservice->addService('lucene', $indexconf);

		if(!empty($settings->_luceneClassDir))
			require_once($settings->_luceneClassDir.'/Lucene.php');
		else
			require_once('vendor/seeddms/lucene/Lucene.php');
	} else {
		$indexconf = null;
		if(isset($GLOBALS['SEEDDMS_HOOKS']['initFulltext'])) {
			foreach($GLOBALS['SEEDDMS_HOOKS']['initFulltext'] as $hookObj) {
				if (method_exists($hookObj, 'isFulltextService') && $hookObj->isFulltextService($settings->_fullSearchEngine)) {
					if (method_exists($hookObj, 'initFulltextService')) {
						$indexconf = $hookObj->initFulltextService(array('engine'=>$settings->_fullSearchEngine, 'dms'=>$dms, 'settings'=>$settings));
					}
				}
			}
		}
		if($indexconf) {
			$fulltextservice->addService($settings->_fullSearchEngine, $indexconf);
		}
	}
	/* setConverters() is deprecated */
	$fulltextservice->setConverters(isset($settings->_converters['fulltext']) ? $settings->_converters['fulltext'] : null);
	$fulltextservice->setConversionMgr($conversionmgr);
	$fulltextservice->setMaxSize($settings->_maxSizeForFullText);
	$fulltextservice->setCmdTimeout($settings->_cmdTimeout);
//	require_once("vendor/seeddms/preview/Preview.php");
	$txtpreviewer = new SeedDMS_Preview_TxtPreviewer($settings->_cacheDir, $settings->_cmdTimeout, $settings->_enableXsendfile);
	if($conversionmgr)
		$txtpreviewer->setConversionMgr($conversionmgr);
	$fulltextservice->setPreviewer($txtpreviewer);

	$dms->addCallback('onPostSetFolder', 'reindexDocumentOrFolderCallback', $fulltextservice);
	$dms->addCallback('onPostSetName', 'reindexDocumentOrFolderCallback', $fulltextservice);
	$dms->addCallback('onPostSetComment', 'reindexDocumentOrFolderCallback', $fulltextservice);
	$dms->addCallback('onPostSetKeywords', 'reindexDocumentOrFolderCallback', $fulltextservice);
	$dms->addCallback('onPostSetKategories', 'reindexDocumentOrFolderCallback', $fulltextservice);
	$dms->addCallback('onPostAddKategories', 'reindexDocumentOrFolderCallback', $fulltextservice);
	$dms->addCallback('onPostRemoveKategories', 'reindexDocumentOrFolderCallback', $fulltextservice);
	$dms->addCallback('onPostAddAttribute', 'reindexDocumentOrFolderCallback', $fulltextservice);
	$dms->addCallback('onPostUpdateAttribute', 'reindexDocumentOrFolderCallback', $fulltextservice);
	$dms->addCallback('onPostRemoveAttribute', 'reindexDocumentOrFolderCallback', $fulltextservice);
}

