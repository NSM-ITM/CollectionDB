<?php
/**
 * Item Loans
 *
 * @copyright Copyright 2016 - National Science Museum Thailand
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * Item Loans Transactions model.
 */
class ItemLoansTransactions extends Omeka_Record_AbstractRecord {
    
    const Stored = 'Stored';
    const Loaned = 'Loaned';
    const Missing = 'Missing';
    const Repairing = 'Repairing';

    public $id;
    public $item_id;
    public $status;
    public $timestamp;

    public function getTransactionByTransactionId($transaction_id = NULL) {
        if (is_null($transaction_id)) {
            return array();
        }

        $params = array('id' => $transaction_id);
        $itemLoansTX = $this->getDb()->getTable('ItemLoansTransactions')->findBy($params);
 
        if(count($itemLoansTX) == 0) {
            return false;
        } else {
            return end($itemLoansTX);
        }
    }

    public function getRecentTransactionByItemId($item_id = NULL) {
        if (is_null($item_id)) {
            return array();
        }

        $params = array('item_id' => $item_id);
        $itemLoansTX = $this->getDb()->getTable('ItemLoansTransactions')->findBy($params);
 
        if(count($itemLoansTX) == 0) {
            return false;
        } else {
            return end($itemLoansTX);
        }
    }

    public function getLoanStatus($item_id = NULL) {
    
        if (is_null($item_id)) {
            return array();
        }

        $params = array('item_id' => $item_id);
        $itemLoansTX = $this->getDb()->getTable('ItemLoansTransactions')->findBy($params);

        if(count($itemLoansTX) == 0) {
            return ItemLoansTransactions::Stored;
        } else {
            return end($itemLoansTX)->status;
        }
    }

    public function getLoanedItemList() {
        $params = array(
                        'sort_field' => 'id',   // for validating purpose
                        'sort_dir' => 'd'
                    );

        $itemList = $this->getDb()->getTable('ItemLoansTransactions')->findBy($params);

        $adjustedList = array();
        $removeList = array();
        $addList = array();
        // repetitive check - given entry must be the last status of each item
        foreach ($itemList as $item) {
            if(
                !array_key_exists($item->item_id, $removeList)
            ) {
                if(
                    $item->status == ItemLoansTransactions::Loaned
                ) {
                    array_push($adjustedList, $item);
                    array_push($addList, $item->item_id);
                }
                $removeList[$item->item_id] = true;
            }
            
        }

        $itemTitle = $this->_mapRealItemData($addList);

        foreach ($adjustedList as $loanedItem) {
            $loanedItem->title = $itemTitle[$loanedItem->item_id];
        }

        // re-sort by expected return
        usort($adjustedList, function($a, $b) {
            return strcmp($a->expected_return, $b->expected_return);
        });



        return $adjustedList;

    }

    public function getGivenItemList($listName = NULL) {

        if (is_null($listName)) {
            $listName = ItemLoansTransactions::Missing;
        }

        $params = array(
                        'sort_field' => 'id', 
                        'sort_dir' => 'd'
                    );

        $itemList = $this->getDb()->getTable('ItemLoansTransactions')->findBy($params);
        $adjustedList = array();
        $removeList = array();
        $addList = array();
        // repetitive check - given entry must be the last status of each item
        foreach ($itemList as $item) {
            if(
                !array_key_exists($item->item_id, $removeList)
            ) {
                if($item->status == $listName) {
                    array_push($adjustedList, $item);
                    array_push($addList, $item->item_id);

                }
                $removeList[$item->item_id] = true;
            }
            
        }

        $itemTitle = $this->_mapRealItemData($addList);

        foreach ($adjustedList as $loanedItem) {
            $loanedItem->title = $itemTitle[$loanedItem->item_id];
        }

        return $adjustedList;

    }

    // get status records
    public function getTransactionRecords($item_id = NULL) {
        $records = $this->getDb()->getTable('ItemLoansTransactions')->findBy(
                                                                            array(
                                                                                'sort_field' => 'id', 
                                                                                'sort_dir' => 'd',
                                                                                'item_id' => $item_id)
                                                                        );
        
        return $records;
    }

    // loan item
    public function loanItem($item_id = NULL) {
        return $this->_addTransaction($item_id, ItemLoansTransactions::Loaned);
    }

    // loan item
    public function returnItem($item_id = NULL) {
        return $this->_addTransaction($item_id, ItemLoansTransactions::Stored);
    }

    // loan item
    public function missItem($item_id = NULL) {
        return $this->_addTransaction($item_id, ItemLoansTransactions::Missing);
    }

    // loan item
    public function repairItem($item_id = NULL) {
        return $this->_addTransaction($item_id, ItemLoansTransactions::Repairing);
    }

    private function _addTransaction($item_id = NULL, $new_status) {
        $db = $this->getDb();
        $itemLoansTX = $db->insert(
                            'ItemLoansTransactions',
                            array(
                                'item_id' => $item_id, 
                                'status' => $new_status
                            ));

        $txid = $this->getDb()->getTable('ItemLoansTransactions')->findBy(
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

    public function _mapRealItemData($itemIDSet = array()) {

        if(count($itemIDSet) == 0) {
            return false;
        }
 
        // find ID of Title Element
        $elements = $this->getDb()->getTable('Element')->findBy(
                                                                        array(
                                                                            'name' => 'Title'
                                                                            )
                                                                    );
        if(count($elements) == 1) {
            $titleElementID = $elements[0]->id;
        } else {
            return false;
        }
        
        //find Titles of Items
        $titleTexts = $this->getDb()->getTable('ElementText')->findBy(
                                                                        array(
                                                                            'record_type' => 'Item',
                                                                            'element_id' => $titleElementID,
                                                                            'record_id' => $itemIDSet
                                                                            )
                                                                    );

        $titleArray = array();
        foreach($titleTexts as $item) {
            $titleArray[$item->record_id] = $item->text;
        }

        return $titleArray;
    }
}
