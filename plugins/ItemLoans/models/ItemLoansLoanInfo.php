<?php
/**
 * Item Loans
 *
 * @copyright Copyright 2016 - National Science Museum Thailand
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * Item Loans Loan Info model.
 */
class ItemLoansLoanInfo extends Omeka_Record_AbstractRecord {

    public $id;
    public $txid;
    public $item_id;
    public $purpose;
    public $condition;
    public $note;
    public $expected_return;
    public $loaned_to;
    public $returned;
    public $timestamp;

    public function getRecentLoanByItemId($item_id = NULL) {
        if (is_null($item_id)) {
            return array();
        }

        $params = array('item_id' => $item_id);
        $loanInfo = $this->getDb()->getTable('ItemLoansLoanInfo')->findBy($params);

        if(count($loanInfo) == 0) {
            return array();
        } else {
            return end($loanInfo);
        }
    }

    public function addEntry($txid, $item_id, $expected_return_datetime = NULL, $purpose, $condition, $note, $loaned_to) {
        // create entry with given info
        $db = $this->getDb();

        if(is_null($expected_return_datetime)) {
            $expected_return_datetime = date('Y-m-d H:i:s',time() + 1000);
        }

        $itemLoanInfo = $db->insert(
                            'ItemLoansLoanInfo',
                            array(
                                'txid' => $txid,
                                'item_id' => $item_id,
                                'expected_return' => $expected_return_datetime,
                                'purpose' => $purpose,
                                'condition' => $condition,
                                'note' => $note,
                                'loaned_to' => $loaned_to,
                            ));

        // verify
        $txid = $this->getDb()->getTable('ItemLoansLoanInfo')->findBy(
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

    public function updateReturn($item_id) {
        $current_loan = $this->getDb()->getTable('ItemLoansLoanInfo')->findBy(
                                                                            array(
                                                                                'sort_field' => 'id', 
                                                                                'sort_dir' => 'd',
                                                                                'item_id' => $item_id),
                                                                           1   // get only first row
                                                                        );
        if(count($current_loan) == 1) {
            $current_loan[0]->returned = true;
            $current_loan[0]->save();
        }
    }

    public function lengthen($item_id, $datetime = NULL) {
        $current_loan = $this->getDb()->getTable('ItemLoansLoanInfo')->findBy(
                                                                            array(
                                                                                'sort_field' => 'id', 
                                                                                'sort_dir' => 'd',
                                                                                'item_id' => $item_id,
                                                                                'returned' => false),
                                                                           1   // get only first row
                                                                        );
        if(count($current_loan) == 1) {
            if(is_null($datetime)) {
                $datetime = date('Y-m-d H:i:s',time() + 1000);
            }

            $current_loan[0]->expected_return = $datetime;
            $current_loan[0]->save();
        }
    }

    public function getExpectedReturn($item_id) {
         if (is_null($item_id)) {
            return false;
        }

        $params = array('item_id' => $item_id);
        $itemLoanInfo = $this->getDb()->getTable('ItemLoansLoanInfo')->findBy($params);

        if(count($itemLoanInfo) == 0) {
            return false;
        } else {
            return end($itemLoanInfo)->expected_return;
        }
    }

    // unreturned items
    public function getUnreturnedItems() {
        $params = array(
                        'sort_field' => 'txid', 
                        'sort_dir' => 'd',
                        'returned' => 0
                    );

        $itemList = $this->getDb()->getTable('ItemLoansLoanInfo')->findBy($params);
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
