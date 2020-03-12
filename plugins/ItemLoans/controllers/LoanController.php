<?php
/**
 * Item Loans
 *
 * @copyright Copyright 2016 - National Science Museum Thailand
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The Item Loans loan controller class.
 *
 * @package ItemLoans
 */
class ItemLoans_LoanController extends Omeka_Controller_AbstractActionController
{    
    public function init() {

        // only post requests are allowed
        if (!$this->getRequest()->isPost()) {
            echo 'Invalid data input protocol';
            die();
        }
        
    }

    public function moveStatusAction() {
        $post_data = $this->getRequest()->getPost();

        $item_id = $post_data['item_id'];
        $new_status = $post_data['new_status'];

        // no room for error..
        // TO-DO error message delivery
        $message = 'to update status of '.$item_id.' to '.$new_status;
        $status = true;

        echo json_encode(array('status' => $status, 'message' => $message, 'new_status' => $new_status));

        // validating status direction
        // allow: Stored->Loaned, Loaned->Stored, Loaned->Missing, Loaned->Repairing, Repairing->Stored, Missing->Stored, Loaned->Loaned (update return date)
        // get current status
        $itemTX = new ItemLoansTransactions();
        $current_status = $itemTX->getLoanStatus($item_id);

        // check flow - call function - TO-DO : fill in all params
        switch(true) {
            case ($current_status == ItemLoansTransactions::Stored and $new_status == ItemLoansTransactions::Loaned):
                $data_pack = $post_data['data_pack'];
                $expected_return = $data_pack['expected_return'];
                $purpose = $data_pack['purpose'];
                $condition = $data_pack['condition'];
                $note = $data_pack['note'];
                $loaned_to = $data_pack['loaned_to'];

                $this->_loan($item_id, $expected_return, $purpose, $condition, $note, $loaned_to);

                break;
  
            case ($current_status == ItemLoansTransactions::Loaned and $new_status == ItemLoansTransactions::Stored):
                $this->_return($item_id);

                break;
            case ($current_status == ItemLoansTransactions::Loaned and $new_status == ItemLoansTransactions::Missing):
                $data_pack = $post_data['data_pack'];
                $missing_note = $data_pack['missing_note'];
                $missing_action_note = $data_pack['missing_action_note'];

                $this->_miss($item_id, $missing_note, $missing_action_note);

                break;
            case ($current_status == ItemLoansTransactions::Loaned and $new_status == ItemLoansTransactions::Repairing):
                $data_pack = $post_data['data_pack'];
                $repair_note = $data_pack['repair_note'];
                $expected_finish = $data_pack['expected_finish'];

                $this->_returnBroken($item_id, $repair_note, $expected_finish);

                break;
            case ($current_status == ItemLoansTransactions::Repairing and $new_status == ItemLoansTransactions::Stored):
                $data_pack = $post_data['data_pack'];
                $repair_finish_note = $data_pack['repair_finish_note'];
                
                $this->_repair($item_id, $repair_finish_note);

                break;
            case ($current_status == ItemLoansTransactions::Missing and $new_status == ItemLoansTransactions::Stored):
                $data_pack = $post_data['data_pack'];
                $missing_recover_note = $data_pack['missing_recover_note'];

                $this->_recover($item_id, $missing_recover_note);

                break;

            default:
                break;

        }

    }

    public function lengthenLoanAction() {
        $post_data = $this->getRequest()->getPost();

        $new_expected_return = $post_data['new_expected_return'];
        $item_id = $post_data['item_id'];

        $date = new DateTime($new_expected_return);
        $new_expected_return = $date->format('Y-m-d H:i:s');

        $this->_lengthen($item_id, $new_expected_return);
        $message = 'Loan has been successfully lengthened';
        echo json_encode(array('status' => 'Loaned', 'message' => $message));

    }
    
    // Stored->Loaned
    public function _loan($item_id, $expected_return, $purpose, $condition, $note, $loaned_to) {
        // create an entry in ItemLoansTransactions and another in ItemLoansLoanInfo

        // TX
        $itemTX = new ItemLoansTransactions();
        $txid = $itemTX->loanItem($item_id);

        if($txid) {
            $date = new DateTime($expected_return);
            $expected_return = $date->format('Y-m-d H:i:s');

            // Loan info
            $itemLoanInfo = new ItemLoansLoanInfo();
            $loanInfo = $itemLoanInfo->addEntry($txid, $item_id, $expected_return, $purpose, $condition, $note, $loaned_to);

            if($loanInfo) {
                return true;

            } else {
                //TO-DO - error message
                return false;

            }

        } else {

            //TO-DO - error message
            return false;

        }

    }
    
    // Loaned->Stored
    public function _return($item_id) {
        // create an entry in ItemLoansTransactions as Loaned -> update returned flag in ItemLoansLoanInfo

        // TX
        $itemTX = new ItemLoansTransactions();
        $txid = $itemTX->returnItem($item_id);

        //update returned flag
        $itemLoans = new ItemLoansLoanInfo();
        $itemLoans->updateReturn($item_id);

    }
  
    // Loaned->Loaned (update data)
    public function _lengthen($item_id, $new_expected_return) {
        // update expected return ItemLoansTransaction

        // lengthen
        $itemLoans = new ItemLoansLoanInfo();
        $itemLoans->lengthen($item_id, $new_expected_return);

    }
    
    // Loaned->Repairing
    public function _returnBroken($item_id, $repair_note, $expected_finish) {
        // create an entry in ItemLoansTransactions as Repairing -> update returned flag in ItemLoansLoanInfo
        // create entry in ItemLoansRepairInfo -> note to repair - expected finish date

        // TX
        $itemTX = new ItemLoansTransactions();
        $txid = $itemTX->repairItem($item_id);

        if($txid) {

            //update returned flag
            $itemLoans = new ItemLoansLoanInfo();
            $itemLoans->updateReturn($item_id);

            $date = new DateTime($expected_finish);
            $expected_finish = $date->format('Y-m-d H:i:s');

            // Repair Info
            $itemRepairInfo = new ItemLoansRepairInfo();
            $repairInfo = $itemRepairInfo->addEntry($txid, $item_id, $expected_finish, $repair_note);

            if($repairInfo) {
                return true;

            } else {
                //TO-DO - error message
                return false;

            }

        } else {

            //TO-DO - error message
            return false;

        }

    }
    
    // Repairing->Stored
    public function _repair($item_id, $repair_finish_note) {
        // create an entry in ItemLoansTransactions as Stored
        // update entry in ItemLoansRepairInfo - fix_note

        // TX
        $itemTX = new ItemLoansTransactions();
        $txid = $itemTX->returnItem($item_id);

        if($txid) {
            // Repair Info
            $itemRepairInfo = new ItemLoansRepairInfo();
            $repairInfo = $itemRepairInfo->updateRepair($item_id, $repair_finish_note);

            if($repairInfo) {
                return true;

            } else {
                //TO-DO - error message
                return false;

            }

        } else {

            //TO-DO - error message
            return false;

        }

    }

    // Loaned->Missing
    public function _miss($item_id, $missing_note, $missing_action_note) {
        // create an entry in ItemLoansTransactions as Missing
        // create missing item info entry

        // TX
        $itemTX = new ItemLoansTransactions();
        $txid = $itemTX->missItem($item_id);

        if($txid) {

            // Missing Info
            $itemMissingInfo = new ItemLoansMissingInfo();
            $missingInfo = $itemMissingInfo->addEntry($txid, $item_id, $missing_note, $missing_action_note);

            if($missingInfo) {
                return true;

            } else {
                //TO-DO - error message
                return false;

            }

        } else {

            //TO-DO - error message
            return false;

        }

    }

    // Missing->Stored
    public function _recover($item_id, $missing_recover_note) {
        // create an entry in ItemLoansTransactions as Stored 
        // update returned flag of the last Loaned transaction

        // TX
        $itemTX = new ItemLoansTransactions();
        $txid = $itemTX->returnItem($item_id);


        if($txid) {

            //update returned flag - finally returned
            $itemLoans = new ItemLoansLoanInfo();
            $itemLoans->updateReturn($item_id);

            // Missing item Info
            $itemMissingInfo = new ItemLoansMissingInfo();
            $missingInfo = $itemMissingInfo->updateMissing($item_id, $missing_recover_note);

            if($missingInfo) {
                return true;

            } else {
                //TO-DO - error message
                return false;

            }

        } else {

            //TO-DO - error message
            return false;

        }
        
    }  

}
