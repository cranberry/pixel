<?php

/*
 * This file is part of Huxtable\Pixel
 */
namespace Huxtable\Pixel;

use Huxtable\Core\File;
use Imagick;
use ImagickDraw;

class Canvas
{
	/**
	 * @var	string
	 */
	protected $backgroundColor='#ffffff00';

	/**
	 * @var	array
	 */
	protected $backgroundGradient;

	/**
	 * @var	int
	 */
	protected $cols;

	/**
	 * @var	array
	 */
	protected $layers = [];

	/**
	 * @var	int
	 */
	protected $pixelSize;

	/**
	 * @var	int
	 */
	protected $rows;

	/**
	 * @var	boolean
	 */
	protected $shouldDraw;

	/**
	 * @param	int		$cols
	 * @param	int		$rows
	 * @param	int		$pixelSize
	 */
	public function __construct( $cols, $rows, $pixelSize )
	{
		$this->cols = $cols * $pixelSize;
		$this->rows = $rows * $pixelSize;
		$this->pixelSize = $pixelSize;

		$this->image = new Imagick();
		$this->draw = new ImagickDraw();
	}

	/**
	 * @param	Huxtable\Pixel\Canvas	$canvas
	 * @param	int						$xOffset
	 * @param	int						$yOffset
	 * @param	int						$compose
	 */
	public function compositeCanvas( Canvas $canvas, $offsetCol, $offsetRow, $compose=Imagick::COMPOSITE_DEFAULT )
	{
		$layer['canvas'] = $canvas;
		$layer['xOffset'] = $offsetCol * $this->pixelSize;
		$layer['yOffset'] = $offsetRow * $this->pixelSize;
		$layer['compose'] = $compose;

		$this->layers[] = $layer;
	}

	/**
	 * @param	int		$col
	 * @param	int		$row
	 * @param	string	$color
	 */
	public function drawAt( $col, $row, $color )
	{
		$this->draw->setFillColor( $color );

		$x1 = $col * $this->pixelSize;
		$y1 = $row * $this->pixelSize;
		$x2 = ($col + 1) * $this->pixelSize - 1;
		$y2 = ($row + 1) * $this->pixelSize - 1;

		/* Don't attempt to draw off canvas */
		if( $x1 < 0 || $x1 >= $this->cols )
		{
			return false;
		}
		if( $x2 < 0 || $x2 >= $this->cols )
		{
			return false;
		}
		if( $y1 < 0 || $y1 >= $this->rows )
		{
			return false;
		}
		if( $y2 < 0 || $y2 >= $this->rows )
		{
			return false;
		}

		$this->draw->rectangle( $x1, $y1, $x2, $y2 );
		$this->shouldDraw = true;
	}

	/**
	 * @param	int		$col
	 * @param	int		$row
	 * @param	string	$color
	 */
	public function drawWithReflectionAt( $col, $row, $color )
	{
		$this->drawAt( $col, $row, $color );
		$this->drawAt( ($this->cols / $this->pixelSize) - $col - 1, $row, $color );
	}

	/**
	 * @param	int		$col1
	 * @param	int		$row1
	 * @param	int		$cols
	 * @param	int		$rows
	 * @param	string	$color
	 */
	public function fillRectangle( $col1, $row1, $cols, $rows, $color )
	{
		$this->draw->setFillColor( $color );

		if( $col1 < 0 )
		{
			$cols = $cols + $col1;
			$col1 = 0;
		}
		if( $row1 < 0 )
		{
			$rows = $rows + $row1;
			$row1 = 0;
		}
		if( $cols >= $this->cols )
		{
			$cols = $this->cols - 1;
		}
		if( $rows >= $this->rows )
		{
			$rows = $this->rows - 1;
		}

		/* Clip if necessary */
		$x1 = $col1 * $this->pixelSize;
		$y1 = $row1 * $this->pixelSize;
		$x2 = ($col1 + $cols) * $this->pixelSize - 1;
		$y2 = ($row1 + $rows) * $this->pixelSize - 1;

		$this->draw->rectangle( $x1, $y1, $x2, $y2 );
		$this->shouldDraw = true;
	}

	/**
	 * @return	int
	 */
	public function getCols()
	{
		return $this->cols / $this->pixelSize;
	}

	/**
	 * @return	Imagick
	 */
	public function getImage()
	{
		if( is_null( $this->backgroundGradient ) )
		{
			$this->image->newImage( $this->cols, $this->rows, $this->backgroundColor );
		}
		else
		{
			$gradient = "gradient:{$this->backgroundGradient['start']}-{$this->backgroundGradient['stop']}";
			$this->image->newPseudoImage( $this->cols, $this->rows, $gradient );
		}

		$this->image->setImageFormat( 'png' );

		if( $this->shouldDraw )
		{
			$this->image->drawImage( $this->draw );
		}

		/* Composite images */
		foreach( $this->layers as $layer )
		{
			$layerImage = $layer['canvas']->getImage();
			$this->image->compositeImage( $layerImage, $layer['compose'], $layer['xOffset'], $layer['yOffset'] );
		}

		return $this->image;
	}

	/**
	 * @return	int
	 */
	public function getPixelSize()
	{
		return $this->pixelSize;
	}

	/**
	 * @return	int
	 */
	public function getRows()
	{
		return $this->rows / $this->pixelSize;
	}

	/**
	 * @param	Huxtable\Core\File\File		$imageFile
	 */
	public function render( File\File $imageFile )
	{
		$image = $this->getImage();

		/*
		 * Set transparent pixel
		 */
		$iterator = $image->getPixelIterator();

		/* Set pixels in first rows */
		$iterator->setIteratorFirstRow();
		$row = $iterator->getCurrentIteratorRow();

		$pixel = $row[0];
		$pixel->setColor( "#ffffff00");
		$pixel = $row[ count( $row ) - 1 ];
		$pixel->setColor( "#ffffff00");

		$iterator->syncIterator();

		/* Set pixels in last rows */
		$iterator->setIteratorLastRow();
		$row = $iterator->getCurrentIteratorRow();

		$pixel = $row[0];
		$pixel->setColor( "#ffffff00");
		$pixel = $row[ count( $row ) - 1 ];
		$pixel->setColor( "#ffffff00");

		$iterator->syncIterator();

		/*
		 * Write to file
		 */
		$imageFile->putContents( $this->image->getImageBlob() );
	}

	/**
	 * @param	string	$backgroundColor
	 */
	public function setBackgroundColor( $backgroundColor )
	{
		$this->backgroundColor = $backgroundColor;
	}

	/**
	 * @param	string	$colorStart
	 * @param	string	$colorStop
	 */
	public function setBackgroundGradient( $colorStart, $colorStop )
	{
		$this->backgroundGradient['start'] = $colorStart;
		$this->backgroundGradient['stop'] = $colorStop;
	}
}
