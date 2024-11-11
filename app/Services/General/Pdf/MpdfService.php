<?php

namespace App\Services\General\Pdf;

use Illuminate\View\View;

class MpdfService
{
    public $pdf;
    public function __construct()
    {
        $this->pdf = new \Mpdf\Mpdf([
            'mode' => '',
            'format' => 'A4',
            'default_font_size' => 0,
            'default_font' => '',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 6,
            'margin_bottom' => 9,
            'margin_header' => 9,
            'margin_footer' => 9,
            'orientation' => 'P',
            'tempDir'=> storage_path('tmp')
        ]);
    }

    public function generate(View $view)
    {
        // dd($view->render());
    //     $bg_img =
    // "https://d1csarkz8obe9u.cloudfront.net/posterpreviews/school-logo-design-template-b3bfdceb55d1cbc48f1ce50fd4e1ef24_screen.jpg?ts=1629941736";

    //     $this->pdf->SetWatermarkImage($bg_img);
        // $this->pdf->showWatermarkImage = false;
        $this->pdf->WriteHTML($view->render());
        return $this;
    }

    public function output()
    {
        return $this->pdf->Output();
        // $this->pdf->Output("Test.pdf", storage_path("pdf/test.pdf"));
        // return $this;
    }

    public function save(string $file_path)
    {
        $this->pdf->Output($file_path , \Mpdf\Output\Destination::FILE);
        return $this;
    }
}
