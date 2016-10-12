<?php



/**
 * Плъгин за документи, които може да ги създават партньори
 * 
 * @category  bgerp
 * @package   colab
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class colab_plg_CreateDocument extends core_Plugin
{
	
	
	/**
	 * Какви роли са необходими за качване или сваляне?
	 */
	public static function on_BeforeGetRequiredRoles($mvc, &$roles, $action, $rec = NULL, $userId = NULL)
	{
		if(($action == 'add' || $action == 'edit')){
			$addContractor = FALSE;
			
			if(core_Users::isContractor($userId)){
				$documents = colab_Setup::get('CREATABLE_DOCUMENTS');
				if(keylist::isIn($mvc->getClassId(), $documents)){
					$addContractor = TRUE;
				}
			}
			
			if(isset($rec)){
				if($action == 'edit'){
					if($rec->createdBy != $userId){
						$addContractor = FALSE;
					}
				} elseif($action == 'add') {
					$sharedFolders = colab_Folders::getSharedFolders($userId);
					if(!in_array($rec->folderId, $sharedFolders)){
						$addContractor = FALSE;
					}
				}
			}
			
			if($addContractor === TRUE){
				$property = ucfirst($action);
				$property = "can{$property}";
				$mvc->{$property} .= ",contractor";
			}
		}
	}
}