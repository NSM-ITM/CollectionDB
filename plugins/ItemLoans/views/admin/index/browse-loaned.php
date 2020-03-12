<?php echo head(array('title' => __('Item Loans'))); ?>
<?php echo common('itemloans-nav'); ?>
<?php echo flash(); ?>

<div class="pagination"><?php echo pagination_links(); ?></div>

<table class="item-loans-tab" tabindex='1'>
    <thead>
    <tr>
        <th><?php echo __('Item ID'); ?></th>
        <th><?php echo __('Item Title'); ?></th>
        <th><?php echo __('Status'); ?></th>
        <th><?php echo __('Purpose'); ?></th>
        <th><?php echo __('Loaned To'); ?></th>
        <th><?php echo __('Timestamp'); ?></th>
        <th><?php echo __('Expected Return Date'); ?></th>
    </tr>
    </thead>
    <tbody>
<?php

    $date = new DateTime(); 
    $today = $date->getTimestamp();

    foreach ($this->loanedItemList as $loanedItem): 
        $expected_return = new DateTime($loanedItem['expected_return']);

        if ($expected_return->getTimestamp() <= $today) {
            $loanedItem['expected_return'] = '<span class="overdue">'.$loanedItem['expected_return'].'</span>';
        }
?>
    <tr>
        <td><a class="loaned-item" data-item-id='<?=$loanedItem['item_id'];?>' href="<?php echo html_escape($this->url("items/show/{$loanedItem['item_id']}")); ?>"><?php echo $loanedItem['item_id']; ?></a></td>
        <td><a class='colorbox-ajax' href="<?php echo html_escape($this->url("item-loans/index/show-item?status={$loanedItem['status']}&item_id={$loanedItem['item_id']}")); ?>" title=""><?php echo $loanedItem['title']; ?></a></td>
        <td><?php echo $loanedItem['status']; ?></td>
        <td><?php echo $loanedItem['purpose']; ?></td>
        <td><?php echo $loanedItem['loaned_to']; ?></a></td>
        <td><?php echo $loanedItem['timestamp']; ?></td>
        <td><?php echo $loanedItem['expected_return']; ?></td>
    </tr>
<?php endforeach; ?>
    </tbody>
</table>

<style>
.overdue {
    color: #FF0000;
}
</style>

<script type="text/javascript">
//<![CDATA[
    jQuery(document).ready(function () {
        var status = 'Loaned';

        jQuery('.colorbox-ajax').colorbox();

    });
//]]>
</script>

<?php echo foot(); ?>
