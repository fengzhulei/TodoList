<?php
/**
 * Contains the edit-project-action
 *
 * @version			$Id$
 * @package			todoList
 * @subpackage	modules
 * @author			Nils Asmussen <nils@script-solution.de>
 * @copyright		2003-2007 Nils Asmussen
 * @link				http://www.script-solution.de
 */

/**
 * The edit-project-action
 *
 * @author			Nils Asmussen <nils@script-solution.de>
 */
class TDL_Action_edit_project_edit_project extends PLIB_Actions_Base
{
	public function perform_action()
	{
		$input = PLIB_Props::get()->input();
		$db = PLIB_Props::get()->db();
		$cats = PLIB_Props::get()->cats();
		$url = PLIB_Props::get()->url();
		$versions = PLIB_Props::get()->versions();

		$pid = $input->get_predef(TDL_URL_ID,'get');
		if($pid == null)
			return TDL_GENERAL_ERROR;
		
		$project_name = $input->get_var('project_name','post',PLIB_Input::STRING);
		$project_name_short = $input->get_var('project_name_short','post',PLIB_Input::STRING);
		$start_day = $input->get_var('start_day','post',PLIB_Input::INTEGER);
		$start_month = $input->get_var('start_month','post',PLIB_Input::INTEGER);
		$start_year = $input->get_var('start_year','post',PLIB_Input::INTEGER);
		
		$start = mktime(0,0,0,$start_month,$start_day,$start_year);
		
		$project = new TDL_Objects_Project(TDL_TB_PROJECTS);
		$project->set_id($pid);
		$project->set_project_name($project_name);
		$project->set_project_name_short($project_name_short);
		$project->set_project_start($start);
		
		if(!$project->check('update'))
			return $project->errors();
		
		$project->update();
		
		$nversions = $input->get_var('version','post');
		if(is_array($nversions))
		{
			foreach($nversions as $id => $version_name)
			{
				$db->sql_qry('UPDATE '.TDL_TB_VERSIONS." SET version_name = '".$version_name."' WHERE id = ".$id);
				$versions->set_element_field($id,'version_name',$version_name);
			}
		}
		
		$categories = $input->get_var('category','post');
		if(is_array($categories))
		{
			foreach($categories as $id => $category_name)
			{
				$db->sql_qry('UPDATE '.TDL_TB_CATEGORIES." SET category_name = '".$category_name."' WHERE id = ".$id);
				$cats->set_element_field($id,'category_name',$category_name);
			}
		}
		
		$this->set_success_msg('Das Projekt wurde erfolgreich editiert');
		$this->set_redirect(
			true,
			$url->get_URL('edit_project','&amp;'.TDL_URL_MODE.'=edit&amp;'.TDL_URL_ID.'='.$pid)
		);
		$this->set_show_status_page(false);
		$this->set_action_performed(true);
	
		return '';
	}
}
?>