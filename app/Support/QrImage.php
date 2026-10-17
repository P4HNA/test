<?php

namespace App\Support;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Throwable;

/**
 * Draws a KHQR payload as a plain square QR code.
 *
 * PayWay also returns a rendered QR (qrImage / qr_image_template), but that is a
 * whole pre-branded KHQR card -- a tall image with its own header, logo and
 * amount -- which does not fit the payment page's design. This renders just the
 * code itself, so it sits in the paper frame like the rest of the site.
 *
 * Server-side and inline, so the payment page loads no third-party script. The
 * previous version pulled a QR library off a public CDN onto the one page where
 * someone is about to pay, which needed an SRI pin to be safe at all.
 */
class QrImage
{
    /**
     * An <svg> element for this payload, ready to echo into a page.
     *
     * SVG rather than a PNG data URI: it stays sharp at any size, and it is a
     * fraction of the bytes.
     *
     * @return string|null null when the payload cannot be encoded, so the caller
     *                     can fall back rather than break the page
     */
    public static function svg(string $payload, int $size = 260): ?string
    {
        $payload = trim($payload);

        if ($payload === '') {
            return null;
        }

        try {
            $result = (new Builder(
                writer: new SvgWriter,
                // No XML prolog: this is inlined into an HTML document, not
                // served as a standalone file.
                writerOptions: [SvgWriter::WRITER_OPTION_EXCLUDE_XML_DECLARATION => true],
                data: $payload,
                // Medium matches what the page used before. A KHQR payload is
                // long, and higher correction would push the modules smaller
                // (harder for a phone camera) for redundancy nobody needs on a
                // screen.
                errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                size: $size,
                // The frame around it already provides the quiet zone.
                margin: 0,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
            ))->build();

            return $result->getString();
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }
}
