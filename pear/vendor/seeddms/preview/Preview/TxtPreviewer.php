<?php
/**
 * Implementation of text preview documents
 *
 * @category   DMS
 * @package    SeedDMS_Preview
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010, Uwe Steinmann
 * @version    Release: @package_version@
 */


/**
 * Class for managing creation of text preview for documents.
 *
 * @category   DMS
 * @package    SeedDMS_Preview
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Preview_TxtPreviewer extends SeedDMS_Preview_Base {

	function __construct($previewDir, $timeout=5, $xsendfile=true) { /* {{{ */
		parent::__construct($previewDir.DIRECTORY_SEPARATOR.'txt', $timeout, $xsendfile);
		$this->converters = array(
		);
	} /* }}} */

	/**
	 * Return the physical filename of the preview image on disc
	 * including the path
	 *
	 * @param object $object document content or document file
	 * @return string file name of preview image
	 */
	public function getFileName($object) { /* {{{ */
		if(!$object)
			return false;

		$document = $object->getDocument();
		$dms = $document->_dms;
		$dir = $this->previewDir.DIRECTORY_SEPARATOR.$document->getDir();
		switch(get_class($object)) {
			case $dms->getClassname('documentcontent'):
				$target = $dir.'t'.$object->getVersion();
				break;
			default:
				return false;
		}
		return $target;
	} /* }}} */

	/**
	 * Check if converter for a given mimetype is set
	 *
	 * @param string $mimetype from mimetype
	 *
	 * @return boolean true if converter exists, otherwise false
	 */
	function hasConverter($from, $to='') { /* {{{ */
		return parent::hasConverter($from, 'text/plain');
	} /* }}} */

	/**
	 * Create a text preview for a given file
	 *
	 * This method creates a preview in text format for a regular file
	 * in the file system and stores the result in the directory $dir relative
	 * to the configured preview directory. The filename of the resulting preview
	 * image is either $target.text (if set) or md5($infile).text.
	 * The $mimetype is used to select the propper conversion programm.
	 * An already existing text preview is replaced.
	 *
	 * @param string $infile name of input file including full path
	 * @param string $dir directory relative to $this->previewDir
	 * @param string $mimetype MimeType of input file
	 * @param string $target optional name of preview image (without extension)
	 * @return boolean true on success, false on failure
	 */
	public function createRawPreview($infile, $dir, $mimetype, $target='') { /* {{{ */
		if(!self::hasConverter($mimetype))
			return true;

		if(!$this->previewDir)
			return false;
		if(!is_dir($this->previewDir.DIRECTORY_SEPARATOR.$dir)) {
			if (!SeedDMS_Core_File::makeDir($this->previewDir.DIRECTORY_SEPARATOR.$dir)) {
				return false;
			}
		}
		if(!file_exists($infile))
			return false;
		if(!$target)
			$target = $this->previewDir.$dir.md5($infile);
		$this->lastpreviewfile = $target.'.txt';
		if($target != '' && (!file_exists($target.'.txt') || filectime($target.'.txt') < filectime($infile))) {
			if($this->conversionmgr) {
				if(!$this->conversionmgr->convert($infile, $mimetype, 'text/plain', $target.'.txt')) {
					$this->lastpreviewfile = '';
					return false;
				}
				$new = true;
			} else {
				$cmd = '';
				$mimeparts = explode('/', $mimetype, 2);
				if(isset($this->converters[$mimetype])) {
					$cmd = str_replace(array('%f', '%o', '%m'), array($infile, $target.'.txt', $mimetype), $this->converters[$mimetype]);
				} elseif(isset($this->converters[$mimeparts[0].'/*'])) {
					$cmd = str_replace(array('%f', '%o', '%m'), array($infile, $target.'.txt', $mimetype), $this->converters[$mimeparts[0].'/*']);
				} elseif(isset($this->converters['*'])) {
					$cmd = str_replace(array('%f', '%o', '%m'), array($infile, $target.'.txt', $mimetype), $this->converters['*']);
				}

				if($cmd) {
					try {
						self::execWithTimeout($cmd, $this->timeout);
						$new = true;
					} catch(Exception $e) {
						$this->lastpreviewfile = '';
						return false;
					}
				}
			}
			return true;
		}
		$new = false;
		return true;
			
	} /* }}} */

	/**
	 * Create preview image
	 *
	 * This function creates a preview image for the given document
	 * content or document file. It internally uses
	 * {@link SeedDMS_Preview::createRawPreview()}. The filename of the
	 * preview image is created by {@link SeedDMS_Preview_Previewer::getFileName()}
	 *
	 * @param object $object instance of SeedDMS_Core_DocumentContent
	 * or SeedDMS_Core_DocumentFile
	 * @return boolean true on success, false on failure
	 */
	public function createPreview($object) { /* {{{ */
		if(!$object)
			return false;

		$document = $object->getDocument();
		$file = $document->_dms->contentDir.$object->getPath();
		$target = $this->getFileName($object);
		return $this->createRawPreview($file, $document->getDir(), $object->getMimeType(), $target);
	} /* }}} */

	/**
	 * Check if a preview image already exists.
	 *
	 * This function is a companion to {@link SeedDMS_Preview_Previewer::createRawPreview()}.
	 *
	 * @param string $infile name of input file including full path
	 * @param string $dir directory relative to $this->previewDir
	 * @return boolean true if preview exists, otherwise false
	 */
	public function hasRawPreview($infile, $dir, $target='') { /* {{{ */
		if(!$this->previewDir)
			return false;
		if(!$target)
			$target = $this->previewDir.$dir.md5($infile);
		if($target !== false && file_exists($target.'.txt') && filectime($target.'.txt') >= filectime($infile)) {
			return true;
		}
		return false;
	} /* }}} */

	/**
	 * Check if a preview txt already exists.
	 *
	 * This function is a companion to {@link SeedDMS_Preview_Previewer::createPreview()}.
	 *
	 * @param object $object instance of SeedDMS_Core_DocumentContent
	 * or SeedDMS_Core_DocumentFile
	 * @return boolean true if preview exists, otherwise false
	 */
	public function hasPreview($object) { /* {{{ */
		if(!$object)
			return false;

		if(!$this->previewDir)
			return false;
		$target = $this->getFileName($object);
		if($target !== false && file_exists($target.'.txt') && filectime($target.'.txt') >= $object->getDate()) {
			return true;
		}
		return false;
	} /* }}} */

	/**
	 * Return a preview image.
	 *
	 * This function returns the content of a preview image if it exists..
	 *
	 * @param string $infile name of input file including full path
	 * @param string $dir directory relative to $this->previewDir
	 * @return boolean/string image content if preview exists, otherwise false
	 */
	public function getRawPreview($infile, $dir, $target='') { /* {{{ */
		if(!$this->previewDir)
			return false;

		if(!$target)
			$target = $this->previewDir.$dir.md5($infile);
		if($target && file_exists($target.'.txt')) {
			$this->sendFile($target.'.txt');
		}
	} /* }}} */

	/**
	 * Return a preview image.
	 *
	 * This function returns the content of a preview image if it exists..
	 *
	 * @param object $object instance of SeedDMS_Core_DocumentContent
	 * or SeedDMS_Core_DocumentFile
	 * @return boolean/string image content if preview exists, otherwise false
	 */
	public function getPreview($object) { /* {{{ */
		if(!$this->previewDir)
			return false;

		$target = $this->getFileName($object);
		if($target && file_exists($target.'.txt')) {
			$this->sendFile($target.'.txt');
		}
	} /* }}} */

	/**
	 * Return file size preview image.
	 *
	 * @param object $object instance of SeedDMS_Core_DocumentContent
	 * or SeedDMS_Core_DocumentFile
	 * @return boolean/integer size of preview image or false if image
	 * does not exist
	 */
	public function getFilesize($object) { /* {{{ */
		$target = $this->getFileName($object);
		if($target && file_exists($target.'.txt')) {
			return(filesize($target.'.txt'));
		} else {
			return false;
		}

	} /* }}} */

	/**
	 * Delete preview image.
	 *
	 * @param object $object instance of SeedDMS_Core_DocumentContent
	 * or SeedDMS_Core_DocumentFile
	 * @return boolean true if deletion succeded or false if file does not exist
	 */
	public function deletePreview($object) { /* {{{ */
		if(!$this->previewDir)
			return false;

		$target = $this->getFileName($object);
		if($target && file_exists($target.'.txt')) {
			return(unlink($target.'.txt'));
		} else {
			return false;
		}
	} /* }}} */

	static function recurseRmdir($dir) {
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? SeedDMS_Preview_Previewer::recurseRmdir("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	}

	/**
	 * Delete all preview text belonging to a document
	 *
	 * This function removes the preview text of all versions and
	 * files of a document including the directory. It actually just
	 * removes the directory for the document in the cache.
	 *
	 * @param object $document instance of SeedDMS_Core_Document
	 * @return boolean true if deletion succeded or false if file does not exist
	 */
	public function deleteDocumentPreviews($document) { /* {{{ */
		if(!$this->previewDir)
			return false;

		$dir = $this->previewDir.DIRECTORY_SEPARATOR.$document->getDir();
		if(file_exists($dir) && is_dir($dir)) {
			return SeedDMS_Preview_Previewer::recurseRmdir($dir);
		} else {
			return false;
		}

	} /* }}} */
}
?>
