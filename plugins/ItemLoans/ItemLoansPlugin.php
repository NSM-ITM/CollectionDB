<?php
/**
 * Item Loans
 *
 * This Omeka 2.0+ plugin logs curatorial actions such as adding, deleting, or
 * modifying items, collections and files.
 *
 * @copyright Copyright 2016 - National Science Museum Thailand
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 *
 * @package ItemLoans
 */

/**
 * Item loans plugin class
 *
 * @package ItemLoans
 */
class ItemLoansPlugin extends Omeka_Plugin_AbstractPlugin {

	protected $_hooks = array(
        'install',
        'uninstall',
        'admin_items_show_sidebar',
        'admin_items_show',
        'admin_head',
        'initialize'
    );

     protected $_filters = array(
     	'admin_navigation_main',
    );

	public function hookInstall () {
		$db = $this->_db;

        // Transaction Table
        $sql = "
        CREATE TABLE IF NOT EXISTS `{$db->ItemLoansTransaction }` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `item_id` int(10) unsigned NOT NULL DEFAULT 0,
            `status` enum('Stored', 'Loaned', 'Repairing', 'Missing') NOT NULL,
            `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            INDEX `txid_item_id` (`id`, `item_id`),
            INDEX (`timestamp`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $db->query($sql);

        // LoanInfo Table
        $sql = "
        CREATE TABLE IF NOT EXISTS `{$db->ItemLoansLoanInfo }` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `txid` int(10) unsigned NOT NULL DEFAULT 0,
            `item_id` int(10) unsigned NOT NULL DEFAULT 0,
            `purpose` mediumtext,
            `condition` mediumtext,
            `note` mediumtext,
            `expected_return` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `loaned_to` varchar(100),
            `returned` boolean NOT NULL DEFAULT FALSE,
            `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`txid`),
            INDEX `lid_txid_item_id` (`id`, `txid`, `item_id`),
            INDEX `item_id` (`item_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $db->query($sql);

        // RepairInfo Table
        $sql = "
        CREATE TABLE IF NOT EXISTS `{$db->ItemLoansRepairInfo }` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `txid` int(10) unsigned NOT NULL DEFAULT 0,
            `item_id` int(10) unsigned NOT NULL DEFAULT 0,
            `repair_note` mediumtext,
            `repair_finish_note` mediumtext,
            `repair_finish_flag` boolean NOT NULL DEFAULT FALSE,
            `expected_finish` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`txid`),
            INDEX `lid_txid_item_id` (`id`, `txid`, `item_id`),
            INDEX `item_id` (`item_id`),
            INDEX (`timestamp`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $db->query($sql);

        // MissingInfo Table
        $sql = "
        CREATE TABLE IF NOT EXISTS `{$db->ItemLoansMissingInfo }` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `txid` int(10) unsigned NOT NULL DEFAULT 0,
            `item_id` int(10) unsigned NOT NULL DEFAULT 0,
            `missing_note` mediumtext,
            `missing_action_note` mediumtext,
            `missing_recover_note` mediumtext,
            `missing_recover_flag` boolean NOT NULL DEFAULT FALSE,
            PRIMARY KEY (`txid`),
            INDEX `lid_txid_item_id` (`id`, `txid`, `item_id`),
            INDEX `item_id` (`item_id`),
            INDEX (`timestamp`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        $db->query($sql);

	}
	
	public function hookUninstall () {
		$db = $this->_db;

        // Drop all tables
        $sql = "DROP TABLE `{$db->ItemLoansTransaction}`";
        $db->query($sql);
        $sql = "DROP TABLE `{$db->ItemLoansLoanInfo}`";
        $db->query($sql);
        $sql = "DROP TABLE `{$db->ItemLoansRepairInfo}`";
        $db->query($sql);
        $sql = "DROP TABLE `{$db->ItemLoansMissingInfo}`";
        $db->query($sql);

	}

    public function hookInitialize()
    {
        add_translation_source(dirname(__FILE__) . '/languages');
    }

    public function hookAdminItemsShow($args) {
        $item = $args['item'];
        $item_id = $item['id'];

        $itemLoanTX = new ItemLoansTransactions();
        $itemLoanRecords = $itemLoanTX->getTransactionRecords($item_id);

        echo common('item-loans-records', array(
            'item_loans_records' => $itemLoanRecords,
        ));

    }

    public function hookAdminItemsShowSidebar($args) {
        $item = $args['item'];

        $item_id = $item['id'];
        
        $itemLoanTX = new ItemLoansTransactions();
        $itemLoansStatusCurrent = $itemLoanTX->getLoanStatus($item_id);

        $itemLoansExpectedReturn = '0000-00-00';
        if($itemLoansStatusCurrent == ItemLoansTransactions::Loaned) {
        	$itemLoanInfo = new ItemLoansLoanInfo();
        	$itemLoansExpectedReturn = $itemLoanInfo->getExpectedReturn($item_id);

        	if($itemLoansExpectedReturn) {
        		$tmp = explode(' ', $itemLoansExpectedReturn);
        		$itemLoansExpectedReturn = $tmp[0];

        	} else {
        		$itemLoansExpectedReturn = '0000-00-00';
        	}

        }
        
        /*
        $itemLoansStatus = array(
                                        'Stored' => 'Stored',
                                        'Loaned' => 'Loaned',
                                        'Repairing' => 'Repairing',
                                        'Missing' => 'Missing',
                                    );
		*/
                                    
        // next possible choices
		$itemLoansStatusOptions = array(
                                        'Stored' => array('Loaned' => 'Loaned'),
                                        'Loaned' => array('Stored' => 'Stored', 'Repairing' => 'Repairing', 'Missing' => 'Missing'),
                                        'Repairing' => array('Stored' => 'Stored'),
                                        'Missing' => array('Stored' => 'Stored'),
                                    );



        echo common('item-loans-show', array(
            'item_loans_status_options' => $itemLoansStatusOptions[$itemLoansStatusCurrent],
            'item_loans_status_current' => $itemLoansStatusCurrent,
            'item_loans_expected_return' => $itemLoansExpectedReturn,
            'item_id' => $item_id
        ));
    }

    public function hookAdminHead() {
        queue_js_file('jquery.colorbox-min');
        queue_js_file('jquery-ui-1.12.1');
    }

    public function filterAdminNavigationMain($nav) {
        $nav[] = array(
            'label' => __('Item Loans'),
            'uri' => url('item-loans')
        );
        return $nav;
    }


}