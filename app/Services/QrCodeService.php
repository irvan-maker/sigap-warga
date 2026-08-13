<?php

namespace App\Services;

use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;

final class QrCodeService
{
    public function asDataUri(string $value): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->asSvg($value));
    }

    public function asSvg(string $value): string
    {
        $qrCode = QrCode::create($value)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(ErrorCorrectionLevel::High)
            ->setSize(420)
            ->setMargin(24)
            ->setRoundBlockSizeMode(RoundBlockSizeMode::None);

        return (new SvgWriter)->write($qrCode)->getString();
    }
}
