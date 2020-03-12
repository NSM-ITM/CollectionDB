<?php
/**
 * Item Loans
 *
 * @copyright Copyright 2016 - National Science Museum Thailand
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * Item Loans Repair Info model.
 */
class ItemLoansRepairInfo extends Omeka_Record_AbstractRecord {

    public $id;
    public $txid;
    public $item_id;
    public $repair_note;
    public $repair_finish_note;
    public $repair_finish_flag;
    public $expected_finish;

    public function getRecentRepairingByItemId($item_id = NULL) {
        if (is_null($item_id)) {
            return array();
        }

        $params = array('item_id' => $item_id);
        $repairingInfo = $this->getDb()->getTable('ItemLoansRepairInfo')->findBy($params);
            
        if(count($repairingInfo) == 0) {
            return array();
        } else {
            return end($repairingInfo);
        }
    }

    public function addEntry($txid, $item_id, $expected_finish_datetime = NULL, $repair_note) {
        // create entry with given info
        $db = $this->getDb();

        if(is_null($expected_finish_datetime)) {
            $expected_finish_datetime = date('Y-m-d H:i:s',time() + 1000);
        }

        $itemRepairInfo = $db->insert(
                            'ItemLoansRepairInfo',
                            array(
                                'txid' => $txid,
                                'item_id' => $item_id,
                                'expected_finish' => $expected_finish_datetime,
                                'repair_note' => $repair_note,
                            ));

        // verify
        $txid = $this->getDb()->getTable('ItemLoansRepairInfo')->findBy(
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

    public function updateRepair($item_id, $repair_finish_note) {
        $repairing_item = $this->getDb()->getTable('ItemLoansRepairInfo')->findBy(
                                                                            array(
                                                                                'sort_field' => 'id', 
                                                                                'sort_dir' => 'd',
                                                                                'item_id' => $item_id),
                                                                           1   // get only first row
                                                                        );
        if(count($repairing_item) == 1) {
            $repairing_item[0]->repair_finish_note = $repair_finish_note;
            $repairing_item[0]->repair_finish_flag = true;
            $repairing_item[0]->save();
        }
    }

    // reparing items
    public function getRepairingItems() {
        $params = array(
                        'sort_field' => 'txid', 
                        'sort_dir' => 'd',
                        'repair_finish_flag' => 0
                    );

        $itemList = $this->getDb()->getTable('ItemLoansRepairInfo')->findBy($params);
        $adjustedList = array();
        $removeList = array();
        // repetitive check - given entry must be the last status of each item
        foreach ($itemList as $item) {
            if(
                !array_key_exists($item->item_id, $removeList)
            ) {
                $adjustedList[$item->item_id] = $item; 
                $removeList[$item->item_id] = true;     
            }
            
        }

        return $adjustedList;

    }

}
