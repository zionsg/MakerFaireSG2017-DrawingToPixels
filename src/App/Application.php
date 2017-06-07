<?php

namespace App;

class Application
{
    protected $gridWidth;
    protected $gridHeight;

    public function __construct(array $config)
    {
        $this->gridWidth = $config['grid_width'];
        $this->gridHeight = $config['grid_height'];
    }

    /**
     * Parse image data uri, resize image and return grid information
     *
     * @return string JSON-encoded string. Example for 2x2 image:
     *     {
     *         "grid": [
     *             [[255, 0, 0], [0, 255, 0]],
     *             [[0, 0, 255], [255, 255, 255]]
     *         ]
     *     }
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

        $output = '';
        $grid = [];
        for ($y = 0; $y < $this->gridHeight; $y++) {
            $row = [];

            for ($x = 0; $x < $this->gridWidth; $x++) {
                $rgb = imagecolorat($resizedImage, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $row[] = [$r, $g, $b];
            }

            $grid[] = $row;
        }

        return $this->response([
            'grid' => $grid,
        ]);
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
