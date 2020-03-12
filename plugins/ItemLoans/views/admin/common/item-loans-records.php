<div class="element-set">
    <h2><?php echo __('Item Loan Records'); ?></h2>
    <div class="item-loans-records">
        <h3><?php echo __('Loan Transaction Records'); ?></h3>
        <?php 
            if(count($this->item_loans_records) == 0): 
        ?>
            <div>
                No loan records for this item
            </div>
        <?php else: ?>
            <table>
                <tr>
                    <th>Loan ID</th>
                    <th>Loan Status</th>
                    <th>Timestamp</th>
                </tr>
                <?php foreach($this->item_loans_records as $record): ?>
                    <tr>
                        <td><?php echo $record->id; ?></td>
                        <td><a class='colorbox-ajax' href="<?php echo html_escape($this->url("item-loans/index/show-record?transaction_id={$record->id}")); ?>" title=""><?php echo $record->status; ?></a></td>
                        <td><?php echo $record->timestamp ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<script type="text/javascript">
//<![CDATA[
    jQuery(document).ready(function () {
         jQuery('.colorbox-ajax').colorbox();
        
    });
//]]>
</script>