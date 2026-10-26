<?php
require_once 'config/config.php';

use Endroid\QrCode\QrCode as EndroidQrCode;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\WebPWriter;

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once __DIR__ . '/../../vendor/autoload.php';
} elseif (defined('BASE_PATH') && file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
} elseif (defined('BASE_PATH') && file_exists(dirname(BASE_PATH) . '/vendor/autoload.php')) {
    require_once dirname(BASE_PATH) . '/vendor/autoload.php';
}

class Qrcode {
    private string $table;
    private string $redirect_url;
    private ?string $uploadedLogoPath = null;

    /**
     * Initializes the QR code generator with the target mode ('static' or 'dynamic').
     *
     * @param string $type The mode of QR code generation ('static' or 'dynamic').
     */
    public function __construct(string $type) {
        if ($type === "static") {
            $this->table = "static_qrcodes";
            $this->redirect_url = "static_qrcodes.php";
        } elseif ($type === "dynamic") {
            $this->table = "dynamic_qrcodes";
            $this->redirect_url = "dynamic_qrcodes.php";
        } else {
            $this->redirect_url = "index.php";
            $this->failure("Type not allowed");
        }
    }

    /**
     * Destructor to clean up any temporary uploaded logo files left on disk.
     */
    public function __destruct() {
        $this->cleanupUploadedLogo();
    }

    /**
     * Sanitizes a user-supplied filename, stripping dangerous characters and path traversal components.
     *
     * @param string $filename The raw input filename.
     * @return string The sanitized filename.
     */
    public function sanitizeFilename(string $filename): string {
        $clean = basename(trim($filename));
        $clean = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return trim($clean) !== '' ? trim($clean) : 'qrcode_' . time();
    }

    /**
     * Constructs a safe, normalized filesystem path for a QR code image file.
     *
     * @param string $filename The base filename.
     * @param string $ext The file extension (e.g. 'png', 'svg', 'webp', 'jpg').
     * @return string The full destination file path.
     */
    public function getSafeFilePath(string $filename, string $ext): string {
        $safeName = $this->sanitizeFilename($filename);
        $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($ext));
        return SAVED_QRCODE_DIRECTORY . $safeName . '.' . $safeExt;
    }

    /**
     * Fetches a single QR code record by its ID from the configured database table.
     *
     * @param int|string $id The QR code database identifier.
     * @return array The associative array containing QR code record data.
     */
    public function getQrcode($id) {
        $db = getDbInstance();
        $db->where('id', (int)$id);
        $result = $db->getOne($this->table);

        if ($result !== null) {
            return $result;
        }

        $this->failure("Qrcode not found");
    }

    /**
     * Converts a hexadecimal color string (#RRGGBB or RRGGBB) to an RGB associative array.
     *
     * @param string $hex The hex color string.
     * @param array $default Default RGB array to return if parsing fails.
     * @return array Associative array with keys 'r', 'g', and 'b'.
     */
    private function hexToRgb(string $hex, array $default = ['r' => 0, 'g' => 0, 'b' => 0]): array {
        $hex = ltrim(trim($hex), '#');
        if (preg_match('/^[a-f\d]{6}$/i', $hex)) {
            return [
                'r' => hexdec(substr($hex, 0, 2)),
                'g' => hexdec(substr($hex, 2, 2)),
                'b' => hexdec(substr($hex, 4, 2))
            ];
        }
        return $default;
    }

    /**
     * Normalizes and validates QR code generation options from raw input data.
     *
     * @param array $input_data Array of raw input parameters from form submission.
     * @return array Associative array of validated rendering and styling options.
     */
    private function setOptions(array $input_data): array {
        $errorCorrectionLevel = 'M';
        if (isset($input_data['level']) && in_array(strtoupper($input_data['level']), ['L', 'M', 'Q', 'H'], true)) {
            $errorCorrectionLevel = strtoupper($input_data['level']);
        }

        $size = 300;
        if (isset($input_data['size'])) {
            $size = min(max((int)$input_data['size'], 100), 2000);
        }

        /**
         * Normalizes a hex color string to 6 lowercase hex characters without leading '#'.
         *
         * @param mixed $color The raw color input.
         * @param string $default Fallback hex code.
         * @return string Normalized hex code.
         */
        $normalizeColor = function($color, $default) {
            if (!is_string($color)) {
                return $default;
            }
            $color = ltrim(trim($color), '#');
            if (preg_match('/^[a-f\d]{6}$/i', $color)) {
                return strtolower($color);
            }
            return $default;
        };

        $foreground = $normalizeColor($input_data['foreground'] ?? null, '000000');
        $background = $normalizeColor($input_data['background'] ?? null, 'ffffff');

        $roundBlock = 'margin';
        if (isset($input_data['round_block']) && in_array($input_data['round_block'], ['none', 'margin', 'enlarge', 'shrink', 'dots', 'rounded'], true)) {
            $roundBlock = $input_data['round_block'];
        }

        $logoScale = 0.20;
        if (isset($input_data['logo_scale'])) {
            $scaleVal = (float)$input_data['logo_scale'];
            if ($scaleVal > 1) {
                $scaleVal = $scaleVal / 100;
            }
            $logoScale = min(max($scaleVal, 0.10), 0.35);
        }

        $logoShape = 'circle';
        if (isset($input_data['logo_shape']) && in_array($input_data['logo_shape'], ['circle', 'rounded', 'square', 'none'], true)) {
            $logoShape = $input_data['logo_shape'];
        }

        $gradientType = 'none';
        if (isset($input_data['gradient_type']) && in_array($input_data['gradient_type'], ['linear', 'radial'], true)) {
            $gradientType = $input_data['gradient_type'];
        }

        $gradientColor = $normalizeColor($input_data['gradient_color'] ?? null, '007bff');
        $gradientAngle = isset($input_data['gradient_angle']) ? min(max((int)$input_data['gradient_angle'], 0), 360) : 45;
        $gradientBalance = isset($input_data['gradient_balance']) ? min(max((int)$input_data['gradient_balance'], 10), 90) : 50;
        $gradientDirection = 'vertical';
        if (isset($input_data['gradient_direction']) && in_array($input_data['gradient_direction'], ['vertical', 'horizontal', 'diagonal'], true)) {
            $gradientDirection = $input_data['gradient_direction'];
        }

        $customEyes = !empty($input_data['custom_eyes_enabled']);
        $eyeOuter = ($customEyes && !empty($input_data['eye_outer'])) ? $normalizeColor($input_data['eye_outer'], '') : '';
        $eyeInner = ($customEyes && !empty($input_data['eye_inner'])) ? $normalizeColor($input_data['eye_inner'], '') : '';

        return [
            'errorCorrectionLevel' => $errorCorrectionLevel,
            'size'                 => $size,
            'foreground'           => $foreground,
            'background'           => $background,
            'roundBlock'           => $roundBlock,
            'logoScale'            => $logoScale,
            'logoShape'            => $logoShape,
            'gradientType'         => $gradientType,
            'gradientColor'        => $gradientColor,
            'gradientAngle'        => $gradientAngle,
            'gradientBalance'      => $gradientBalance,
            'gradientDirection'    => $gradientDirection,
            'eyeOuter'             => $eyeOuter,
            'eyeInner'             => $eyeInner
        ];
    }

    /**
     * Resolves logo file path from uploaded file or preset name.
     */
    private function resolveLogoPath(): ?string {
        // 1. Check custom uploaded file
        if (isset($_FILES['logo_file']) && is_uploaded_file($_FILES['logo_file']['tmp_name'])) {
            $file = $_FILES['logo_file'];
            if ($file['error'] === UPLOAD_ERR_OK && $file['size'] > 0 && $file['size'] <= 5 * 1024 * 1024) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                $allowed = ['image/png', 'image/jpeg', 'image/webp'];
                if (in_array($mime, $allowed, true)) {
                    $logosDir = SAVED_QRCODE_DIRECTORY . 'logos/';
                    if (!is_dir($logosDir)) {
                        @mkdir($logosDir, 0755, true);
                    }
                    $ext = match ($mime) {
                        'image/png'     => 'png',
                        'image/jpeg'    => 'jpg',
                        'image/webp'    => 'webp',
                        default         => 'png'
                    };
                    $targetName = 'logo_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $targetPath = $logosDir . $targetName;
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $this->uploadedLogoPath = $targetPath;
                        return $targetPath;
                    }
                }
            }
        }

        // 2. Check preset symbol selection
        if (!empty($_POST['preset_logo'])) {
            $presetName = preg_replace('/[^a-zA-Z0-9_\-]/', '', strtolower(trim($_POST['preset_logo'])));
            if ($presetName !== '' && $presetName !== 'none') {
                $baseDir = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2)) . '/dist/img/presets/';
                if (file_exists($baseDir . $presetName . '.svg')) {
                    return $baseDir . $presetName . '.svg';
                }
                if (file_exists($baseDir . $presetName . '.png')) {
                    return $baseDir . $presetName . '.png';
                }
            }
        }

        return null;
    }

    /**
     * Determines whether module at (row, col) in an N x N QR code is part of outer eye, inner eye, or body.
     */
    private function getEyeRole(int $row, int $col, int $N): string {
        $isTL = ($row < 7 && $col < 7);
        $isTR = ($row < 7 && $col >= $N - 7);
        $isBL = ($row >= $N - 7 && $col < 7);
        if (!$isTL && !$isTR && !$isBL) return 'body';

        $lr = ($row < 7) ? $row : ($row - ($N - 7));
        $lc = ($col < 7) ? $col : ($col - ($N - 7));

        if ($lr === 0 || $lr === 6 || $lc === 0 || $lc === 6) return 'outer';
        if ($lr >= 2 && $lr <= 4 && $lc >= 2 && $lc <= 4) return 'inner';
        return 'body';
    }

    /**
     * Draws a filled rounded rectangle in GD.
     */
    private function drawFilledRoundRect($im, int $x, int $y, int $w, int $h, int $radius, int $color): void {
        $radius = min($radius, (int)floor($w / 2), (int)floor($h / 2));
        if ($radius <= 0) {
            imagefilledrectangle($im, $x, $y, $x + $w, $y + $h, $color);
            return;
        }
        imagefilledrectangle($im, $x + $radius, $y, $x + $w - $radius, $y + $h, $color);
        imagefilledrectangle($im, $x, $y + $radius, $x + $w, $y + $h - $radius, $color);
        $d = $radius * 2;
        imagefilledellipse($im, $x + $radius, $y + $radius, $d, $d, $color);
        imagefilledellipse($im, $x + $w - $radius, $y + $radius, $d, $d, $color);
        imagefilledellipse($im, $x + $radius, $y + $h - $radius, $d, $d, $color);
        imagefilledellipse($im, $x + $w - $radius, $y + $h - $radius, $d, $d, $color);
    }

    /**
     * Post-processes a rendered raster QR code image in GD:
     * - Applies linear/radial gradients using user-specified rotation angle and balance percentage.
     * - Colors outer and inner corner eye markers independently if enabled.
     * - Carves out the selected punchout shape (circle, rounded square, square) with background padding.
     * - Draws the emblem / logo image centered with alpha blending.
     */
    private function applyRasterStyling($im, array $options, ?string $logoPath) {
        $width = imagesx($im);
        $height = imagesy($im);

        $fg = $this->hexToRgb($options['foreground'], ['r' => 0, 'g' => 0, 'b' => 0]);
        $bg = $this->hexToRgb($options['background'], ['r' => 255, 'g' => 255, 'b' => 255]);
        $gCol = $this->hexToRgb($options['gradientColor'], ['r' => 0, 'g' => 123, 'b' => 255]);

        $customEyes = !empty($options['eyeOuter']) || !empty($options['eyeInner']);
        $eyeOuterRgb = !empty($options['eyeOuter']) ? $this->hexToRgb($options['eyeOuter'], $fg) : $fg;
        $eyeInnerRgb = !empty($options['eyeInner']) ? $this->hexToRgb($options['eyeInner'], $fg) : $fg;

        // Base image is guaranteed black (0) modules on white (255) background
        $minX = $width; $maxX = 0;
        $minY = $height; $maxY = 0;
        $foundDark = false;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($im, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                if ($r < 128) {
                    $foundDark = true;
                    if ($x < $minX) $minX = $x;
                    if ($x > $maxX) $maxX = $x;
                    if ($y < $minY) $minY = $y;
                    if ($y > $maxY) $maxY = $y;
                }
            }
        }

        $moduleSize = 10.0;
        $N = 25;
        if ($foundDark && $maxX > $minX && $maxY > $minY) {
            $qrW = $maxX - $minX + 1;
            $checkY = $minY;
            $eyeRun = 0;
            for ($x = $minX; $x <= $maxX; $x++) {
                $r = (imagecolorat($im, $x, $checkY) >> 16) & 0xFF;
                if ($r < 128) {
                    $eyeRun++;
                } else {
                    break;
                }
            }
            if ($eyeRun > 0) {
                $moduleSize = $eyeRun / 7.0;
            }
            $N = max(21, (int)round($qrW / $moduleSize));
        }

        // Precompute Gradient Look-Up Table (1001 steps)
        $tMid = max(0.1, min(0.9, ($options['gradientBalance'] ?? 50) / 100.0));
        $gradLut = [];
        for ($i = 0; $i <= 1000; $i++) {
            $p = $i / 1000.0;
            if ($p <= $tMid) {
                $f = ($tMid > 0) ? (0.5 * ($p / $tMid)) : 0.5;
            } else {
                $f = ($tMid < 1.0) ? (0.5 + 0.5 * (($p - $tMid) / (1.0 - $tMid))) : 0.5;
            }
            $f = max(0.0, min(1.0, $f));
            $gradLut[$i] = [
                'r' => (int)round((1.0 - $f) * $fg['r'] + $f * $gCol['r']),
                'g' => (int)round((1.0 - $f) * $fg['g'] + $f * $gCol['g']),
                'b' => (int)round((1.0 - $f) * $fg['b'] + $f * $gCol['b'])
            ];
        }

        // Gradient geometry
        $cx = $width / 2.0;
        $cy = $height / 2.0;
        $angle = (float)($options['gradientAngle'] ?? 45);
        $rad = $angle * (M_PI / 180.0);
        $ux = cos($rad);
        $uy = sin($rad);
        $drawDim = ($maxX > $minX) ? ($maxX - $minX + 1) : $width;
        $L = max(1.0, ($drawDim / 2.0) * (abs($ux) + abs($uy)));
        $maxR = max(1.0, $drawDim / 1.3);

        $bgAlloc = imagecolorallocate($im, $bg['r'], $bg['g'], $bg['b']);

        $isDots = in_array($options['roundBlock'] ?? 'margin', ['shrink', 'dots'], true);
        $isRounded = ($options['roundBlock'] ?? '') === 'rounded';

        if ($isDots || $isRounded) {
            // Reconstruct the N x N module grid from base image
            $matrix = [];
            for ($r = 0; $r < $N; $r++) {
                $yMid = (int)round($minY + ($r + 0.5) * $moduleSize);
                for ($c = 0; $c < $N; $c++) {
                    $xMid = (int)round($minX + ($c + 0.5) * $moduleSize);
                    if ($xMid < $width && $yMid < $height) {
                        $val = (imagecolorat($im, $xMid, $yMid) >> 16) & 0xFF;
                        $matrix[$r][$c] = ($val < 128);
                    } else {
                        $matrix[$r][$c] = false;
                    }
                }
            }

            // Clear canvas to user background
            imagefilledrectangle($im, 0, 0, $width, $height, $bgAlloc);

            // Draw customized modules (dots or rounded blocks)
            for ($r = 0; $r < $N; $r++) {
                for ($c = 0; $c < $N; $c++) {
                    if (!$matrix[$r][$c]) {
                        continue;
                    }

                    $xMid = (int)round($minX + ($c + 0.5) * $moduleSize);
                    $yMid = (int)round($minY + ($r + 0.5) * $moduleSize);

                    $target = null;
                    if ($customEyes && $foundDark) {
                        $role = $this->getEyeRole($r, $c, $N);
                        if ($role === 'outer' && !empty($options['eyeOuter'])) {
                            $target = $eyeOuterRgb;
                        } elseif ($role === 'inner' && !empty($options['eyeInner'])) {
                            $target = $eyeInnerRgb;
                        }
                    }

                    if ($target === null) {
                        if ($options['gradientType'] === 'linear') {
                            $proj = ($xMid - $cx) * $ux + ($yMid - $cy) * $uy;
                            $p = max(0.0, min(1.0, ($proj + $L) / (2.0 * $L)));
                            $target = $gradLut[(int)round($p * 1000)];
                        } elseif ($options['gradientType'] === 'radial') {
                            $dist = sqrt(($xMid - $cx) * ($xMid - $cx) + ($yMid - $cy) * ($yMid - $cy));
                            $p = max(0.0, min(1.0, $dist / $maxR));
                            $target = $gradLut[(int)round($p * 1000)];
                        } else {
                            $target = $fg;
                        }
                    }

                    $colAlloc = imagecolorallocate($im, $target['r'], $target['g'], $target['b']);

                    if ($isDots) {
                        $dotDiam = max(2, (int)round($moduleSize * 0.84));
                        imagefilledellipse($im, $xMid, $yMid, $dotDiam, $dotDiam, $colAlloc);
                    } else {
                        // Rounded module
                        $x1 = (int)round($minX + $c * $moduleSize + 0.5);
                        $y1 = (int)round($minY + $r * $moduleSize + 0.5);
                        $dim = max(2, (int)round($moduleSize - 1.0));
                        $rad = max(2, (int)round($moduleSize * 0.28));
                        $this->drawFilledRoundRect($im, $x1, $y1, $dim, $dim, $rad, $colAlloc);
                    }
                }
            }
        } else {
            // Standard / Square: apply background color and foreground gradients across all pixels
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $rgb = imagecolorat($im, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    // Grayscale base: 0 = pure module, 255 = pure background
                    $k = 1.0 - ($r / 255.0);

                    if ($k < 0.08) {
                        // Background pixel: draw user's selected background
                        imagesetpixel($im, $x, $y, $bgAlloc);
                        continue;
                    }

                    $target = null;
                    if ($customEyes && $foundDark) {
                        $cIdx = (int)floor(($x - $minX) / $moduleSize);
                        $rIdx = (int)floor(($y - $minY) / $moduleSize);
                        $role = $this->getEyeRole($rIdx, $cIdx, $N);
                        if ($role === 'outer' && !empty($options['eyeOuter'])) {
                            $target = $eyeOuterRgb;
                        } elseif ($role === 'inner' && !empty($options['eyeInner'])) {
                            $target = $eyeInnerRgb;
                        }
                    }

                    if ($target === null) {
                        if ($options['gradientType'] === 'linear') {
                            $proj = ($x - $cx) * $ux + ($y - $cy) * $uy;
                            $p = max(0.0, min(1.0, ($proj + $L) / (2.0 * $L)));
                            $target = $gradLut[(int)round($p * 1000)];
                        } elseif ($options['gradientType'] === 'radial') {
                            $dist = sqrt(($x - $cx) * ($x - $cx) + ($y - $cy) * ($y - $cy));
                            $p = max(0.0, min(1.0, $dist / $maxR));
                            $target = $gradLut[(int)round($p * 1000)];
                        } else {
                            $target = $fg;
                        }
                    }

                    $finR = (int)round((1.0 - $k) * $bg['r'] + $k * $target['r']);
                    $finG = (int)round((1.0 - $k) * $bg['g'] + $k * $target['g']);
                    $finB = (int)round((1.0 - $k) * $bg['b'] + $k * $target['b']);

                    $pixelColor = imagecolorallocate($im, $finR, $finG, $finB);
                    imagesetpixel($im, $x, $y, $pixelColor);
                }
            }
        }

        // Punchout Shape and Emblem Compositing
        if ($logoPath !== null && file_exists($logoPath)) {
            $logoPixelSize = (int)round($width * $options['logoScale']);
            $logoX = (int)round(($width - $logoPixelSize) / 2.0);
            $logoY = (int)round(($height - $logoPixelSize) / 2.0);
            $punchoutPadding = max(4, (int)round($moduleSize * 0.8));
            $bgAlloc = imagecolorallocate($im, $bg['r'], $bg['g'], $bg['b']);

            // 1. Draw Punchout Background
            if ($options['logoShape'] === 'circle') {
                $radius = ($logoPixelSize / 2.0) + $punchoutPadding;
                $diam = (int)round($radius * 2.0);
                imagefilledellipse($im, (int)round($cx), (int)round($cy), $diam, $diam, $bgAlloc);
            } elseif ($options['logoShape'] === 'square') {
                $px = $logoX - $punchoutPadding;
                $py = $logoY - $punchoutPadding;
                $pdim = $logoPixelSize + ($punchoutPadding * 2);
                imagefilledrectangle($im, $px, $py, $px + $pdim, $py + $pdim, $bgAlloc);
            } elseif ($options['logoShape'] === 'rounded') {
                $px = $logoX - $punchoutPadding;
                $py = $logoY - $punchoutPadding;
                $pdim = $logoPixelSize + ($punchoutPadding * 2);
                $this->drawFilledRoundRect($im, $px, $py, $pdim, $pdim, 8, $bgAlloc);
            }

            // 2. Load and draw emblem
            $effectiveLogoPath = $logoPath;
            $isSvg = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION)) === 'svg';
            if ($isSvg) {
                $candidatePng = preg_replace('/\.svg$/i', '.png', $logoPath);
                if (file_exists($candidatePng)) {
                    $effectiveLogoPath = $candidatePng;
                } elseif (extension_loaded('imagick') && class_exists(\Imagick::class)) {
                    try {
                        $imLogo = new \Imagick();
                        $imLogo->setBackgroundColor(new \ImagickPixel('transparent'));
                        $imLogo->readImage($logoPath);
                        $imLogo->setImageFormat('png32');
                        $tempPng = sys_get_temp_dir() . '/logo_raster_' . md5($logoPath . filemtime($logoPath)) . '.png';
                        $imLogo->writeImage($tempPng);
                        $imLogo->clear();
                        $imLogo->destroy();
                        if (file_exists($tempPng)) {
                            $effectiveLogoPath = $tempPng;
                        }
                    } catch (\Throwable $e) {}
                }
            }

            $logoData = @file_get_contents($effectiveLogoPath);
            if ($logoData !== false) {
                $logoGd = @imagecreatefromstring($logoData);
                if ($logoGd !== false) {
                    imagealphablending($im, true);
                    imagesavealpha($im, true);
                    imagecopyresampled(
                        $im,
                        $logoGd,
                        $logoX,
                        $logoY,
                        0,
                        0,
                        $logoPixelSize,
                        $logoPixelSize,
                        imagesx($logoGd),
                        imagesy($logoGd)
                    );
                    imagedestroy($logoGd);
                }
            }
        }

        return $im;
    }

    /**
     * Generates a new QR code image, applies raster or vector styling, and persists the record to the database.
     *
     * @param array $input_data Rendering options and form parameters.
     * @param array $data_to_db Database record columns to insert.
     * @param string $data_to_qrcode URL-encoded payload to encode into QR code.
     * @return void
     */
    public function addQrcode(array $input_data, array $data_to_db, string $data_to_qrcode): void {
        $options = $this->setOptions($input_data);

        $allowedFormats = ['png', 'svg', 'webp', 'jpg', 'jpeg'];
        $format = strtolower(trim($data_to_db['format'] ?? 'png'));
        if ($format === 'gif' || $format === 'eps') {
            $format = 'png';
        }
        if ($format === 'webp' && !function_exists('imagewebp')) {
            $format = 'png';
        }
        if (!in_array($format, $allowedFormats, true)) {
            $this->failure("Invalid image format requested.");
        }

        $fileExt = ($format === 'jpeg') ? 'jpg' : $format;
        $data_to_db['format'] = $fileExt;
        $data_to_db['filename'] = $this->sanitizeFilename($data_to_db['filename']);
        $data_to_db['qrcode'] = $data_to_db['filename'] . '.' . $fileExt;
        $filename = $this->getSafeFilePath($data_to_db['filename'], $fileExt);

        if (file_exists($filename)) {
            $this->failure('You cannot create a new qr code with an existing name on the server!');
        }

        $logoPath = $this->resolveLogoPath();

        try {

        // When a center logo is used, elevate ECC level to High or Quartile to ensure scanability
        $eccLevelChoice = $options['errorCorrectionLevel'];
        if ($logoPath !== null) {
            $eccLevelChoice = in_array($eccLevelChoice, ['Q', 'H'], true) ? $eccLevelChoice : 'H';
        }

        $payload = urldecode($data_to_qrcode);

        // Render QR Code using Endroid QrCode
        if (class_exists(EndroidQrCode::class)) {
            $eccEnum = match ($eccLevelChoice) {
                'L'     => ErrorCorrectionLevel::Low,
                'Q'     => ErrorCorrectionLevel::Quartile,
                'H'     => ErrorCorrectionLevel::High,
                default => ErrorCorrectionLevel::Medium
            };

            $roundBlockEnum = match ($options['roundBlock']) {
                'none'    => RoundBlockSizeMode::None,
                'enlarge' => RoundBlockSizeMode::Enlarge,
                'shrink'  => RoundBlockSizeMode::Shrink,
                default   => RoundBlockSizeMode::Margin
            };

            $fg = $this->hexToRgb($options['foreground'], ['r' => 0, 'g' => 0, 'b' => 0]);
            $bg = $this->hexToRgb($options['background'], ['r' => 255, 'g' => 255, 'b' => 255]);

            try {
                $dir = dirname($filename);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }

                if ($fileExt === 'svg') {
                    $writer = new SvgWriter();
                    $qrCode = new EndroidQrCode(
                        data: $payload,
                        encoding: new Encoding('UTF-8'),
                        errorCorrectionLevel: $eccEnum,
                        size: $options['size'],
                        margin: 10,
                        roundBlockSizeMode: $roundBlockEnum,
                        foregroundColor: new Color($fg['r'], $fg['g'], $fg['b']),
                        backgroundColor: new Color($bg['r'], $bg['g'], $bg['b'])
                    );

                    $logo = null;
                    if ($logoPath !== null && file_exists($logoPath) && class_exists(Logo::class)) {
                        $logoWidth = (int)round($options['size'] * $options['logoScale']);
                        $logo = new Logo(
                            path: $logoPath,
                            resizeToWidth: $logoWidth,
                            resizeToHeight: $logoWidth,
                            punchoutBackground: false
                        );
                    }

                    $writerOptions = [];
                    if (in_array($options['roundBlock'], ['shrink', 'dots', 'rounded'], true)) {
                        $writerOptions['compact'] = false;
                    }
                    $result = !empty($writerOptions) ? $writer->write($qrCode, $logo, options: $writerOptions) : $writer->write($qrCode, $logo);
                    $svgContent = $result->getString();

                    if ($options['gradientType'] !== 'none') {
                        $c1 = $this->hexToRgb($options['foreground']);
                        $c2 = $this->hexToRgb($options['gradientColor']);
                        $midR = (int)round(($c1['r'] + $c2['r']) / 2);
                        $midG = (int)round(($c1['g'] + $c2['g']) / 2);
                        $midB = (int)round(($c1['b'] + $c2['b']) / 2);
                        $midHex = sprintf('#%02x%02x%02x', $midR, $midG, $midB);

                        $fgHex = '#' . $options['foreground'];
                        $gHex = '#' . $options['gradientColor'];

                        $vbMinX = 0.0;
                        $vbMinY = 0.0;
                        $vbW = (float)$options['size'];
                        $vbH = (float)$options['size'];
                        if (preg_match('/viewBox=["\']\s*(-?[\d.]+)[,\s]+(-?[\d.]+)[,\s]+(-?[\d.]+)[,\s]+(-?[\d.]+)\s*["\']/i', $svgContent, $vbMatches)) {
                            $vbMinX = (float)$vbMatches[1];
                            $vbMinY = (float)$vbMatches[2];
                            $vbW = (float)$vbMatches[3];
                            $vbH = (float)$vbMatches[4];
                        }
                        $cx = $vbMinX + ($vbW / 2);
                        $cy = $vbMinY + ($vbH / 2);

                        if ($options['gradientType'] === 'radial') {
                            $r = max($vbW, $vbH) * 0.7;
                            $gradDef = '<defs><radialGradient id="qr-grad" gradientUnits="userSpaceOnUse" cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '">'
                                     . '<stop offset="0%" stop-color="' . $fgHex . '"/>'
                                     . '<stop offset="' . $options['gradientBalance'] . '%" stop-color="' . $midHex . '"/>'
                                     . '<stop offset="100%" stop-color="' . $gHex . '"/>'
                                     . '</radialGradient></defs>';
                        } else {
                            $angle = $options['gradientAngle'];
                            $x1 = $vbMinX;
                            $y1 = $cy;
                            $x2 = $vbMinX + $vbW;
                            $y2 = $cy;
                            $gradDef = '<defs><linearGradient id="qr-grad" gradientUnits="userSpaceOnUse" x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" gradientTransform="rotate(' . $angle . ', ' . $cx . ', ' . $cy . ')">'
                                     . '<stop offset="0%" stop-color="' . $fgHex . '"/>'
                                     . '<stop offset="' . $options['gradientBalance'] . '%" stop-color="' . $midHex . '"/>'
                                     . '<stop offset="100%" stop-color="' . $gHex . '"/>'
                                     . '</linearGradient></defs>';
                        }

                        $svgContent = preg_replace('/(<svg[^>]*>)/i', '$1' . $gradDef, $svgContent, 1);
                        $svgContent = str_ireplace('fill="' . $fgHex . '"', 'fill="url(#qr-grad)"', $svgContent);
                    }

                    if (in_array($options['roundBlock'], ['shrink', 'dots'], true)) {
                        $svgContent = preg_replace('/(<rect\b(?![^>]*\brx=)[^>]*\bid=["\']?block["\']?[^\/>]*)(\/?>)/i', '$1 rx="40%" ry="40%"$2', $svgContent);
                    } elseif ($options['roundBlock'] === 'rounded') {
                        $svgContent = preg_replace('/(<rect\b(?![^>]*\brx=)[^>]*\bid=["\']?block["\']?[^\/>]*)(\/?>)/i', '$1 rx="25%" ry="25%"$2', $svgContent);
                    }

                    if ($options['logoShape'] === 'circle') {
                        $punchRadius = ($options['size'] * $options['logoScale'] / 2) + 6;
                        $punchDef = '<circle cx="' . ($options['size'] / 2) . '" cy="' . ($options['size'] / 2) . '" r="' . $punchRadius . '" fill="#' . $options['background'] . '"/>';
                        $svgContent = preg_replace('/(<image\b)/i', $punchDef . '$1', $svgContent, 1);
                    } elseif ($options['logoShape'] === 'rounded') {
                        $dim = ($options['size'] * $options['logoScale']) + 12;
                        $pX = ($options['size'] - $dim) / 2;
                        $pY = ($options['size'] - $dim) / 2;
                        $punchDef = '<rect x="' . $pX . '" y="' . $pY . '" width="' . $dim . '" height="' . $dim . '" rx="8" ry="8" fill="#' . $options['background'] . '"/>';
                        $svgContent = preg_replace('/(<image\b)/i', $punchDef . '$1', $svgContent, 1);
                    }

                    file_put_contents($filename, $svgContent);
                } else {
                    $writer = new PngWriter();
                    $qrCode = new EndroidQrCode(
                        data: $payload,
                        encoding: new Encoding('UTF-8'),
                        errorCorrectionLevel: $eccEnum,
                        size: $options['size'],
                        margin: 10,
                        roundBlockSizeMode: $roundBlockEnum,
                        foregroundColor: new Color(0, 0, 0),
                        backgroundColor: new Color(255, 255, 255)
                    );

                    $result = $writer->write($qrCode);
                    $rawIm = @imagecreatefromstring($result->getString());
                    if ($rawIm === false) {
                        throw new \RuntimeException("Failed to initialize GD image from generated QR data.");
                    }

                    // Convert palette-based PNG from Endroid to a genuine TrueColor GD image
                    $w = imagesx($rawIm);
                    $h = imagesy($rawIm);
                    $im = imagecreatetruecolor($w, $h);
                    imagecopy($im, $rawIm, 0, 0, 0, 0, $w, $h);
                    imagedestroy($rawIm);

                    $im = $this->applyRasterStyling($im, $options, $logoPath);

                    $saved = match ($fileExt) {
                        'webp'        => function_exists('imagewebp') ? imagewebp($im, $filename, 95) : imagepng($im, $filename, 9),
                        'jpg', 'jpeg' => imagejpeg($im, $filename, 95),
                        default       => imagepng($im, $filename, 9)
                    };
                    imagedestroy($im);

                    if (!$saved) {
                        throw new \RuntimeException("Failed to save final QR image to " . $filename);
                    }
                }
            } catch (Throwable $e) {
                $this->failure("Error generating QR code: " . $e->getMessage());
            }
        } else {
            $this->failure("QR generator dependency is unavailable.");
        }

        $db = getDbInstance();
        $last_id = $db->insert($this->table, $data_to_db);

        if ($last_id) {
            $this->success('Qr code added successfully!');
        } else {
            if (file_exists($filename)) {
                @unlink($filename);
            }
            $this->failure('Insert failed: ' . $db->getLastError());
        }
        } finally {
            $this->cleanupUploadedLogo();
        }
    }

    /**
     * Updates an existing QR code record in the database and renames associated files if necessary.
     *
     * @param array $input_data Raw form input containing ID and update fields.
     * @param array $data_to_db Columns to update in the database.
     * @return void
     */
    public function editQrcode(array $input_data, array $data_to_db): void {
        $db = getDbInstance();
        $old_qrcode = $this->getQrcode($input_data["id"]);

        $data_to_db['filename'] = $this->sanitizeFilename($data_to_db['filename']);
        $oldFormat = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($old_qrcode["format"] ?? 'png'));
        $data_to_db['qrcode'] = $data_to_db['filename'] . '.' . $oldFormat;

        $oldFile = $this->getSafeFilePath($old_qrcode["filename"], $oldFormat);
        $newFile = $this->getSafeFilePath($data_to_db['filename'], $oldFormat);

        $renamed = false;
        if (!file_exists($newFile) || $newFile === $oldFile) {
            if (file_exists($oldFile) && $oldFile !== $newFile) {
                try {
                    if (!rename($oldFile, $newFile)) {
                        $this->failure('Failed to rename QR code file.');
                    }
                    $renamed = true;
                } catch (Exception $e) {
                    $this->failure($e->getMessage());
                }
            }

            $db->where('id', (int)$input_data["id"]);
            $stat = $db->update($this->table, $data_to_db);

            if (!$stat && $renamed) {
                if (!@rename($newFile, $oldFile)) {
                    $this->failure('Update failed: ' . $db->getLastError() . ' (Rollback failed: unable to restore file)');
                }
            }


        } else {
            $this->failure('You cannot edit a qr code with an existing name on the server!');
        }

        if ($stat) {
            $this->success('Qr code updated successfully!');
        } else {
            $this->failure('Update failed: ' . $db->getLastError());
        }
    }

    /**
     * Safely deletes a QR code database record and removes the corresponding image file from disk.
     *
     * @param int $id ID of the QR code to delete.
     * @param bool $async Whether the deletion request is asynchronous (suppresses flash message).
     * @return void
     */
    public function deleteQrcode(int $id, bool $async = false): void {
        $db = getDbInstance();
        $qrcode = $this->getQrcode($id);

        $filePath = $this->getSafeFilePath($qrcode["filename"] ?? '', $qrcode["format"] ?? 'png');
        $stagedPath = null;
        if (file_exists($filePath)) {
            $stagedPath = $filePath . '.deleting.' . bin2hex(random_bytes(6));
            if (!@rename($filePath, $stagedPath)) {
                $this->failure('Unable to stage QR code file for deletion.');
            }
        }

        $db->where('id', $id);
        $status = $db->delete($this->table);

        if (!$status) {
            if ($stagedPath !== null && file_exists($stagedPath)) {
                if (!@rename($stagedPath, $filePath)) {
                    $this->failure('Unable to delete qr code and failed to restore file.');
                }
            }
            $this->failure('Unable to delete qr code');
        }

        if ($stagedPath !== null && file_exists($stagedPath)) {
            if (!@unlink($stagedPath)) {
                $this->failure('Unable to finalize deletion of QR code file.');
            }
        }

        if (!$async) {
            $this->info('Qr code deleted successfully!');
        }
    }

    /**
     * Cleans up temporary uploaded logo file if present on disk.
     *
     * @return void
     */
    private function cleanupUploadedLogo(): void {
        if ($this->uploadedLogoPath !== null && file_exists($this->uploadedLogoPath)) {
            @unlink($this->uploadedLogoPath);
            $this->uploadedLogoPath = null;
        }
    }

    /**
     * Sets a session failure flash message and redirects to the target listing URL.
     *
     * @param string $message The failure message to display.
     * @return void
     */
    private function failure(string $message): void {
        $this->cleanupUploadedLogo();
        $_SESSION['failure'] = $message;
        header('Location: ' . $this->redirect_url);
        exit();
    }

    /**
     * Sets a session success flash message and redirects to the target listing URL.
     *
     * @param string $message The success message to display.
     * @return void
     */
    private function success(string $message): void {
        $this->cleanupUploadedLogo();
        $_SESSION['success'] = $message;
        header('Location: ' . $this->redirect_url);
        exit();
    }

    /**
     * Sets a session info flash message and redirects to the target listing URL.
     *
     * @param string $message The information message to display.
     * @return void
     */
    private function info(string $message): void {
        $_SESSION['info'] = $message;
        header('Location: ' . $this->redirect_url);
        exit();
    }
}
