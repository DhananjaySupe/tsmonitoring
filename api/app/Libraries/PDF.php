<?php namespace App\Libraries;
	
	class PDF
	{
		
		function __construct() {
		}
		
		public function generate($html, $params = array()){
			$filename = isset($params['name']) ? $params['name'] : '';
			$download = (isset($params['download']) && $params['download']) ? $params['download'] : false;
			$dir = (isset($params['dir']) && !empty($params['dir'])) ? $params['dir'] : realpath(FCPATH . 'tmp');
			$orientation = (isset($params['orientation']) && !empty($params['orientation'])) ? strtolower($params['orientation']) : "portrait";
			$paper = (isset($params['paper']) && !empty($params['paper'])) ? $params['paper'] : "a4";
			$watermark = isset($params['watermark']) ? $params['watermark'] : '';
			if ($orientation == 'landscape') {
				$orientation = 'L';
				} else {
				$orientation = 'P';
			}
			$config = array(
            'mode' => 'UTF-8',
            'format' => $paper,
            'default_font_size' => '13',
            'default_font' => 'Helvetica , Arial, sans-serif',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'margin_header' => 8,
            'margin_footer' => 8,
            'orientation' => $orientation,
            'tempDir' => FCPATH . '/temp'
			);
			require APPPATH.'/ThirdParty/vendor/autoload.php';
			$defaultFontConfig = (new \Mpdf\Config\FontVariables())->getDefaults();
			$fontData = $defaultFontConfig['fontdata'];
			$config['fontdata'] = $fontData + [
            'sacramento' => [
			'R' => 'Sacramento-Regular.ttf'
            ]
			];
			$mpdf = new \Mpdf\Mpdf($config);
			//$mpdf->SetProtection(array('print'));
			$mpdf->SetTitle("");
			$mpdf->SetAuthor("Phone Shild");
			if (!empty($watermark)) {
				$mpdf->SetWatermarkText($watermark);
				$mpdf->showWatermarkText = true;
				$mpdf->watermark_font = 'DejaVuSansCondensed';
				$mpdf->watermarkTextAlpha = 0.1;
			}
			$mpdf->SetDisplayMode('default');
			$mpdf->shrink_tables_to_fit = 1;
			$mpdf->keep_table_proportions = true;
			if (isset($params['styles']) && !empty($params['styles'])) {
				$mpdf->WriteHTML($params['styles'], 1);
				if (is_array($html)) {
					foreach ($html as $k => $h) {
						if ($k) { 
							$mpdf->AddPage();
						}
						$mpdf->WriteHTML($h);
					}
					} else {
					$mpdf->WriteHTML($html);
				}
				} else {
				if (is_array($html)) {
					foreach ($html as $k => $h) {
						if ($k) { 
							$mpdf->AddPage();
						}
						$mpdf->WriteHTML($h);
					}
					} else {
					$mpdf->WriteHTML($html);
				}
			}
			if (!empty($filename)) {
				$mpdf->Output($dir . '/' . $filename, ($download ? 'D' : 'F'));
				} else {
				$mpdf->Output();
			}
		}
	}
