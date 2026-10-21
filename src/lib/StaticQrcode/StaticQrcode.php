<?php
require_once 'config/config.php';
require_once BASE_PATH . '/lib/ICS/ICS.php';
require_once BASE_PATH . '/lib/vCard/vCard.php';

if (QRCODE_GENERATOR === "external-api.qrserver.com") {
    require_once BASE_PATH . '/lib/Qrcode/Qrcode.php';
}

if (QRCODE_GENERATOR === "internal-chillerlan.qrcode") {
    require_once BASE_PATH . '/lib/Qrcode/Qrcode-intchil.php';
}

class StaticQrcode {
    private $sData;         // Data for the qr code
    private $sContent;      // Content to be stored in the database
    private Qrcode $qrcode_instance;
    /**
     *
     */
    public function __construct() {
        $this->qrcode_instance = new Qrcode("static");
    }

    /**
     *
     */
    public function __destruct()
    {
    }
    
    /**
     * Set friendly columns\' names to order tables\' entries
     */
    public function setOrderingValues()
    {
        $ordering = [
            'id' => 'ID',
            'id_owner' => 'Owner',
            'filename' => 'File Name',
            'type' => 'Type',
            'content' => 'Content',
            'qrcode' => 'Qr Code',
            'created_at' => 'Created at',
            'updated_at' => 'Updated at'
        ];

        return $ordering;
    }
    
    /**
     * create a qr code of type "text"
     * @string text -> required
     */
    public function textQrcode($text)
    {
        if($text != NULL){
            $this->sData = $text;
            $this->sContent = '<strong>Text:</strong> ' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            $this->addQrcode("text");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "email"
     * @string email -> required
     * @string subject
     * @string message -> required
     */
    public function emailQrcode($email, $subject, $message)
    {
        if($email != NULL && $message != NULL){
            $this->sData = 'MATMSG:TO:'.$email.';SUB:'.$subject.';BODY:'.$message.';';
            $this->sContent = '<strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Subject:</strong> ' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Message:</strong> ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("email");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "phone"
     * @int country code -> required
     * @string phone number -> required
     */
    public function phoneQrcode($country_code, $phone_number)
    {
        if($phone_number != NULL){
            $this->sData = 'TEL:'.$country_code.$phone_number;  
            $this->sContent = '<strong>Phone number:</strong> ' . htmlspecialchars($country_code . $phone_number, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("phone");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "sms"
     * @int country code -> required
     * @string phone number -> required
     * @string message -> required
     */
    public function smsQrcode($country_code, $phone_number, $message)
    {
        if($phone_number != NULL && $message != NULL){
            $this->sData = 'SMSTO:'.$country_code.$phone_number.':'.$message;  
            $this->sContent = '<strong>Phone number:</strong> ' . htmlspecialchars($country_code . $phone_number, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Message:</strong> ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("sms");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "whatsapp"
     * @int country code -> required
     * @string phone number -> required
     * @string message
     */
    public function whatsappQrcode($country_code, $phone_number, $message)
    {
        if($phone_number != NULL){
            $this->sData = 'https://wa.me/'.$country_code.$phone_number.'?text='.$message;  
            $this->sContent = '<strong>Phone number:</strong> ' . htmlspecialchars($country_code . $phone_number, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Message:</strong> ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("whatsapp");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "skype"
     * @string skype username -> required
     */
    public function skypeQrcode($skype_username)
    {
        if($skype_username != NULL){
            $this->sData = 'skype:'.$skype_username.'?call';
            $this->sContent = '<strong>Skype username:</strong> ' . htmlspecialchars($skype_username, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("skype");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "location"
     * @int latitude -> required
     * @int longitude -> required
     */
    public function locationQrcode($latitude, $longitude)
    {
        if($latitude != NULL && $longitude != NULL){
            $this->sData = 'GEO:'.$latitude.','.$longitude.';';
            $this->sContent = '<strong>Latitude:</strong> ' . htmlspecialchars($latitude, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Longitude:</strong> ' . htmlspecialchars($longitude, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("location");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "vcard"
     * 
     */
    public function vcardQrcode($fullname, $nickname, $email, $website, $phone, $home_phone, $work_phone, $company, $role, $categories, $note, $photo, $address, $city, $postcode, $state)
    {
        if($fullname != NULL && $phone != NULL){
            
            $vcard = new vCard;
            $vcard->name($fullname);
            $vcard->nickName($nickname); 
            $vcard->email($email); 
            $vcard->url($website); 
            $vcard->cellPhone($phone); 
            $vcard->homePhone($home_phone); 
            $vcard->workPhone($work_phone); 
            $vcard->organization($company); 
            $vcard->role($role); 
            $vcard->categories($categories); 
            $vcard->note($note); 
            $vcard->photo($photo); 
            $vcard->address($address, $city, $postcode, $state); 
            $vcard->create();
            
            $this->sData = $vcard->get();
            $this->sContent = '<div class="row"><div class="col-sm-4">';
            
                $this->sContent .= '<strong>Full name:</strong> ' . htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Nickname:</strong> ' . htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Website:</strong> ' . htmlspecialchars($website, ENT_QUOTES, 'UTF-8') . '</div>';
            
            $this->sContent .= '<div class="col-sm-4">';
            
                $this->sContent .= '<strong>Company:</strong> ' . htmlspecialchars($company, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Role:</strong> ' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Categories:</strong> ' . htmlspecialchars($categories, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Note:</strong> ' . htmlspecialchars($note, ENT_QUOTES, 'UTF-8') . '</div>';
                
            $this->sContent .= '<div class="col-sm-4">';
            
                $this->sContent .= '<strong>Phone:</strong> ' . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Home Phone:</strong> ' . htmlspecialchars($home_phone, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Work phone:</strong> ' . htmlspecialchars($work_phone, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Address:</strong> ' . htmlspecialchars($address, ENT_QUOTES, 'UTF-8') . '&nbsp;' . htmlspecialchars($city, ENT_QUOTES, 'UTF-8') . '&nbsp;' . htmlspecialchars($postcode, ENT_QUOTES, 'UTF-8') . '&nbsp;' . htmlspecialchars($state, ENT_QUOTES, 'UTF-8') . '</div>';
            
            $this->sContent .= '</div>';

            $this->addQrcode("vcard");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "event"
     * @string description -> required
     * @string start event -> required
     * @string end event -> required
     * @string location
     * @string summary
     * @string url
     */
    public function eventQrcode($title, $start, $end, $timezone, $location, $description, $url)
    {
        if($title != NULL && $start != NULL && $end != NULL){
            header('Content-Type: text/calendar; charset=utf-8');
            header('Content-Disposition: attachment; filename=invite.ics');

            $timezoneFrom = $timezone;

            if(empty($timezoneFrom)){
                $timezoneFrom = 'Europe/Berlin';
            }

            $tmpStart = new DateTime($start, new DateTimeZone($timezoneFrom));
            $tmpEnd = new DateTime($end, new DateTimeZone($timezoneFrom));
            $tmpStart->setTimezone(new DateTimeZone("UTC"));
            $tmpEnd->setTimezone(new DateTimeZone("UTC"));

            $ics = new ICS(array(
                'location' => $location,
                'description' => $description,
                'dtstart' => $tmpStart->format("Y-m-d H:i:s"),
                'dtend' => $tmpEnd->format("Y-m-d H:i:s"),
                'summary' => $title,
                'url' => $url
            ));
            
            $this->sData = $ics->to_string();
            $this->sContent = '<div class="row"><div class="col-sm-4">';
            $this->sContent .= '<strong>Title:</strong> ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '<br>' .
                               '<strong>Start event:</strong> ' . htmlspecialchars($start, ENT_QUOTES, 'UTF-8') . '<br>' .
                               '<strong>End event:</strong> ' . htmlspecialchars($end, ENT_QUOTES, 'UTF-8') . '<br>' .
                               '<strong>Time zone:</strong> ' . htmlspecialchars($timezone, ENT_QUOTES, 'UTF-8') . '<br></div>';
            $this->sContent .= '<div class="col-sm-4">';
            $this->sContent .= '<strong>Location:</strong> ' . htmlspecialchars($location, ENT_QUOTES, 'UTF-8') . '<br>' .
                               '<strong>Description:</strong> ' . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . '<br>' .
                               '<strong>URL:</strong> ' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</div>';
            $this->sContent .= '</div>';

            $this->addQrcode("event");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "bookmark"
     * @string title
     * @string url -> required
     */
    public function bookmarkQrcode($url, $title)
    {
        if($url != NULL){
            $this->sData = 'MEBKM:TITLE:'.$title.';URL:'.$url.';';  
            $this->sContent = '<strong>Title:</strong> ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Url:</strong> ' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("bookmark");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "wifi"
     * @string encryption -> required
     * @string ssid -> required
     * @string password
     */
    public function wifiQrcode($encryption, $ssid, $password)
    {
        if($ssid != NULL){
            $this->sData = 'WIFI:T:'.$encryption.';S:'.$ssid.';P:'.$password.';';  
            $this->sContent = '<strong>Encryption:</strong> ' . htmlspecialchars($encryption, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>SSID:</strong> ' . htmlspecialchars($ssid, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Password:</strong> ' . htmlspecialchars($password, ENT_QUOTES, 'UTF-8');

            $this->addQrcode("wifi");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "paypal"
     * @string payment type -> required
     * @string email -> required
     * @string item_name -> required
     * @int item_id
     * @int amount -> required
     * @string currency -> required
     * @int shipping
     * @int tax_rate
     */
    public function paypalQrcode($payment_type, $email, $item_name, $item_id, $amount, $currency, $shipping, $tax_rate)
    {
        if($email != NULL && $item_name != NULL && $amount != NULL){
            $this->sData = 'https://www.paypal.com/webapps/xorouter?cmd='.$payment_type.'&business='.$email.'&item_name='.$item_name.'&item_number='.$item_id.'&amount='.$amount.'&currency_code='.$currency.'&shipping='.$shipping.'&tax_rate='.$tax_rate;
            
            $this->sContent = '<div class="row"><div class="col-sm-4">';
            
                $this->sContent .= '<strong>Payment type:</strong> ' . htmlspecialchars($payment_type, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Email:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Item name:</strong> ' . htmlspecialchars($item_name, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Item id:</strong> ' . htmlspecialchars($item_id, ENT_QUOTES, 'UTF-8') . '</div>';
            
            $this->sContent .= '<div class="col-sm-4">';
            
                $this->sContent .= '<strong>Amount:</strong> ' . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Currency:</strong> ' . htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Shipping:</strong> ' . htmlspecialchars($shipping, ENT_QUOTES, 'UTF-8') . '<br>' .
                                   '<strong>Tax rate:</strong> ' . htmlspecialchars($tax_rate, ENT_QUOTES, 'UTF-8') . '</div>';
                
            $this->sContent .= '</div>';

            $this->addQrcode("paypal");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "bitcoin"
     * @string address -> required
     * @int amount -> required
     * @string label
     * @string message
     */
    public function bitcoinQrcode($address, $amount, $label, $message)
    {
        if($address != NULL && $amount != NULL){
            $this->sData = 'bitcoin:'.$address.'?amount='.$amount.'&label='.$label.'&message='.$message;
            $this->sContent = '<strong>BTC address:</strong> ' . htmlspecialchars($address, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Amount:</strong> ' . htmlspecialchars($amount, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Label:</strong> ' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Message:</strong> ' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        
            $this->addQrcode("bitcoin");
        }
        else
            $this->requiredFieldsError();
    }
    
    /**
     * create a qr code of type "2FA"
     * @string algorithms -> required
     * @string secret -> required
     * @string label -> required
     * @string issuer
     * otpauth://TYPE/LABEL?PARAMETERS
     * otpauth://totp/Example:alice@google.com?secret=JBSWY3DPEHPK3PXP&issuer=Example
     */
    public function twofaQrcode($algorithms, $secret, $label, $issuer)
    {
        if($algorithms != NULL && $secret != NULL && $label != NULL){
            $this->sData = 'otpauth://' . $algorithms . '/' . $label . '?secret=' . $secret;

            if (!empty($issuer)) {
                $this->sData .= '&issuer=' . $issuer;
            }

            $this->sContent = '<strong>Type:</strong> ' . htmlspecialchars($algorithms, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Secret:</strong> ' . htmlspecialchars($secret, ENT_QUOTES, 'UTF-8') . '<br>' .
                              '<strong>Label:</strong> ' . htmlspecialchars(rawurldecode($label), ENT_QUOTES, 'UTF-8');

            if (!empty($issuer)) {
                $this->sContent .= '<br><strong>Issuer:</strong> ' . htmlspecialchars(rawurldecode($issuer), ENT_QUOTES, 'UTF-8');
            }

            $this->addQrcode("2fa");
        }
        else
            $this->requiredFieldsError();
    }

    public function getQrcode($id) {
        return $this->qrcode_instance->getQrcode($id);
    }
    
    /**
     * Add qr code
     * Check out http://goqr.me/api/ for more information
     * We save the file obtained with the chosen name and in the selected folder
     * We save into db the url of qrcode image
     */
    private function addQrcode($type) {
        if ($_SESSION['type'] === 'super') {
            $data_to_db['id_owner'] = !empty($_POST['id_owner']) ? (int)$_POST['id_owner'] : NULL;
        } else {
            $data_to_db['id_owner'] = (int)$_SESSION['user_id'];
        }

        $data_to_db['created_at'] = date('Y-m-d H:i:s');
        $data_to_db['created_by'] = (int)$_SESSION['user_id'];
        $data_to_db['filename'] = $this->qrcode_instance->sanitizeFilename($_POST['filename']);
        $data_to_db['type'] = $type;
        $data_to_db['format'] = strtolower(trim($_POST['format'] ?? 'png'));
        $data_to_db['qrcode'] = $data_to_db['filename'].'.'.$data_to_db['format'];
        $data_to_db['content'] = $this->sContent;

        if(isset($_POST['level']))
            $input_data["level"] = $_POST['level'];

        if(isset($_POST['size']))
            $input_data["size"] = $_POST['size'];

        $input_data["foreground"] = $_POST['foreground'];
        $input_data["background"] = $_POST['background'];

        $data_to_qrcode = urlencode($this->sData);

        $this->qrcode_instance->addQrcode($input_data, $data_to_db, $data_to_qrcode);
    }
    
    /**
     * Edit qr code
     * 
     */
    public function editQrcode($input_data) {
        $existing = $this->getQrcode($input_data["id"]);
        if ($_SESSION['type'] !== 'super') {
            if (!isset($existing['id_owner']) || (int)$existing['id_owner'] !== (int)$_SESSION['user_id']) {
                $this->failure("You cannot edit this qrcode because it belongs to another user");
            }
            $data_to_db['id_owner'] = (int)$_SESSION['user_id'];
        } else {
            $data_to_db['id_owner'] = !empty($input_data['id_owner']) ? (int)$input_data['id_owner'] : NULL;
        }

        $data_to_db['filename'] = $this->qrcode_instance->sanitizeFilename($input_data['filename']);
        $data_to_db['created_at'] = date('Y-m-d H:i:s');

        $this->qrcode_instance->editQrcode($input_data, $data_to_db);
    }
    
    /**
     * Delete qr code
     * 
     */
    public function deleteQrcode($id, $async = false) {
        if($_SESSION['type'] === "super") {
            $this->qrcode_instance->deleteQrcode($id, $async);
        } else if ($_SESSION['type'] === "admin") {
            $qrcode = $this->getQrcode($id);

            if(!isset($qrcode["id_owner"]))
                $this->failure("You cannot delete this qrcode");

            require_once BASE_PATH . '/lib/Users/Users.php';
            $users = new Users();
            $user = $users->getUser($_SESSION['user_id']);

            if($user["id"] === $qrcode["id_owner"])
                $this->qrcode_instance->deleteQrcode($id, $async);
            else
                $this->failure("You cannot delete this qrcode because it's of another user");
        }
    }
    
    /**
     * Flash message Failure process
     */
    private function failure($message) {
        $_SESSION['failure'] = $message;
        header('Location: static_qrcodes.php');
    	exit();
    }
    
    /**
     * Flash message Success process
     */
    private function success($message) {
        $_SESSION['success'] = $message;
        header('Location: static_qrcodes.php');
    	exit();
    }
    
    /**
     * Flash message Info process
     */
    private function info($message) {
        $_SESSION['info'] = $message;
        header('Location: static_qrcodes.php');
    	exit();
    }
    
    /**
     * Error message if not filled in all the fields required by the type of the qr code
     */
    private function requiredFieldsError() {
        $this->failure('The qr code cannot be created if you do not fill in all the required fields (*)');
    }

    public function debug($data) {
        echo '<pre>' . var_export($data, true) . '</pre>';
        exit();
    }
}
?>