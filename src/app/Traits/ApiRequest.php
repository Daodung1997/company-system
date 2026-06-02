<?php

namespace App\Traits;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

trait ApiRequest
{
    public function client()
    {
        return new Client([
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function get($url, $header = [])
    {
        try {
            $headers = ['headers' => $header];
            $request = $this->client()->request('GET', $url, $headers);
            $response = $request->getBody()->getContents();

            return json_decode($response, true);
        } catch (GuzzleException $e) {
            $this->log($url, $e);
        }
    }

    public function post($url, $data, $header = [])
    {
        try {
            $body = ['body' => json_encode($data, JSON_UNESCAPED_SLASHES)];
            $headers = ['headers' => $header];
            $request = $this->client()->request('POST', $url, array_merge($body, $headers));
            $response = $request->getBody()->getContents();

            return json_decode($response, true);
        } catch (RequestException $e) {
            $this->log($url, $e, $data);
            if ($e->hasResponse()) {
                $response = $e->getResponse();

                return json_decode($response->getBody(), true);
            }
        }
    }

    public function delete($url, $header = [])
    {
        try {
            $headers = ['headers' => $header];
            $request = $this->client()->request('DELETE', $url, $headers);
            $response = $request->getBody()->getContents();

            return json_decode($response, true);
        } catch (GuzzleException $e) {
            $this->log($url, $e);
        }
    }

    public function postForm($url, $data, $header = [])
    {
        try {
            $body = ['form_params' => $data];
            $headers = ['headers' => $header];
            $request = $this->client()->request('POST', $url, array_merge($body, $headers));
            $content = $request->getBody()->getContents();

            return json_decode($content, true);
        } catch (GuzzleException $e) {
            $this->log($url, $e, $data);
        }
    }

    private function log($url, $e, $data = null)
    {
        Log::debug(' ****** Start ApiRequest Log ****** '.Carbon::now());
        Log::debug(' *** Call API: '.$url);
        if (! empty($data)) {
            Log::debug(' *** Data API: ', $data);
        }
        Log::debug(' *** Result API: '.$e->getMessage());
    }
}

// Ex:
// $data = $this->get('https://gorest.co.in/public-api/users/1676');
// $data = $this->post('https://gorest.co.in/public-api/users/', ['name' => "quandv", 'email' => 'quandv.125@gmail.com', 'status' => 'Active', 'gender' => 'Female']);
// $data = $this->put('https://gorest.co.in/public-api/users/123', ['name' => "quandv", 'email' => 'quandv.125@gmail.com', 'status' => 'Active']);
// $data = $this->del('https://gorest.co.in/public-api/users/123');
