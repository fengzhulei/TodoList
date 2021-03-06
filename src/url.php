<?php
/**
 * Contains the url-class
 * 
 * @package			todolist
 * @subpackage	src
 *
 * Copyright (C) 2003 - 2016 Nils Asmussen
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

/**
 * An extended URL-class which contains some additional stuff.
 *
 * @package			todolist
 * @subpackage	src
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class TDL_URL extends FWS_URL
{
	/**
	 * Works the same like get_url but is mainly intended for usage in the templates.
	 * You can use the following shortcut for the constants (in <var>$additional</var>):
	 * <code>$<name></code>
	 * This will be mapped to the constant:
	 * <code><constants_prefix><name></code>
	 * Note that the constants will be assumed to be in uppercase!
	 * 
	 * @param string|int $target the action-parameter (0 = current, -1 = none)
	 * @param string $additional additional parameters
	 * @param string $separator the separator of the params (default is &amp;)
	 * @return string the url
	 */
	public static function simple_url($target = 0,$additional = '',$separator = '&amp;')
	{
		if($additional != '')
		{
			$additional = preg_replace_callback(
				'/\$([a-z0-9_]+)/i',
				function($m)
				{
					return constant('TDL_'.$m[1]);
				},
				$additional
			);
		}
		return self::get_url($target,$additional,$separator);
	}
	
	/**
	 * The default method. This generates an URL with given parameters and returns it.
	 * The extern-variables (if you want it) and the session-id (if necessary)
	 * will be appended.
	 * The file will be <var>$_SERVER['PHP_SELF']</var>.
	 *
	 * @param string|int $target the action-parameter (0 = current, -1 = none)
	 * @param string $additional additional parameters
	 * @param string $separator the separator of the params (default is &amp;)
	 * @return string the url
	 */
	private static function get_url($target = 0,$additional = '',$separator = '&amp;')
	{
		$url = new TDL_URL();
		$url->set_separator($separator);
		
		$input = FWS_Props::get()->input();
		
		// add action
		$action_param = $input->get_var(TDL_URL_ACTION,'get',FWS_Input::STRING);
		if($target === 0 && $action_param !== null)
			$url->set(TDL_URL_ACTION,$action_param);
		else if($target !== -1)
			$url->set(TDL_URL_ACTION,$target);
		else
			$url->set(TDL_URL_ACTION,'view_entries');
		
		// add additional params
		foreach(FWS_Array_Utils::advanced_explode($separator,$additional) as $param)
		{
			@list($k,$v) = explode('=',$param);
			$url->set($k,$v);
		}
		
		return $url->to_url();
	}
	

	/**
	 * Builds an URL for the given module.
	 *
	 * @param string|int $mod the module-name (0 = current, -1 = none)
	 * @param string $separator the separator of the params (default is &amp;)
	 * @return string the url
	 */
	public static function build_mod_url($mod = 0,$separator = '&amp;')
	{
		$url = self::get_mod_url($mod,$separator);
		return $url->to_url();
	}

	/**
	 * Builds an URL-instance for the given module.
	 *
	 * @param string|int $mod the module-name (0 = current, -1 = none)
	 * @param string $separator the separator of the params (default is &amp;)
	 * @return BS_URL the url-instance
	 */
	public static function get_mod_url($mod = 0,$separator = '&amp;')
	{
		$url = new self();
		$url->set_separator($separator);

		if($mod === 0)
		{
			$input = FWS_Props::get()->input();
			$action = $input->get_var(TDL_URL_ACTION,'get',FWS_Input::STRING);
			if($action != null)
				$url->set(TDL_URL_ACTION,$action);
		}
		else if($mod !== -1)
			$url->set(TDL_URL_ACTION,$mod);

		return $url;
	}

	/**
	 * Builds the base-url for the entries
	 *
	 * @return FWS_URL the URL
	 */
	public static function get_entry_url()
	{
		$input = FWS_Props::get()->input();
		$url = new self();
		$vals = array(
			TDL_URL_S_KEYWORD,
			TDL_URL_S_FROM_CHANGED_DATE,TDL_URL_S_TO_CHANGED_DATE,
			TDL_URL_S_FROM_START_DATE,TDL_URL_S_TO_START_DATE,
			TDL_URL_S_FROM_FIXED_DATE,TDL_URL_S_TO_FIXED_DATE,
			TDL_URL_S_TYPE,TDL_URL_S_PRIORITY,TDL_URL_S_STATUS,TDL_URL_S_CATEGORY,
			TDL_URL_SITE
		);
		foreach($vals as $val)
			$url->set($val,$input->get_predef($val,'get'));
		$url->set(TDL_URL_ORDER,$input->get_predef(TDL_URL_ORDER,'get','changed'));
		$url->set(TDL_URL_AD,$input->get_predef(TDL_URL_AD,'get','DESC'));
		return $url;
	}

	/**
	 * Constructor
	 */
	public function __construct()
	{
		self::set_append_extern_vars(true);
		
		parent::__construct();
	}

	/**
	 * @see FWS_URL::is_intern($param)
	 *
	 * @param string $param
	 * @return boolean
	 */
	public function is_intern($param)
	{
		static $params = null;
		if($params === null)
		{
			$params = array(
				TDL_URL_ACTION,TDL_URL_LOC,TDL_URL_MODE,TDL_URL_AT,TDL_URL_ID,TDL_URL_IDS,TDL_URL_SID,
				TDL_URL_SITE,TDL_URL_ORDER,TDL_URL_AD,TDL_URL_LIMIT,TDL_URL_S_KEYWORD,
				TDL_URL_S_FROM_CHANGED_DATE,TDL_URL_S_TO_CHANGED_DATE,TDL_URL_S_FROM_START_DATE,
				TDL_URL_S_TO_START_DATE,TDL_URL_S_FROM_FIXED_DATE,TDL_URL_S_TO_FIXED_DATE,
				TDL_URL_S_TYPE,TDL_URL_S_PRIORITY,TDL_URL_S_STATUS,TDL_URL_S_CATEGORY
			);
		}

		return in_array($param,$params);
	}
}
?>