<?php
/**
 * Item Loans
 *
 * @copyright Copyright 2016 - National Science Museum Thailand
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The Item Loans index controller class.
 *
 * @package ItemLoans
 */
class ItemLoans_IndexController extends Omeka_Controller_AbstractActionController
{    
    public function init() {
       
    }
    
    public function indexAction() {
        $this->_helper->redirector('browse-loaned');
        return;
    }
    
    public function browseLoanedAction() {
        $itemTX = new ItemLoansTransactions();
        $itemLoan = new ItemLoansLoanInfo();

        // get all loaned items (no returned flag)
        $loanedItemObjectList = $itemTX->getLoanedItemList();
        $loanedItemObjectInfoList = $itemLoan->getUnreturnedItems(); 

        $loanedItemList = array();
        foreach($loanedItemObjectList as $i => $loanedItem){
            $loanedItemList[$i]['id'] = $loanedItem['id'];
            $loanedItemList[$i]['item_id'] = $loanedItem['item_id'];
            $loanedItemList[$i]['status'] = $loanedItem['status'];
            $loanedItemList[$i]['timestamp'] = $loanedItem['timestamp'];

            // item title
            $loanedItemList[$i]['title'] = $loanedItem['title'];
            
            // additional info
            $loanedItemList[$i]['purpose'] = $loanedItemObjectInfoList[$loanedItem['item_id']]->purpose;
            $loanedItemList[$i]['condition'] = $loanedItemObjectInfoList[$loanedItem['item_id']]->condition;
            $loanedItemList[$i]['note'] = $loanedItemObjectInfoList[$loanedItem['item_id']]->note;
            $loanedItemList[$i]['expected_return'] = $loanedItemObjectInfoList[$loanedItem['item_id']]->expected_return;
            $loanedItemList[$i]['loaned_to'] = $loanedItemObjectInfoList[$loanedItem['item_id']]->loaned_to;
        }

        $this->view->loanedItemList = $loanedItemList;
        return;
    }
    
    public function browseRepairingAction() {
        $itemTX = new ItemLoansTransactions();
        $repairInfo = new ItemLoansRepairInfo();

        // get all repairing items
        $repairingItemObjectList = $itemTX->getGivenItemList(ItemLoansTransactions::Repairing);
        $repairingItemObjectInfoList = $repairInfo->getRepairingItems(); 
        
        $repairingItemList = array();
        foreach($repairingItemObjectList as $i => $repairingItem){
            $repairingItemList[$i]['id'] = $repairingItem['id'];
            $repairingItemList[$i]['item_id'] = $repairingItem['item_id'];
            $repairingItemList[$i]['status'] = $repairingItem['status'];
            $repairingItemList[$i]['timestamp'] = $repairingItem['timestamp'];

            // item title
            $repairingItemList[$i]['title'] = $repairingItem['title'];

            // get additional info from RepairInfo
            $repairingItemList[$i]['expected_finish'] = $repairingItemObjectInfoList[$repairingItem['item_id']]->expected_finish;
             
            
        }

        $this->view->repairingItemList = $repairingItemList;
        return;
    }
    
    public function browseMissingAction() {
        $itemTX = new ItemLoansTransactions();

        // get all repairing items
        $missingItemObjectList = $itemTX->getGivenItemList(ItemLoansTransactions::Missing);
        $missingItemList = array();
        foreach($missingItemObjectList as $i => $missingItem){
            $missingItemList[$i]['id'] = $missingItem['id'];
            $missingItemList[$i]['item_id'] = $missingItem['item_id'];
            $missingItemList[$i]['status'] = $missingItem['status'];
            $missingItemList[$i]['timestamp'] = $missingItem['timestamp'];

            // item title
            $missingItemList[$i]['title'] = $missingItem['title'];
        }

        $this->view->missingItemList = $missingItemList;
        return;
    }

    public function showItemAction() {
        $get_data = $this->getRequest()->getQuery();

        if(!empty($get_data)) {
            $item_id = $get_data['item_id'];
            $status = $get_data['status'];
            $return_data = array('status' => false);

            $itemTX = new ItemLoansTransactions();
            $return_data['transaction'] = $itemTX->getRecentTransactionByItemId($item_id);
            $return_data['item_status'] = $status;


            if($status == ItemLoansTransactions::Loaned) {
                $loanInfo = new ItemLoansLoanInfo();

                $return_data['loan_info'] = $loanInfo->getRecentLoanByItemId($item_id);
                $return_data['status'] = true;

            } else if ($status == ItemLoansTransactions::Missing) {
                $missingInfo = new ItemLoansMissingInfo();

                $return_data['missing_info'] = $missingInfo->getRecentMissingByItemId($item_id);
                $return_data['status'] = true;
            } else if ($status == ItemLoansTransactions::Repairing) {
                $repairingInfo = new ItemLoansRepairInfo();

                $return_data['repairing_info'] = $repairingInfo->getRecentRepairingByItemId($item_id);
                $return_data['status'] = true;
            }

            $this->view->return_data = $return_data;
        }

    }
    
    public function showRecordAction() {
        $get_data = $this->getRequest()->getQuery();

        if(!empty($get_data)) {
            $transaction_id = $get_data['transaction_id'];
            $return_data = array('status' => false);

            $itemTX = new ItemLoansTransactions();
            $return_data['transaction'] = $itemTX->getTransactionByTransactionId($transaction_id);
            
        
            $status = $return_data['transaction']->status;
            $item_id = $return_data['transaction']->item_id;

            if($status == ItemLoansTransactions::Loaned) {
                $loanInfo = new ItemLoansLoanInfo();

                $return_data['loan_info'] = $loanInfo->getRecentLoanByItemId($item_id);
                $return_data['status'] = true;

            } else if ($status == ItemLoansTransactions::Missing) {
                $missingInfo = new ItemLoansMissingInfo();

                $return_data['missing_info'] = $missingInfo->getRecentMissingByItemId($item_id);
                $return_data['status'] = true;
            } else if ($status == ItemLoansTransactions::Repairing) {
                $repairingInfo = new ItemLoansRepairInfo();

                $return_data['repairing_info'] = $repairingInfo->getRecentRepairingByItemId($item_id);
                $return_data['status'] = true;
            }
        
            $return_data['item_status'] = $status;
            
            $this->view->return_data = $return_data;
        }

    }
    

}
