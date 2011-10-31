<?php

/**
 * Клас 'doc_Wrapper'
 *
 * Поддържа системното меню и табовете на пакета 'doc'
 *
 * @category   Experta Framework
 * @package    doc
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id: $
 * @link
 * @since
 */
class doc_Wrapper extends core_Plugin
{
    
    
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('doc_Folders', 'Папки');

        $threadsUrl = array();
        if($folderId = request::get('folderId', 'int')) {
            $threadsUrl = array('doc_Threads', 'list', 'folderId' => $folderId);
        }
        $tabs->TAB('doc_Threads', 'Нишки', $threadsUrl);
        
        $threadDocumentsUrl = array();
        if($threadId = request::get('threadId', 'int')) {
            if(doc_Threads::haveRightFor('read', $threadId)) {
                $threadDocumentsUrl = array('doc_ThreadDocuments', 'list', 'threadId' => $threadId);
            }
        }
        $tabs->TAB('doc_ThreadDocuments', 'Документи', $threadDocumentsUrl);
        
        $tpl = $tabs->renderHtml($tpl, $invoker->className);
        
        $tpl->append(tr($invoker->title) . " » " , 'PAGE_TITLE');
    }
}