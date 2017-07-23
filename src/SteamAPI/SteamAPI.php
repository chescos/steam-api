<?php

namespace SteamAPI;
use Exception;

use GuzzleHttp\Client;

class SteamAPI
{
    protected $client;
    protected $apiKey;

    public function __construct($apiKey = false)
    {
        // assign api key
        $this->apiKey = $apiKey;

        // create guzzle instance
        $this->client = new Client([
            'base_uri'      => 'https://api.steampowered.com',
            'http_errors'   => false,
        ]);
    }

    public function getAssetClassInfo($classid)
    {
        return $this->getResponse('ISteamEconomy/GetAssetClassInfo/v1', [
            'query' => [
                'appid'         => 730,
                'class_count'   => 1,
                'classid0'      => $classid
            ],
            'tree'  => [
                'result'
            ]
        ]);
    }

    public function getUserProfile($steamid)
    {
        return $this->getResponse('ISteamUser/GetPlayerSummaries/v2', [
            'query' => [
                'steamids' => $steamid
            ],
            'tree'  => [
                'response',
                'players',
                0
            ]
        ]);
    }

    public function getOwnedGames($steamid)
    {
        return $this->getResponse('IPlayerService/GetOwnedGames/v1', [
            'query' => [
                'steamid' => $steamid
            ],
            'tree'  => [
                'response',
                'games'
            ]
        ]);
    }

    public function getSteamIDFromVanityUrl($vanityUrl, $type = 1)
    {
        return $this->getResponse('ISteamUser/ResolveVanityURL/v1', [
            'query' => [
                'vanityurl' => $vanityUrl,
                'url_type'  => $type
            ],
            'tree'  => [
                'response',
                'steamid'
            ]
        ]);
    }

    public function getUserGroups($steamid)
    {
        return $this->getResponse('ISteamUser/GetUserGroupList/v1', [
            'query' => [
                'steamid' => $steamid
            ],
            'tree'  => [
                'response',
                'groups'
            ]
        ]);
    }

    public function getUserInventory($steamid)
    {
        return $this->getResponse('http://steamcommunity.com/profiles/' . $steamid . '/inventory/json/730/2');
    }

    public function getRSAKey($username)
    {
        return $this->getResponse('https://steamcommunity.com/login/getrsakey', [
            'query' => [
                'username' => $username
            ]
        ]);
    }

    public function doLogin($params)
    {
        return $this->getResponse('https://steamcommunity.com/login/dologin/', [
            'query'         => $params,
            'method'        => 'POST',
        ]);
    }

    private function getResponse($endpoint, $options = [])
    {
        // all available custom options and their default values
        $defaultOptions = [
            'method'            => 'GET',
            'tree'              => [],
            'json_response'     => true,
        ];

        $guzzleOptions = [
            'query' => []
        ];

        // loop through passed options
        foreach($options as $option => $value) {
            // option is a default option
            if(isset($defaultOptions[$option])) {
                $defaultOptions[$option] = $value;
            }
            // option is a guzzle option
            else if(isset($guzzleOptions[$option])) {
                // add to guzzle options array
                $guzzleOptions[$option] = $value;
            }
        }

        // add default guzzle query parameters
        $guzzleOptions['query'] = array_merge($guzzleOptions['query'], [
            'key'       => $this->apiKey,
            'format'    => 'json'
        ]);

        // perform request
        $response = $this->client->request($defaultOptions['method'], $endpoint, $guzzleOptions);

        // get response body content
        $content = $response->getBody();

        // response should be in JSON format
        if($defaultOptions['json_response']) {
            // decode JSON content
            $content = json_decode($content, true);

            // throw error on invalid json data
            if(!$content) {
                throw new Exception('Failed to decode JSON response for endpoint "' . $endpoint . '".');
            }

            // throw error on steam error response
            if(isset($content['response']['error']) && (!isset($content['response']['success']) || !$content['response']['success'])) {
                throw new Exception('Steam returned an error: ' . $content['response']['error']);
            }
        }

        // throw error on all status codes but 200
        if($response->getStatusCode() !== 200) {
            throw new Exception('Steam returned an invalid HTTP status code: ' . $response->getStatusCode());
        }

        // loop through tree
        foreach($defaultOptions['tree'] as $index) {
            // index exists
            if(isset($content[$index])) {
                $content = $content[$index];
            }
            // index does not exist, throw exception
            else {
                throw new Exception('Steam API tree error: key "' . $index . '" does not exist');
            }
        }

        // return the result, cast as string if it's not a json response
        return $defaultOptions['json_response'] ? $content : (string) $content;
    }

}
