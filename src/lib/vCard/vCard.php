<?php
/**
 * @title            QR Code
 * @desc             Compatible to vCard 4.0 or higher.
 *
 * @author           Pierre-Henry Soria <ph7software@gmail.com>
 * @copyright        (c) 2012-2018, Pierre-Henry Soria. All Rights Reserved.
 * @license          GNU General Public License <http://www.gnu.org/licenses/gpl.html>
 * @version          1.2
 */

class vCard
{
    private $sData;

    /**
     * Sanitize single-line vCard property values to prevent newline injection (SEC-17).
     */
    private function cleanProp($val)
    {
        return str_replace(["\r", "\n"], '', (string)$val);
    }

    /**
     * Sanitize and escape multi-line vCard values per RFC 6350 (SEC-17).
     */
    private function cleanMultiline($val)
    {
        $val = str_replace('\\', '\\\\', (string)$val);
        $val = str_replace(["\r\n", "\r", "\n"], "\\n", $val);
        return $val;
    }

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->sData = "BEGIN:VCARD\n";
        $this->sData .= "VERSION:4.0\n";
    }

    /**
     * The name of the person.
     *
     * @param string $sName
     *
     * @return self
     */
    public function name($sName)
    {
        $this->sData .= 'N:' . $this->cleanProp($sName) . "\n";
        return $this;
    }

    /**
     * @param string $sAddress
     *
     * @return self
     */
    public function address($sAddress, $sCity, $sPostcode, $sState)
    {
        $this->sData .= 'ADR:;;' . $this->cleanProp($sAddress) . ';';
        $this->sData .= $this->cleanProp($sCity) . ';' . $this->cleanProp($sPostcode) . ';' . $this->cleanProp($sState) . "\n";
        return $this;
    }

    /**
     * @param string $sNickname
     *
     * @return self
     */
    public function nickName($sNickname)
    {
        $this->sData .= 'NICKNAME:' . $this->cleanProp($sNickname) . "\n";
        return $this;
    }

    /**
     * @param string $sMail
     *
     * @return self
     */
    public function email($sMail)
    {
        $this->sData .= 'EMAIL;TYPE=PREF,INTERNET:' . $this->cleanProp($sMail) . "\n";
        return $this;
    }

    /**
     * @param string $sVal
     *
     * @return self
     */
    public function workPhone($sVal)
    {
        $this->sData .= 'TEL;;TYPE=work:' . $this->cleanProp($sVal) . "\n";
        return $this;
    }

    /**
     * @param string $sVal
     *
     * @return self
     */
    public function homePhone($sVal)
    {
        $this->sData .= 'TEL;;TYPE=home:' . $this->cleanProp($sVal) . "\n";
        return $this;
    }
    
    /**
     * @param string $sVal
     *
     * @return self
     */
    public function cellPhone($sVal)
    {
        $this->sData .= 'TEL:' . $this->cleanProp($sVal) . "\n";
        return $this;
    }

    /**
     * @param string $sUrl
     *
     * @return self
     */
    public function url($sUrl)
    {
        $sUrl = $this->cleanProp($sUrl);
        $sUrl = (substr($sUrl, 0, 4) != 'http') ? 'http://'.$sUrl : $sUrl;
        $this->sData .= 'URL:' . $sUrl . "\n";
        return $this;
    }

    /**
     * A list of "tags" that can be used to describe the object represented by this vCard.
     *
     * @param string $sCategories
     *
     * @return self
     */
    public function categories($sCategories)
    {
        $this->sData .= 'CATEGORIES:' . $this->cleanProp($sCategories) . "\n";
        return $this;
    }

    /**
     * Photo (avatar).
     *
     * @param string $sImgUrl URL of the image.
     *
     * @return self
     *
     * @throws InvalidArgumentException If the image format is invalid.
     */
    public function photo($sImgUrl)
    {
        if($sImgUrl != NULL){
            $sImgUrl = $this->cleanProp($sImgUrl);
            $parsedPath = parse_url($sImgUrl, PHP_URL_PATH);
            $bIsImgExt = strtolower(pathinfo($parsedPath ?: $sImgUrl, PATHINFO_EXTENSION));

            if (in_array($bIsImgExt, ['jpeg', 'jpg', 'png', 'gif', 'webp'], true)) {
                $sExt = ($bIsImgExt === 'jpeg' || $bIsImgExt === 'jpg') ? 'JPEG' : strtoupper($bIsImgExt);
                $this->sData .= 'PHOTO;VALUE=URL;TYPE='.$sExt.':'.$sImgUrl."\n";
            } else {
                throw new InvalidArgumentException('Invalid format Image!');
            }

            return $this;
        }
    }

    /**
     * The role, occupation, or business category of the vCard object within an organization.
     *
     * @param string $sRole e.g., Executive
     *
     * @return self
     */
    public function role($sRole)
    {
        $this->sData .= 'ROLE:' . $this->cleanProp($sRole) . "\n";
        return $this;
    }

    /**
     * The organization / company.
     *
     * @param string $sOrg e.g., Google;GMail Team;Spam Detection Squad
     *
     * @return self
     */
    public function organization($sOrg)
    {
        $this->sData .= 'ORG:' . $this->cleanProp($sOrg) . "\n";
        return $this;
    }

    /**
     * The supplemental information or a comment that is associated with the vCard.
     *
     * @param string $sText
     *
     * @return self
     */
    public function note($sText)
    {
        $this->sData .= 'NOTE:' . $this->cleanMultiline($sText) . "\n";
        return $this;
    }

    /**
     *
     * @return self
     */
    public function create()
    {
        $this->sData .= 'END:VCARD';
        return $this;
    }

    /**
     * Get the vCard data.
     */
    public function get()
    {
        return $this->sData;
    }
}
