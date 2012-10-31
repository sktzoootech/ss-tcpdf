# TCPDF

## Overview

This module extends Page_Controller (see: _config.php) with an action 
that converts a page into a PDF document and downloads it.

The PDFControllerExtension provides the function PDFLink() that generates 
a link formatted with the 'convert to pdf' action name and the filename 
of the generated PDF document based on the page's title.

## Usage

1. Place a link in your templates to call the action:

    <a href="$PDFLink" title="Download this page as a PDF document">Save as PDF</a>

2. Create a layout template called PdfPage.ss in your theme:
    
    /themes/--your-theme-name--/templates/Layout/PdfPage.ss 