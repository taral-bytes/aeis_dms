<?php
/**
 * Implementation of various file system operations
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2022 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class to file operation in the document management system
 * Use the methods of this class only for files below the content
 * directory but not for temporäry files, cache files or log files.
 *
 * @category   DMS
 * @package    SeedDMS_Core
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2022 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Core_File {
	/**
	 * @param $old
	 * @param $new
	 * @return bool
	 */
	static function renameFile($old, $new) { /* {{{ */
		return @rename($old, $new);
	} /* }}} */

	/**
	 * @param $file
	 * @return bool
	 */
	static function removeFile($file) { /* {{{ */
		return @unlink($file);
	} /* }}} */

	/**
	 * @param $source
	 * @param $target
	 * @return bool
	 */
	static function copyFile($source, $target) { /* {{{ */
		return @copy($source, $target);
	} /* }}} */

	/**
	 * @param $source
	 * @param $target
	 * @return bool
	 */
	static function moveFile($source, $target) { /* {{{ */
		/** @noinspection PhpUndefinedFunctionInspection */
		if (!self::copyFile($source, $target))
			return false;
		/** @noinspection PhpUndefinedFunctionInspection */
		return self::removeFile($source);
	} /* }}} */

	/**
	 * @param $file
	 * @return bool|int
	 */
	static function fileSize($file) { /* {{{ */
		if(!$a = @fopen($file, 'r'))
			return false;
		fseek($a, 0, SEEK_END);
		$filesize = ftell($a);
		fclose($a);
		return $filesize;
	} /* }}} */

	/**
	 * Return the mimetype of a given file
	 *
	 * This method uses finfo to determine the mimetype
	 * but will correct some mimetypes which are
	 * not propperly determined or could be more specific, e.g. text/plain
	 * when it is actually text/markdown. In thoses cases
	 * the file extension will be taken into account.
	 *
	 * @param string $filename name of file on disc
	 * @return string mimetype
	 */
	static function mimetype($filename) { /* {{{ */
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mimetype = finfo_file($finfo, $filename);

		switch($mimetype) {
		case 'application/octet-stream':
		case 'text/plain':
			$lastDotIndex = strrpos($filename, ".");
			if($lastDotIndex === false) $fileType = ".";
			else $fileType = substr($filename, $lastDotIndex);
			if($fileType == '.md')
				$mimetype = 'text/markdown';
			elseif($fileType == '.tex')
				$mimetype = 'text/x-tex';
			elseif($fileType == '.docx')
				$mimetype = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
			break;
		}
		return $mimetype;
	} /* }}} */

	/**
	 * @param integer $size
	 * @param array $sizes list of units for 10^0, 10^3, 10^6, ..., 10^(n*3) bytes
	 * @return string
	 */
	static function format_filesize($size, $sizes = array('Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB')) { /* {{{ */
		if ($size == 0) return('0 Bytes');
		if ($size == 1) return('1 Byte');
		/** @noinspection PhpIllegalArrayKeyTypeInspection */
		return (round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $sizes[$i]);
	} /* }}} */

	/**
	 * Parses a string like '[0-9]+ *[BKMGT]*' into an integer
	 * B,K,M,G,T stand for byte, kilo byte, mega byte, giga byte, tera byte
	 * If the last character is omitted, bytes are assumed.
	 *
	 * @param $str
	 * @return bool|int
	 */
	static function parse_filesize($str) { /* {{{ */
		if(!preg_match('/^([0-9]+) *([BKMGT]*)$/', trim($str), $matches))
			return false;
		$value = $matches[1];
		$unit = $matches[2] ? $matches[2] : 'B';
		switch($unit) {
			case 'T':
				return $value * 1024 * 1024 * 1024 *1024;
				break;
			case 'G':
				return $value * 1024 * 1024 * 1024;
				break;
			case 'M':
				return $value * 1024 * 1024;
				break;
			case 'K':
				return $value * 1024;
				break;
			default;
				return (int) $value;
				break;
		}
		/** @noinspection PhpUnreachableStatementInspection */
		return false;
	} /* }}} */

	/**
	 * @param $file
	 * @return string
	 */
	static function file_exists($file) { /* {{{ */
		return file_exists($file);
	} /* }}} */

	/**
	 * @param $file
	 * @return string
	 */
	static function checksum($file) { /* {{{ */
		return md5_file($file);
	} /* }}} */

	/**
	 * @param $string mimetype
	 * @return string file extension with the dot or an empty string
	 */
	static function fileExtension($mimetype) { /* {{{ */
		switch($mimetype) {
		case "application/pdf":
		case "image/png":
		case "image/gif":
		case "image/jpg":
			$expect = substr($mimetype, -3, 3);
			break;
		default:
			$mime_map = [
				'video/3gpp2'                                                               => '3g2',
				'video/3gp'                                                                 => '3gp',
				'video/3gpp'                                                                => '3gp',
				'application/x-compressed'                                                  => '7zip',
				'audio/x-acc'                                                               => 'aac',
				'audio/ac3'                                                                 => 'ac3',
				'application/postscript'                                                    => 'ai',
				'audio/x-aiff'                                                              => 'aif',
				'audio/aiff'                                                                => 'aif',
				'audio/x-au'                                                                => 'au',
				'video/x-msvideo'                                                           => 'avi',
				'video/msvideo'                                                             => 'avi',
				'video/avi'                                                                 => 'avi',
				'application/x-troff-msvideo'                                               => 'avi',
				'application/macbinary'                                                     => 'bin',
				'application/mac-binary'                                                    => 'bin',
				'application/x-binary'                                                      => 'bin',
				'application/x-macbinary'                                                   => 'bin',
				'image/bmp'                                                                 => 'bmp',
				'image/x-bmp'                                                               => 'bmp',
				'image/x-bitmap'                                                            => 'bmp',
				'image/x-xbitmap'                                                           => 'bmp',
				'image/x-win-bitmap'                                                        => 'bmp',
				'image/x-windows-bmp'                                                       => 'bmp',
				'image/ms-bmp'                                                              => 'bmp',
				'image/x-ms-bmp'                                                            => 'bmp',
				'application/bmp'                                                           => 'bmp',
				'application/x-bmp'                                                         => 'bmp',
				'application/x-win-bitmap'                                                  => 'bmp',
				'application/cdr'                                                           => 'cdr',
				'application/coreldraw'                                                     => 'cdr',
				'application/x-cdr'                                                         => 'cdr',
				'application/x-coreldraw'                                                   => 'cdr',
				'image/cdr'                                                                 => 'cdr',
				'image/x-cdr'                                                               => 'cdr',
				'zz-application/zz-winassoc-cdr'                                            => 'cdr',
				'application/mac-compactpro'                                                => 'cpt',
				'application/pkix-crl'                                                      => 'crl',
				'application/pkcs-crl'                                                      => 'crl',
				'application/x-x509-ca-cert'                                                => 'crt',
				'application/pkix-cert'                                                     => 'crt',
				'text/css'                                                                  => 'css',
				'text/x-comma-separated-values'                                             => 'csv',
				'text/comma-separated-values'                                               => 'csv',
				'application/vnd.msexcel'                                                   => 'csv',
				'application/x-director'                                                    => 'dcr',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
				'application/x-dvi'                                                         => 'dvi',
				'message/rfc822'                                                            => 'eml',
				'application/x-msdownload'                                                  => 'exe',
				'video/x-f4v'                                                               => 'f4v',
				'audio/x-flac'                                                              => 'flac',
				'video/x-flv'                                                               => 'flv',
				'image/gif'                                                                 => 'gif',
				'application/gpg-keys'                                                      => 'gpg',
				'application/x-gtar'                                                        => 'gtar',
				'application/x-gzip'                                                        => 'gzip',
				'application/mac-binhex40'                                                  => 'hqx',
				'application/mac-binhex'                                                    => 'hqx',
				'application/x-binhex40'                                                    => 'hqx',
				'application/x-mac-binhex40'                                                => 'hqx',
				'text/html'                                                                 => 'html',
				'image/x-icon'                                                              => 'ico',
				'image/x-ico'                                                               => 'ico',
				'image/vnd.microsoft.icon'                                                  => 'ico',
				'text/calendar'                                                             => 'ics',
				'application/java-archive'                                                  => 'jar',
				'application/x-java-application'                                            => 'jar',
				'application/x-jar'                                                         => 'jar',
				'image/jp2'                                                                 => 'jp2',
				'video/mj2'                                                                 => 'jp2',
				'image/jpx'                                                                 => 'jp2',
				'image/jpm'                                                                 => 'jp2',
				'image/jpeg'                                                                => 'jpeg',
				'image/pjpeg'                                                               => 'jpeg',
				'application/x-javascript'                                                  => 'js',
				'application/json'                                                          => 'json',
				'text/json'                                                                 => 'json',
				'application/vnd.google-earth.kml+xml'                                      => 'kml',
				'application/vnd.google-earth.kmz'                                          => 'kmz',
				'text/x-log'                                                                => 'log',
				'audio/x-m4a'                                                               => 'm4a',
				'application/vnd.mpegurl'                                                   => 'm4u',
				'text/markdown'                                                             => 'md',
				'audio/midi'                                                                => 'mid',
				'application/vnd.mif'                                                       => 'mif',
				'video/quicktime'                                                           => 'mov',
				'video/x-sgi-movie'                                                         => 'movie',
				'audio/mpeg'                                                                => 'mp3',
				'audio/mpg'                                                                 => 'mp3',
				'audio/mpeg3'                                                               => 'mp3',
				'audio/mp3'                                                                 => 'mp3',
				'video/mp4'                                                                 => 'mp4',
				'video/mpeg'                                                                => 'mpeg',
				'application/oda'                                                           => 'oda',
				'audio/ogg'                                                                 => 'ogg',
				'video/ogg'                                                                 => 'ogg',
				'application/ogg'                                                           => 'ogg',
				'application/x-pkcs10'                                                      => 'p10',
				'application/pkcs10'                                                        => 'p10',
				'application/x-pkcs12'                                                      => 'p12',
				'application/x-pkcs7-signature'                                             => 'p7a',
				'application/pkcs7-mime'                                                    => 'p7c',
				'application/x-pkcs7-mime'                                                  => 'p7c',
				'application/x-pkcs7-certreqresp'                                           => 'p7r',
				'application/pkcs7-signature'                                               => 'p7s',
				'application/pdf'                                                           => 'pdf',
				'application/octet-stream'                                                  => 'pdf',
				'application/x-x509-user-cert'                                              => 'pem',
				'application/x-pem-file'                                                    => 'pem',
				'application/pgp'                                                           => 'pgp',
				'application/x-httpd-php'                                                   => 'php',
				'application/php'                                                           => 'php',
				'application/x-php'                                                         => 'php',
				'text/php'                                                                  => 'php',
				'text/x-php'                                                                => 'php',
				'application/x-httpd-php-source'                                            => 'php',
				'image/png'                                                                 => 'png',
				'image/x-png'                                                               => 'png',
				'application/powerpoint'                                                    => 'ppt',
				'application/vnd.ms-powerpoint'                                             => 'ppt',
				'application/vnd.ms-office'                                                 => 'ppt',
				'application/msword'                                                        => 'doc',
				'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
				'application/x-photoshop'                                                   => 'psd',
				'image/vnd.adobe.photoshop'                                                 => 'psd',
				'audio/x-realaudio'                                                         => 'ra',
				'audio/x-pn-realaudio'                                                      => 'ram',
				'application/x-rar'                                                         => 'rar',
				'application/rar'                                                           => 'rar',
				'application/x-rar-compressed'                                              => 'rar',
				'audio/x-pn-realaudio-plugin'                                               => 'rpm',
				'application/x-pkcs7'                                                       => 'rsa',
				'text/rtf'                                                                  => 'rtf',
				'text/richtext'                                                             => 'rtx',
				'video/vnd.rn-realvideo'                                                    => 'rv',
				'application/x-stuffit'                                                     => 'sit',
				'application/smil'                                                          => 'smil',
				'text/srt'                                                                  => 'srt',
				'image/svg+xml'                                                             => 'svg',
				'application/x-shockwave-flash'                                             => 'swf',
				'application/x-tar'                                                         => 'tar',
				'application/x-gzip-compressed'                                             => 'tgz',
				'image/tiff'                                                                => 'tiff',
				'text/plain'                                                                => 'txt',
				'text/x-vcard'                                                              => 'vcf',
				'application/videolan'                                                      => 'vlc',
				'text/vtt'                                                                  => 'vtt',
				'audio/x-wav'                                                               => 'wav',
				'audio/wave'                                                                => 'wav',
				'audio/wav'                                                                 => 'wav',
				'application/wbxml'                                                         => 'wbxml',
				'video/webm'                                                                => 'webm',
				'audio/x-ms-wma'                                                            => 'wma',
				'application/wmlc'                                                          => 'wmlc',
				'video/x-ms-wmv'                                                            => 'wmv',
				'video/x-ms-asf'                                                            => 'wmv',
				'application/xhtml+xml'                                                     => 'xhtml',
				'application/excel'                                                         => 'xl',
				'application/msexcel'                                                       => 'xls',
				'application/x-msexcel'                                                     => 'xls',
				'application/x-ms-excel'                                                    => 'xls',
				'application/x-excel'                                                       => 'xls',
				'application/x-dos_ms_excel'                                                => 'xls',
				'application/xls'                                                           => 'xls',
				'application/x-xls'                                                         => 'xls',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
				'application/vnd.ms-excel'                                                  => 'xlsx',
				'application/xml'                                                           => 'xml',
				'text/xml'                                                                  => 'xml',
				'text/xsl'                                                                  => 'xsl',
				'application/xspf+xml'                                                      => 'xspf',
				'application/x-compress'                                                    => 'z',
				'application/x-zip'                                                         => 'zip',
				'application/zip'                                                           => 'zip',
				'application/x-zip-compressed'                                              => 'zip',
				'application/s-compressed'                                                  => 'zip',
				'multipart/x-zip'                                                           => 'zip',
				'text/x-scriptzsh'                                                          => 'zsh',
			];
			$expect = isset($mime_map[$mimetype]) === true ? $mime_map[$mimetype] : '';
		}
		return $expect;
	} /* }}} */

	/**
	 * @param $old
	 * @param $new
	 * @return bool
	 */
	static function renameDir($old, $new) { /* {{{ */
		return @rename($old, $new);
	} /* }}} */

	/**
	 * @param $path
	 * @return bool
	 */
	static function makeDir($path) { /* {{{ */
		
		if( !is_dir( $path ) ){
			$res=@mkdir( $path , 0777, true);
			if (!$res) return false;
		}

		return true;

/* some old code 
		if (strncmp($path, DIRECTORY_SEPARATOR, 1) == 0) {
			$mkfolder = DIRECTORY_SEPARATOR;
		}
		else {
			$mkfolder = "";
		}
		$path = preg_split( "/[\\\\\/]/" , $path );
		for(  $i=0 ; isset( $path[$i] ) ; $i++ )
		{
			if(!strlen(trim($path[$i])))continue;
			$mkfolder .= $path[$i];

			if( !is_dir( $mkfolder ) ){
				$res=@mkdir( "$mkfolder" ,  0777);
				if (!$res) return false;
			}
			$mkfolder .= DIRECTORY_SEPARATOR;
		}

		return true;

		// patch from alekseynfor safe_mod or open_basedir

		global $settings;
		$path = substr_replace ($path, "/", 0, strlen($settings->_contentDir));
		$mkfolder = $settings->_contentDir;

		$path = preg_split( "/[\\\\\/]/" , $path );

		for(  $i=0 ; isset( $path[$i] ) ; $i++ )
		{
			if(!strlen(trim($path[$i])))continue;
			$mkfolder .= $path[$i];

			if( !is_dir( $mkfolder ) ){
				$res= @mkdir( "$mkfolder" ,  0777);
				if (!$res) return false;
			}
			$mkfolder .= DIRECTORY_SEPARATOR;
		}

		return true;
*/
	} /* }}} */

	/**
	 * @param $path
	 * @return bool
	 */
	static function removeDir($path) { /* {{{ */
		$handle = @opendir($path);
		while ($entry = @readdir($handle) )
		{
			if ($entry == ".." || $entry == ".")
				continue;
			else if (is_dir($path . DIRECTORY_SEPARATOR . $entry))
			{
				if (!self::removeDir($path . DIRECTORY_SEPARATOR . $entry ))
					return false;
			}
			else
			{
				if (!@unlink($path . DIRECTORY_SEPARATOR . $entry))
					return false;
			}
		}
		@closedir($handle);
		return @rmdir($path);
	} /* }}} */

	/**
	 * @param $sourcePath
	 * @param $targetPath
	 * @return bool
	 */
	static function copyDir($sourcePath, $targetPath) { /* {{{ */
		if (mkdir($targetPath, 0777)) {
			$handle = @opendir($sourcePath);
			while ($entry = @readdir($handle) ) {
				if ($entry == ".." || $entry == ".")
					continue;
				else if (is_dir($sourcePath . $entry)) {
					if (!self::copyDir($sourcePath . DIRECTORY_SEPARATOR . $entry, $targetPath . DIRECTORY_SEPARATOR . $entry))
						return false;
				} else {
					if (!@copy($sourcePath . DIRECTORY_SEPARATOR . $entry, $targetPath . DIRECTORY_SEPARATOR . $entry))
						return false;
				}
			}
			@closedir($handle);
		}
		else
			return false;

		return true;
	} /* }}} */

	/**
	 * @param $sourcePath
	 * @param $targetPath
	 * @return bool
	 */
	static function moveDir($sourcePath, $targetPath) { /* {{{ */
		/** @noinspection PhpUndefinedFunctionInspection */
		if (!self::copyDir($sourcePath, $targetPath))
			return false;
		/** @noinspection PhpUndefinedFunctionInspection */
		return self::removeDir($sourcePath);
	} /* }}} */

	// code by Kioob (php.net manual)
	/**
	 * @param $source
	 * @param bool $level
	 * @return bool|string
	 */
	static function gzcompressfile($source, $level=false) { /* {{{ */
		$dest=$source.'.gz';
		$mode='wb'.$level;
		$error=false;
		if($fp_out=@gzopen($dest,$mode)) {
			if($fp_in=@fopen($source,'rb')) {
				while(!feof($fp_in))
					@gzwrite($fp_out,fread($fp_in,1024*512));
				@fclose($fp_in);
			}
			else $error=true;
			@gzclose($fp_out);
		}
		else $error=true;

		if($error) return false;
		else return $dest;
	} /* }}} */
}
