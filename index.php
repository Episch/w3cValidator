<?php
require_once 'libs/simple_html_dom.php';	//http://simplehtmldom.sourceforge.net/
require_once 'libs/fpdf.php'; 				//http://fpdf.de/downloads/releases/release-1.81.html

/**
 * Class HTMLValidator
 */
class HTMLValidator {

	/**
	 * @var string
	 */
	protected $validatorUrl = "https://validator.w3.org/nu/?doc=";

	/**
	 * @var string
	 */
	protected $testUrl = "";

	/**
	 * @var string
	 */
	protected $html = '';

	/**
	 * @var array
	 */
	protected $errors = array();

	/**
	 * @var string
	 */
	protected $output = '';

	/**
	 * HTMLValidator constructor.
	 *
	 * @param string $url
	 */
	public function __construct($url = ''){
		$this->setUrl($url);
	}

	/**
	 * @param string $url
	 *
	 * @return $this (changeble with validate)
	 */
	public function setUrl($url = ''){
		$this->testUrl = $url;
		return $this;
	}

	/**
	 * validate the URL by the w3c validator and prepare the error results
	 * @return $this (changeble with export)
	 */
	public function validate(){

		$this->html = file_get_contents($this->validatorUrl.$this->testUrl);

		$selector = "ol li";
		$parsing = str_get_html($this->html);

		$this->errors['testingURL'] = $this->testUrl;

		foreach($parsing->find($selector) as $element){
			$type = trim($element->find('strong', 0)->plaintext);
			$content = trim($element->find('span', 0)->plaintext);
			$this->errors[$type][] = array(
				'message' => $content,
				'type' => $type,
				'content' => $element->outertext,
			);
		}

		return $this;
	}

	/**
	 * prepare the export with the selected format
	 * @param string $format
	 */
	public function export($format = 'json'){

		switch($format){

			default:
			case 'json':
				header('Content-Type: application/json');
				$this->output = json_encode($this->errors, JSON_PRETTY_PRINT);
				break;
			case 'xml':
				header("Content-type: text/xml");
				$this->output = "<?xml version='1.0' encoding='UTF-8'?>";
				break;
			case 'html':
				header("Content-type: text/html");
				$this->output = $this->createHTML();
				break;
			case 'pdf':
				$this->output = $this->createPDF();
				break;
		}

	}

	/**
	 * create HTML output from content
	 */
	private function createHTML(){
		$content = $this->errors;
		$html = "<!DOCTYPE html>";
		$html .= "<h1>w3c Validator</h1>";
		$html .= "<h2>URL: ". $content['testingURL'] ."</h2>";
		$html .= "<h3>Warnings [" . count($content['Warning']) . "]</h3>";
		foreach($content['Warning'] as $key => $value){
			$html .= $value["content"];
		}

		$html .= "<h3>Errors [" . count($content['Error']) . "]</h3>";
		foreach($content['Error'] as $key => $value){
			$html .= $value["content"];
		}
		echo $html;
	}

	/**
	 * create PDF output from content
	 */
	private function createPDF(){

		$content = $this->errors;

		$pdf = new FPDF();
		$pdf->AddPage();

		$pdf->SetTextColor(51,51,51);

		// headline
		$pdf->SetFont('Arial','B',18);
		$headline = 'w3c Validation';
		$pdf->Cell(40,10, $headline);
		$pdf->Ln(15);

		// testing url
		$pdf->SetFont('Arial','B',16);
		$pageURL = $content['testingURL'];
		$pdf->Cell(65,10, $pageURL);
		$pdf->Ln(30);

		// warnings
		$warnings = $content['Warning'];
		$pdf->SetFont('Arial','B',16);
		$pdf->Cell(65,10, "Warnings [" . count($warnings) . "]");
		$pdf->Ln(12);
		foreach($warnings as $key => $value){
			$pdf->SetFont('Arial','B',14);
			$pdf->MultiCell(180 ,10, $value["message"]);
			$pdf->Ln(5);
		}
		$pdf->Ln(30);

		// errors
		$errors = $content['Error'];
		$pdf->SetFont('Arial','B',16);
		$pdf->Cell(65,10, "Errors [" . count($errors) . "]");
		$pdf->Ln(12);
		foreach($errors as $key => $value){
			$pdf->SetFont('Arial','B',14);
			$pdf->MultiCell(160 ,10, $value["message"]);
			$pdf->Ln(5);
		}
		$pdf->Ln(5);

		$pdf->Output();
	}

	/**
	 * return the result errors as string
	 * @return string
	 */
	public function __toString(){
		return (string)$this->output;
	}

}

$validator = new HTMLValidator();
$validator->setUrl("http://google.de")->validate()->export("json");
echo $validator;