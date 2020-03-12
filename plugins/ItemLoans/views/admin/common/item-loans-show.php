<div class="item-loans panel">
    <h4><?php echo __('Item Loans'); ?></h4>
    <div>
        <p>
            <?php echo __('Current Status: ').'<span id="item-loans-status-current">'.$item_loans_status_current.'</span>'; ?>
        </p>
        <?php
            if($item_loans_status_current == 'Loaned'):
        ?>

            <div id="item-loans-update-lengthen">
                Returning Date: <?= $item_loans_expected_return ?> <br>
                Enlengthen Loan Until <br>(date format 01/12/2016)  <input type="text" id="item-loans-new-expected-return">
                <button id="item-loans-lengthen-submit">Submit</button>
            </div>
            <div id="item-loans-update-lengthen-result" style="display:none">
            </div>
        <?php endif; ?>
       
        <p>
            <?php echo __('Change Status: '); ?>
        </p>
        <div>
            <?php echo get_view()->formSelect('item_loans_item_status', null, null, $item_loans_status_options); ?>
            <button id="item-loans-update-status">Proceed</button>
        </div>

    </div>
    <div style="display:none" id="item-loans-loan-data-input">
        <div>
            Purpose: <input type="text" id="item-loans-purpose">
        </div>
        <div>
            Condition: <input type="text" id="item-loans-condition">
        </div>
        <div>
            Loaned To: <input type="text" id="item-loans-loaned-to">
        </div>
        <div>
            Expected Return: <input type="text" id="item-loans-expected-return">
        </div>
        <div>
            Note: <input type="text" id="item-loans-note">
        </div>
        <button id="item-loans-create-loan">Update</button>
    </div>
    <div style="display:none" id="item-loans-repair-data-input">
        <div>
            Expected Finish: <input type="text" id="item-loans-expected-finish">
        </div>
        <div>
            Note: <input type="text" id="item-loans-repair-note">
        </div>
        <button id="item-loans-create-repair">Update</button>
    </div>
    <div style="display:none" id="item-loans-finish-repair-data-input">
        <div>
            Note: <input type="text" id="item-loans-finish-repair-note">
        </div>
        <button id="item-loans-finish-repair">Update</button>
    </div>
    <div style="display:none" id="item-loans-missing-data-input">
        <div>
            Missing Note: <input type="text" id="item-loans-missing-note">
        </div>
        <div>
            Missing Action Note: <input type="text" id="item-loans-missing-action-note">
        </div>
        <button id="item-loans-create-missing">Update</button>
    </div>
    <div style="display:none" id="item-loans-recover-missing-data-input">
        <div>
            Recover Note: <input type="text" id="item-loans-recover-missing-note">
        </div>
        <button id="item-loans-recover-missing">Update</button>
    </div>
</div>

<script type="text/javascript">
//<![CDATA[
    jQuery(document).ready(function () {
        var item_id = <?= $item_id;?>;
        var status = '<?= $item_loans_status_current;?>';
        var new_status;

        jQuery('#item-loans-expected-return').datepicker();
        jQuery('#item-loans-expected-finish').datepicker();
        jQuery('#item-loans-new-expected-return').datepicker();

        // proceed status - get new forms if required, otherwise - submit
        jQuery('#item-loans-update-status').click(function(){ 
            new_status = jQuery('#item_loans_item_status').find(':selected').val();
            
            // hide all showing
            jQuery('#item-loans-loan-data-input').hide();
            jQuery('#item-loans-repair-data-input').hide();
            jQuery('#item-loans-finish-repair-data-input').hide();
            jQuery('#item-loans-missing-data-input').hide();
            jQuery('#item-loans-recover-missing-data-input').hide();

            if(new_status == 'Loaned') {
                jQuery('#item-loans-loan-data-input').show();
            } else if(new_status == 'Repairing') {
                jQuery('#item-loans-repair-data-input').show();
            } else if(new_status == 'Stored' && status == 'Repairing') {
                jQuery('#item-loans-finish-repair-data-input').show();
            } else if(new_status == 'Missing') {
                jQuery('#item-loans-missing-data-input').show();
            } else if(new_status == 'Stored' && status == 'Missing') {
                jQuery('#item-loans-recover-missing-data-input').show();
            } else {
                submitStatusChange();
            }

        });

        // submit missing loaned item 
        jQuery('#item-loans-create-missing').click(function() {
            var missing_note = jQuery('#item-loans-missing-note').val();
            var missing_action_note = jQuery('#item-loans-missing-action-note').val();

            var data_pack = {
                                missing_note: missing_note,
                                missing_action_note: missing_action_note,
                            };
            submitStatusChange(data_pack);
        });

        // submit recovered missing item
        jQuery('#item-loans-recover-missing').click(function() {
            var missing_recover_note = jQuery('#item-loans-recover-missing-note').val();

            var data_pack = {
                                missing_recover_note: missing_recover_note
                            };
            submitStatusChange(data_pack);
        });

        // submit repair loaned item - return broken
        jQuery('#item-loans-create-repair').click(function() {
            var expected_finish = jQuery('#item-loans-expected-finish').val();
            var repair_note = jQuery('#item-loans-repair-note').val();

            var data_pack = {
                                expected_finish: expected_finish, 
                                repair_note: repair_note
                            };
            submitStatusChange(data_pack);
        });

        // submit finish repairing broken returned item
        jQuery('#item-loans-finish-repair').click(function() {
            var repair_finish_note = jQuery('#item-loans-finish-repair-note').val();

            var data_pack = {
                                repair_finish_note: repair_finish_note
                            };
            submitStatusChange(data_pack);
        });

        // submit loan creation
        jQuery('#item-loans-create-loan').click(function() {
            var purpose = jQuery('#item-loans-purpose').val();
            var condition = jQuery('#item-loans-condition').val();
            var loaned_to = jQuery('#item-loans-loaned-to').val();
            var expected_return = jQuery('#item-loans-expected-return').val();
            var note = jQuery('#item-loans-note').val();

            var data_pack = {
                                purpose: purpose, 
                                condition: condition, 
                                loaned_to: loaned_to, 
                                expected_return: expected_return, 
                                note: note
                            };
            submitStatusChange(data_pack);
        });

        // submit loan lengthening
        jQuery('#item-loans-lengthen-submit').click(function() {
            var new_expected_return = jQuery('#item-loans-new-expected-return').val();

            if(new_expected_return != '') {
                jQuery.ajax({
                    type: 'POST',
                    url: '/omeka/admin/item-loans/loan/lengthen-loan',    // TO-DO remove hardcode
                    dataType: 'json',
                    data: {
                        item_id: item_id,
                        new_expected_return: new_expected_return,

                    },
                    success: function(data){
                        console.log(data);  // TO-DO update display error message / if available

                        if(data.status) {
                            console.log(data.message);

                            jQuery('#item-loans-update-lengthen').hide();
                            jQuery('#item-loans-update-lengthen-result').html(data.message);
                            jQuery('#item-loans-update-lengthen-result').show();
                        }
                    }


                });
           }

        });

        // common submission
        function submitStatusChange(data_pack = null) {
            jQuery.ajax({
                type: 'POST',
                url: '/omeka/admin/item-loans/loan/move-status',    // TO-DO remove hardcode
                dataType: 'json',
                data: {
                    item_id: item_id,
                    new_status: new_status,
                    data_pack: data_pack
                },
                success: function(data){
                    console.log(data);  // TO-DO update display error message / if available
                    jQuery('#item-loans-status-current').html(data.new_status);

                    if(data_pack != null) {
                        jQuery('#item-loans-loan-data-input').hide();
                    }
                }

            });

        }
    });
//]]>
</script>