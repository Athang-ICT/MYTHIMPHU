<?php
namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Renderer\RendererInterface;


class PrintController extends AbstractActionController
{

    /**
     * @var \TCPDF
     */
    protected $tcpdf;

    /**
     * @var RendererInterface
     */
    protected $renderer;

    public function __construct($tcpdf, $renderer)
    {
        $this->tcpdf = $tcpdf;
        $this->renderer = $renderer;
    }

    public function indexAction()
   {
		if($this->getRequest()->isPost()):
			$data = $this->getRequest()->getPost();
		endif;
		//$orientation = $this->params()->fromPost('orientation', 'L');
        //echo '<pre>'; print_r($data['orentation']); exit;
        $html= '<style>
		.widget-header{text-align:center;}
        ul.list-unstyled{list-style-type:none;}
        li.center{text-align:center;}
        table {
			width: 93%;
			word-wrap: break-word;
			border-collapse: collapse; 
			
		}
		td, th {
			border: 1px solid #dddddd; 
			text-align: left;
		}
        h5{text-align:center;}
        .table-primary{background-color:#d2e1f3;color:#1e293b;border-color:#c0cfe1};
        .table-success{background-color:#d5f0da;color:#1e293b;border-color:#c3dcca;}
        .table-info{background-color:#d9ebf9;color:#1e293b;border-color:#c6d8e6;}
        .table-warning{background-color:#fde1cd;color:#1e293b;border-color:#e7cfbe;}
        .table-danger{background-color:#f7d7d7;color:#1e293b;border-color:#e1c6c7;}
        .text-start{text-align:left!important;}
        .text-end{text-align:right!important;}
	   .text-center{text-align:center!important;}
        .fs-1{font-size:24px!important;}
        .fs-2{font-size:20px!important;}
        .fs-3{font-size:16px!important;}
        .fs-4{font-size:14px!important;}
        .fs-5{font-size:12px!important;}
        .fs-6{font-size:10px!important;}
        .fw-bold{font-weight:700;}
        .text-wrap{white-space:normal!important;}
        input{white-space:normal!important;}
		.remarks { width: 20%;font-size:9px; }
    </style>' . $data['dom'];
		 // Initialize TCPDF
		$pdf = $this->tcpdf;
		$pdf->SetTitle($data['title']);
		$pdf->SetFont('times', '', 10, '', false);
		
		$pdf->SetMargins(12, 10, 15); // Increase the values as needed
		$pdf->SetHeaderMargin(3);
		$pdf->SetFooterMargin(5);
        

		// Language settings
		$lg = [];
		$lg['a_meta_charset'] = 'UTF-8';
		$pdf->setLanguageArray($lg);

		// Add a page with the specified orientation and size
		$pdf->AddPage($data['orentation'], $data['size']);

		// Determine the absolute path to the public folder
		$publicPath = getcwd() . '/public/';

		// Set the header image path
        if($data['orentation']=="P"){$headerImagePath = $publicPath . 'images/bhutanpostheader.jpg';}
        else{$headerImagePath = $publicPath . 'images/header3.jpg';}
		
        
		// Check if the image file exists
		if (file_exists($headerImagePath)) {
            if($data['orentation']=="P"){$pdf->Image($headerImagePath, 10, 10, 190, 30, '', '', '', false, 300, '', false, false, 0, false, false, false);}
        else{$pdf->Image($headerImagePath, 10, 10, 270, 27, '', '', '', false, 300, '', false, false, 0, false, false, false);}
			// Add some space after the header image
			$pdf->Ln(20);
		} else {
			throw new Exception('Header image not found: ' . $headerImagePath);
		}

		// Write HTML content to the PDF
		$pdf->writeHTML($html, true, false, true, false, '');

		if (ob_get_length()) {
			ob_end_clean(); // clear anything in the output buffer
		}
		$pdf->Output($data['title'] . '.pdf', 'I');
		exit;
    }
}
