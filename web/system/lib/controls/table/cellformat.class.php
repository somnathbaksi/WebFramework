<?php
/**
 * Scavix Web Development Framework
 *
 * Copyright (c) 2007-2012 PamConsult GmbH
 * Copyright (c) since 2013 Scavix Software Ltd. & Co. KG
 *
 * This library is free software; you can redistribute it
 * and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation;
 * either version 3 of the License, or (at your option) any
 * later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library. If not, see <http://www.gnu.org/licenses/>
 *
 * @author PamConsult GmbH http://www.pamconsult.com <info@pamconsult.com>
 * @copyright 2007-2012 PamConsult GmbH
 * @author Scavix Software Ltd. & Co. KG http://www.scavix.com <info@scavix.com>
 * @copyright since 2012 Scavix Software Ltd. & Co. KG
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */
namespace ScavixWDF\Controls\Table;

use ScavixWDF\Localization\CultureInfo;

/**
 * Handles cell formatting in a <Table>.
 * 
 * Never instanciate this class yourself, this will be done by <Table::SetFormat> or <Table::SetColFormat>.
 * Valid formats are:
 * - duration
 * - fixed, pre, preformatted
 * - date
 * - time
 * - datetime
 * - curreny
 * - int, integer
 * - percent
 * - float, double, f2, d2
 * In fact you may also use `array('float',4)` if you want a float with four decimal places but this array syntax only applies to float/double.
 * How the values are actually formatted depends on the <CultureInfo> you chose.
 */
class CellFormat
{
    var $format = false;
    var $blank_if_false = false;
	var $conditional_css = array();

	function __construct($format=false,$blank_if_false=false,$conditional_css=array())
	{
		if( $format )
		{
			if( is_string($format) )
			{
				switch( strtolower($format) )
				{
					case 'f2': 
					case 'd2': 
					case 'float': 
					case 'double': 
						$format = array('double',2);
						break;
				}
			}			
			$this->format = $format;
			$this->blank_if_false = $blank_if_false;
			$this->conditional_css = $conditional_css;
		}
	}
	
	/**
	 * Gets the format.
	 * 
	 * @return string The format
	 */
	function GetFormat()
	{
		if( is_array($this->format) )
			list($format,$options) = $this->format;
		else
			$format = $this->format;
		return $format;
	}

	/**
	 * @internal Performs formatting of a table cell (<Td>)
	 */
	function Format(&$cell,$culture=false)
	{
		$full_content = $cell->GetContent();
		$content = $this->FormatContent($full_content,$culture);
		if( $this->blank_if_false && !$content )
		{
			$cell->SetContent("");
			return;
		}

		$cell->SetContent( $content );
		$ccss = $this->GetConditonalCss();
		if( $ccss )
		{
			$cs = isset($cell->style)?$cell->style:"";
			$cell->style = $cs.$ccss;
		}
	}
	
	/**
	 * Formats a given string.
	 * 
	 * @param string $full_content The string to format
	 * @param CultureInfo $culture <CultureInfo> object or false if not present
	 * @return string The formatted string
	 */
	function FormatContent($full_content,$culture=false)
	{
		$this->content = $content = trim(strip_tags($full_content));
		if( $this->blank_if_false && !$content )
			return "";
		
		if( is_array($this->format) )
		{
			list($format,$options) = $this->format;
			$format = strtolower($format);
			if(!is_array($options))
				$options = array($options);
		}
		else
			$format = strtolower($this->format);

		if( $format == 'duration' )
		{
			$completedur = $dur = intval($content);
			$s = sprintf("%02u",$dur % 60);
			$dur = floor($dur / 60);
			$h = floor($dur / 60);
			if( $completedur == 0 )
				$content = "0:00";
			elseif( $h > 0 )
			{
				$m = sprintf("%02u",$dur % 60);
				$content = str_replace($content,"$h:$m:$s",$full_content);
			}
			else
			{
				$m = sprintf("%u",$dur % 60);				
				$content = str_replace($content,"$m:$s",$full_content);
			}
		}
		if( $format == 'fixed' || $format == 'pre' || $format == 'preformatted' )
		{
			$content = str_replace($content,"<pre>$content</pre>",$full_content);
		}
		elseif( $culture )
		{
			switch( $format )
			{
				case 'date':
					$content = str_replace($content,$culture->FormatDate($content),$full_content);
					break;
				case 'time':
					$content = str_replace($content,$culture->FormatTime($content),$full_content);
					break;
				case 'datetime':
					$content = str_replace($content,$culture->FormatDateTime($content),$full_content);
					break;
				case 'currency':
					$content = str_replace($content,$culture->FormatCurrency($content),$full_content);
					break;
				case 'int':
				case 'integer':
					$content = str_replace($content,$culture->FormatInt($content),$full_content);
					break;
				case 'percent':
					$content = str_replace($content,$culture->FormatInt($content)."%",$full_content);
					break;
				case 'float':
				case 'double':
					$content = str_replace($content,$culture->FormatNumber($content,intval($options[0])),$full_content);
					break;
			}
		}
		else
			$content = str_replace($content,sprintf($format,$content),$full_content);
		
		return $content;
	}

	private function GetConditonalCss()
	{
		$content = $this->content;
		foreach( $this->conditional_css as $cond=>$css )
		{
			switch( strtolower($cond) )
			{
				case 'neg':
				case 'negative':
					if( floatval($content) < 0 )
					{
//						log_debug("$cond => $css");
						return $css;
					}
					break;
				case 'pos':
				case 'positive':
					if( floatval($content) > 0 )
					{
//						log_debug("$cond => $css");
						return $css;
					}
					break;
				case 'copy':
//					log_debug("$cond => ".render_var($css));
					return $css->GetConditonalCss();
					break;
			}
		}
		return "";
	}
}
