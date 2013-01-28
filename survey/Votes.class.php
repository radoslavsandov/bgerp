<?php



/**
 * Модел "Гласуване"
 *
 *
 * @category  bgerp
 * @package   survey
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class survey_Votes extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'Гласуване';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools, survey_Wrapper, plg_Sorting';
    
  
    /**
     * Кои полета да се показват в листовия изглед
     */
    //var $listFields = 'id, iban, contragent=Контрагент, currencyId, type';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Гласуване";

    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'survey, ceo, admin';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'survey, ceo, admin';
	
	
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('alternativeId', 'key(mvc=survey_Alternatives)', 'caption=Въпрос, input=hidden, silent');
    	$this->FLD('rate', 'int', 'caption=Отговор');
    	$this->FLD('userUid', 'varchar(32)', 'caption=Потребител');
    	
    	$this->setDbUnique('alternativeId, userUid');
    }
    
    
    /**
     * Екшън който записва гласуването
     */
    function act_Vote()
    {
    	$this->requireRightFor('add');
    	
    	//Намираме на кой въпрос, кой отговор е избран
    	expect($alternativeId = Request::get('alternativeId'));
    	expect($rowId = Request::get('id'));
    	
    	// Подготвяме записа
    	$rec = new stdClass();
    	$rec->alternativeId = $alternativeId;
    	$rec->rate = $rowId;
    	$rec->userUid = static::getUserUid();
    	
    	if($this->haveRightFor('add')) {
    		
    		// Записваме Гласа
    		$this->save($rec, NULL, 'ignore');
    	}
    	
    	// Редиректваме след като вота е регистриран
    	return new Redirect(getRetUrl());
    }
    
    
    /**
     * Намираме userUid-a  на гласувалия потребител:
     * Ако е потребител в системата това е ид-то му,
     * Ако анкетата е изпратена по поща това е мид-а на анкетата
     * Ако потребителя не е потребител в системата и нямаме мид, записваме
     * Ип-то му
     * @return varchar $userUid - Потребителя, който е гласувал
     */
    static function getUserUid()
    {
    	if(core_Users::haveRole('user')) {
    		$userUid = core_Users::getCurrent();
    	} elseif($mid = Request::get('m')) {
    		$userUid = $mid;
    	} else {
    		$userUid = $_SERVER['REMOTE_ADDR'];
    	}
    	
    	return $userUid;
    }
    
    
    /**
     * Преброява гласовете
     * @param int alternativeId - ид на въпроса
     * @param int row - реда който е избран
     * @return int $count - Броя гласове, които е получила всяка опция
     */
    static function countVotes($alternativeId, $row)
    {
    	$query = static::getQuery();
    	$query->where(array("#alternativeId = [#1#]", $alternativeId));
    	$query->where(array("#rate = [#1#]", $row));
    	$count = $query->count();
    	
    	return $count;
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if($fields['-list']) {
    		$altRec = survey_Alternatives::fetch($rec->alternativeId);
    		$altRow = survey_Alternatives::RecToVerbal($altRec,'label,-list');
    		
    		$row->alternativeId = $altRow->label;
    	}
    }
    
    
    /**
     * Метод проверяващ дали даден потребител вече е отговорил на даден въпрос
     * @return mixed $rec->rate/FALSE - отговора който е посочен или FALSE
     * ако няма запис
     */
    static function hasUserVoted($alternativeId)
    {
    	$userUid = static::getUserUid();
    	$query = static::getQuery();
    	$query->where("#alternativeId = {$alternativeId}");
    	$query->where("#userUid = '{$userUid}'");
    	if($rec = $query->fetch()) {
    		
    		return $rec->rate;
    	}
    	
    	return FALSE;
    }
    
    
    /**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		if($action== 'add' && !isset($rec)) {
			
			// Предпазване от добавяне на нов постинг в act_List
			$res = 'no_one';
		}
	}
}