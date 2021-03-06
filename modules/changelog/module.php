<?php
/**
 * Contains the changelog-module
 * 
 * @package			todolist
 * @subpackage	modules
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
 * The changelog-module
 * 
 * @package			todolist
 * @subpackage	modules
 * @author			Nils Asmussen <nils@script-solution.de>
 */
final class TDL_Module_changelog extends TDL_Module
{
	/**
	 * @see FWS_Module::init($doc)
	 * 
	 * @param TDL_Document $doc
	 */
	public function init($doc)
	{
		parent::init($doc);
		
		$input = FWS_Props::get()->input();
		$renderer = $doc->use_default_renderer();
		
		$mode = $input->get_predef(TDL_URL_MODE,'get');
		if($mode == 'export')
			$doc->use_raw_renderer();
		
		$renderer->add_breadcrumb('Changelog',TDL_URL::build_mod_url('changelog'));
	}
	
	/**
	 * @see FWS_Module::run()
	 */
	public function run()
	{
		$input = FWS_Props::get()->input();
		$cfg = FWS_Props::get()->cfg();
		$versions = FWS_Props::get()->versions();
		$tpl = FWS_Props::get()->tpl();
		$db = FWS_Props::get()->db();
		$functions = FWS_Props::get()->functions();
		$user = FWS_Props::get()->user();
		$locale = FWS_Props::get()->locale();

		$mode = $input->get_predef(TDL_URL_MODE,'get');
		if($mode == 'export')
		{
			$this->_export();
			return;
		}
		
		$where = ' WHERE entry_fixed_date > 0';
		
		if($cfg['project_id'] != 0)
		{
			$sel_version = $versions->get_element_with(array('project_id' => $cfg['project_id']));
			$title = $sel_version['project_name'];
			$where .= ' AND e.project_id = '.$cfg['project_id'];
		}
		else
			$title = $locale->_('All Projects');
		
		$tpl->add_variables(array(
			'title' => $title
		));
		
		$entries = array();
		$last_version = '';
		$rows = $db->get_rows(
			'SELECT e.id,entry_title,e.project_id,entry_fixed_date,entry_start_version,
							entry_fixed_version,entry_type
			 FROM '.TDL_TB_ENTRIES.' e
			 LEFT JOIN '.TDL_TB_VERSIONS.' v ON entry_fixed_version = v.id
			 '.$where.'
			 ORDER BY e.project_id DESC, v.version_name DESC, entry_fixed_date DESC'
		);
		foreach($rows as $data)
		{
			$tpldata = array();
			$tpldata['show_version'] = false;
			
			if($last_version != $data['entry_fixed_version'])
			{
				$fixed_version = $versions->get_element($data['entry_fixed_version']);
				
				$tpldata['show_version'] = true;
				$tpldata['product_version'] = $fixed_version['project_name'].' :: '.$fixed_version['version_name'];
				
				$last_version = $data['entry_fixed_version'];
			}
			
			$tpldata['type'] = $data['entry_type'];
			$tpldata['type_text'] = $functions->get_type_text($data['entry_type']);
			$tpldata['title'] = $data['entry_title'];
			$tpldata['date'] = FWS_Date::get_date($data['entry_fixed_date']);
			
			$entries[] = $tpldata;
		}
		
		$tpl->add_variable_ref('entries',$entries);
	}
	
	/**
	 * exports the changelog
	 */
	private function _export()
	{
		$cfg = FWS_Props::get()->cfg();
		$db = FWS_Props::get()->db();
		$versions = FWS_Props::get()->versions();

		$text = '';
		
		$where = ' WHERE entry_fixed_date > 0';
		if($cfg['project_id'] != 0)
			$where .= ' AND project_id = '.$cfg['project_id'];
		
		$last_version = '';
		$rows = $db->get_rows(
			'SELECT id,entry_title,project_id,entry_fixed_date,entry_start_version,
							entry_fixed_version,entry_type,
							IF(entry_fixed_version = 0,entry_start_version,entry_fixed_version) version
			 FROM '.TDL_TB_ENTRIES.'
			 '.$where.'
			 ORDER BY project_id DESC, version DESC, entry_fixed_date DESC'
		);
		foreach($rows as $data)
		{
			if($last_version != $data['version'])
			{
				if($data['entry_fixed_version'] > 0)
					$fixed_version = $versions->get_element($data['entry_fixed_version']);
				else
					$fixed_version = $versions->get_element($data['entry_start_version']);
				
				$text .= '### '.$fixed_version['project_name'].' :: '.$fixed_version['version_name'].' ###'."\n";
				
				$last_version = $data['version'];
			}
			
			$text .= '	['.$data['entry_type'].'] '.$data['entry_title']."\n";
		}
		
		// set result to renderer
		$doc = FWS_Props::get()->doc();
		$doc->set_mimetype('text/plain');
		$doc->use_raw_renderer()->set_content(FWS_StringHelper::htmlspecialchars_back($text));
	}
}
?>