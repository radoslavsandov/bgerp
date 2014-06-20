<?php



/**
 * Клас 'deals_Wrapper'
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('deals_Deals', 'Финансови', 'dealsMaster, ceo');
        $this->TAB('deals_AdvanceDeals', 'ПОЛ', 'dealsMaster, ceo');
        $this->TAB('deals_DebitDocuments', 'Прехвърления', 'dealsMaster, ceo');
        
        $this->title = 'Сделки';
    }
}