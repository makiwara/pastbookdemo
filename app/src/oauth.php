<?php


namespace PastBookSocialApp;
use \MetzWeb\Instagram\Instagram;

/**
*  
*/
abstract class AbstractOAuth {
    
    public function __construct($provider, $config) {
        $this->provider = $provider;
        $this->config = $config;
    }
    public static function Factory($provider, $config) { 
        $providers = array(
            "instagram" => '\PastBookSocialApp\InstagramOAuth',
        );
        return new $providers[$provider]($provider, $config);
    }

    public abstract function getLoginUrl();    
    public abstract function getOAuthToken($code);
}

class InstagramOAuth extends AbstractOAuth {

    public function __construct($provider, $config) {
        parent::__construct($provider, $config);
        $this->instagram = new Instagram(array(
            'apiKey'      => $this->config['key'],
            'apiSecret'   => $this->config['secret'],
            'apiCallback' => "http://localhost:8888/auth/".$this->provider
            // TODO: fix apiCallback URL
        ));
    }

    public function getLoginUrl() {
        return $this->instagram->getLoginUrl();
    }
    public function getOAuthToken($code) {
        return $this->instagram->getOAuthToken($code)->access_token;
    }

}