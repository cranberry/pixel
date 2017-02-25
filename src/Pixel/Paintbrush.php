<?php

/*
 * This file is part of Cranberry\Pixel
 */
namespace Cranberry\Pixel;

class Paintbrush extends Mask
{
	/**
	 * @return	array
	 */
	public function getPixels()
	{
		$pixels = $this->pixels;

		$totalPixelCount = count( $this->pixels ) * count( $this->pixels[0] );
		$randomizedPixelCount = floor( $totalPixelCount * 0.05 );

		for( $row = 0; $row < $this->rows; $row++ )
		{
			for( $col = 0; $col < $this->cols; $col++ )
			{
				$random = rand( 1, $totalPixelCount );
				if( $random >= 1 && $random <= $randomizedPixelCount )
				{
					$pixels[$row][$col] = !$pixels[$row][$col];
				}
			}
		}

		return $pixels;
	}
}
