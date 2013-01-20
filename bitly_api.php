<?php
/*
This is a php library for accessing the bitly api
http://github.com/yasar11732/bitly-api-php

Copyright (C) Yaşar Arabacı yasar11732@gmail.com

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

Usage:
require("bitly_api.php");
$bitly = new Bitly(array("login" => "yourlogin", "api_key" => "yourapikey"));
# or to use oauth2 endpoints
$bitly = new Bitly(array("access_token" => "youraccesstoken");
$data = $bitly->shorten('http://www.google.com/');
*/

define('BITLYPATH', pathinfo(__FILE__, PATHINFO_BASENAME));

class BitlyError extends Exception
{
} // all bitly errors will throw this.

require("bitly_http.php");


class Bitly
{
    const host = 'api.bit.ly';
    const ssl_host = 'api-ssl.bit.ly';
    
    /*
    @parameter $params: array of arguments, should either provide login
    and api_key pair or access_token. secret is optional
    Example:
    $bitly = new Bitly(array("login" => "YOURLOGIN", "api_key" => "YOURAPIKEY"));
    # or
    $bitly = new Bitly(array("access_token" => "TOKEN")); .
    */
    function __construct($params)
    {
        $this->login        = @$params["login"] ? $params["login"] : FALSE;
        $this->api_key      = @$params["api_key"] ? $params["api_key"] : FALSE;
        $this->access_token = @$params["access_token"] ? $params["access_token"] : FALSE;
        $this->secret       = @$params["secret"] ? $params["secret"] : FALSE;
        $this->user_agent   = sprintf("PHP/%d.%d.%d bitly_api/%s", PHP_MAJOR_VERSION, PHP_MINOR_VERSION, PHP_RELEASE_VERSION, '?');
    }
    
    /*
    creates a bitly link for a given url.
    @parameter $uri: long url to shorten // mandatory
    @parameter $params["x_login"]: login of a user to shorten on behalf of // optional
    @parameter $params["x_apiKey"]: apiKey of a user to shorten on behalf of // optional
    @parameter $params["domain"]: bit.ly[default], bitly.com, or j.mp // optional
    */
    public function shorten($uri, $params = array())
    {
        $params["uri"] = $uri;
        $data          = $this->call(self::host, 'v3/shorten', $params, $this->secret);
        return $data["data"];
    }
    
    /*
    given a bitly url or hash, decode it and return the target url
    @parameter $params["hash"]: one or more bitly hashes
    @parameter $params["shortUrl"]: one or more bitly short urls
    @parameter $params["link"]: one or more bitly short urls (preferred vocabulary)
    */
    public function expand($params)
    {
        if (@$params["link"] && !@$params["shortUrl"]) {
            $params["shortUrl"] = $params["link"];
        }
        
        if (!@$params["hash"] && !@$params["shortUrl"]) {
            throw new BitlyError("MISSING_ARG_SHORTURL", 500);
        }
        
        $data = $this->call(self::host, 'v3/expand', $params, $this->secret);
        return $data['data']['expand'];
    }
    
    // given a bitly url or hash, get statistics about the clicks on that link
    public function clicks($params)
    {
        trigger_error("/v3/clicks is depricated in favor of /v3/link/clicks", E_USER_WARNING);
        if (!@$params["hash"] && !@$params["shortUrl"]) {
            throw new BitlyError("MISSING_ARG_SHORTURL", 500);
        }
        
        $data = $this->call(self::host, 'v3/clicks', $params, $this->secret);
        return $data['data']['clicks'];
    }
    
    // given a bitly url or hash, get statistics about the referrers of that link
    public function referrers($params)
    {
        trigger_error("/v3/referrers is depricated in favor of /v3/link/referrers", E_USER_WARNING);
        if (!@$params["hash"] && !@$params["shortUrl"]) {
            throw new BitlyError("MISSING_ARG_SHORTURL", 500);
        }
        
        $data = $this->call(self::host, 'v3/referrers', $params, $this->secret);
        return $data['data']['referrers'];
    }
    
    /*
    given a bitly url or hash, get a time series of clicks
    per day for the last 30 days in reverse chronological order (most recent to least recent)
    */
    public function clicks_by_day($params)
    {
        trigger_error("/v3/clicks_by_day is depricated in favor of /v3/link/clicks?unit=day", E_USER_WARNING);
        if (!@$params["hash"] && !@$params["shortUrl"]) {
            throw new BitlyError("MISSING_ARG_SHORTURL", 500);
        }
        
        $data = $this->call(self::host, 'v3/clicks_by_day', $params, $this->secret);
        return $data['data']['clicks_by_day'];
    }
    
    /*
    given a bitly url or hash, get a time series of clicks
    per minute for the last 30 minutes in reverse chronological
    order (most recent to least recent)
    */
    public function clicks_by_minute($params)
    {
        trigger_error("/v3/clicks_by_minute is depricated in favor of /v3/link/clicks?unit=minute", E_USER_WARNING);
        if (!@$params["hash"] && !@$params["shortUrl"]) {
            throw new BitlyError("MISSING_ARG_SHORTURL", 500);
        }
        
        $data = $this->call(self::host, 'v3/clicks_by_minute', $params, $this->secret);
        return $data['data']['clicks_by_minute'];
    }
    
    public function link_clicks($link, $params = array())
    {
        $params["link"] = $link;
        $data           = $this->call_oauth2_metrics('v3/link/clicks', $params);
        return $data["link_clicks"];
    }
    
    // return the count of bitly encoders who saved this link
    public function link_encoders_count($link, $params = array())
    {
        $params["link"] = $link;
        $data           = $this->call(self::host, 'v3/link/encoders_count', $params, $this->secret);
        return $data["data"];
    }
    
    // returns the domains that are referring traffic to a single bitly link
    public function link_referring_domains($link, $params = array())
    {
        $params["link"] = $link;
        $data           = $this->call_oauth2_metrics("v3/link/referring_domains", $params);
        return $data["referring_domains"];
    }
    
    // returns the pages that are referring traffic to a single bitly link, grouped by domain
    public function link_referrers_by_domains($link, $params = array())
    {
        $params["link"] = $link;
        $data           = $this->call_oauth2_metrics("v3/link/referrers_by_domain", $params);
        return $data["referrers"];
    }
    
    // returns the pages that are referring traffic to a single bitly link, grouped by domain
    public function link_referrers($link, $params = array())
    {
        $params["link"] = $link;
        $data           = $this->call_oauth2_metrics("v3/link/referrers", $params);
        return $data["referrers"];
    }
    
    // return number of shares of a bitly link
    public function link_shares($link, $params = array())
    {
        $params["link"] = $link;
        $data           = $this->call_oauth2_metrics("v3/link/shares", $params);
        return $data;
    }
    
    public function link_countries($link, $params = array())
    {
        $params["link"] = $link;
        $data           = $this->call_oauth2_metrics("v3/link/countries", $params);
        return $data["countries"];
    }
    
    // aggregate number of clicks on all of this user's bitly links
    public function user_clicks($params)
    {
        $data = $this->call_oauth2_metrics("v3/user/clicks", $params);
        return $data;
    }
    
    // agggregate metrics about countries from which people are clicking on all of a user's bitly links
    public function user_countries($params)
    {
        $data = $this->call_oauth2_metrics("v3/user/countries", $params);
        return $data["countries"];
    }
    
    public function user_popular_links($params)
    {
        $data = $this->call_oauth2_metrics("v3/user/popular_links", $params);
        return $data["popular_links"];
    }
    
    // aggregate metrics about the referrers for all of the authed user's bitly links
    public function user_referrers($params)
    {
        $data = $this->call_oauth2_metrics("v3/user/referrers", $params);
        return $data["referrers"];
    }
    
    // aggregate metrics about the domains referring traffic to all of the authed user's bitly links
    public function user_referring_domains($params)
    {
        $data = $this->call_oauth2_metrics("v3/user/referring_domains", $params);
        return $data["referring_domains"];
    }
    
    // number of shares by authed user in given time period
    public function user_share_counts($params)
    {
        $data = $this->call_oauth2_metrics("v3/user/share_counts", $params);
        return $data["share_counts"];
    }
    
    // number of shares by authed user broken down by type (facebook, twitter, email) in a give time period
    public function user_share_counts_by_share_type($params)
    {
        $data = $this->call_oauth2_metrics("v3/user/share_counts_by_share_type", $params);
        return $data["share_counts_by_share_type"];
    }
    
    public function user_shorten_counts($params)
    {
        $data = $this->call_oauth2_metrics("v3/user/shorten_counts", $params);
        return $data["shorten_counts"];
    }
    
    public function user_tracking_domain_list()
    {
        $data = $this->call_oauth2_metrics("v3/user/tracking_domain_list", array());
        return $data["tracking_domains"];
    }
    
    public function user_tracking_domain_clicks($domain, $params = array())
    {
        $params["domain"] = $domain;
        $data             = $this->call_oauth2_metrics("v3/user/tracking_domain_clicks", $params);
        return $data["tracking_domain_clicks"];
    }
    
    public function user_tracking_domain_shorten_counts($domain, $params = array())
    {
        $params["domain"] = $domain;
        $data             = $this->call_oauth2_metrics("v3/user/tracking_domain_shorten_counts", $params);
        return $data["tracking_domain_domain_shorten_counts"];
    }
    
    // return or update info about a user
    public function user_info($params)
    {
        $data = $this->call_oauth2_metrics("v3/user/info", $params);
        return $data;
    }
    
    public function user_link_history($params)
    {
        if (is_bool(@$params["archived"])) {
            $params["archived"] = $params["archived"] ? "true" : "false";
        }
        $data = $this->call_oauth2("v3/user/link_history", $params);
        return $data["link_history"];
    }
    
    public function user_network_history($params)
    {
        $params["expand_client_id"] = @$params["expand_client_id"] ? "true" : "false";
        $params["expand_user"]      = @$params["expand_user"] ? "true" : "false";
        $data                       = $this->call_oauth2("v3/user/link_history", $params);
        return $data;
    }
    
    // return the page title for a given bitly link
    public function info($params)
    {
        if (@$params["link"] && !@$params["shortUrl"]) {
            $params["shortUrl"] = $params["link"];
            unset($params["link"]);
        }
        
        if (!@$params["hash"] && !@$params["shortUrl"]) {
            throw new BitlyError("MISSING_ARG_SHORTURL", 500);
        }
        
        $data = $this->call(self::host, "v3/info", $params, $this->secret);
        return $data["data"]["info"];
    }
    
    // query for a bitly link based on a long url (or list of long urls)
    public function link_lookup($url)
    {
        $params["url"] = $url;
        $data          = $this->call(self::host, "v3/link/lookup", $params, $this->secret);
        return $data["data"]["link_lookup"];
    }
    
    // query for a bitly link based on a long url
    public function lookup($url)
    {
        trigger_error("/v3/lookup is depricated in favor of /v3/link/lookup", E_USER_WARNING);
        $params["url"] = $url;
        $data          = $this->call(self::host, "v3/lookup", $params, $this->secret);
        return $data["data"]["lookup"];
    }
    
    /*
    Changes link metadata in a user's history.
    @parameter $link: the bitly link to be edited
    @parameter $edit: a comma separated string of links to be edited.
    @parameter $params["note"]: _optional_ a description of, or note about, this bitmark
    @parameter $params["private"]: _optional_ "true" or "false" indicating privacy setting
    @parameter $params["user_ts"]: _optional_ timestamp as an integer epoch.
    @parameter $params["archived"]: _optional_ "true" or "false" indicating whether or not link is to archived
    */
    public function user_link_edit($link, $edit, $params)
    {
        if (!$link || !is_string($link)) {
            throw new BitlyError("MISSING_ARG_LINK", 500);
        }
        
        if (!$edit || !is_string($edit)) {
            throw new BitlyError("MISSING_ARG_EDIT", 500);
        }
        
        if (array_key_exists("private", $params)) {
            if (is_bool($params["private"])) {
                $params["private"] = $params["private"] ? "true" : "false";
            }
        }
        
        if (array_key_exists("archived", $params)) {
            if (is_bool($params["archived"])) {
                $params["archived"] = $params["archived"] ? "true" : "false";
            }
        }
        
        $data = $this->call_oauth2("v3/user/link_edit", $params);
        return $data["link_edit"];
    }
    
    // query for whether a user has shortened a particular long URL. don't confuse with v3/link/lookup.
    public function user_link_lookup($url)
    {
        $params["url"] = $url;
        $data          = $this->call("v3/user/link_lookup", $params, $this->secret);
        return $data['data']['link_lookup'];
    }
    
    // save a link into the user's history
    public function user_link_save($params)
    {
        if (!@$params["longUrl"] && !@$params["long_url"]) {
            throw new BitlyError("MISSING_ARG_LONG_URL", 500);
        }
        
        $params["longUrl"] = @$params["longUrl"] ? $params["longUrl"] : $params["long_url"];
        unset($params["long_url"]);
        
        $data = $this->call_oauth2("v3/user/link_save", $params);
        return $data['link_save'];
    }
    
    // is the domain assigned for bitly.pro?
    public function pro_domain($domain)
    {
        if (!$domain) {
            throw new BitlyError("MISSING_ARG_DOMAIN", 500);
        }
        
        if (preg_match('|^https?://|', $params["domain"])) {
            throw new BitlyError("INVALID_BARE_DOMAIN", 500);
        }
        $params["domain"] = $domain;
        $data             = $this->call(self::host, "v3/bitly_pro_domain", $params, $this->secret);
        return $data['data']['bitly_pro_domain'];
    }
    
    // archive a bundle for the authenticated user
    public function bundle_archive($bundle_link)
    {
        $params["bundle_link"] = $bundle_link;
        return $this->call_oauth2_metrics("v3/bundle/archive", $params);
    }
    
    // list bundles by user (defaults to authed user)
    public function bundle_bundles_by_user($params)
    {
        if (is_bool(@$params["expand_user"])) {
            $params["expand_user"] = $params["expand_user"] ? "true" : "false";
        }
        return $this->call_oauth2_metrics("v3/bundle/bundles_by_user", $params);
    }
    
    public function bundle_clone($bundle_link)
    {
        $params["bundle_link"] = $bundle_link;
        return $this->call_oauth2_metrics("v3/bundle/clone", $params);
    }
    
    // add a collaborator a bundle
    public function bundle_collaborator_add($bundle_link, $collaborator = FALSE)
    {
        $params["bundle_link"] = $bundle_link;
        if ($collaborator && is_string($collaborator)) {
            $params["collaborator"] = $collaborator;
        }
        return $this->call_oauth2_metrics("v3/bundle/collaborator_add", $params);
    }
    
    // remove a collaborator from a bundle
    public function bundle_collaborator_remove($bundle_link, $collaborator)
    {
        $params["bundle_link"]  = $bundle_link;
        $params["collaborator"] = $collaborator;
        return $this->call_oauth2_metrics("v3/bundle/collaborator_remove", $params);
    }
    
    // list the contents of a bundle
    public function bundle_contents($bundle_link, $expand_user = FALSE)
    {
        $params["bundle_link"] = $bundle_link;
        if ($expand_user) {
            $params["expand_user"] = $params["expand_user"] ? "true" : "false";
        }
        return $this->call_oauth2_metrics("v3/bundle/contents", $params);
    }
    
    // create a bundle
    public function bundle_create($params)
    {
        if (is_bool(@$params["private"])) {
            $params["private"] = $params["private"] ? "true" : "false";
        }
        return $this->call_oauth2_metrics("v3/bundle/create", $params);
    }
    
    // edit a bundle for the authenticated user
    public function bundle_edit($bundle_link, $params = array())
    {
        $params["bundle_link"] = $bundle_link;
        if (is_bool(@$params["private"])) {
            $params["private"] = $params["private"] ? "true" : "false";
        }
        if (is_bool(@$params["preview"])) {
            $params["preview"] = $params["preview"] ? "true" : "false";
        }
        return $this->call_oauth2_metrics("v3/bundle/edit", $params);
    }
    
    // add a link to a bundle
    public function bundle_link_add($bundle_link, $link, $title = FALSE)
    {
        $params["bundle_link"] = $bundle_link;
        $params["link"]        = $link;
        if (is_string($title)) {
            $params["title"] = $title;
        }
        return $this->call_oauth2_metrics("v3/bundle/link_add", $params);
    }
    
    // add a comment to a link in a bundle
    public function bundle_link_comment_add($bundle_link, $link, $comment)
    {
        $params = array(
            "bundle_link" => $bundle_link,
            "link" => $link,
            "comment" => $comment
        );
        return $this->call_oauth2_metrics("v3/bundle/link_comment_add", $params);
    }
    
    // edit a comment on a link in a bundle
    public function bundle_link_comment_edit($bundle_link, $link, $comment_id, $comment)
    {
        $params = array(
            "bundle_link" => $bundle_link,
            "link" => $link,
            "comment_id" => $comment_id,
            "comment" => $comment
        );
        return $this->call_oauth2_metrics("v3/bundle/link_comment_edit", $params);
    }
    
    // remove a comment on a link in a bundle
    public function bundle_link_comment_remove($bundle_link, $link, $comment_id)
    {
        $params = array(
            "bundle_link" => $bundle_link,
            "link" => $link,
            "comment_id" => $comment_id
        );
        return $this->call_oauth2_metrics("v3/bundle/link_comment_remove", $params);
    }
    
    // edit the title for a link
    public function bundle_link_edit($bundle_link, $link, $edit, $title = FALSE, $preview = FALSE)
    {
        $params = array(
            "bundle_link" => $bundle_link,
            "link" => $link
        );
        if ($edit === "title") {
            $params["edit"]  = $edit;
            $params["title"] = $title;
        } elseif ($edit === "preview") {
            $params["edit"]    = $edit;
            $params["preview"] = $preview ? "true" : "false";
        } else {
            throw new BitlyError("PARAM EDIT MUST HAVE VALUE TITLE OR PREVIEW", 500);
        }
        return $this->call_oauth2_metrics("v3/bundle/link_edit", $params);
    }
    
    // remove a link from a bundle
    public function bundle_link_remove($bundle_link, $link)
    {
        $params = array(
            "bundle_link" => $bundle_link,
            "link" => $link
        );
        return $this->call_oauth2_metrics("v3/bundle/link_remove", $params);
    }
    
    // reorder the links in a bundle
    public function bundle_link_reorder($bundle_link, $link, $display_order)
    {
        $params = array(
            "bundle_link" => $bundle_link,
            "link" => $link,
            "display_order" => $display_order
        );
        return $this->call_oauth2_metrics("v3/bundle/link_reorder", $params);
    }
    
    // remove a pending collaborator from a bundle
    public function bundle_pending_collaborator_remove($bundle_link, $collaborator)
    {
        $params = array(
            "bundle_link" => $bundle_link,
            "collaborator" => $collaborator
        );
        return $this->call_oauth2_metrics("v3/bundle/pending_collaborator_remove", $params);
    }
    
    // get the number of views on a bundle
    public function bundle_view_count($bundle_link)
    {
        $params = array(
            "bundle_link" => $bundle_link
        );
        return $this->call_oauth2_metrics("v3/bundle/view_count", $params);
    }
    
    public function user_bundle_history()
    {
        return $this->call_oauth2_metrics("v3/user/bundle_history", array());
    }
    
    public function highvalue($limit = 10, $lang = 'en')
    {
        $params = array(
            "limit" => $limit,
            "lang" => $lang
        );
        return $this->call_oauth2_metrics("v3/highvalue", $params);
    }
    
    public function realtime_bursting_phrases()
    {
        $data = $this->call_oauth2_metrics("v3/realtime/bursting_phrases", array());
        return $data["phrases"];
    }
    
    public function realtime_hot_phrases()
    {
        $data = $this->call_oauth2_metrics("v3/realtime/hot_phrases", array());
        return $data["phrases"];
    }
    
    public function realtime_clickrate($phrase)
    {
        $params["phrase"] = $phare;
        $data             = $this->call_oauth2_metrics("v3/realtime/clickrate", $params);
        return $data["rate"];
    }
    
    public function link_info($link)
    {
        $params["link"] = $link;
        return $this->call_oauth2_metrics("v3/link/info", $params);
    }
    
    public function link_content($link, $content_type = "html")
    {
        $params["link"]         = $link;
        $params["content_type"] = $content_type;
        $data                   = $this->call_oauth2_metrics("v3/link/content", $params);
        return $data["content"];
    }
    
    public function link_social($link, $content_type = "html")
    {
        $params["link"]         = $link;
        $params["content_type"] = $content_type;
        $data                   = $this->call_oauth2_metrics("v3/link/social", $params);
        return $data["social_scores"];
    }
    
    public function link_category($link)
    {
        $params["link"] = $link;
        $data           = $this->call_oauth2_metrics("v3/link/category", $params);
        return $data["categories"];
    }
    
    public function link_location($link)
    {
        $params["link"] = $link;
        $data           = $this->call_oauth2_metrics("v3/link/location", $params);
        return $data["locations"];
    }
    
    public function link_language($link)
    {
        $params["link"] = $link;
        $data           = $this->call_oauth2_metrics("v3/link/language", $params);
        return $data["languages"];
    }
    
    public function search($query, $params = array())
    {
        $params["query"] = $query;
        if (!array_key_exists("limit", $params)) {
            $params["limit"] = 10; // default to 10
        }
        if (!array_key_exists("lang", $params)) {
            $params["lang"] = 'en'; // default to english
        }
        $data = $this->call_oauth2_metrics("v3/search", $params);
        return $data["results"];
    }
    // php's http_build_query is bugged: http://php.net/manual/en/function.http-build-query.php#77377
    private function buildquery($arr, $b = '', $c = 0)
    {
        if (!is_array($arr))
            return FALSE;
        
        foreach ($arr as $k => $v) {
            if ($c) {
                $k = $b; // for bulk processing bitly api expects no [] for arrays.
            } elseif (is_int($k)) {
                $k = $b . $k;
            }
            if (is_array($v) || is_object($v)) {
                $r[] = $this->buildquery($v, $k, 1);
                continue;
            }
            $r[] = urlencode($k) . "=" . urlencode($v);
        }
        return implode("&", $r);
    }
    
    private function generateSignature($params, $signature)
    {
        if (!$params || !$signature) {
            return "";
        }
        $hash_string = "";
        if (!in_array("t", $params)) {
            $params["t"] = strval(intval(microtime(TRUE)));
        }
        $keys = array_keys($params);
        sort($keys);
        foreach ($keys as $key) {
            if (is_array($params[$key])) {
                foreach ($params[$key] as $v) {
                    $hash_string .= $v;
                }
            } else {
                $hash_string .= $params[$key];
            }
        }
        $hash_string .= secret;
        $digest = md5($hash_string);
        return substr($digest, 0, 10);
    }
    
    private function call($host, $method, $params, $secret = FALSE, $timeout = 5000)
    {
        if (!@$params["format"]) {
            $params['format'] = 'json'; // default to json
        }
        
        if ($this->access_token !== FALSE) {
            $scheme                 = 'https';
            $params["access_token"] = $this->access_token;
            $host                   = self::ssl_host;
        } else {
            $scheme           = 'http';
            $params["login"]  = $this->login;
            $params["apiKey"] = $this->api_key;
        }
        
        if ($secret) {
            $params["signature"] = $this->generateSignature($params);
        }
        
        $request = sprintf("%s://%s/%s?%s", $scheme, $host, $method, $this->buildquery($params));
        
        try {
            $http_response = get($request, $timeout, $user_agent = $this->user_agent);
            if ($http_response["http_status_code"] != 200) {
                throw new BitlyError($http_response["result"], 500);
            } else if (strncmp($http_response["result"], "{", 1)) {
                throw new BitlyError($http_response["result"], 500);
            }
            $data = json_decode($http_response["result"], TRUE);
            
            if (!array_key_exists("status_code", $data) || $data["status_code"] != 200) {
                if (array_key_exists("status_code", $data)) {
                    $code = $data["status_code"];
                } else {
                    $code = 500;
                }
                if (array_key_exists("status_txt", $data)) {
                    $status = $data["status_txt"];
                } else {
                    $status = "UNKNOWN_ERROR";
                }
                
                throw new BitlyError($status, $code);
            }
            return $data;
            
        }
        catch (BitlyError $e) {
            throw $e;
        }
        catch (Exception $e) {
            throw new BitlyError("", $e->code);
        }
    }
    
    private function call_oauth2_metrics($endpoint, $params)
    {
        if (array_key_exists("unit", $params)) {
            in_array($params["unit"], array(
                "minute",
                "hour",
                "day",
                "week",
                "mweek",
                "month"
            )) or exit("");
        }
        
        if (array_key_exists("units", $params)) {
            is_int($params["units"]) or exit(sprintf("Unit (%r) must be integer", $params["units"]));
        }
        
        if (array_key_exists("tz_offset", $params)) {
            // tz_offset can either be a hour offset, or a timezone like North_America/New_York
            if (is_int($params["tz_offset"]) && ($params["tz_offset"] < -12 || $params["tz_offset"] > 12)) {
                exit("integer tz_offset must be between -12 and 12");
            } elseif (!is_string($params["tz_offset"])) {
                exit("");
            }
        }
        
        if (is_bool(@$params["rollup"])) {
            $params["rollup"] = $params["rollup"] ? "true" : "false";
        }
        
        return $this->call_oauth2($endpoint, $params);
    }
    
    private function call_oauth2($endpoint, $params)
    {
        if (!$this->access_token) {
            throw new BitlyError(sprintf("This %s endpoint requires OAuth", $endpoint));
        }
        $data = $this->call(self::ssl_host, $endpoint, $params);
        return $data["data"];
    }
}
