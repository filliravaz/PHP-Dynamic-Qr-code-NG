<?php
require_once 'config/config.php';

class Qrcode {
    private string $table;
    private string $redirect_url;

    /**
     *
     */
    public function __construct($type) {
        if($type === "static") {
            $this->table = "static_qrcodes";
            $this->redirect_url = "static_qrcodes.php";
        } else if($type === "dynamic") {
            $this->table = "dynamic_qrcodes";
            $this->redirect_url = "dynamic_qrcodes.php";
        } else {
            $this->redirect_url = "index.php";
            $this->failure("Type not allowed");
        }
    }

    /**
     *
     */
    public function __destruct()
    {
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

        $db->where('id', $id);
        $result = $db->getOne($this->table);

        if($result !== NULL)
            return $result;
        else
            $this->failure("Qrcode not found");
    }
    
    /**
     * Set option for qr code like:
     * Error Correction Level, size (default = 100), foreground, background
     * return array of values
     */
    private function setOptions($input_data) {
        $errorCorrectionLevel = 'L';

        if (isset($input_data['level']) && in_array($input_data['level'], array('L','M','Q','H')))
            $errorCorrectionLevel = $input_data['level'];
      
        $size = 100;
        if (isset($input_data['size']))
            $size = min(max((int)$input_data['size'], 100), 1000);

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

        //$logo = $_POST['optionlogo'];
           
        return array(
            "errorCorrectionLevel" => $errorCorrectionLevel,
            "size" => $size,
            "foreground" => $foreground,
            "background" => $background,
            //"optionlogo" => $logo,
        );
    }
    
    /**
     * Add qr code
     * Check out http://goqr.me/api/ for more information
     * We save the file obtained with the chosen name and in the selected folder
     * We save into db the url of qrcode image
     */
    public function addQrcode($input_data, $data_to_db, $data_to_qrcode) {
        $options = $this->setOptions($input_data);

        $allowedFormats = ['png', 'gif', 'jpg', 'jpeg', 'svg', 'eps'];
        $format = strtolower(trim($data_to_db['format'] ?? 'png'));
        if (!in_array($format, $allowedFormats, true)) {
            $this->failure("Invalid image format requested.");
        }
        $data_to_db['format'] = $format;
        $data_to_db['filename'] = $this->sanitizeFilename($data_to_db['filename']);

        $filename = $this->getSafeFilePath($data_to_db['filename'], $data_to_db['format']);

        // Define explicitly so the flow below never depends on failure() exiting.
        $last_id = false;
        $db = null;

        if(!file_exists($filename)){
            $queryParams = [
                'data' => urldecode($data_to_qrcode),
                'size' => $options['size'] . 'x' . $options['size'],
                'ecc' => $options['errorCorrectionLevel'],
                'margin' => '0',
                'color' => $options['foreground'],
                'bgcolor' => $options['background'],
                'qzone' => '2',
                'format' => $data_to_db['format']
            ];
            $url = 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query($queryParams);

            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 10
                ]
            ]);
            $content = @file_get_contents($url, false, $ctx);
            if ($content === false) {
                $this->failure("Failed to retrieve QR code from external service.");
            }

            // Never persist an unvalidated third-party response under saved_qrcode/.
            if (!$this->isValidExternalImage($content, $data_to_db['format'])) {
                error_log('External QR service returned unexpected content for format ' . $data_to_db['format']);
                $this->failure("External QR service returned an invalid image.");
            }
        
            try{
                file_put_contents($filename, $content);
            }
            catch(Exception $e){
                $this->failure($e->getMessage());
            }
            
            // If you want you can customi<e qr code with logo
            //$this->addLogo($data_to_db['qrcode'], $options['optionlogo']);
              
            $db = getDbInstance();
            $last_id = $db->insert($this->table, $data_to_db);
        }
        else
            $this->failure('You cannot create a new qr code with an existing name on the server!');
        
        if ($last_id){
            $this->success('Qr code added successfully!');
        }
        else {
            error_log('Insert failed: ' . ($db !== null ? $db->getLastError() : 'no database handle'));
            $this->failure('Failed to create QR code.');
        }
    }

    /**
     * Validate that a response from the external QR API matches the requested format.
     * Raster/EPS formats are checked by magic bytes; SVG must be a plain <svg> document
     * without scripts, event handlers, foreign objects or external references.
     */
    private function isValidExternalImage(string $content, string $format): bool {
        if ($content === '' || strlen($content) > 5 * 1024 * 1024) {
            return false;
        }

        switch ($format) {
            case 'png':
                return strncmp($content, "\x89PNG\r\n\x1a\n", 8) === 0;
            case 'gif':
                return strncmp($content, 'GIF87a', 6) === 0 || strncmp($content, 'GIF89a', 6) === 0;
            case 'jpg':
            case 'jpeg':
                return strncmp($content, "\xFF\xD8\xFF", 3) === 0;
            case 'eps':
                return strncmp($content, '%!PS', 4) === 0;
            case 'svg':
                if (!preg_match('/^\s*(<\?xml[^>]*\?>\s*)?(<!--.*?-->\s*)*(<!DOCTYPE[^>\[]*>\s*)?<svg[\s>]/is', $content)) {
                    return false;
                }
                if (stripos($content, '<!ENTITY') !== false || stripos($content, '<![CDATA[') !== false) {
                    return false;
                }
                // Pre-filter: reject external stylesheet imports or CSS url(...) references anywhere in markup
                if (preg_match('/@import\b|\burl\s*\(/i', $content)) {
                    return false;
                }
                // Inspect all <style> text blocks and style="..." attributes for unsafe CSS
                if (preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $content, $styleBlocks)) {
                    foreach ($styleBlocks[1] as $block) {
                        if ($this->isUnsafeCss($block)) {
                            return false;
                        }
                    }
                }
                if (preg_match_all('/\sstyle\s*=\s*["\']([^"\']*)["\']/is', $content, $styleAttrs)) {
                    foreach ($styleAttrs[1] as $attrVal) {
                        if ($this->isUnsafeCss($attrVal)) {
                            return false;
                        }
                    }
                }
                // Inspect markup only: the vendor echoes the (escaped) QR payload as text in
                // <desc>/<title>, which must not cause false positives.
                if (!preg_match_all('/<[^>]*>/', $content, $tags)) {
                    return false;
                }
                $forbiddenTag = '/^<\s*\/?\s*(script|foreignObject|iframe|object|embed|use|image|a|animate|set|handler|listener)\b/i';
                $forbiddenAttr = '/\s(on[a-z]+|href|xlink:href|src)\s*=|javascript:|data:/i';
                foreach ($tags[0] as $tag) {
                    if (preg_match($forbiddenTag, $tag) || preg_match($forbiddenAttr, $tag)) {
                        return false;
                    }
                }

                // Additionally parse the XML to inspect actual elements and attributes
                if (class_exists('DOMDocument')) {
                    $prevErrors = libxml_use_internal_errors(true);
                    try {
                        $dom = new DOMDocument();
                        if (!$dom->loadXML($content, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR)) {
                            return false;
                        }

                        $root = $dom->documentElement;
                        if (!$root || strtolower($root->localName ?: $root->nodeName) !== 'svg') {
                            return false;
                        }

                        $forbiddenElements = [
                            'script', 'foreignobject', 'iframe', 'object', 'embed',
                            'use', 'image', 'a', 'animate', 'set', 'handler', 'listener'
                        ];

                        foreach ($dom->getElementsByTagName('*') as $el) {
                            $elName = strtolower($el->localName ?: $el->nodeName);
                            if (in_array($elName, $forbiddenElements, true)) {
                                return false;
                            }

                            // Inspect text content inside <style> elements for external URLs or @import
                            if ($elName === 'style' && $this->isUnsafeCss($el->textContent)) {
                                return false;
                            }

                            if ($el->hasAttributes()) {
                                foreach ($el->attributes as $attr) {
                                    $attrLocal = strtolower($attr->localName ?: $attr->nodeName);
                                    $attrNode = strtolower($attr->nodeName);

                                    if (str_starts_with($attrLocal, 'on') || str_starts_with($attrNode, 'on')) {
                                        return false;
                                    }

                                    if (in_array($attrLocal, ['href', 'src'], true) || in_array($attrNode, ['href', 'src', 'xlink:href'], true)) {
                                        return false;
                                    }

                                    // Inspect attribute values (specifically style attributes) for unsafe CSS or script URIs
                                    if ($this->isUnsafeCss($attr->value)) {
                                        return false;
                                    }

                                    $val = preg_replace('/[\x00-\x20\s]+/', '', strtolower(html_entity_decode($attr->value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                                    if (str_contains($val, 'javascript:') || str_contains($val, 'data:')) {
                                        return false;
                                    }
                                }
                            }
                        }
                    } finally {
                        libxml_clear_errors();
                        libxml_use_internal_errors($prevErrors);
                    }
                }

                return true;
            default:
                return false;
        }
    }

    /**
     * Inspect CSS content from a <style> tag or style attribute for external
     * references (@import, url()), executable expressions, or script protocols.
     */
    private function isUnsafeCss(string $css): bool {
        $clean = html_entity_decode($css, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Strip CSS comments /* ... */
        $clean = preg_replace('/\/\*.*?\*\//s', '', $clean) ?? $clean;

        // Reject @import, expression(), behavior:, or -moz-binding:
        if (preg_match('/@import\b|expression\s*\(|behavior\s*:|-moz-binding\s*:/i', $clean)) {
            return true;
        }

        // Decode CSS hex escapes (e.g. \75\72\6c -> url) and character escapes
        $unescaped = preg_replace_callback('/\\\\([0-9a-fA-F]{1,6}\s?)/', function($m) {
            $code = hexdec(trim($m[1]));
            return ($code > 0 && $code < 0x110000) ? mb_chr($code, 'UTF-8') : '';
        }, $clean) ?? $clean;
        $unescaped = preg_replace('/\\\\(.)/s', '$1', $unescaped) ?? $unescaped;

        // Reject url(...) references, javascript:, and data: URIs
        if (preg_match('/\burl\s*\(/i', $unescaped) || preg_match('/javascript:|data:/i', $unescaped)) {
            return true;
        }

        return false;
    }
    
    /**
     * Edit qr code
     * 
     */
    public function editQrcode($input_data, $data_to_db) {
        $db = getDbInstance();
        $old_qrcode = $this->getQrcode($input_data["id"]);

        $data_to_db['filename'] = $this->sanitizeFilename($data_to_db['filename']);
        // SEC-06 & CWE-639: Do not accept a user-supplied basename as the record's file reference.
        // Preserve existing reference or resolve and validate the record's actual image on disk.
        if (!empty($old_qrcode['qrcode'])) {
            $data_to_db['qrcode'] = $old_qrcode['qrcode'];
        } else {
            $origFormat = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($old_qrcode['format'] ?? 'png'));
            $origFilename = $this->sanitizeFilename($old_qrcode['filename'] ?? '');
            $legacyDiskName = (!empty($origFilename) && !empty($origFormat)) ? ($origFilename . '.' . $origFormat) : null;
            $legacyPath = ($legacyDiskName !== null) ? (SAVED_QRCODE_DIRECTORY . $legacyDiskName) : null;

            if ($legacyPath !== null && file_exists($legacyPath) && is_file($legacyPath)) {
                $data_to_db['qrcode'] = $legacyDiskName;
            } else {
                $data_to_db['qrcode'] = $old_qrcode['qrcode'] ?? null;
            }
        }

        $db->where('id', (int)$input_data["id"]);
        $stat = $db->update($this->table, $data_to_db);

        if ($stat){
            $this->success('Qr code updated successfully!');
        }
        else {
            error_log('Update failed: ' . $db->getLastError());
            $this->failure('Failed to update QR code.');
        }
    }

    
    /**
     * Delete qr code
     * 
     */
    public function deleteQrcode($id, $async = false) {
        $intId = filter_var($id, FILTER_VALIDATE_INT);
        if ($intId === false || $intId <= 0) {
            if (!$async) {
                $this->failure('QR code not found.');
            }
            return;
        }

        $db = getDbInstance();

        $db->where('id', (int)$intId);
        $qrcode = $db->getOne($this->table);
        if (!$qrcode) {
            if (!$async) {
                $this->failure('QR code not found.');
            }
            return;
        }

        $db->where('id', (int)$intId);
        $status = $db->delete($this->table);
        
        $diskName = !empty($qrcode['qrcode']) ? basename($qrcode['qrcode']) : ($this->sanitizeFilename($qrcode['filename'] ?? '') . '.' . preg_replace('/[^a-zA-Z0-9]/', '', strtolower($qrcode['format'] ?? 'png')));
        $filePath = SAVED_QRCODE_DIRECTORY . $diskName;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        if ($status)
            if (!$async) {
                $this->info('Qr code deleted successfully!');
            }
        else
            if (!$async) {
                $this->failure('Unable to delete qr code');
            }
    }
    
    /**
     * Add logo
     * IMPORTANT: I do not recommend to use this option because there may be problems with the scanning of the qr code as some readers may not recognize the code
     */
    private function addLogo($src, $logo = 'none') {
        try
        {
            if($logo != 'none')
            {
                $logo = imagecreatefrompng($_SERVER['HTTP_HOST'].'/admin'.$logo);
                $QR = imagecreatefrompng(BASE_PATH.$src);
            
	            $QR_width = imagesx($QR);
	            $QR_height = imagesy($QR);
	
	            $logo_width = imagesx($logo);
	            $logo_height = imagesy($logo);
	
	            // Scale logo to fit in the QR Code
	            $logo_qr_width = $QR_width/3;
	            $scale = $logo_width/$logo_qr_width;
	            $logo_qr_height = $logo_height/$scale;
	            
	            // You can try also with imagecopymerge() with same arguments
	            imagecopyresampled($QR, $logo, $QR_width/3, $QR_height/3, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);
	    
	            //$output = Set directory for saving image;
	            header('Content-Type: image/png'); 
	            imagepng($QR /*, $output*/); 
                imagedestroy($QR);
            }
        }
        catch(Exception $e)
        {
            $this->failure($e->getMessage());
        }
    }

    /**
     * Flash message Failure process
     */
    private function failure($message) {
        $_SESSION['failure'] = $message;
        header('Location: ' . $this->redirect_url);
    	exit();
    }
    
    /**
     * Flash message Success process
     */
    private function success($message) {
        $_SESSION['success'] = $message;
        header('Location: ' . $this->redirect_url);
    	exit();
    }
    
    /**
     * Flash message Info process
     */
    private function info($message) {
        $_SESSION['info'] = $message;
        header('Location: ' . $this->redirect_url);
    	exit();
    }

    public function debug($data) {
        echo '<pre>' . var_export($data, true) . '</pre>';
        exit();
    }
}
?>
