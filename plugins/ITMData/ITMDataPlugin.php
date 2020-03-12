<?php
/**
 * ITM Data
 * Validating restricted data inputs for ITM collection
 *
 * This Omeka 2.0+ plugin logs curatorial actions such as adding, deleting, or
 * modifying items, collections and files.
 *
 * @copyright Copyright 2016 - National Science Museum Thailand
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 *
 * @package ITMData
 */

/**
 * ITMData plugin class
 *
 * @package ITMData
 */
class ITMDataPlugin extends Omeka_Plugin_AbstractPlugin {

	protected $_hooks = array(
        'install',
        'uninstall',
        'admin_head',
        'initialize'
    );

     protected $_filters = array(
     	                           'itemIdentifierValidator' => array('Validate', 'Item', 'Dublin Core', 'Identifier')
                                );

	public function hookInstall () {
		

	}
	
	public function hookUninstall () {
		
	}

    public function hookInitialize() {
        add_translation_source(dirname(__FILE__) . '/languages');
    }

    public function hookAdminHead() {

    }

    public function itemIdentifierValidator($isValid, $args) {

        $text = $args['text'];
        $added = $args['record']->getProperty('added');

        $text = str_replace('-', '', $text);

        $messenger = new Omeka_Controller_Action_Helper_FlashMessenger();

        // TO-DO add proper logic to validate item identifier by collection
        if(strlen($text) != 14) {
            $messenger->addMessage(__('Incorrect Identifier - Length'), 'error');
            return false;
        }
        
        $itc = substr($text, 0, 3);
        $rcv_year = substr($text, 3, 2);
        $collection_no = substr($text, 5, 2);
        $sub_collection_1 = substr($text, 7, 2);
        $sub_collection_2 = substr($text, 9, 2);
        $group_counting_no = substr($text, 11, 3);

        if($itc != 'ITC') {
            
            $messenger->addMessage(__('Incorrect Identifier - Format'), 'error');
            return false;

        }

        // receiving year (after BE 2550)
        if((int) $rcv_year < 50 ) {
            $messenger->addMessage(__('Incorrect Identifier - Year (before 2550)'), 'error');
            return false;
        }

        // check item collection no
        $collection_list = array('01', '02', '03', '04');   // TO-DO remove hardcode
        if(!in_array($collection_no, $collection_list)){
            $messenger->addMessage(__('Incorrect Identifier - Collection Number'), 'error');
            return false;
        }
        
        // check item sub collection 1 no
        // check item sub collection 2 no
        if(!is_numeric($sub_collection_1) || !is_numeric($sub_collection_2)){
            $messenger->addMessage(__('Incorrect Identifier - Sub Collection Number'), 'error');
            return false;
        }

        // check item running no within sub collection - sub collection 1 - sub collection 2
        // query last item with 'ITCYYAABBCC%' 
        $item_group_identifier = $rcv_year.$collection_no.$sub_collection_1.$sub_collection_2;

        // find ID of Title Element
        $elements = get_db()->getTable('Element')->findBy(
                                                            array(
                                                                'name' => 'Identifier'
                                                                )
                                                        );
        if(count($elements) == 1) {
            $identifier_element_id = $elements[0]->id;
        } else {
            return false;
        }

        // get record
        // find element id
        $element = get_db()->getTable('Element')->findByElementSetNameAndElementName('Dublin Core', 'Identifier');
        $element_id = $element->id;
        
        // get a record
        $items = get_db()->getTable('ElementText')->findBySql(
                                                            'element_id ='.$element_id.' and text like "%'.$item_group_identifier.'%"'
                                                            );
    
        // validating item identifier 
        if(count($items) == 0) {
            // first item in this collection
            // check for nothing

        } else {
            // added item in this collection
            // recent record Identifier
            $last_record = end($items);
            $recent_identifier = $last_record->text;
            $recent_counting_no = substr($recent_identifier, 11, 3);

            if (empty($added)) {
                // in case adding new item
                if((int) $recent_counting_no >= (int) $group_counting_no ) {
                    $messenger->addMessage(__('Incorrect Identifier - number has been used in the assigned group (recent number is '.$recent_counting_no.')'), 'error');
                    return false;
                }

            } else {
                // new item in this collection
                // check for nothing

            }

        }

        return true;

    }

}