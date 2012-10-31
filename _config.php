<?php

// add the extension to pages
if (class_exists('SiteTree')) {
	Object::add_extension('Page_Controller', 'PDFControllerExtension');
}