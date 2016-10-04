<?php

/*
 * This file is part of Huxtable\Pixel
 */
namespace Huxtable\Pixel;

$pathBasePixel	= __DIR__;
$pathSrcPixel	= $pathBasePixel . '/src/Pixel';
$pathVendorPixel	= $pathBasePixel . '/vendor';

/*
 * Initialize autoloading
 */
include_once( $pathSrcPixel . '/Autoloader.php' );
Autoloader::register();
