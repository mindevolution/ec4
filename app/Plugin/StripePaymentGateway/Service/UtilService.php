<?php
/*
* Plugin Name : StripePaymentGateway
*
* Copyright (C) 2020 Subspire. All Rights Reserved.
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\StripePaymentGateway\Service;

include_once(dirname(__FILE__).'/../vendor/stripe/stripe-php/init.php');

use http\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Plugin\StripePaymentGateway\Entity\StripeConfig;
use Plugin\StripePaymentGateway\Entity\CheckoutSession;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session as StSession;
use Plugin\StripePaymentGateway\Entity\LicenseKey;

class UtilService{
    protected $container;
    protected $em;
    protected $router;

    const LIC_URL = "https://subspire.co.jp/?wc-api=software-api&";
    // const LIC_URL = "http://wordpress-69479-1385070.cloudwaysapps.com/?wc-api=software-api&";
    const PROD_ID = "stripe_eccube4";

    public function __construct(
        ContainerInterface $container
    ){
        $this->container = $container;
        $this->em = $this->container->get('doctrine.orm.entity_manager');
        $this->router = $this->container->get('router');

        // $config = $this->em->getRepository(StripeConfig::class)->get();
        // Stripe::setApiKey($config->getSecretKey());
    }
    
    public function requestLicense($key){
        $url = UtilService::LIC_URL
            .'request=activation'
            .'&email='.$key->getEmail()
            .'&license_key='.$key->getLicenseKey()
            .'&product_id=' . UtilService::PROD_ID
            .'&instance='.$key->getInstance();
        $client = new \GuzzleHttp\Client(['defaults' => [
            'verify' => false
        ]]);
        $response = $client->request('GET', $url);
        if ($response->getStatusCode() == 200) {
            $body = (string)$response->getBody();
            $content = @json_decode($body);
        } else {
            $content = false;
        }
        return $content !== false && isset($content->activated) && $content->activated === true;

    }

    /**
     * return value
     * "test"
     * "authed"
     * "unauthed"
     */
    public function checkLicense(){
        $license_repo = $this->em->getRepository(LicenseKey::class);
        $key = $license_repo->get();
        if($key){
            if($key->getKeyType() === 1){
                return "test";
            }
            if($this->requestLicense($key)){
                return "authed";
            }else{
                return "unauthed";
            }
        }
        else{
            return "unauthed";
        }
    }
    public static function truncateString($string,$length=100,$append="&hellip;") {
        $string = trim($string);

        if(strlen($string) > $length) {
            $string = wordwrap($string, $length);
            $string = explode("\n", $string, 2);
            $string = $string[0] . $append;
        }

        return $string;
    }
}
