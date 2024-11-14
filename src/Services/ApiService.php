<?php
namespace XAutoPoster\Services;

class ApiService {
    private $options;
    private $twitter;
    
    public function __construct() {
        $this->options = get_option('xautoposter_options', []);
        $this->initTwitter();
    }
    
    private function initTwitter() {
        if (!empty($this->options['api_key']) && 
            !empty($this->options['api_secret']) && 
            !empty($this->options['access_token']) && 
            !empty($this->options['access_token_secret'])) {
            
            $this->twitter = new TwitterService(
                $this->options['api_key'],
                $this->options['api_secret'],
                $this->options['access_token'],
                $this->options['access_token_secret']
            );
        }
    }
    
    public function verifyTwitterCredentials() {
        if (!$this->twitter) {
            throw new \Exception('Twitter API bilgileri eksik');
        }
        
        try {
            return $this->twitter->verifyCredentials();
        } catch (\Exception $e) {
            error_log('Twitter API Doğrulama Hatası: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function sharePost($post) {
        if (!$this->twitter) {
            throw new \Exception('Twitter API bağlantısı kurulamadı');
        }
        
        try {
            return $this->twitter->sharePost($post);
        } catch (\Exception $e) {
            error_log('Twitter Paylaşım Hatası: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function getMetrics($tweetId) {
        if (!$this->twitter) {
            return null;
        }
        
        try {
            return $this->twitter->getTweetMetrics($tweetId);
        } catch (\Exception $e) {
            error_log('Twitter Metrik Hatası: ' . $e->getMessage());
            return null;
        }
    }
}