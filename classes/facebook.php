<?php

namespace mod_sharedpanel;

use Facebook\Authentication\AccessToken;
use Facebook\Exceptions\FacebookResponseException;
use Facebook\Exceptions\FacebookSDKException;

defined('MOODLE_INTERNAL') || die();

include __DIR__ . "/../lib/facebook/autoload.php";

class facebook extends card
{
    public function __construct($modinstance) {
        parent::__construct($modinstance);
    }

    public function is_enabled() {
        $config = get_config('sharedpanel');
        if (empty($config->FBappID) ||
            empty($config->FBsecret)
        ) {
            return false;
        }
        return true;
    }

    public function import() {
        $config = get_config('sharedpanel');
        if (empty($config->FBappID) || empty($config->FBsecret)) {
            $this->error->message = "認証情報が入力されていません。";
            return false;
        }

        $fb = new \Facebook\Facebook([
            'app_id' => $config->FBappID,
            'app_secret' => $config->FBsecret,
            'default_graph_version' => 'v2.10',
        ]);

        $access_token = $fb->getApp()->getAccessToken();

        $response = $fb->get(
            $this->moduleinstance->fbgroup1,
            $access_token->getValue()
        );

        try {
            $response = $fb->get(
                $this->moduleinstance->fbgroup1,
                $access_token->getValue()
            );
        } catch (FacebookResponseException $e) {
            echo 'Graph returned an error: ' . $e->getMessage();
            exit;
        } catch (FacebookSDKException $e) {
            echo 'Facebook SDK returned an error: ' . $e->getMessage();
            exit;
        }
        $graphNode = $response->getGraphNode();

    }

    public function get_error() {
        return $this->error;
    }
}