<?php
/**
 * Implementation of preview documents
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
 * Class for managing creation of preview images for documents.
 *
 * @category   DMS
 * @package    SeedDMS_Preview
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2011, Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Preview_Previewer extends SeedDMS_Preview_Base {
	/**
	 * @var integer $width maximum width/height of resized image
	 * @access protected
	 */
	protected $width;

	/**
	 * Create instance of image previewer
	 *
	 * @param string $previewDir path of base directory where all images are
	 * stored. This directory will have a subdirectory derived from the object id.
	 * @param integer $width default width of an image
	 * @param integer $timeout timeout for shell commands to create a preview image
	 * @param boolean $xsendfile if set to true the apache module xsendfile will
	 * be used.
	 */
	function __construct($previewDir, $width=40, $timeout=5, $xsendfile=true) { /* {{{ */
		parent::__construct($previewDir.DIRECTORY_SEPARATOR.'png', $timeout, $xsendfile);
		$this->converters = array(
		);
		$this->width = intval($width);
	} /* }}} */

	/**
	 * Return the physical filename of the preview image on disc
	 * including the path
	 *
	 * @param object $object document content or document file
	 * @param integer $width width of preview image
	 * @return string file name of preview image
	 */
	public function getFileName($object, $width=0) { /* {{{ */
		if(!$object)
			return false;

		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);

		$document = $object->getDocument();
		$dms = $document->_dms;
		$dir = $this->previewDir.DIRECTORY_SEPARATOR.$document->getDir();
		switch(get_class($object)) {
			case $dms->getClassname('documentcontent'):
				$target = $dir.'p'.$object->getVersion().'-'.$width;
				break;
			case $dms->getClassname('documentfile'):
				$target = $dir.'f'.$object->getID().'-'.$width;
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
		return parent::hasConverter($from, 'image/png');
	} /* }}} */

	/**
	 * Create a preview image for a given file
	 *
	 * This method creates a preview image in png format for a regular file
	 * in the file system and stores the result in the directory $dir relative
	 * to the configured preview directory. The filename of the resulting preview
	 * image is either $target.png (if set) or md5($infile)-$width.png.
	 * The $mimetype is used to select the propper conversion programm.
	 * An already existing preview image is replaced.
	 *
	 * @param string $infile name of input file including full path
	 * @param string $dir directory relative to $this->previewDir
	 * @param string $mimetype MimeType of input file
	 * @param integer $width width of generated preview image
	 * @param string $target optional name of preview image (without extension)
	 * @param boolean $new will be set to true if the preview images was created
	 * @return boolean true on success, false on failure
	 */
	public function createRawPreview($infile, $dir, $mimetype, $width=0, $target='', &$new=false) { /* {{{ */
		if(!self::hasConverter($mimetype))
			return false;

		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
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
			$target = $this->previewDir.DIRECTORY_SEPARATOR.$dir.md5($infile).'-'.$width;
		$this->lastpreviewfile = $target.'.png';
		if($target != '' && (!file_exists($target.'.png') || filectime($target.'.png') < filectime($infile))) {
			if($this->conversionmgr) {
				if(!$this->conversionmgr->convert($infile, $mimetype, 'image/png', $target.'.png', array('width'=>$width))) {
					$this->lastpreviewfile = '';
					return false;
				}
				$new = true;
			} else {
				$cmd = '';
				$mimeparts = explode('/', $mimetype, 2);
				if(isset($this->converters[$mimetype])) {
					$cmd = str_replace(array('%w', '%f', '%o', '%m'), array($width, $infile, $target.'.png', $mimetype), $this->converters[$mimetype]);
				} elseif(isset($this->converters[$mimeparts[0].'/*'])) {
					$cmd = str_replace(array('%w', '%f', '%o', '%m'), array($width, $infile, $target.'.png', $mimetype), $this->converters[$mimeparts[0].'/*']);
				} elseif(isset($this->converters['*'])) {
					$cmd = str_replace(array('%w', '%f', '%o', '%m'), array($width, $infile, $target.'.png', $mimetype), $this->converters['*']);
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
	 * @param integer $width desired width of preview image
	 * @param boolean $new will be set to true if the preview images was created
	 * @return boolean true on success, false on failure
	 */
	public function createPreview($object, $width=0, &$new=false) { /* {{{ */
		if(!$object)
			return false;

		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		$document = $object->getDocument();
		$file = $document->_dms->contentDir.$object->getPath();
		$target = $this->getFileName($object, $width);
		return $this->createRawPreview($file, $document->getDir(), $object->getMimeType(), $width, $target, $new);
	} /* }}} */

	/**
	 * Check if a preview image already exists.
	 *
	 * This function is a companion to {@link SeedDMS_Preview_Previewer::createRawPreview()}.
	 *
	 * @param string $infile name of input file including full path
	 * @param string $dir directory relative to $this->previewDir
	 * @param integer $width desired width of preview image
	 * @return boolean true if preview exists, otherwise false
	 */
	public function hasRawPreview($infile, $dir, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;
		$target = $this->previewDir.DIRECTORY_SEPARATOR.$dir.md5($infile).'-'.$width;
		if($target !== false && file_exists($target.'.png') && filectime($target.'.png') >= filectime($infile)) {
			return true;
		}
		return false;
	} /* }}} */

	/**
	 * Check if a preview image already exists.
	 *
	 * This function is a companion to {@link SeedDMS_Preview_Previewer::createPreview()}.
	 *
	 * @param object $object instance of SeedDMS_Core_DocumentContent
	 * or SeedDMS_Core_DocumentFile
	 * @param integer $width desired width of preview image
	 * @return boolean true if preview exists, otherwise false
	 */
	public function hasPreview($object, $width=0) { /* {{{ */
		if(!$object)
			return false;

		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;
		$target = $this->getFileName($object, $width);
		if($target !== false && file_exists($target.'.png') && filectime($target.'.png') >= $object->getDate()) {
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
	 * @param integer $width desired width of preview image
	 * @return boolean/string image content if preview exists, otherwise false
	 */
	public function getRawPreview($infile, $dir, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;

		$target = $this->previewDir.DIRECTORY_SEPARATOR.$dir.md5($infile).'-'.$width;
		if($target && file_exists($target.'.png')) {
			$this->sendFile($target.'.png');
		}
	} /* }}} */

	/**
	 * Return a preview image.
	 *
	 * This function returns the content of a preview image if it exists..
	 *
	 * @param object $object instance of SeedDMS_Core_DocumentContent
	 * or SeedDMS_Core_DocumentFile
	 * @param integer $width desired width of preview image
	 * @return boolean/string image content if preview exists, otherwise false
	 */
	public function getPreview($object, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;

		$target = $this->getFileName($object, $width);
		if($target && file_exists($target.'.png')) {
			$this->sendFile($target.'.png');
		}
	} /* }}} */

	/**
	 * Return file size preview image.
	 *
	 * @param object $object instance of SeedDMS_Core_DocumentContent
	 * or SeedDMS_Core_DocumentFile
	 * @param integer $width desired width of preview image
	 * @return boolean/integer size of preview image or false if image
	 * does not exist
	 */
	public function getFilesize($object, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		$target = $this->getFileName($object, $width);
		if($target && file_exists($target.'.png')) {
			return(filesize($target.'.png'));
		} else {
			return false;
		}

	} /* }}} */

	/**
	 * Delete preview image.
	 *
	 * @param object $object instance of SeedDMS_Core_DocumentContent
	 * or SeedDMS_Core_DocumentFile
	 * @param integer $width desired width of preview image
	 * @return boolean true if deletion succeded or false if file does not exist
	 */
	public function deletePreview($object, $width=0) { /* {{{ */
		if($width == 0)
			$width = $this->width;
		else
			$width = intval($width);
		if(!$this->previewDir)
			return false;

		$target = $this->getFileName($object, $width);
		if($target && file_exists($target.'.png')) {
			return(unlink($target.'.png'));
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
	 * Delete all preview images belonging to a document
	 *
	 * This function removes the preview images of all versions and
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
