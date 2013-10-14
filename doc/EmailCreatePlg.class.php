<?php



/**
 * Клас 'doc_EmailCreatePlg'
 *
 * Плъгин за добавяне на бутона Имейл
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class doc_EmailCreatePlg extends core_Plugin
{
    
	/**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
    	// Добавя интерфейс за генериране на имейл
        $mvc->interfaces = arr::make($mvc->interfaces);
        setIfNot($mvc->interfaces['email_DocumentIntf'], 'email_DocumentIntf');
    }
    
    
    /**
     * Добавя бутон за създаване на имейл
     * @param stdClass $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareSingleToolbar($mvc, &$res, $data)
    {
        //Ако сме задали текста на бутона в класа използваме него
        $emailButtonText = $mvc->emailButtonText;
        
        //В противен случай използваме текста по подразбиране
        setIfNot($emailButtonText, 'Имейл');
        
        if (($data->rec->state != 'draft') && ($data->rec->state != 'rejected') ) {
            $retUrl = array($mvc, 'single', $data->rec->id);
            
            // Бутон за отпечатване
            $data->toolbar->addBtn($emailButtonText, array(
                    'email_Outgoings',
                    'add',
                    'originId' => $data->rec->containerId,
                    'ret_url'=>$retUrl
                ),
                'ef_icon = img/16/email_edit.png,title=Изпращане на документа по имейл', 'onmouseup=saveSelectedTextToSession();');
        }
    }
}