<?php

/*
 * This file is part of Cranberry\Pixel
 */
namespace Cranberry\Pixel;

use Cranberry\Core\File;
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
	 * @var	Cranberry\Pixel\Mask
	 */
	protected $stencil;

	/**
	 * @var	int
	 */
	protected $stencilColOffset;

	/**
	 * @var	int
	 */
	protected $stencilRowOffset;

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
	 * @param	Cranberry\Pixel\Mask	$stencil
	 * @param	int						$colOffset
	 * @param	int						$rowOffset
	 */
	public function applyStencil( Mask $stencil, $colOffset, $rowOffset )
	{
		$this->stencil = $stencil;
		$this->stencilColOffset = $colOffset;
		$this->stencilRowOffset = $rowOffset;
	}

	/**
	 * @param	Cranberry\Pixel\Canvas	$canvas
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

		/*
		 * Stencil
		 */
		if( $this->stencil != null )
		{
			$minStencilCol = $this->stencilColOffset;
			$maxStencilCol = $this->stencilColOffset + $this->stencil->getCols();
			$minStencilRow = $this->stencilRowOffset;
			$maxStencilRow = $this->stencilRowOffset + $this->stencil->getRows();

			if( $col >= $minStencilCol && $col <= $maxStencilCol )
			{
				if( $row >= $minStencilRow && $row <= $maxStencilRow )
				{
					$relativeStencilCol = $col - $this->stencilColOffset;
					$relativeStencilRow = $row - $this->stencilRowOffset;

					/* The stencil mask is filled at this point, so we won't draw */
					if( $this->stencil->isFilledAt( $relativeStencilCol, $relativeStencilRow ) )
					{
						return false;
					}
				}
			}
		}

		$this->draw->rectangle( $x1, $y1, $x2, $y2 );
		$this->shouldDraw = true;
	}

	/**
	* @param	int		$col1
	* @param	int		$row1
	* @param	int		$col2
	* @param	int		$row2
	* @param	string	$color
	 */
	public function drawLine( $col1, $row1, $col2, $row2, $color )
	{
		$rise = ($row2 - $row1);
		$run = ($col2 - $col1);
		$slope = $run != 0 ? $rise / $run : null;
		$rowIntercept = $row1 - ($slope * $col1);

		/* Vertical line */
		if( $slope === null )
		{
			for( $row = $row1; $row <= $row2; $row++ )
			{
				$this->drawAt( $col1, $row, $color );
			}

			return;
		}

		if( $slope > 1 || $slope < 0 )
		{
			for( $row = $row1; $row <= $row2; $row++ )
			{
				$col = ceil( ($row - $rowIntercept) / $slope );
				$this->drawAt( $col, $row, $color );
			}
		}

		for( $col = $col1; $col <= $col2; $col++ )
		{
			$row = floor( $slope * $col + $rowIntercept );
			$this->drawAt( $col, $row, $color );
		}
	}

	/**
	 * @param	Cranberry\Pixel\Mask	$mask
	 * @param	string					$color
	 * @param	int						$colOffset
	 * @param	int						$rowOffset
	 */
	public function drawWithMask( Mask $mask, $color, $colOffset, $rowOffset )
	{
		$maskPixels = $mask->getPixels();

		foreach( $maskPixels as $row => $cols )
		{
			foreach( $cols as $col => $fill )
			{
				if( $fill )
				{
					$this->drawAt( ($colOffset + $col), ($rowOffset + $row), $color );
				}
			}
		}
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
	 *
	 */
	public function removeStencil()
	{
		$this->stencil = null;
		$this->stencilColOffset = null;
		$this->stencilRowOffset = null;
	}

	/**
	 * @param	Cranberry\Core\File\File		$imageFile
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
