<?php
/**
 * Basic implementation of a PDF generator, extends TCPDF
 * 
 * @see http://www.tcpdf.org/
 * @author Russell Michell <russell@silverstripe.com>
 */

/**
 * Its required to include this library manually as it has been 
 * excluded from the SS manifest to improve flush load times 
 */
require BASE_PATH.'/tcpdf/thirdparty/tcpdf/tcpdf.php';

class PDFGenerator extends TCPDF {
	
	public $pdf_generator_class = 'TCPDF';
	public $pdf_defaults = array(
		'document' => array(
			'logo'			=> 'logo.gif', // TCPDF ref's its images relative to its own 'images' directory
			'logo_width'	=> 36,
			'author'		=> 'SilverStripe CMS'
		),
		'generator' => array(
			'orientation'	=> 'P',
			'units'			=> 'px',
			'page_size'		=> 'A4',
			'output'		=> 'F',
			'header_block'	=> false
		)
	);
	public $pdf_generator_object;
	public static $pdf_filename = '';
	
	/*
	 * The constructor. Sets up a basic set of defaults used for generating a PDF
	 */
	public function __construct($filename, $output='F') {
		parent::__construct(
			$this->pdf_defaults['generator']['orientation'],
			$this->pdf_defaults['generator']['units'], 
			$this->pdf_defaults['generator']['page_size'], true, 'UTF-8', false, true
		);
		$this->setPDFDefaults($output,$filename);
	}
	
	/**
	 * Generate a PDF file according to some defaults and save it to the filesystem
	 * 
	 * @param string $file The absolute path to where the PDF file will be generated
	 * @param string $content the parsed template markup or plain-text string content to pass to TCPDF
	 * @return boolean false if the file was not generated and outputted to the filesystem for some reason, true otherwise
	 */
	public function generatePDF($content) {
		if(!$content || ($content && !strlen($content) > 0)) {
			return false;
		}		
		
		// There shouldn't be a file here becuase we clean up after ourselves, but just in case, we'll do it again:
		if(is_file(self::$pdf_filename)) {
			$this->unlinkPDF();
		}

		// Create new PDF document
		$this->AddPage();
		$this->writeHTML($content);

		// Output PDF document to the filesystem
		$this->Output(self::$pdf_filename,$this->pdf_defaults['generator']['output']);

		if(!is_file(self::$pdf_filename)) {
			return false;
		}
		return true;
	}
	
	/**
	 * Simple removes a secified file from the filesystem
	 * 
	 * @param string $pdf_fileName Absolute path to the file that needs to be removed 
	 * @return boolean false if there was a problem unlinking the file, true otherwise
	 */
	public function unlinkPDF() {
		if(file_exists(self::$pdf_filename)) {
			if(!unlink(self::$pdf_filename)) {
				user_error("PDF was unable to be removed from the filesystem.");
				return false;
			}
			return true;
		}
	}	
	
	/*
	 * Set some PDF defaults so we don't clutter the main method calls
	 */
	public function setPDFDefaults($output, $filename) {
		
		$this->pdf_defaults['generator']['output'] = $output;
		self::$pdf_filename = $filename;
		
		$this->SetFont('Helvetica', '', 11, '', true);
		$this->setFontSubsetting(true);
		$this->SetCreator($this->pdf_defaults['document']['author']);
		//$this->SetHeaderData($this->pdf_defaults['document']['logo'], $this->pdf_defaults['document']['logo_width'], '', '');
		$this->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		$this->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$this->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		$this->SetMargins(55, 35, 55, true);
		$this->SetFooterMargin(50);
		$this->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		$this->setImageScale(PDF_IMAGE_SCALE_RATIO);
		$this->setJPEGQuality(100);
	}	
	
	/*
	 * Override all header defaults
	 */
	public function Header() {
		if($this->pdf_defaults['generator']['header_block']) {
			parent::Header();
		}
	}
	
}