<?php
if (!defined('BITLYPATH'))
    exit('No direct script access allowed');
/*
This file is finds and uses available extension for http requests.
Defaults to:
1) curl
2) Not available

Author: Yaşar Arabacı
yasar11732@gmail.com
yasararabaci.tumblr.com

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; version 2
of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

*/


function makeCurlHttp($url, $timeout, $user_agent)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // see: http://stackoverflow.com/a/316732/886669
    curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . "/cacert.pem");
    $curl_version = curl_version();
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent . ' ' . $curl_version["version"]);
    $result = curl_exec($ch);
    
    $http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return array(
        'http_status_code' => $http_status_code,
        'result' => $result
    );
}


function get($url, $timeout, $user_agent)
{
    if (in_array('curl', get_loaded_extensions())) {
        return makeCurlHttp($url, $timeout, $user_agent);
    } else {
        throw new BitlyError("No available extension for http requests.", 12);
    }
}
