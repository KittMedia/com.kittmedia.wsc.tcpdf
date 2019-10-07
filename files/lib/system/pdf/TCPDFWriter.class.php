<?php
namespace wcf\system\pdf;
use wcf\system\io\AtomicWriter;
use wcf\util\FileUtil;
use wcf\util\StringUtil;

define('K_PATH_IMAGES', '');
require(WCF_DIR.'lib/system/api/tcpdf/tcpdf.php');

/**
 * Creates a PDF file with TCPDF (https://tcpdf.org/).
 *
 * @author	Dennis Kraffczyk
 * @copyright	2011-2019 KittMedia
 * @license	Free <https://kittmedia.com/licenses/#licenseFree>
 * @package	com.kittmedia.wsc.tcpdf
 * @category	Suite Core
 */
class TCPDFWriter extends \TCPDF {
	/**
	 * Author of the pdf
	 * @var		string
	 */
	public $author;
	
	/**
	 * Creator of the pdf
	 * @var		string
	 */
	public $creator;
	
	/**
	 * Character encoding
	 * @var string
	 */
	public $encoding = 'UTF-8';
	
	/**
	 * Format of the pdf
	 * @var		string
	 */
	public $format = 'A4';
	
	/**
	 * Orientation of the pdf
	 * @var		string
	 */
	public $orientation = 'portrait';
	
	/**
	 * Title of the pdf
	 * @var		string
	 */
	public $title;
	
	/**
	 * User measure unit
	 * @var		string
	 */
	public $unit = 'mm';
	
	/**
	 * `1` if PDF/A-1b (ISO 19005-1:2005) should be used,
	 * `2` if PDF/A-2 should be used,
	 * `3` if PDF/A-3 shoud be used
	 * or `false` if PDF/A mode should be disabled
	 * @var		integer|boolean
	 */
	public $usePdfAMode = false;
	
	/**
	 * Indicates if the input of the pdf is in unicode
	 * @var		boolean
	 */
	public $useUnicode = true;
	
	/**
	 * Constructs a new instance of the TCPDFWriter.
	 */
	public function __construct() {
		$this->orientation = StringUtil::firstCharToUpperCase($this->orientation);
		if (!\in_array(\mb_strtolower($this->orientation), ['l', 'landscape', 'p', 'portrait'])) {
			throw new \UnexpectedValueException('Invalid value `'.$this->orientation.'` for $orientation set.');
		}
		
		$this->unit = \mb_strtolower($this->unit);
		if (!\in_array($this->unit, ['cm', 'centimeter', 'in', 'inch', 'mm', 'millimeter', 'pt', 'point'])) {
			throw new \UnexpectedValueException('Invalid value `'.$this->unit.'` for $unit set.');
		}
		
		$this->format = \mb_strtoupper($this->format);
		if (!\in_array($this->format, \array_keys(\TCPDF_STATIC::$page_formats))) {
			throw new \UnexpectedValueException('Invalid value `'.$this->format.'` for $format set.');
		}
		
		parent::__construct(
			$this->orientation,
			$this->unit,
			$this->format,
			$this->useUnicode,
			$this->encoding,
			false, // deprecated parameter
			$this->usePdfAMode
		);
		
		$this->setDocumentInformation();
	}
	
	/**
	 * Returns a correct name for downloading the pdf.
	 * If '$name' is given, the value will be checked for '.pdf'-ending.
	 * Otherwise a random name will be set used as name.
	 * @param	string		$name
	 * @return	string
	 */
	protected static function getDownloadName($name = '') {
		if (empty($name)) {
			$name = \mb_substr(StringUtil::getRandomID(), 0, 8).'.pdf';
		}
		else if (!StringUtil::endsWith($name, '.pdf')) {
			$name .= '.pdf';
		}
		
		return $name;
	}
	
	/**
	 * Saves the pdf locally at the given path.
	 * @param	string		$pathWithFileName
	 */
	public function saveOnDisk($pathWithFileName) {
		$atomicWriter = new AtomicWriter($pathWithFileName);
		$atomicWriter->write($this->getSourceCode());
		$atomicWriter->flush();
		$atomicWriter->close();
		
		FileUtil::makeWritable($pathWithFileName);
	}
	
	/**
	 * Sets the document information like author, creator and title.
	 * @param	null|string	$author
	 * @param	null|string	$creator
	 * @param	null|string	$title
	 */
	public function setDocumentInformation($author = null, $creator = null, $title = null) {
		if ($author === null) {
			if (empty($this->author)) {
				$this->author = 'WoltLab Suite Core '.WCF_VERSION;
			}
		}
		else {
			$this->author = $author;
		}
		
		if ($creator === null) {
			if (empty($this->creator)) {
				$this->creator = 'WoltLab Suite Core '.WCF_VERSION;
			}
		}
		else {
			$this->creator = $creator;
		}
		
		if ($title === null) {
			if (empty($this->title)) {
				$this->title = '';
			}
		}
		else {
			$this->title = $title;
		}
		
		$this->setAuthor($this->author);
		$this->setCreator($this->creator);
		$this->setTitle($this->title);
	}
	
	/**
	 * Shows the 'Save as'-dialog and forces the download.
	 * @param	string		$downloadFileName
	 * @see		TCPDF::Output()
	 */
	public function showDownloadDialog($downloadFileName = '') {
		$this->Output(static::getDownloadName($downloadFileName), 'D');
	}
	
	/**
	 * Shows the pdf within the browser if a pdf-plugin is available.
	 * If pdf-plugin is not available, the browser will be forced to download
	 * the pdf within the given $downloadFileName. If the pdf-plugin supports downloading the
	 * file afterwards, the $downloadFileName will be used as the file name.
	 * @param	string		$downloadFileName
	 * @see		TCPDF::Output()
	 */
	public function showInBrowser($downloadFileName = '') {
		$this->Output(static::getDownloadName($downloadFileName), 'I');
	}
	
	/**
	 * Returns the source code of the pdf document.
	 * @return	string
	 * @see		TCPDF::Output()
	 */
	public function getSourceCode() {
		return $this->Output(null, 'S');
	}
}
