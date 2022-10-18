<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 22.07.2018
 * Time: 12:20
 */
namespace Delivery\Shipox\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;

class GeoClient extends AbstractHelper {
    protected $_logFile = 'shipox_geo.log';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->scopeConfig->getValue('api/geo/url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getRegion()
    {
        return $this->scopeConfig->getValue('api/geo/region', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return mixed
     */
    public function getGoogleApiKey() {
        return $this->scopeConfig->getValue('delivery_shipox/service/gmap_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $searchString
     * @param $city
     * @param $region
     * @return null
     */
    public function getLatLon($searchString, $city, $region) {
        if (isset($stringArray)) {
            $stringArray = implode("+", $searchString);
        }
        else {
            $stringArray = null;
        }
        $containedString = '';
        $city = trim($city);
        if (is_array($region)) {
            $region = $region['region'];
        }
        $region = trim($region);


//        foreach ($stringArray as $string) {
//            $string = trim($string);
//
//            if(!empty($string))
//                $containedString .= $string."+";
//        }

        $containedString .= $city . $stringArray;

        if(!empty($region)) {
            $containedString .= "+".$region;
        }

        $data = array(
            'address' => $containedString
        );

        $response = $this->sendRequest($data);

        if($region)
            return $response['geometry']['location'];

        return null;
    }

    /**
     * @param null $data
     * @return null
     * @internal param $url
     * @internal param $search
     */
    private function sendRequest($data = null)
    {
        $apiURL = $this->getUrl();

        $data['region'] = $this->getRegion();
        $data['key'] = $this->getGoogleApiKey();
        $data['language'] = 'en';

        $client = new \Zend_Http_Client($apiURL . http_build_query($data));

        try {
            $client->setMethod(\Zend_Http_Client::GET);
        } catch (\Zend_Http_Client_Exception $e) {
        }
        try {
            $client->setHeaders('Content-type', 'application/json');
        } catch (\Zend_Http_Client_Exception $e) {
        }
        try {
            $client->setHeaders('Accept', 'application/json');
        } catch (\Zend_Http_Client_Exception $e) {
        }

        try {
            $response = $client->request();

            if ($response->isSuccessful()) {
                $responseBody = json_decode($response->getBody(), true);

                if($responseBody['status'] == "OK") {
                    return $responseBody['results'][0];
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } catch (\Exception $e) {
            return null;
        }
    }

}
