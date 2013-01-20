<?php
define("BITLYTESTPATH", pathinfo(__FILE__, PATHINFO_BASENAME));
/*
To test bitly-api-php;
1) get your access token from http://bitly.com/a/oauth_apps
2) copy accesstoken.php.example to accesstoken.php
3) open accesstoken.php and paste your token into the script.
*/
require("accesstoken.php");
if (!defined('ACCESSTOKEN'))
    exit('Check your accesstoken.php file');

require("bitly_api.php");
header("Content-type: text/plain");

function testBasicFunctions()
{
    $auth  = array(
        "access_token" => ACCESSTOKEN
    );
    $bitly = new Bitly($auth);
    
    echo "Testing shorten;" . PHP_EOL;
    $data = $bitly->shorten("http://betaworks.com/page?parameter=value#anchor");
    print_r($data);
    if (!$data)
        exit("shorten fail: shorten returned no data");
    if (@$data["long_url"] !== "http://betaworks.com/page?parameter=value#anchor") {
        echo @$data["long_url"] . "\n";
        exit("shorten fail: Wrong long url");
    }
    if (!@$data["hash"])
        exit("shorten fail: no hash");
    echo "OK" . PHP_EOL;
    
    // ===============================================================
    
    echo "Testing expand with single hash" . PHP_EOL;
    $params = array(
        "hash" => $data["hash"]
    );
    $data   = $bitly->expand($params);
    print_r($data);
    if (!$data)
        exit("expand fail: expand returned no data.");
    if (!@$data[0]["long_url"])
        exit("expand fail: expand didn\'t return long url.");
    echo "OK" . PHP_EOL;
    
    // ===============================================================
    
    echo "Testing expand with multiple hashes" . PHP_EOL;
    $params = array(
        "hash" => array(
            "Xky7y9",
            "VR455I"
        )
    );
    $data   = $bitly->expand($params);
    print_r($data);
    if (!$data)
        exit("expand (multi) fail: expand returned no data.");
    if (!@$data[0]["long_url"])
        exit("expand (multi) fail: expand didn\'t return long url.");
    echo "OK" . PHP_EOL;
    
    // ===============================================================
    
    echo "Testing clicks" . PHP_EOL;
    $params = array(
        "hash" => array(
            "Xky7y9",
            "VR455I"
        )
    );
    $data   = $bitly->clicks($params);
    print_r($data);
    if (!$data)
        exit("clicks: clicks returned no data");
    echo "OK" . PHP_EOL;
    
    // ===============================================================
    
    echo "Testing link clicks" . PHP_EOL;
    $data = $bitly->link_clicks("http://bit.ly/Xky7y9");
    print_r($data);
    if (!$data)
        exit("link clicks: link clicks returned no data");
    echo "OK" . PHP_EOL;
    
    // ===============================================================
    
    echo "Testing lookup" . PHP_EOL;
    $data = $bitly->lookup("http://www.google.com/");
    print_r($data);
    if (!$data)
        exit("lookup: lookup returned no data");
    echo "OK" . PHP_EOL;
    
    // ===============================================================
    
    echo "Testing search" . PHP_EOL;
    $data = $bitly->search("osman");
    print_r($data);
    if (!$data)
        exit("search: search returned no data");
    echo "OK" . PHP_EOL;
}

testBasicFunctions();
