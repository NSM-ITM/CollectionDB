<?php
/**
 * Item Loans
 *
 * @copyright Copyright 2016 - National Science Museum Thailand
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * Item Loans Missing Info model.
 */
class ItemLoansMissingInfo extends Omeka_Record_AbstractRecord {

    public $id;
    public $txid;
    public $item_id;
    public $missing_note;
    public $missing_action_note;
    public $missing_recover_note;
    public $missing_recover_flag;


    public function getRecentMissingByItemId($item_id = NULL) {
        if (is_null($item_id)) {
            return array();
        }

        $params = array('item_id' => $item_id);
        $missingInfo = $this->getDb()->getTable('ItemLoansMissingInfo')->findBy($params);

        if(count($missingInfo) == 0) {
            return array();
        } else {
            return end($missingInfo);
        }
    }

    public function addEntry($txid, $item_id, $missing_note, $missing_action_note) {
        // create entry with given info
        $db = $this->getDb();

        $itemRepairInfo = $db->insert(
                            'ItemLoansMissingInfo',
                            array(
                                'txid' => $txid,
                                'item_id' => $item_id,
                                'missing_note' => $missing_note,
                                'missing_action_note' => $missing_action_note,
                            ));

        // verify
        $txid = $this->getDb()->getTable('ItemLoansMissingInfo')->findBy(
                                                                        array(
                                                                            'sort_field' => 'id', 
                                                                            'sort_dir' => 'd',
                                                                            'item_id' => $item_id),
                                                                        1   // get only first row
                                                                    );

        if(count($txid) == 1) {
            return $txid[0]->id;
        }
    
        return false;
    }

    public function updateMissing($item_id, $missing_recover_note) {
        $missing_item = $this->getDb()->getTable('ItemLoansMissingInfo')->findBy(
                                                                            array(
                                                                                'sort_field' => 'id', 
                                                                                'sort_dir' => 'd',
                                                                                'item_id' => $item_id),
                                                                           1   // get only first row
                                                                        );
        if(count($missing_item) == 1) {
            $missing_item[0]->missing_recover_note = $missing_recover_note;
            $missing_item[0]->missing_recover_flag = true;
            $missing_item[0]->save();
        }
    }

}
