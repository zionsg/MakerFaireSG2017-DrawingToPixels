<?php

namespace App;

/**
 * @api {post} /app Submit drawing information
 *
 * @apiParam {string} image_data_uri Data URI containing a representation of the image
 * @apiSuccessExample {json} Success-Response:
 *     HTTP/1.1 200 OK
 *     {
 *       "grid": [
 *         ["#ff0000", "#00ff00"],
 *         ["#0000ff", "#ffffff"]
 *       ],
 *       "api_call": {
 *         "code": 200,
 *         "response": "OK"
 *       }
 *     }
 * @see http://apidocjs.com
 */
class Application
{
    protected $endpointUrl;
    protected $gridWidth;
    protected $gridHeight;

    public function __construct(array $config)
    {
        $this->endpointUrl = $config['endpoint_url'];
        $this->gridWidth = $config['grid_width'];
        $this->gridHeight = $config['grid_height'];
    }

    /**
     * Parse image data uri, resize image and return grid information
     *
     * @return void JSON-encoded string will be echoed as per @apiSuccessExample
     */
    public function run()
    {
        // Get image data uri from POST request param
        $imgDataUri = isset($_POST['image_data_uri']) ? $_POST['image_data_uri'] : '';
        if (! $imgDataUri) {
            return $this->response([
                'grid' => []
            ]);
        }

        // Create image resource
        $imgData = str_replace(' ', '+', $imgDataUri);
        $imgData = substr($imgData, strpos($imgData, ',') + 1);
        $imgData = base64_decode($imgData);
        $image = imagecreatefromstring($imgData);
        $width = imagesx($image);
        $height = imagesy($image);

        // Resize image to fit grid
        $resizedImage = imagecreatetruecolor($this->gridWidth, $this->gridHeight);
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $this->gridWidth, $this->gridHeight, $width, $height);

        // Sending to external API column by column, with even columns reversed
        // LED board is wired as follows for a 3 x 2 grid:
        //     01  04  05
        //     02  03  06
        $grid = [];
        for ($x = 0; $x < $this->gridWidth; $x++) {
            $column = [];

            for ($y = 0; $y < $this->gridHeight; $y++) {
                $rgb = imagecolorat($resizedImage, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $cell = sprintf(
                    '#%s%s%s',
                    $this->decToHex($r),
                    $this->decToHex($g),
                    $this->decToHex($b)
                );

                if (0 === ($x % 2)) {
                    $column[] = $cell;
                } else {
                    array_unshift($column, $cell);
                }
            }

            $grid[] = implode(',', $column);
        }

        // Send data to external API
        $data = implode(',', $grid);
        $apiCall = $this->call($this->endpointUrl, ['data' => $data]);

        return $this->response([
            'grid' => $grid,
            'api_call' => $apiCall,
        ]);
    }

    /**
     * Convert decimal to 2-digit hex
     *
     * @param  int $decimal
     * @return string
     */
    protected function decToHex($decimal)
    {
        $hex = dechex($decimal);

        return str_pad($hex, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Send cURL request to external API
     *
     * @param  string $url
     * @param  array $data
     * @return array ['code' => <HTTP response code>, 'response' => <response data>]
     */
    protected function call($url, array $data)
    {
        if (! $url) {
            return [
                'code' => null,
                'response' => null,
            ];
        }

        $headers = ['Content-Type: application/x-www-form-urlencoded; charset=utf-8'];
        $postDataStr = '';
        foreach ($data as $key => $value) {
            $postDataStr .= "${key}=${value}";
        }

        $curlHandler = curl_init();
        curl_setopt_array($curlHandler, [
            CURLOPT_RETURNTRANSFER => true, // return value instead of output to browser
            CURLOPT_HEADER => false, // do not include headers in return value
            CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'], // some servers reject requests with no user agent
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postDataStr,
        ]);
        $apiResponse = curl_exec($curlHandler);
        $curlInfo = curl_getinfo($curlHandler);
        $apiCode = $curlInfo['http_code'];
        curl_close($curlHandler);

        return [
            'code' => $apiCode,
            'response' => $apiResponse,
        ];
    }

    /**
     * Return JSON response
     *
     * @param  array $data
     * @return void
     */
    protected function response(array $data, $responseCode = 200)
    {
        $response = json_encode($data);
        header_remove();
        http_response_code($responseCode);
        header('Content-Type: application/json; charset=utf8');
        echo $response;
        exit;
    }
}
