<?php
require_once 'config/config.php';

use Endroid\QrCode\Builder\Builder;
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

    public function __destruct() {
    }

    public function sanitizeFilename(string $filename): string {
        $clean = basename(trim($filename));
        $clean = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return trim($clean) !== '' ? trim($clean) : 'qrcode_' . time();
    }

    public function getSafeFilePath(string $filename, string $ext): string {
        $safeName = $this->sanitizeFilename($filename);
        $safeExt = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($ext));
        return SAVED_QRCODE_DIRECTORY . $safeName . '.' . $safeExt;
    }

    public function getQrcode($id) {
        $db = getDbInstance();
        $db->where('id', (int)$id);
        $result = $db->getOne($this->table);

        if ($result !== null) {
            return $result;
        }

        $this->failure("Qrcode not found");
    }

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

    private function setOptions(array $input_data): array {
        $errorCorrectionLevel = 'M';
        if (isset($input_data['level']) && in_array(strtoupper($input_data['level']), ['L', 'M', 'Q', 'H'], true)) {
            $errorCorrectionLevel = strtoupper($input_data['level']);
        }

        $size = 300;
        if (isset($input_data['size'])) {
            $size = min(max((int)$input_data['size'], 100), 2000);
        }

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
        if (isset($input_data['round_block']) && in_array($input_data['round_block'], ['none', 'margin', 'enlarge', 'shrink'], true)) {
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

        return [
            'errorCorrectionLevel' => $errorCorrectionLevel,
            'size'                 => $size,
            'foreground'           => $foreground,
            'background'           => $background,
            'roundBlock'           => $roundBlock,
            'logoScale'            => $logoScale
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
                $allowed = ['image/png', 'image/jpeg', 'image/svg+xml', 'image/webp'];
                if (in_array($mime, $allowed, true)) {
                    $logosDir = SAVED_QRCODE_DIRECTORY . 'logos/';
                    if (!is_dir($logosDir)) {
                        @mkdir($logosDir, 0755, true);
                    }
                    $ext = match ($mime) {
                        'image/png'     => 'png',
                        'image/jpeg'    => 'jpg',
                        'image/svg+xml' => 'svg',
                        'image/webp'    => 'webp',
                        default         => 'png'
                    };
                    $targetName = 'logo_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    $targetPath = $logosDir . $targetName;
                    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
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

    public function addQrcode(array $input_data, array $data_to_db, string $data_to_qrcode): void {
        $options = $this->setOptions($input_data);

        $allowedFormats = ['png', 'svg', 'webp', 'jpg', 'jpeg'];
        $format = strtolower(trim($data_to_db['format'] ?? 'png'));
        if ($format === 'gif' || $format === 'eps') {
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

        // When a center logo is used, elevate ECC level to High or Quartile to ensure scanability
        $eccLevelChoice = $options['errorCorrectionLevel'];
        if ($logoPath !== null) {
            $eccLevelChoice = in_array($eccLevelChoice, ['Q', 'H'], true) ? $eccLevelChoice : 'H';
        }

        $payload = urldecode($data_to_qrcode);

        // Render QR Code using Endroid QrCode if available
        if (class_exists(Builder::class)) {
            $writer = match ($fileExt) {
                'svg'   => new SvgWriter(),
                'webp'  => new WebPWriter(),
                default => new PngWriter()
            };

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
                $builder = Builder::create()
                    ->writer($writer)
                    ->data($payload)
                    ->encoding(new Encoding('UTF-8'))
                    ->errorCorrectionLevel($eccEnum)
                    ->size($options['size'])
                    ->margin(10)
                    ->roundBlockSizeMode($roundBlockEnum)
                    ->foregroundColor(new Color($fg['r'], $fg['g'], $fg['b']))
                    ->backgroundColor(new Color($bg['r'], $bg['g'], $bg['b']));

                if ($logoPath !== null && file_exists($logoPath)) {
                    $logoWidth = (int)round($options['size'] * $options['logoScale']);
                    $builder->logoPath($logoPath)
                            ->logoResizeToWidth($logoWidth)
                            ->logoPunchoutBackground(true);
                }

                $result = $builder->build();

                // Ensure directory exists
                $dir = dirname($filename);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }

                $result->saveToFile($filename);
            } catch (Throwable $e) {
                $this->failure("Error generating QR code: " . $e->getMessage());
            }
        } else {
            // Fallback to external service if vendor package is absent
            $queryParams = [
                'data'    => $payload,
                'size'    => $options['size'] . 'x' . $options['size'],
                'ecc'     => $eccLevelChoice,
                'color'   => $options['foreground'],
                'bgcolor' => $options['background'],
                'format'  => $fileExt
            ];
            $url = 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query($queryParams);
            $content = @file_get_contents($url);
            if ($content === false) {
                $this->failure("Failed to generate QR code.");
            }
            @file_put_contents($filename, $content);
        }

        $db = getDbInstance();
        $last_id = $db->insert($this->table, $data_to_db);

        if ($last_id) {
            $this->success('Qr code added successfully!');
        } else {
            $this->failure('Insert failed: ' . $db->getLastError());
        }
    }

    public function editQrcode(array $input_data, array $data_to_db): void {
        $db = getDbInstance();
        $old_qrcode = $this->getQrcode($input_data["id"]);

        $data_to_db['filename'] = $this->sanitizeFilename($data_to_db['filename']);
        $oldFormat = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($old_qrcode["format"] ?? 'png'));
        $data_to_db['qrcode'] = $data_to_db['filename'] . '.' . $oldFormat;

        $oldFile = $this->getSafeFilePath($old_qrcode["filename"], $oldFormat);
        $newFile = $this->getSafeFilePath($data_to_db['filename'], $oldFormat);

        if (!file_exists($newFile) || $newFile === $oldFile) {
            $db->where('id', (int)$input_data["id"]);
            $stat = $db->update($this->table, $data_to_db);

            if (file_exists($oldFile) && $oldFile !== $newFile) {
                try {
                    rename($oldFile, $newFile);
                } catch (Exception $e) {
                    $this->failure($e->getMessage());
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

    public function deleteQrcode(int $id, bool $async = false): void {
        $db = getDbInstance();
        $qrcode = $this->getQrcode($id);

        $db->where('id', $id);
        $status = $db->delete($this->table);

        $filePath = $this->getSafeFilePath($qrcode["filename"], $qrcode["format"] ?? 'png');
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        if ($status) {
            if (!$async) {
                $this->info('Qr code deleted successfully!');
            }
        } else {
            if (!$async) {
                $this->failure('Unable to delete qr code');
            }
        }
    }

    private function failure(string $message): void {
        $_SESSION['failure'] = $message;
        header('Location: ' . $this->redirect_url);
        exit();
    }

    private function success(string $message): void {
        $_SESSION['success'] = $message;
        header('Location: ' . $this->redirect_url);
        exit();
    }

    private function info(string $message): void {
        $_SESSION['info'] = $message;
        header('Location: ' . $this->redirect_url);
        exit();
    }
}
