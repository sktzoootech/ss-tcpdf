<?php
/**
 * Action for converting a page to a PDF document
 *
 * @author Michael Parkhill <mike@silverstripe.com>
 * @license http://silverstripe.org/bsd-license/
 */
class PDFControllerExtension extends Extension {

	/**
	 * @var array
	 */
    public static $allowed_actions = array(
		'topdf',
	);

	/**
	 * Return a link to generate the current content item as a PDF
	 * 
	 * @return string
	 */
	public function PdfLink() {
		return $this->owner->Link('topdf').'/'.$this->owner->data()->URLSegment.'.pdf';
	}

	/**
	 * Generates a PDF file for the current page
	 */
	public function topdf() {
		$page = $this->owner->data();
		$outname = $page->URLSegment . '.pdf';
		$content = $page->renderWith('PdfPage');
		singleton('PdfService')->renderContent($content, 'browser', $outname);
		return;
	}
}