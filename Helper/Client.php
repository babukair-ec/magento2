<?php
/**
 * @category   Shipox - Carrier Type
 * @package    Delivery_Shipox
 * @author     Shipox Delivery - Furkat Djamolov
 * @website    www.shipox.com
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 **/

namespace Delivery\Shipox\Helper;

use \Delivery\Shipox\Helper\ShipoxLogger;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\Serialize\SerializerInterface;

class Client extends AbstractHelper
{
    protected $_logFile = 'shipox_api.log';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    protected $shipoxLogger;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    protected $httpClientFactory;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    protected $_helperClient;
    protected $_configWriter;
    protected $_timezone;
    protected $_directDb;

    private $_countryId = 229;
    private $_countryCode = 'AE';
    private $_countryName = 'United Arab Emirates';
    private $_currency = 'AED';
    private $_intAvailability = false;
    private $_host = 'my.shipox.com';
    private $_decimalPoint = 2;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Psr\Log\LoggerInterface $logger,
        SerializerInterface $serializer,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Config\Model\ResourceModel\Config\Data\CollectionFactory $directDb,
        ShipoxLogger $shipoxLogger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->httpClientFactory = $httpClientFactory;
        $this->_configWriter = $configWriter;
        $this->_timezone = $timezone;
        $this->_directDb = $directDb;
        $this->shipoxLogger = $shipoxLogger;
    }

    public function authenticate($data) {
        $data['remember_me'] = true;
        $response = $this->sendRequest($this->scopeConfig->getValue('api/destination/authenticate', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), 'post', $data, true);
        return $response;
    }

    /**
     * @param $url
     * @param string $requestMethod
     * @param null $data
     * @param bool $getToken
     * @return null
     */
    private function sendRequest($url, $requestMethod = 'get', $data = null, $getToken = false)
    {
        $this->shipoxLogger->setFileName($this->_logFile);

        $apiURL = $this->getAPIBaseURl();
        $client = $this->httpClientFactory->create();

        try {
            $client->setUri($apiURL . $url);
        } catch (\Zend_Http_Client_Exception $e) {

        }

        switch ($requestMethod) {
            case 'get':
                try {
                    $client->setMethod(\Zend_Http_Client::GET);
                } catch (\Zend_Http_Client_Exception $e) {

                }
                break;
            case 'post':
                try {
                    $client->setMethod(\Zend_Http_Client::POST);
                } catch (\Zend_Http_Client_Exception $e) {
                }
                break;
            case 'put':
                try {
                    $client->setMethod(\Zend_Http_Client::PUT);
                } catch (\Zend_Http_Client_Exception $e) {
                }
                break;
            case 'delete':
                try {
                    $client->setMethod(\Zend_Http_Client::DELETE);
                } catch (\Zend_Http_Client_Exception $e) {
                }
                break;
        }

        $json = $data ? json_encode($data) : '';

        $this->shipoxLogger->message($apiURL . $url);
        $this->shipoxLogger->message($data);

        try {
            $client->setConfig(array('timeout' => 30));
        } catch (\Zend_Http_Client_Exception $e) {
        }
        $client->setRawData($json, 'application/json');

        try {
            $client->setHeaders('Content-type', 'application/json');
            $client->setHeaders('x-app-type', 'magento-extension-v2');
        } catch (\Zend_Http_Client_Exception $e) {
        }

        if (!$getToken) {

            $collection = $this->_directDb->create();
            $collection->addScopeFilter('default', 0, 'delivery_shipox');
            $newTokenData = $collection->getData();

            if (isset($newTokenData) && is_array($newTokenData) && count($newTokenData) > 0 &&  array_key_exists('value', $newTokenData[0])) {
                $token = $newTokenData[0]['value'];
            }
            else {
                $token = $this->scopeConfig->getValue('delivery_shipox/auth/jwt_token', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            }

            if ($token) {
                try {
                    $client->setHeaders('Authorization','Bearer ' . $token);
                } catch (\Zend_Http_Client_Exception $e) {
                }
                try {
                    $client->setHeaders('Accept','application/json');
                } catch (\Zend_Http_Client_Exception $e) {
                }
            } else {
                try {
                    $client->setHeaders('Accept','*/*');
                } catch (\Zend_Http_Client_Exception $e) {
                }
            }
        } else {
            try {
                $client->setHeaders('Accept','*/*');
            }
            catch (\Zend_Http_Client_Exception $e) {

            }
        }

        try {
            $response = $client->request();

            if ($response->isSuccessful()) {
                $this->shipoxLogger->message(json_decode($response->getBody()));
                return json_decode($response->getBody(), true);
            } else  {
                $this->shipoxLogger->message(json_decode($response->getBody()));
                return json_decode($response->getBody(), true);
            }
        } catch (\Exception $e) {
            $this->shipoxLogger->message($e);
            return null;
        }
    }

    /**
     * Only Live api url will be responded
     * @return string
     */
    public function getAPIBaseURl()
    {
        return $this->scopeConfig->getValue('api/myconfig/live', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Check Token Expired or not
     */
    public function checkToken() {
        $date = $this->_timezone->date()->getTimestamp();
        $tokenDate = $this->scopeConfig->getValue('delivery_shipox/auth/token_time', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $timeDiff = $date - $tokenDate;
        $hours      = floor($timeDiff /3600);
        if ($hours >= 23) {
            $userCred = array (
                'username' => $this->scopeConfig->getValue('general/auth/user_name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'password' => $this->scopeConfig->getValue('general/auth/password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            );

            $authData = $this->authenticate($userCred);

            if ($authData['status'] == 'success') {
                $data = $authData['data'];
                $this->_configWriter->save('delivery_shipox/auth/jwt_token', $data['id_token']);
                $this->_configWriter->save('delivery_shipox/auth/token_time',$this->_timezone->date()->getTimestamp());
            }
        }
        $marketplaceSettings = $this->getMarketplaceSettings();

        if (!isset($marketplaceSettings['country_id'])) {
            $this->updateCustomerMarketplace();
        };
    }

    /**
     * @return null
     */
    public function getCountryList()
    {
        $this->checkToken();
        $response = $this->sendRequest('/api/v1/country/list');
        return isset($response['data']) ? $response['data'] : $response;
    }

    public function getCityList()
    {
        $this->checkToken();
        $response = $this->sendRequest('/api/v2/cities');
        return isset($response['data']) ? $response['data'] : $response;
    }

    public function getCourierTypeList()
    {
        $this->checkToken();
        $response = $this->sendRequest('/api/v1/service_types');
        return isset($response['data']) ? $response['data'] : $response;
    }

    public function getPackageMenu($query = '')
    {
        $response = $this->sendRequest($this->scopeConfig->getValue('api/destination/package_menu', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) . $query);
        return isset($response['data']) ? $response['data'] : $response;
    }

    /**
     * @param $city
     * @return null
     */
    public function isValidCity($city)
    {
        if(isset($city['region'])) {
            $city_name = $city['region'];
            $data = array(
                'city_name' => $city_name
            );
        } else {
            $data = array(
                'city_name' => $city
            );
        }

        $response = $this->sendRequest($this->scopeConfig->getValue('api/destination/city_by_name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) . "?" . http_build_query($data), 'get');
        return $response;
    }

    /**
     * @param $data
     * @return null
     * @internal param $query
     */
    public function getPackagesPrices($data, $version = 0)
    {
        if ($data) {
            if($version == 1) {
                $queried_data = http_build_query($data);
                $response = $this->sendRequest($this->scopeConfig->getValue('api/destination/packages_prices_v2', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) . "?" . preg_replace('/\[\d+\]/', '', urldecode($queried_data)));
                return $response;
            } else {
                $response = $this->sendRequest($this->scopeConfig->getValue('api/destination/packages_prices', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) . "?" . http_build_query($data));
                return isset($response['data']) ? $response['data'] : $response;
            }
        }

        return null;
    }

    /**
     * @param $data
     * @return null
     */
    public function postCreateOrder($data, $version = 0)
    {
        $this->checkToken();
        if($version == 1) {
            $response = $this->sendRequest($this->scopeConfig->getValue('api/destination/order_v2', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), 'post', $data);
        } else {
            $response = $this->sendRequest($this->scopeConfig->getValue('api/destination/order', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), 'post', $data);
        }
        return $response;
    }

    /**
     * @param string $orderNumber
     * @return null
     */
    public function getOrderItem($orderNumber = '')
    {
        $response = $this->sendRequest($this->scopeConfig->getValue('api/destination/order_item', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) . "/" . $orderNumber);
        return $response;
    }

    /**
     * @param $orderId
     * @param $data
     * @return null
     */
    public function updateStatus($orderId, $data)
    {
        $this->checkToken();
        $response = $this->sendRequest(str_replace("{id}", $orderId, $this->scopeConfig->getValue('api/destination/status_update', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)), 'put', $data);
        return $response;
    }

    /**
     * @param $orderId
     * @return null
     * @comment get airway bill link PDF
     */
    public function getAirwayBill($orderId) {
        $response = $this->sendRequest(str_replace("{id}", $orderId, $this->scopeConfig->getValue('api/destination/get_airwaybill', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)));
        return $response;
    }

    /**
     * @param $config_path
     * @return mixed
     * @comment get magento scope configuration value
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return |null
     * @comment get customer marketplace
     */
    public function getCustomerMarketplace()
    {
        $response = $this->sendRequest($this->scopeConfig->getValue('api/destination/marketplace', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), 'get');
        return $response;
    }

    /**
     * @param $data
     * @return null |null
     * @comment Get location by address
     */
    public function getLocationByAddress($data)
    {
        $this->checkToken();
        $response = $this->sendRequest($this->scopeConfig->getValue('api/destination/get_location_by_address', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) . "?" . http_build_query($data));
        return $response;
    }

    public function updateCustomerMarketplace() {
        $response = $this->getCustomerMarketplace();
        if ($response['status'] == 'success') {
            $marketplace = $response['data'];

            $this->_configWriter->save('delivery_shipox/merchant/currency', $marketplace['currency']);
            $this->_configWriter->save('delivery_shipox/merchant/custom', $marketplace['custom']);
            $this->_configWriter->save('delivery_shipox/merchant/company_name', $marketplace['name']);
            $this->_configWriter->save('delivery_shipox/merchant/decimal_point', (isset($marketplace['setting']['settings']['decimalPoint'])) ? $marketplace['setting']['settings']['decimalPoint'] : $this->_decimalPoint);
            $this->_configWriter->save('delivery_shipox/merchant/disable_international_orders', $marketplace['setting']['settings']['disableInternationalOrders']);
            $this->_configWriter->save('delivery_shipox/merchant/host', isset($marketplace['setting']['settings']['customerDomain']) ? $marketplace['setting']['settings']['customerDomain'] : 'my.shipox.com');
            $this->_configWriter->save('delivery_shipox/merchant/country_id', $marketplace['country']['id']);
            $this->_configWriter->save('delivery_shipox/merchant/country_code', $marketplace['country']['description']);
            $this->_configWriter->save('delivery_shipox/merchant/name', $marketplace['country']['name']);
        }
    }

    /**
     * @return int
     */
    public function getCountryId()
    {
        $marketplaceSettings = $this->getMarketplaceSettings();
        return isset($marketplaceSettings['country_id']) ? $marketplaceSettings['country_id'] : $this->_countryId;
    }

    /**
     * @return string
     */
    public function getCountryCode()
    {
        $marketplaceSettings = $this->getMarketplaceSettings();
        return isset($marketplaceSettings['country_code']) ? $marketplaceSettings['country_code'] : $this->_countryCode;
    }

    /**
     * @return string
     */
    public function getMarketplaceHost()
    {
        $marketplaceSettings = $this->getMarketplaceSettings();
        return isset($marketplaceSettings['host']) ? $marketplaceSettings['host'] : $this->_host;
    }

    /**
     * @return string
     */
    public function getCountryName()
    {
        $marketplaceSettings = $this->getMarketplaceSettings();
        return isset($marketplaceSettings['country']['name']) ? $marketplaceSettings['country']['name'] : $this->_countryName;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        $marketplaceSettings = $this->getMarketplaceSettings();
        return isset($marketplaceSettings['currency']) ? $marketplaceSettings['currency'] : $this->_currency;
    }

    /**
     * @return bool
     */
    public function getInternationalAvailability()
    {
        $marketplaceSettings = $this->getMarketplaceSettings();
        return isset($marketplaceSettings['disable_international_orders']) ? !$marketplaceSettings['disable_international_orders'] : $this->_intAvailability;
    }

    /**
     * @return Shipox merchant configs
     */
    public function getMarketplaceSettings()
    {
        return $this->getConfig('delivery_shipox/merchant');
    }

}