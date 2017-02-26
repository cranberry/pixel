<?php

/*
 * This file is part of Cranberry\Pixel
 */
namespace Cranberry\Pixel;

class Mask
{
	/**
	 * @var	int
	 */
	protected $cols;

	/**
	 * @var	array
	 */
	protected $pixels=[];

	/**
	 * @var	int
	 */
	protected $rows;

	/**
	 * Define all pixels, and fill if appropriate
	 *
	 * @param	int			$cols
	 * @param	int			$rows
	 * @param	boolean		$fill
	 */
	public function __construct( $cols, $rows, $fill=true )
	{
		$this->cols = $cols;
		$this->rows = $rows;

		for( $row = 0; $row < $this->rows; $row++ )
		{
			for( $col = 0; $col < $this->cols; $col++ )
			{
				$this->pixels[$row][$col] = ($fill == true);
			}
		}
	}

	/**
	 * @param	int			$col
	 * @param	int			$row
	 */
	public function clearAt( $col, $row )
	{
		if( isset( $this->pixels[$row][$col] ) )
		{
			$this->pixels[$row][$col] = false;
		}
	}

	/**
	 * @param	Cranberry\Pixel\Mask	$mask
	 * @param	string					$color
	 * @param	int						$colOffset
	 * @param	int						$rowOffset
	 */
	public function clearMask( Mask $mask, $colOffset, $rowOffset )
	{
		$maskPixels = $mask->getPixels();

		foreach( $maskPixels as $row => $cols )
		{
			foreach( $cols as $col => $enabled )
			{
				if( $enabled == true )
				{
					$this->clearAt( $col + $colOffset, $row + $rowOffset );
				}
			}
		}
	}

	/**
	 * @param	int			$col
	 * @param	int			$row
	 */
	public function fillAt( $col, $row )
	{
		if( isset( $this->pixels[$row][$col] ) )
		{
			$this->pixels[$row][$col] = true;
		}
	}

	/**
	 * @param	int		$col1
	 * @param	int		$row1
	 * @param	int		$col2
	 * @param	int		$row2
	 */
	public function fillRectangle( $col1, $row1, $col2, $row2 )
	{
		for( $col = $col1; $col <= $col2; $col++ )
		{
			for( $row = $row1; $row <= $row2; $row++ )
			{
				$this->fillAt( $col, $row );
			}
		}
	}

	/**
	 * @return	int
	 */
	public function getCols()
	{
		return $this->cols;
	}

	/**
	 * @return	array
	 */
	public function getPixels()
	{
		return $this->pixels;
	}

	/**
	 * @return	int
	 */
	public function getRows()
	{
		return $this->rows;
	}

	/**
	 * Swap filled pixels to unfilled, unfilled pixels to filled
	 */
	public function invert()
	{
		for( $row = 0; $row < $this->rows; $row++ )
		{
			for( $col = 0; $col < $this->cols; $col++ )
			{
				$currentValue = $this->pixels[$row][$col];
				switch( $currentValue )
				{
					case true:
					case false:
						$newValue = !$currentValue;
						break;

					default:
						$newValue = $currentValue;
						break;
				}

				$this->pixels[$row][$col] = $newValue;
			}
		}
	}

	/**
	 * @param	int		$col
	 * @param	int		$row
	 * @return	boolean
	 */
	public function isFilledAt( $col, $row )
	{
		if( !isset( $this->pixels[$row][$col] ) )
		{
			return false;
		}

		return $this->pixels[$row][$col] == true;
	}
}
