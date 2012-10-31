<?php
/**
 * Action for converting a page to a PDF document
 *
 * @author Michael Parkhill <mike@silverstripe.com>
 * @license http://silverstripe.org/bsd-license/
 */
class PDFService {

	/**
	 * @var string
	 */
	protected static $temp_folder_name = 'tcpdf';

	/** 
	 * Render html string as a pdf
	 *
	 * @param string $content the content to render
	 * @return string|bool 
	 */ 
	public function renderContent($content, $outputTo = 'browser', $outname = 'pdf-file') {
		return $this->render($content, $outputTo, $outname);
	}

	/**
	 * Render a page as a PDF
	 *
	 * @param SiteTree $page The page that should be rendered
	 * @param String $action An action for the page to render
	 * @param String $outputTo 'file' or 'browser'
	 * @return String The filename of the output file
	 */
	public function renderPage($page, $action='', $outputTo = null, $outname='') {
		$link = Director::makeRelative($page->Link($action));
		return $this->renderUrl($link, $outputTo, $outname);
	}

	/**
	 * Renders the contents of a silverstripe URL into a PDF
	 *
	 * @param String $url A relative URL that silverstripe can execute
	 * @param String $outputTo
	 */
	public function renderUrl($url, $outputTo = null, $outname='') {
		if (strpos($url, '/') === 0) {
			// fix it
			$url = Director::makeRelative($url);
		}
		// convert the URL to content and 'test' the request, ensure the current session remains active
		$response = Director::test($url, null, new Session($_SESSION));
		if ($response->getStatusCode() == 200) {
			$content = $response->getBody();
			return $this->render($content, $outputTo, $outname);
		} else {
			throw new Exception("Failed rendering URL $url: " . $response->getStatusCode() . " - " . $response->getStatusDescription());
		}
	}

	/**
	 * Renders passed in content to a PDF.
	 *
	 * If $outputTo == '', then the temporary filename is returned, with the expectation
	 * that the caller will correctly handle the streaming of the content.
	 *
	 * @param String $content
	 * 			Raw content to render into a pdf
	 * @param String $outputTo
	 * 				'file' or 'browser'
	 * @param String $outname
	 * 				A filename if the pdf is sent direct to the browser
	 * @return String
	 * 				The filename of the output file
	 */
	protected function render($content, $outputTo = null, $outname='') {
		// Setup a temp folder for the pdfs
		$tempFolder = getTempFolder();
		if (!is_dir($tempFolder)) {
			throw new Exception("Temp directory could not be found " . var_export(getTempFolder(), true));
		}
		$pdfFolder = $tempFolder . '/' . self::$temp_folder_name;
		if (!file_exists($pdfFolder)) {
			@mkdir($pdfFolder, 0750);
		}
		if (!is_dir($pdfFolder)) {
			throw new Exception("PDF temp directory could not be found " . $pdfFolder);
		}

		// Change all the links and urls in the content to use absolute paths
		$content = $this->fixLinks($content);

		// Translate all the breaking spaces that tinymce litters everywhere
		$content = $this->fixEntities($content); 

		// Create a temp pdf file ready for the pdf convertor output 
		$pdfFile = tempnam($pdfFolder, "pdf_");

		// Start up the pdf generator and tell it where to write its output
		$pdfGenerator = new PDFGenerator($pdfFile);

		// Convert the content into pdf
		if(!$pdfGenerator->generatePDF($content)) {
			throw new Exception("PDF could not be created");
		}

		if (!file_exists($pdfFile)) {
			throw new Exception("Could not generate pdf " . $pdfFile);
		}

		// Return the pdf contents if the output type is anything but 'browser'
		if ($outputTo != 'browser') {
			return $pdfFile;
		}

		// Return the pdf to the browser as a file download
		if (file_exists($pdfFile)) {
			$filedata = array(
				'path' => $pdfFile,
				'name' => urlencode(htmlentities($outname)),
				'mime' => "application/pdf"
			);

			// TODO: use this instead
			//$this->send_file($filedata);

			$size = filesize($pdfFile);
			$type = "application/pdf";
			$name = urlencode(htmlentities($outname));
			if (!headers_sent()) {
				// set cache-control headers explicitly for https traffic, otherwise no-cache will be used,
				// which will break file attachments in IE
				// Thanks to Niklas Forsdahl <niklas@creamarketing.com>
				if (isset($_SERVER['HTTPS'])) {
					header('Cache-Control: private');
					header('Pragma: ');
				}
				header('Content-disposition: attachment; filename=' . $name);
				header('Content-type: application/pdf'); //octet-stream');
				header('Content-Length: ' . $size);
				readfile($pdfFile);
			} else {
				echo "Invalid file";
			}

			// Delete the pdf temp file
			unlink($pdfFile);
		}

		return true;
	}

	/**
	 * Converts relative URLs into absolute URLs
	 *
	 * @param String $content The name of the file to fix links within
	 */
	protected function fixLinks($content) {
		$value = new SS_HTMLValue($content);
		$base = $value->getElementsByTagName('base');
		if ($base && $base->item(0)) {
			$base = $base->item(0)->getAttribute('href');
			$check = array('a' => 'href', 'link' => 'href', 'img' => 'src');
			foreach ($check as $tag => $attr) {
				if ($items = $value->getElementsByTagName($tag)) {
					foreach ($items as $item) {
						$href = $item->getAttribute($attr);
						if ($href && $href{0} != '/' && strpos($href, 'mailto:') === false && strpos($href, '://') === false) {
							$item->setAttribute($attr, $base . $href);
						}
					}
				}
			}
		}
		return $value->getContent();
	}

	/**
	 * Replace html entities
	 */
	protected function fixEntities($content) {
		$search = array('&nbsp;', 'Â');
		$replace = array(' ');
		return str_replace($search, $replace, $content);
	}

	/*
	 * Override SS's HTTPRequest cos it don't be workin' for IE 7 and 8
	 * 
	 * @param array $filedata
	 * @see http://www.htmlforums.com/php-programming/t-force-download-in-ie8-is-it-impossible-117254.html
	 * 
	 * Works for: 
	 * OSX: FF13,Safari 5.1.7,Chrome 20
	 * WinXP: IE7
	 * WinVista: IE7
	 * Win7: IE8,IE9
	 */
	public function send_file($filedata) {
		$filebody = file_get_contents($filedata['path']);
		$response = new SS_HTTPResponse($filebody);
		if(preg_match("#MSIE\s(6|7|8)?\.0#",$_SERVER['HTTP_USER_AGENT'])) {
			// IE headers
			$response->addHeader("Cache-Control","public");
			$response->addHeader("Content-Disposition","attachment; filename=\"".basename($filedata['name'])."\"");
			$response->addHeader("Content-Type","application/force-download");
			$response->addHeader("Content-Type","application/octet-stream");
			$response->addHeader("Content-Type","application/download");
			$response->addHeader("Content-Type",$filedata['mime']);
			$response->addHeader("Content-Description","File Transfer");
			$response->addHeader("Content-Length",filesize($filedata['path']));	
		}
		else {
			// Everyone else
			$response->addHeader("Content-Type", $filedata['mime']."; name=\"".addslashes($filedata['name'])."\"");
			$response->addHeader("Content-disposition", "attachment; filename=".addslashes($filedata['name']));
			$response->addHeader("Content-Length",strlen($filebody));
		}
		return $response;
	}

}