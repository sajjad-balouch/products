<?php

namespace App\Support;

use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class Qr
{
    /** Inline SVG for embedding in a page. Only dark modules are drawn, on the page's white background. */
    public static function svg(string $data): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'svgAddXmlHeader' => false,
            'drawLightModules' => false,
        ]);

        return (new QRCode($options))->render($data);
    }
}
