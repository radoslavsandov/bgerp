<?php


/**
 * Клас 'gen_Plugin' -
 *
 * Добавя родословно дърво към хората от визитника
 *
 * @category   Experta Framework
 * @package    gen
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class gen_Plugin extends core_Plugin
{
    
    
    /**
     *  Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        $mvc->FLD('mother', 'key(mvc=crm_Persons, allowEmpty, select=name)', 'caption=Родители->Майка');
        $mvc->FLD('father', 'key(mvc=crm_Persons, allowEmpty, select=name)', 'caption=Родители->Баща');
    }
    
    
    /**
     *  Извиква се преди извличането на вербална стойност за поле от запис
     */
    function on_AfterRecToVerbal($mvc, $row, $rec) 
    {   
        $row->parents = new ET();

        if($rec->mother) {
            $row->parents->append(ht::createLink($mvc->getVerbal($rec, 'mother'), array('crm_Persons', 'single', $rec->mother)));
        }
        if($rec->father) {
            $row->parents->append('<br>');  
            $row->parents->append(   ht::createLink( $mvc->getVerbal($rec, 'father') , array('crm_Persons', 'single', $rec->father))) ;
        }

    }
    
    
    /**
     *  Извиква се след поготовката на колоните ($data->listFields)
     */
    function on_AfterPrepareListFields($mvc, $data)
    { 
        $data->listFields = $this->insertAfter($data->listFields, 'nameList', 'parents', 'Родители');
    }
    



    /**
     *
     */
    function on_AfterPrepareEditForm($mvc, $data)
    { 
        $mothers = crm_Persons::makeArray4Select('name', "#salutation != 'mr' AND #salutation != 'miss'");
        $fathers = crm_Persons::makeArray4Select('name', "#salutation != 'mrs' AND #salutation != 'miss'");
        
        if($data->form->rec->id) { 
            unset($mothers[$data->form->rec->id]);
            unset($fathers[$data->form->rec->id]);
        }
        $data->form->setOptions('mother', $mothers);
        $data->form->setOptions('father', $fathers);

        if(!count($mothers)) $data->form->setField('mother', 'input=none');
        if(!count($fathers)) $data->form->setField('father', 'input=none');

    }

    
    /**
     *  @todo Чака за документация...
     */
    function insertAfter($sourceArr, $afterField, $key, $value)
    {
        foreach($sourceArr as $k => $v) {
            $destArr[$k] = $v;
            
            if($k == $afterField) {
                $destArr[$key] = $value;
            }
        }
        
        return $destArr;
    }
}