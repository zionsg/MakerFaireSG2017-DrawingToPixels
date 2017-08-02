# MakerFaire SG 2017 - Sketch Drawing To Pixels

This was the precursor to the [actual drawing app](https://github.com/zionsg/MakerFaireSG2017-KonvaDrawingToPixels) which uses Konva instead. It allows users to do a freehand sketch in the browser and send it as a scaled down set of pixels to the NeoPixel LED matrix of the Interactive Wall.

## Requirements
- PHP >= 7.0
- Apache >= 2.4
- [Composer](https://getcomposer.org/)
- [Yarn](https://yarnpkg.com/)

## Installation
- Clone this repo.
- Run `composer install` to install PHP dependencies.
- Run `yarn install` to install client-side dependencies such as Javascript and CSS libraries.
- Copy `config/autoload/local.php.dist` to `config/autoload/local.php` and update values accordingly.
- Run `index.html` in browser. To debug, check console output in browser.
