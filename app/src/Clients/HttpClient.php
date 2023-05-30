<?php
namespace SWCPR\Clients;

use \WpOrg\Requests\{Requests, Response, Hooks};

/**
 * HttpClient is implemented on top of WpOrg\Requests, but with a crucial fix: ensure CURL only resolves hostname over ipv4, as SWC is only reachable over ipv4.
 */
class HttpClient {
    private array $defaultRequestOptions = [];

    public function __construct() {
        $hooks = new Hooks();
        $hooks->register('curl.before_request', function($curlHandle) {
            curl_setopt($curlHandle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        });

        $this->defaultRequestOptions['hooks'] = $hooks;
        $this->defaultRequestOptions['useragent'] = 'swcombine-prospecting-results (Coding challenge, Tisdar Parrelli)';
    }

    public function get($url, $headers = [], $options = []): Response
    {

        return Requests::get($url, $headers, array_merge($this->defaultRequestOptions, $options));
    }

    public function head($url, $headers = [], $options = []): Response
    {
        return Requests::head($url, $headers, array_merge($this->defaultRequestOptions, $options));
    }

    public function delete($url, $headers = [], $options = []): Response
    {
        return Requests::delete($url, $headers, array_merge($this->defaultRequestOptions, $options));
    }

    public function post($url, $headers = [], $data = [], $options = []): Response
    {
        return Requests::post($url, $headers, $data, array_merge($this->defaultRequestOptions, $options));
    }

    public function put($url, $headers = [], $data = [], $options = []): Response
    {
        return Requests::put($url, $headers, $data, array_merge($this->defaultRequestOptions, $options));
    }

    public function options($url, $headers = [], $data = [], $options = []): Response
    {
        return Requests::options($url, $headers, $data, array_merge($this->defaultRequestOptions, $options));
    }

    public function patch($url, $headers, $data = [], $options = []): Response
    {
        return Requests::patch($url, $headers, $data, array_merge($this->defaultRequestOptions, $options));
    }
}