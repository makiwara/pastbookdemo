<?php


namespace PastBookSocialApp;
use \MetzWeb\Instagram\Instagram;

/**
*  
*/
abstract class AbstractOAuth {
    
    public function __construct($provider, $app) {
        $this->provider = $provider;
        $this->app = $app;
        $this->config = $app['oauth'][$provider];
    }
    public static function Factory($provider, $config) { 
        $providers = array(
            "instagram" => '\PastBookSocialApp\InstagramOAuth',
        );
        return new $providers[$provider]($provider, $config);
    }

    public abstract function getLoginUrl();    
    public abstract function getOAuthToken($code);

    public abstract function getMedia($limit=NULL, $year=NULL, $month=NULL);
}

class InstagramOAuth extends AbstractOAuth {

    public function __construct($provider, $app) {
        parent::__construct($provider, $app);
        $this->instagram = new Instagram(array(
            'apiKey'      => $this->config['key'],
            'apiSecret'   => $this->config['secret'],
            'apiCallback' => false,
        ));
    }

    private function _patchApiCallback() {
        // TODO figure out and make this better.
        $this->instagram->setApiCallback(
            $this->app["url_generator"]->generate('auth', 
                array("provider"=>$this->provider), true)
        );        
    }

    public function getLoginUrl() {
        $this->_patchApiCallback();
        return $this->instagram->getLoginUrl();
    }
    public function getOAuthToken($code) {
        $this->_patchApiCallback();
        $token = $this->instagram->getOAuthToken($code);
        return $token->access_token;
    }
    public function setOAuthToken($token) {
        $this->instagram->setAccessToken($token);
    }

    // Run through all photos and filter by year/month if provided
    public function getMedia($limit=NULL, $year=NULL, $month=NULL) {
        $limit = $limit? (int) ltrim($limit, '0') : NULL;
        $month = $month? (int) ltrim($month, '0') : NULL;
        $year  = $year ? (int) ltrim($year , '0') : NULL;
        $photos = $this->instagram->getUserMedia();
        $out = array(); 
        $result = $photos;
        while ($result) {
            foreach ($result->data as $media) {
                $dt = getdate($media->created_time);
                $is_valid = True;
                if ($year) $is_valid = ($dt['year'] == $year);
                if ($is_valid and $month) $is_valid = ($dt['mon'] == $month);
                if ($is_valid and ($media->type !== 'video')) {
                    array_push( $out, array(
                        $media->images->thumbnail->url,
                        $media->images->standard_resolution->url
                    ));
                }
                if ($limit and count($out) == $limit) break 2;
            }
            $result = $this->instagram->pagination($result);
        }
        return $out;
    }

}