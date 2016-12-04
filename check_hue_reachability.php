#!/usr/bin/php
<?php


class HueIcingaCheck
{
    /**
     * @var string
     */
    protected $bridgeIp;

    /**
     * @var string
     */
    protected $bridgeUser;

    /**
     * HueIcingaCheck constructor.
     * @param string $bridgeIp
     * @param string $bridgeUser
     */
    public function __construct($bridgeIp, $bridgeUser)
    {
        if (!filter_var($bridgeIp, FILTER_VALIDATE_IP)) {
            throw new RuntimeException("invalid ip address");
        }
        $this->bridgeIp = $bridgeIp;
        $this->bridgeUser = $bridgeUser;
    }

    /**
     * @param $url
     * @return object
     */
    protected function apiGetRequest($url)
    {
        $output = file_get_contents(sprintf("http://%s/api/%s/%s", $this->bridgeIp, $this->bridgeUser, $url));
        if ($output === false) {
            throw new RuntimeException("invalid url specified");
        }

        $returnObject = json_decode($output);
        if ($returnObject === null) {
            throw new RuntimeException("json returned cannot be parsed");
        }
        return $returnObject;
    }

    public function isUpdateAvailable()
    {
        $response = $this->apiGetRequest("config");
        if (isset($response->swupdate)) {
            return 0 !== $response->swupdate->updatestate;
        } else {
            throw new RuntimeException("No update information available. Authorization failed?");
        }
    }

    /**
     * @return array
     */
    public function findUnavailableLights()
    {
        $response = $this->apiGetRequest("lights");
        $unavailableLights = [];
        foreach ($response AS $lightInfo) {
            if ($lightInfo->state->reachable !== true) {
                $unavailableLights[] = $lightInfo->name;
            }
        }
        return $unavailableLights;
    }
}

try {
    $hueIcingaCheck = new HueIcingaCheck($argv[1], $argv[2]);
    if ($hueIcingaCheck->isUpdateAvailable()) {
        echo "Update available!".PHP_EOL;
        exit(1);
    }
    $unavailableLights = $hueIcingaCheck->findUnavailableLights();
    if (count($unavailableLights) > 0) {
        echo "Unvailable lights: ".implode(", ", $unavailableLights).PHP_EOL;
        exit(1);
    }

    echo "OK".PHP_EOL;
    exit(0);
} catch (Exception $e) {
    echo "Check failed with exception ".$e->getMessage().PHP_EOL;
    exit(2);
}
