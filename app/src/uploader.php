<?php

namespace PastBookSocialApp;

/* ********
 *
 * Very simple local uploader. Subject to move to the cloud.
 *
 */
class Uploader {

    public function __construct($app) {
        $this->config = $app['uploader'];
    }

    public function upload($url, $unique_id) {
        $filename = $unique_id.".jpg";
        $ch = curl_init($url);
        $fp = fopen($this->config["local_path"].$filename, "w");
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        return $this->config["url_prefix"].$filename;
    }
}
