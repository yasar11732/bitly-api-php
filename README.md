bitly API php library
========================

## Basic Usage

    require("bitly_api.php");
    $bitly = new Bitly(array("login" => "yourlogin", "api_key" => "yourapikey"));
    # or to use oauth2 endpoints
    $bitly = new Bitly(array("access_token" => "youraccesstoken");
    $data = $bitly->shorten('http://www.google.com/');

You need to have bitly\_api.php, bitly\_http.php and cacert.pem in the same directory.
If you have issues with SSL certificates, try updating cacert.pem from http://curl.haxx.se/ca/cacert.pem

## Run tests

Your username is the lowercase name shown when you login to bitly, your access token can be fetched using the following ( http://dev.bitly.com/authentication.html ):

    curl -u "username:password" -X POST "https://api-ssl.bitly.com/oauth/access_token"

To run tests copy accesstoken.php.example to accesstoken.php and fill in your access token.

    bitly-api-php $ php test.php
    
Or navigate to test.php if you have a webserver running.

## API Documentation

http://dev.bitly.com/
