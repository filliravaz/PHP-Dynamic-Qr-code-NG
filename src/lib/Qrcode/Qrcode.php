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
            error_log('Insert failed: ' . $db->getLastError());
            $this->failure('Failed to create QR code.');
        }
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
