<?php 

namespace Phpdev;

class WsseAuthHeader extends \SoapHeader
{
    private $wssNs = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    private $wsuNs = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd';
    private $passType = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText';
    private $nonceType = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary';

    function __construct($username,$password )
    {
        $created = gmdate('Y-m-d\TH:i:s\Z');
        $nonce = mt_rand();
        $encodedNonce = base64_encode(pack('H*', sha1(pack('H*', $nonce) . pack('a*', $created) . pack('a*', $password))));
        $root = new \SimpleXMLElement('<root/>');
        $security = $root->addChild('wsse:Security', null, $this->wssNs);
        $usernameToken = $security->addChild('wsse:UsernameToken', null, $this->wssNs);
        $usernameToken->addChild('wsse:Username', $username, $this->wssNs);
        $passNode = $usernameToken->addChild('wsse:Password', htmlspecialchars($password, ENT_XML1, 'UTF-8'), $this->wssNs);
        $passNode->addAttribute('Type', $this->passType);
        $nonceNode = $usernameToken->addChild('wsse:Nonce', $encodedNonce, $this->wssNs);
        $nonceNode->addAttribute('EncodingType', $this->nonceType);
        $usernameToken->addChild('wsu:Created', $created, $this->wsuNs);
        $root->registerXPathNamespace('wsse', $this->wssNs);
        $full = $root->xpath('/root/wsse:Security');
        $auth = $full[0]->asXML();
        parent::__construct($this->wssNs, 'Security', new \SoapVar($auth, XSD_ANYXML), true);

    }
}

class HesapEkstreRequest
{
  public $BaslangicTarihi;
  public $BitisTarihi;
  public $BagliMusteriNumarasi;
}

Class Halkbank{
    public $username = "";
    public $pasword = "";
    public $customerno = "";
    public $client = "";
    
    function __construct($username, $password, $customerno)
    {
        $this->username       = $username;
        $this->password       = $password;
        $this->customerno     = $customerno;
        $wsse_header = new WsseAuthHeader($username, $password); 
        $this->client = new \SoapClient("https://webservice.halkbank.com.tr/HesapEkstreOrtakWS/HesapEkstreOrtak.svc?wsdl");
        $this->client->__setSoapHeaders(array($wsse_header));
    }

    
    function hesap_hareketleri($date1,$date2,$type){
        $request = new HesapEkstreRequest();
        $request->BaslangicTarihi=$date1;
        $request->BitisTarihi=$date2;
        if ( $type=='bagli_musteri'){
            $request->BagliMusteriNumarasi=$this->customerno;
        }
        $requestParams = array('request' => $request);
        try {
            if ( $type=='bagli_musteri'){
                $response = $this->client->BagliMusteriEkstreSorgulama($requestParams);
            } else {
                $response = $this->client->EkstreSorgulama($requestParams);

            }
            return json_encode([
                'statu'=>true,
                'response' => $response
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'statu'=>false,
                'response' => $$this->client->__getLastRequest()
            ]);
        }
    }

}


?>