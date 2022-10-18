<?php
/**
 * Created by Shipox.
 * User: Furkat
 * Date: 22.07.2018
 * Time: 11:46
 */

namespace Delivery\Shipox\Helper;
use \Delivery\Shipox\Helper\ShipoxLogger;
use \Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Setup\Controller\Session;
use Magento\Quote\Model\QuoteRepository;
use Magento\TestFramework\Event\Magento;

class Data extends AbstractHelper
{
    protected $_code = 'delivery';
    protected $_logFile = 'shipox_data.log';
    protected $currencySymbol;
    protected $_localeCurrency;
    protected $clientHelper;
    protected $modelCourierType;
    protected $modelVehicleType;
    protected $modelStatusMapping;
    protected $modelTracking;
    protected $modelShipox;
    protected $iterator;
    protected $quoteRepository;
    protected $_countryFactory;
    protected $shipoxLogger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    protected $geoClient;
    protected $_configWriter;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\Currency $currencySymbol,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\Model\ResourceModel\Iterator $_iterator,
        \Delivery\Shipox\Helper\Client $clientHelper,
        \Delivery\Shipox\Helper\Dbclient $clientDbHelper,
        \Delivery\Shipox\Helper\GeoClient $geo_Client,
        \Delivery\Shipox\Model\Couriertype $modelCouriertype,
        \Delivery\Shipox\Model\Vehicletype $modelVehicletype,
        \Delivery\Shipox\Model\Statusmapping $modelStatusmapping,
        \Delivery\Shipox\Model\Tracking $model_tracking,
        \Delivery\Shipox\Model\Shipox $model_shipox,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        QuoteRepository $quoteRepository,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        ShipoxLogger $shipoxLogger
    )
    {
        parent::__construct($context);
        $this->_scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->currencySymbol = $currencySymbol;
        $this->_localeCurrency = $localeCurrency;
        $this->clientHelper = $clientHelper;
        $this->geoClient = $geo_Client;
        $this->modelCourierType = $modelCouriertype;
        $this->modelVehicleType = $modelVehicletype;
        $this->modelStatusMapping = $modelStatusmapping;
        $this->clientDbHelper = $clientDbHelper;
        $this->modelTracking = $model_tracking;
        $this->modelShipox = $model_shipox;
        $this->iterator = $_iterator;
        $this->_configWriter = $configWriter;
        $this->quoteRepository = $quoteRepository;
        $this->_countryFactory = $countryFactory;
        $this->shipoxLogger = $shipoxLogger;
    }

    /**
     *  Get Shipox Website url according to Environment
     */
    public function getShipoxSiteURL()
    {
        if ($this->_scopeConfig->getValue('general/service/sandbox_flag', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 1) {
            return $this->_scopeConfig->getValue('tracking/myconfig/prelive_tracking_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }
        return 'https://' . $this->_scopeConfig->getValue('delivery_shipox/merchant/host', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
//        return $this->_scopeConfig->getValue('tracking/myconfig/live_tracking_url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $timestamp
     * @return string
     */
    public function setExpiredDate($timestamp)
    {
        $date = date('Y-m-d', strtotime('+1 year', $timestamp));
        return $date;
    }

    /**
     * Get Company Name
     * @return mixed
     */
    public function getCompanyName() {
       if(!empty($this->_scopeConfig->getValue('carriers/' . $this->_code . '/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE))) {
           return $this->_scopeConfig->getValue('carriers/' . $this->_code . '/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
       }

        if(!empty($this->_scopeConfig->getValue('delivery_shipox/merchant/company_name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE))) {
            return $this->_scopeConfig->getValue('delivery_shipox/merchant/company_name', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        }

        return $this->_scopeConfig->getValue('delivery_shipox/service/title', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $weight
     * @return mixed
     */
    public function getPackageWeightForShipox($weight)
    {
        $menuOption = $this->_scopeConfig->getValue('carriers/' . $this->_code . '/default_weight', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        if ($menuOption == 0)
            return $weight;

        return $menuOption;
    }

    /**
     * @return bool
     */
    public function isAllowedSystemCurrency()
    {
        try {
            $currencyCode = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
            return ($currencyCode === $this->clientHelper->getCurrency()) ? true : false;
        } catch (NoSuchEntityException $e) {
        }
    }

    /**
     * @param $countryCode
     * @return int
     */
    public function getCountryShipoxId($countryCode)
    {
        $request = $this->clientHelper;
        $result = $request->getCountryList();
        $countryId = $this->getLocalCountryId();

        foreach ($result as $country) {
            if ($country['code'] == $countryCode) {
                $countryId = $country['id'];
                break;
            }
        }

        return $countryId;
    }

    /**
     * @return mixed
     */
    public function getLocalCountryId()
    {
        return $this->_scopeConfig->getValue('carriers/' . $this->_code . '/base_country_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $countryId
     * @return bool
     */
    public function isInternationalAvailable($countryId)
    {
        return $countryId == $this->clientHelper->getCountryId() ? true : false;
    }

    /**
     * @param $countryId
     * @return bool
     */
    public function isAllowedCountry($countryId)
    {
        $allowedCountryList = explode(",", $this->_scopeConfig->getValue('carriers/' . $this->_code . '/specificcountry', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));
        if (in_array($countryId, $allowedCountryList)) {
            return true;
        }
        return false;
    }

    /**
     * @param int $totalWeight
     * @param $countryId
     * @return int
     */
    public function getPackageType($totalWeight = 0, $countryId)
    {
        $request = $this->clientHelper;
        $marketplaceCountryId = $request->getCountryId();
        $requestPackage = array(
            "from_country_id" => (int)$marketplaceCountryId,
            "to_country_id" => $countryId,
        );

        $result = $request->getPackageMenu('?' . http_build_query($requestPackage));

        foreach ($result['list'] as $package) {
            if ($package["weight"] >= $totalWeight) {
                return $package["menu_id"];
            }
        }

        return 0;
    }

    /**
     * @param $countryId
     * @return bool
     */
    public function isDomestic($countryId)
    {
        $marketplaceCountryId = $this->clientHelper->getCountryId();
        return $countryId == $marketplaceCountryId ? true : false;
    }


    /**
     * @param $shippingAddress
     * @return array
     */
    public function getAddressLatLon($shippingAddress)
    {
        $responseArray = array();
        $shipoxApiClient = $this->clientHelper;
        $shipoxGeoClient = $this->geoClient;

        $city = $shippingAddress->getCity();
        $region = $shippingAddress->getRegion();

        if ($this->isUrgentEnabled()) {

            $street = $shippingAddress->getStreet();
            $geoLatLon = $shipoxGeoClient->getLatLon($street, $city, $region);

            if ($geoLatLon) {
                $responseArray['lat'] = $geoLatLon['lat'];
                $responseArray['lon'] = $geoLatLon['lng'];
            }

        }

        if (empty($responseArray)) {
            $shipoxCity = $shipoxApiClient->isValidCity($this->getStateRegion($shippingAddress));

            if ($shipoxCity['status'] == 'success') {
                $responseArray['lat'] = $shipoxCity['data']['latitude'];
                $responseArray['lon'] = $shipoxCity['data']['longitude'];
            } else {
                $geoLatLon = $shipoxGeoClient->getLatLon(null, $city, $region);

                if ($geoLatLon) {
                    $responseArray['lat'] = $geoLatLon['lat'];
                    $responseArray['lon'] = $geoLatLon['lng'];
                }
            }
        }

        return $responseArray;
    }

    /**
     * @param $shippingAddress
     * @param bool $isArray
     * @return null
     */
    function getAddressLocation($shippingAddress, $isArray = false)
    {
//        $this->shipoxLogger->setFileName($this->_logFile);

        $shipoxApiClient = $this->clientHelper;
        $countries = $shipoxApiClient->getCountryList();

        if($isArray) {
            $countryCode = $shippingAddress['countryCode'];
            $city = $shippingAddress['city'];
            $region = $shippingAddress['region'];
            $street = $shippingAddress['street'];
        } else {
            $countryCode = $shippingAddress->getCountryId();
            $city = $shippingAddress->getCity();
            $region = $shippingAddress->getRegion();
            $street = $shippingAddress->getStreet();
        }

        $countryIndex = array_search($countryCode, $countries);
        $domestic = $this->isDomestic($countries[$countryIndex]['id']);
//        $country = $this->_countryFactory->create()->loadByCode($countryCode);

        $responseArray = array();

        if ($this->isUrgentEnabled()) {
            $geoLatLon = $this->geoClient->getLatLon($street, $city, $region);

            if ($geoLatLon) {
                $responseArray['lat'] = $geoLatLon['lat'];
                $responseArray['lon'] = $geoLatLon['lng'];
            }
        }

        if (empty($responseArray)) {
            $shippingProvince = $region;
            if (is_array($region) && isset($region['region'])) {
                $shippingProvince = $region['region'];
            }

            $request = array(
                'address' => is_array($street) ? implode(",", (array)$street) : $street,
                'city' => $city,
                'country' => $countryCode,
                'provinceOrState' => $shippingProvince,
                'domestic' => $domestic,
            );

            $location = $this->clientHelper->getLocationByAddress($request);

            if ($location['status'] == 'success') {
                if (!is_null($location['data']['lat']) && !is_null($location['data']['lon'])) {
                    $responseArray['lat'] = $location['data']['lat'];
                    $responseArray['lon'] = $location['data']['lon'];
                }

            }
        }

        return $responseArray;
    }

    /**
     * @return bool
     */
    public function isUrgentEnabled()
    {
        if (strpos($this->_scopeConfig->getValue('carriers/' . $this->_code . '/carrier_options', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), 'bullet') === false)
            return false;

        return true;
    }

    /**
     * @param $shippingMethod
     * @return int|null
     */
    public function getPackageIdFromString($shippingMethod)
    {
        $items = explode("-", $shippingMethod);
        if (is_array($items))
            return intval($items[0]);

        return null;
    }

    /**
     * @param $shippingAddress
     * @return mixed
     */
    public function getStateRegion($shippingAddress)
    {
        $stateRegion = $shippingAddress->getCity();
        if (!is_null($shippingAddress->getRegion())) {
            $stateRegion = $shippingAddress->getRegion();
        }

        return $stateRegion;
    }

    /**
     * @param $menuId
     * @param $customerLatLonAddress
     * @param null $additionalData
     * @param bool $isFirstItem
     * @return null
     */
    public function getPackagesPricesList($menuId, $customerLatLonAddress, $additionalData = null, $isFirstItem = false, $quote = null)
    {
        $shipoxApiClient = $this->clientHelper;
        $merchantLatLonAddress = explode(",", $this->_scopeConfig->getValue('general/merchant/lat_lon', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));

        $countries = $shipoxApiClient->getCountryList();
        $countryIndex = array_search($additionalData['country_code'], $countries);

        if (!empty($merchantLatLonAddress)) {

            $courierTypes = (is_array($additionalData) && array_key_exists('courier_type', $additionalData)) ? $additionalData['courier_type'] : $this->scopeConfig->getValue('carriers/' . $this->_code . '/carrier_options', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $api_version = $this->_scopeConfig->getValue('general/service/api_version', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $strUpperCouriers = strtoupper($courierTypes);

            if ($api_version == 1) {

                $items = $quote->getAllItems();

                $weight = 0;
                foreach ($items as $item) {
                    $weight += ($item->getWeight() * $item->getQty());
                }

                $serviceTypes = explode(',', $strUpperCouriers);
//                $serviceTypes = str_replace(',', ' ', $strUpperCouriers);

                $request = array(
                    "from_latitude" => $merchantLatLonAddress[0],
                    "to_latitude" => (string)$customerLatLonAddress['lat'],
                    "from_longitude" => $merchantLatLonAddress[1],
                    "to_longitude" => (string)$customerLatLonAddress['lon'],
                    "from_country_id" => $this->_scopeConfig->getValue('general/merchant/country_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    "to_country_id" => (isset($countries[$countryIndex]['id'])) ? $countries[$countryIndex]['id'] : 229,
                    "service_types" => $serviceTypes,
                    'dimensions.domestic' => true,
                    'dimensions.unit' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/unit_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'dimensions.weight' => ($weight != 0) ? $weight : $this->_scopeConfig->getValue('carriers/' . $this->_code . '/default_weight', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'dimensions.width' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/width', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'dimensions.length' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/length', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'dimensions.height' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/height', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                );

                $response = $shipoxApiClient->getPackagesPrices($request, $api_version);

                if (isset($response['data']['list']) && $response['data']['list']) {
                    if ($isFirstItem) {
                        foreach ($response['data']['list'] as $item) {
                            return $item['courier_type']['type'];
                        }
                        return null;
                    }
                    return $response['data']['list'];
                }
            } else {
                $request = array(
                    "service" => $this->_scopeConfig->getValue('api/myconfig/service', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    "from_lat" => $merchantLatLonAddress[0],
                    "to_lat" => (string)$customerLatLonAddress['lat'],
                    "from_lon" => $merchantLatLonAddress[1],
                    "to_lon" => (string)$customerLatLonAddress['lon'],
                    "menu_id" => $menuId,
                    "courier_type" => $strUpperCouriers
                );

                if (is_array($additionalData) && array_key_exists('vehicle_type', $additionalData))
                    $request['vehicle_type'] = $additionalData['vehicle_type'];

                $response = $shipoxApiClient->getPackagesPrices($request, $api_version);

                if (isset($response['list']) && $response['list']) {
                    if ($isFirstItem) {
                        foreach ($response['list'] as $item) {
                            return $item['packages'][0]['id'];
                        }
                        return null;
                    }
                    return $response['list'];
                }
            }

        }
        return null;
    }


    /**
     * @param $customerLatLonAddress
     * @param null $additionalData
     * @param false $isFirstItem
     * @param null $quote
     * @return |null
     */
    public function getPackagePriceListNewModel($customerLatLonAddress, $additionalData = null, $isFirstItem = false, $quote = null)
    {
        $shipoxApiClient = $this->clientHelper;
        $merchantLatLonAddress = explode(",", $this->_scopeConfig->getValue('general/merchant/lat_lon', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));

//        $countries = $shipoxApiClient->getCountryList();
//        $countryIndex = array_search($additionalData['country_code'], $countries);

        $isDomestic = isset($additionalData['is_domestic']) ? $additionalData['is_domestic'] : false;
        $toCountryId = isset($additionalData['to_country_id']) ? $additionalData['to_country_id'] : 229;

        if (!empty($merchantLatLonAddress)) {
            $courierTypes = (is_array($additionalData) && array_key_exists('courier_type', $additionalData)) ? $additionalData['courier_type'] : $this->scopeConfig->getValue('carriers/' . $this->_code . '/carrier_options', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
//            $api_version = $this->_scopeConfig->getValue('general/service/api_version', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

            $strUpperCouriers = strtoupper($courierTypes);
            $items = $quote->getAllItems();

            $weight = 0;
            foreach ($items as $item) {
                $weight += ($item->getWeight() * $item->getQty());
            }

            $serviceTypes = explode(',', $strUpperCouriers);

            $request = array(
                "from_latitude" => $merchantLatLonAddress[0],
                "to_latitude" => (string)$customerLatLonAddress['lat'],
                "from_longitude" => $merchantLatLonAddress[1],
                "to_longitude" => (string)$customerLatLonAddress['lon'],
                "from_country_id" => $this->_scopeConfig->getValue('general/merchant/country_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                "to_country_id" => $toCountryId,
                "service_types" => $serviceTypes,
                'dimensions.domestic' => $isDomestic,
                'dimensions.unit' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/unit_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'dimensions.weight' => ($weight != 0) ? $weight : $this->_scopeConfig->getValue('carriers/' . $this->_code . '/default_weight', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'dimensions.width' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/width', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'dimensions.length' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/length', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'dimensions.height' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/height', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            );

            $response = $shipoxApiClient->getPackagesPrices($request, 1);

            if (isset($response['data']['list']) && $response['data']['list']) {
                if ($isFirstItem) {
                    foreach ($response['data']['list'] as $item) {
                        return $item['courier_type']['type'];
                    }
                    return null;
                }
                return $response['data']['list'];
            }

        }
        return null;
    }

    /**
     * @param $shippingAddress
     * @param bool $isArray
     * @return string
     */
    public function getFullDestination($shippingAddress, $isArray = false)
    {
        $response = '';
        if($isArray) {
            $countryCode = $shippingAddress['countryCode'];
            $city = $shippingAddress['city'];
            $region = $shippingAddress['region'];
            $street = $shippingAddress['street'];
            $response .= $countryCode;
            $state = array();
        } else {
            $countryCode = $shippingAddress->getCountryId();
            $city = $shippingAddress->getCity();
            $region = $shippingAddress->getRegion();
            $street = $shippingAddress->getStreet();
            $state = $this->getStateRegion($shippingAddress);
            $response .= !empty($shippingAddress->getCountry()) ? $shippingAddress->getCountry() : $countryCode;
        }

        if (isset($state["region"])) {
            $response .= " " .$state["region"];
        }

        $shippingProvince = $region;
        if (is_array($shippingProvince) && isset($shippingProvince['region'])) {
            $shippingProvince = $shippingProvince['region'];
        }

        $response .= " " . $shippingProvince . " " . $city;

        return $response . " " . (is_array($street) ? implode(" ", (array)$street) : $street);
    }

    /**
     * @param $shippingMethod
     * @param $modelType
     * @return null
     */
    public function getModelItemIfExists($shippingMethod, $modelType)
    {

        $model = null;
        switch ($modelType) {
            case 'courier':
                $model = $this->modelCourierType;
                break;
            case 'vehicle':
                $model = $this->modelVehicleType;
                break;
        }

        if ($model) {
            $items = $model->toValueArray();

            foreach ($items as $item) {
                if (strpos($shippingMethod, $item) !== false) {
                    return $item;
                    break;
                }
            }
        }
        return null;
    }

    /**
     * @param $destination
     * @return null
     */
    public function extractLatLonArrayFromString($destination)
    {
        $response = null;
        $array = explode(",", $destination);

        if (count($array) == 2) {
            $response['lat'] = $array[0];
            $response['lon'] = $array[1];
        }

        return $response;
    }

    /**
     * @param $order
     * @param $packageId
     * @param $customerAddressLatLon
     * @param $shipoxOrderDetails
     * @return array
     */
    public function pushShipoxOrder($order, $packageId, $customerAddressLatLon, $shipoxOrderDetails)
    {
        $responseData = $this->shipoxOrderV2($order, $packageId, $customerAddressLatLon, $shipoxOrderDetails);
        return $responseData;
    }


    /**
     * @param $order
     * @param $shipoxPackage
     * @param $customerAddressLatLon
     * @param $shipoxOrderDetails
     * @return array
     */
    public function pushShipoxOrderV2($order, $shipoxPackage, $customerAddressLatLon, $shipoxOrderDetails)
    {
        $shipoxApiClient = $this->clientHelper;

        $responseData = array();
        $requestData = array();

        $merchantLatLonAddress = explode(",", $this->_scopeConfig->getValue('general/merchant/lat_lon', \Magento\Store\Model\ScopeInterface::SCOPE_STORE));

        $shippingAddress = $order->getShippingAddress();
//        $shippingMethod = $order->getShippingMethod();

//        $countries = $shipoxApiClient->getCountryList();
//        $countryCode = $order->getShippingAddress()->getCountryId();
//        $countryIndex = array_search($countryCode, $countries);

//        $cities = $shipoxApiClient->getCityList();
//        $cityName = $shippingAddress->getCity();
//        $cityIndex = array_search($cityName, $cities);
//        $default_weight = $this->_scopeConfig->getValue('carriers/' . $this->_code . '/default_weight', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
//        if (!empty($order->getWeight())) {
//            $default_weight = $order->getWeight();
//        }

        $items = $order->getAllItems();

        $weight = 0;
        foreach ($items as $item) {
            $weight += ($item->getWeight() * $item->getQtyOrdered());
        }

        $default_weight = ($weight != 0) ? $weight : $this->_scopeConfig->getValue('carriers/' . $this->_code . '/default_weight', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $paymentType = $order->getPayment()->getMethodInstance()->getCode();

        // Magento Order ID As a Reference
        $requestData['reference_id'] = $order->getIncrementId();

        //Charge Items COD
        $requestData['charge_items'] = array();

        switch ($paymentType) {
            case 'cashondelivery':
            case 'phoenix_cashondelivery':

                //Payer
                $requestData['payer'] = 'recipient';

                $requestData['charge_items'] = array(
                    array(
                        'charge_type' => "cod",
                        'charge' => $order->getGrandTotal(), // ($order->getBaseGrandTotal() - $order->getBaseShippingAmount())
                    ),
                    array(
                        'charge_type' => "service_custom",
                        'charge' => 0, //$this->getCustomService($this->_scopeConfig->getValue('carriers/' . $this->_code . '/allowed_payment_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), $order->getBaseShippingAmount())
                    )
                );
                break;
            default:
                $requestData['payer'] = 'sender';

                $requestData['charge_items'] = array(
                    array(
                        'charge_type' => "cod",
                        'charge' => 0
                    ),
                    array(
                        'charge_type' => "service_custom",
                        'charge' => 0, //$this->getCustomService($this->_scopeConfig->getValue('carriers/' . $this->_code . '/allowed_payment_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), $order->getBaseShippingAmount())
                    )
                );
                break;
        }

        $requestData['parcel_value'] = $order->getBaseGrandTotal() - $order->getShippingAmount();

        $requestData['sender_data'] = [
            'address_type' => $this->_scopeConfig->getValue('general/merchant/address_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'name' => $this->_scopeConfig->getValue('general/merchant/fullname', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'email' => $this->_scopeConfig->getValue('general/merchant/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'apartment' => $this->_scopeConfig->getValue('general/merchant/apartment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'building' => $this->_scopeConfig->getValue('general/merchant/building', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'street' => $this->_scopeConfig->getValue('general/merchant/street', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'city' => [
                'name' => $this->_scopeConfig->getValue('general/merchant/city_code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            ],
            'country' => [
                'id' => $this->_scopeConfig->getValue('general/merchant/country_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            ],
            'phone' => $this->_scopeConfig->getValue('general/merchant/phone_number', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'lat' => trim($merchantLatLonAddress[0]),
            'lon' => trim($merchantLatLonAddress[1]),
        ];

        $requestData['recipient_data'] = [
            'address_type' => 'residential',
            'name' => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getMiddlename() . ' ' . $shippingAddress->getLastname(),
            'email' => $shippingAddress->getEmail(),
            'apartment' => '',
            'building' => '',
            'street' => $shipoxOrderDetails['destination'],
            'city' => [
                'name' => $shippingAddress->getCity(),
            ],
            'country' => [
                'id' => $shipoxPackage['to_country'],
            ],
            'phone' => $shippingAddress->getTelephone(),
            'lat' => trim($customerAddressLatLon['lat']),
            'lon' => trim($customerAddressLatLon['lon']),
        ];

//        if (isset($shipoxOrderDetails['package_note'])) {
//            $requestData['note'] = $shipoxOrderDetails['package_note'];
//        }

        //Payment Type
        $requestData['payment_type'] = $this->_scopeConfig->getValue('carriers/' . $this->_code . '/allowed_payment_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $requestData['dimensions'] = array(
            'weight' => $default_weight,
            'width' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/width', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'length' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/length', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'height' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/height', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'unit' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/unit_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'domestic' => $shipoxPackage['domestic'] ? 'true' : 'false',
        );

        $requestData['package_type'] = array(
            'id' => $shipoxPackage['package_id'],

            'package_price' => array(
                'id' => $shipoxPackage['package_price_id'],
            ));

        $orderItems = '';
        foreach ($order->getAllItems() as $ord) {
            $orderItems .= $ord->getName() . ' ' . $ord->getSku() . ' - Qty: ' . $ord->getQtyOrdered() . ', ';
        }

        //Order items
        $requestData['note'] = $orderItems;
        //If Recipient Not Available
        $requestData['recipient_not_available'] = 'do_not_deliver';
        $requestData['force_create'] = 'true';
//        $requestData['piece_count'] = intval($order->getData('total_qty_ordered'));
        $requestData['fragile'] = 'true';

        $response = $shipoxApiClient->postCreateOrder($requestData, 1);

        if ($response['status'] == 'success') {
            $responseData = $response['data'];
        }

        return $responseData;
    }

    public function shipoxOrderV2($order, $packageId, $customerAddressLatLon, $shipoxOrderDetails)
    {
        $shipoxApiClient = $this->clientHelper;

        $responseData = array();
        $requestData = array();

        $shippingAddress = $order->getShippingAddress();
        $shippingMethod = $order->getShippingMethod();

        $countries = $shipoxApiClient->getCountryList();
        $countryCode = $order->getShippingAddress()->getCountryId();
        $countryIndex = array_search($countryCode, $countries);

        $cities = $shipoxApiClient->getCityList();
        $cityName = $shippingAddress->getCity();
        $cityIndex = array_search($cityName, $cities);
        $default_weight = $this->_scopeConfig->getValue('carriers/' . $this->_code . '/default_weight', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (!empty($order->getWeight())) {
            $default_weight = $order->getWeight();
        }

        $paymentType = $order->getPayment()->getMethodInstance()->getCode();

        // Magento Order ID As a Reference
        $requestData['reference_id'] = $order->getIncrementId();

        //Charge Items COD
        $requestData['charge_items'] = array();

        switch ($paymentType) {
            case 'cashondelivery':
            case 'phoenix_cashondelivery':

                //Payer
                $requestData['payer'] = 'recipient';

                $requestData['charge_items'] = array(
                    array(
                        'paid' => false,
                        'charge_type' => "cod",
                        'charge' => ($order->getBaseGrandTotal() - $order->getBaseShippingAmount())
                    ),
                    array(
                        'paid' => false,
                        'charge_type' => "service_custom",
                        'charge' => $this->getCustomService($this->_scopeConfig->getValue('carriers/' . $this->_code . '/allowed_payment_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), $order->getBaseShippingAmount())
                    )
                );
                break;
            default:
                $requestData['payer'] = 'sender';

                $requestData['charge_items'] = array(
                    array(
                        'paid' => false,
                        'charge_type' => "cod",
                        'charge' => 0
                    ),
                    array(
                        'paid' => false,
                        'charge_type' => "service_custom",
                        'charge' => $this->getCustomService($this->_scopeConfig->getValue('carriers/' . $this->_code . '/allowed_payment_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE), $order->getBaseShippingAmount())
                    )
                );
                break;
        }

        $requestData['parcel_value'] = $order->getBaseGrandTotal() - $order->getShippingAmount();

        $requestData['sender_data'] = [
            'address_type' => $this->_scopeConfig->getValue('general/merchant/address_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'name' => $this->_scopeConfig->getValue('general/merchant/fullname', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'email' => $this->_scopeConfig->getValue('general/merchant/email', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'apartment' => $this->_scopeConfig->getValue('general/merchant/apartment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'building' => $this->_scopeConfig->getValue('general/merchant/building', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'street' => $this->_scopeConfig->getValue('general/merchant/street', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'city' => [
                'code' => $this->_scopeConfig->getValue('general/merchant/city_code', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            ],
            'country' => [
                'id' => $this->_scopeConfig->getValue('general/merchant/country_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            ],
            'phone' => $this->_scopeConfig->getValue('general/merchant/phone_number', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
        ];

        $requestData['recipient_data'] = [
            'address_type' => 'residential',
            'name' => $shippingAddress->getFirstname() . ' ' . $shippingAddress->getMiddlename() . ' ' . $shippingAddress->getLastname(),
            'email' => $shippingAddress->getEmail(),
            'apartment' => '',
            'building' => '',
            'street' => $shipoxOrderDetails['destination'],
            'city' => [
                'code' => strtolower($shippingAddress->getCity()),
            ],
            'country' => [
                'id' => (isset($countries[$countryIndex]['id'])) ? $countries[$countryIndex]['id'] : '',
            ],
            'phone' => $shippingAddress->getTelephone(),
        ];

//        if (isset($shipoxOrderDetails['package_note'])) {
//            $requestData['note'] = $shipoxOrderDetails['package_note'];
//        }

        //Payment Type
        $requestData['payment_type'] = $this->_scopeConfig->getValue('carriers/' . $this->_code . '/allowed_payment_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $requestData['dimensions'] = array(
            'weight' => $default_weight,
            'width' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/width', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'length' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/length', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'height' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/height', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'unit' => $this->_scopeConfig->getValue('carriers/' . $this->_code . '/unit_type', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
            'domestic' => true,
        );
        $requestData['package_type'] = array(
            'courier_type' => $packageId
        );

        $orderItems = '';
        foreach ($order->getAllItems() as $ord) {
            $orderItems .= $ord->getName() . ' ' . $ord->getSku() . ' - Qty: ' . $ord->getQtyOrdered() . ', ';
//                'Price'         => $ord->getPrice(),
        }


        //Order items
        $requestData['note'] = $orderItems;
        //If Recipient Not Available
        $requestData['recipient_not_available'] = 'do_not_deliver';
        $requestData['force_create'] = true;
        $requestData['piece_count'] = intval($order->getData('total_qty_ordered'));
        $requestData['fragile'] = true;

        $response = $shipoxApiClient->postCreateOrder($requestData, 1);

        if ($response['status'] == 'success') {
            $responseData = $response['data'];
        }

        return $responseData;
    }

    /**
     * @param $paymentOption
     * @param $price
     * @return int
     */
    public function getCustomService($paymentOption, $price)
    {
        if ($paymentOption == 'credit_balance')
            return 0;

        return $price;
    }

    /**
     * @param $order
     * @return array
     */
    public function generateProductQuantityArray($order)
    {
        $itemsQuantity = array();

        $items = $order->getAllItems();

        foreach ($items as $item) {
            if (!$item->getQtyToShip()) {
                continue;
            }

            if ($item->getIsVirtual()) {
                continue;
            }

            $itemId = $item->getId();
            $itemsQuantity[$itemId] = $item->getQtyOrdered();
        }

        return $itemsQuantity;
    }

    /**
     * @param $order
     * @return array
     */

    public function getProperPackagesForOrder($order)
    {

        $responseArray = array(
            'status' => false,
            'message' => '',
            'data' => null
        );

        if (!$this->isAllowedSystemCurrency()) {
            $responseArray['message'] = "Base Currency is not proper for Shipox";
        }

        $shippingAddress = $order->getShippingAddress();
        $countryId = $this->getCountryShipoxId($shippingAddress->getCountry());

        if (!$this->isInternationalAvailable($countryId)) {
            $responseArray['message'] = "International Delivery is not available";
        }

        if ($this->isAllowedCountry($countryId)) {

            $packageWeight = $this->getPackageWeightForShipox($order->getWeight());
            $menuId = $this->getPackageType($packageWeight, $countryId);
            $customerLatLonAddress = $this->getAddressLocation($shippingAddress);
            $quoteId = $order->getQuoteId();
            $quote = $this->quoteRepository->get($quoteId);

            if (!empty($customerLatLonAddress)) {
                $packagePrices = $this->getPackagesPricesList($menuId, $customerLatLonAddress, false, $quote);

                if ($packagePrices) {

                    $priceArr = array();
                    foreach ($packagePrices as $listItem) {
                        $packages = $listItem['packages'];
                        $name = $listItem['name'];
                        $vehicle_type = $listItem['vehicle_type'];

                        foreach ($packages as $packageItem) {
                            $label = $packageItem['delivery_label'];
                            $price = $packageItem['price']['total'];
                            $method = base64_encode($packageItem['id'] . "-" . $vehicle_type . "_" . $packageItem['courier_type']);

                            $response['type'] = 'success';
                            $priceArr[$method] = array('label' => $name . " - " . $label, 'amount' => $price);
                        }
                    }
                    $responseArray['status'] = true;
                    $responseArray['menuId'] = $menuId;
                    $responseArray['data'] = $priceArr;

                } else {
                    $responseArray['message'] = "Shipox doesn't have any proper package for this Shipment";
                }
            } else {
                $responseArray['message'] = "Oops, We couldn't get customer Latitude and Longitude address";
            }
        } else {
            $responseArray['message'] = "Select Current Country as allowed";
        }

        return $responseArray;
    }

    /**
     * @param $status
     * @return mixed
     */
    public function isOrderCancellable($status)
    {
        $statusMapping = $this->modelStatusMapping;
        $statusList = $statusMapping->statusList();

        return $statusList[$status]['cancellable'];
    }

    /**
     * @param $shipoxOrderData
     * @return bool
     */
    public function isShipoxOrderCompleted($shipoxOrderData)
    {
        return ($shipoxOrderData['status'] === 'completed') ? true : false;
    }

    /**
     * @param $status
     * @return bool
     */
    public function isShipoxOrderCanReject($status)
    {
        switch ($status) {
            case 'completed':
            case 'returned_to_wing':
            case 'returned_to_origin':
            case 'cancelled':
                return false;
        }

        return true;
    }

    /**
     * @param $shipoxOrderId
     * @param bool $isShipoxOrderStatusUpdateNeeded
     * @param null $order
     * @return null
     * @internal param bool $isUpdateTable
     */
    public function getShipoxOrderDetails($shipoxOrderId, $isShipoxOrderStatusUpdateNeeded = false, $order = null)
    {
        $shipoxApiClient = $this->clientHelper;
        $shipoxDBClient = $this->clientDbHelper;

        $shipoxDetails = $shipoxApiClient->getOrderItem($shipoxOrderId);

        if ($shipoxDetails['status'] == 'success') {
            if ($isShipoxOrderStatusUpdateNeeded) {

                $shipoxDataTable = $shipoxDBClient->getData($order->getQuoteId());
                if (!is_null($shipoxDataTable->getData())) {
                    $shipoxDataTable->setShipoxOrderStatus($shipoxDetails['data']['status']);
                    $shipoxDataTable->save();
                }
            }
            return $shipoxDetails['data'];
        }

        return null;
    }

    /**
     * @param $order
     * @param $reason
     * @return array
     */
    public function cancelShipoxOrder($order, $reason)
    {
        $statusMapping = $this->modelStatusMapping; //TO do model for status

        $shipoxOrder = $this->modelTracking->getOrderTrackingData($order);

        $responseData = array(
            'status' => false,
            'message' => 'Oops, there is some error with the Server'
        );

        if (!empty($shipoxOrder)) {

            $shipoxDetails = $this->getShipoxOrderDetails($shipoxOrder['shipox_order_number'], true, $order);

            if (!empty($shipoxDetails)) {
                $shipoxApiClient = $this->clientHelper;

                $transfer = array(
                    'note' => $reason,
                    'reason' => $reason,
                    'status' => 'cancelled'
                );

                if ($statusMapping->isOrderStatusCancellable($shipoxDetails['status'])) {
                    $response = $shipoxApiClient->updateStatus($shipoxOrder['shipox_order_id'], $transfer);

                    if ($response['success'] == 'success') {
                        $responseData['status'] = true;
                        $responseData['message'] = 'Order has been cancelled.';
                    } else {
                        $responseData['message'] = $response['message'];
                    }
                }
            } else {
                $responseData['message'] = 'Cannot find order details from Shipox';
            }
        }

        return $responseData;
    }

    /**
     * @param $shipoxOrder
     * @return string
     */
    public function getOrderTrackerURL($shipoxOrder)
    {
        return $shipoxOrder['shipox_order_url'] = $this->getShipoxSiteURL() . $this->_scopeConfig->getValue('tracking/myconfig/path', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) . $shipoxOrder['shipox_order_number'];
    }

    /**
     * @param $orderId
     * @return null
     */
    public function getShipoxOrderAirWayBill($orderId)
    {
        $shipoxApiClient = $this->clientHelper;

        $response = $shipoxApiClient->getAirwayBill($orderId);
        if ($response['status'] == 'success') {
            return $response['data']['value'];
        }

        return null;
    }

    /**
     * @param $status
     * @return bool
     */
    public function isLastOrderStatus($status)
    {

        switch ($status) {
            case 'returned_to_wing':
            case 'returned_to_origin':
            case 'cancelled':
                return true;
        }

        return false;
    }

    /**
     * @param $isPossibleToCreateNewOrder
     * @param $shipoxOrderFromServer
     * @return bool
     */
    public function canRecreateShipoxOrder($isPossibleToCreateNewOrder, $shipoxOrderFromServer)
    {
        if ($isPossibleToCreateNewOrder)
            return true;

        if (!$isPossibleToCreateNewOrder && !$this->_scopeConfig->getValue('carriers/' . $this->_code . '/reordering', \Magento\Store\Model\ScopeInterface::SCOPE_STORE))
            return false;

        if (!is_null($shipoxOrderFromServer)) {
            if ($this->isLastOrderStatus($shipoxOrderFromServer['status']))
                return true;
        }

        return false;
    }

    /**
     * @param $orderId
     */
    public function deactivateLastShipoxOrders($orderId)
    {
        $model = $this->modelShipox;

        $collection = $model->getCollection()
            ->addAttributeToSelect(array('order_id', 'active_order'))
            ->addFieldToFilter('order_id', $orderId);

        $this->iterator->walk($collection->getSelect(), array(array($this, 'orderCallback')));
    }

    /**
     * @param $args
     */
    public function orderCallback($args)
    {
        $shipoxOrder = $this->modelShipox;
        $shipoxOrder->setData($args['row']);
        $shipoxOrder->setActiveOrder(0);
        $shipoxOrder->getResource()->saveAttribute($shipoxOrder, 'active_order'); // save only changed attribute instead of whole object
    }
}