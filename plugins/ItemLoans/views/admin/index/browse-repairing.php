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
        <th><?php echo __('Timestamp'); ?></th>
        <th><?php echo __('Expected Finish'); ?></th>
    </tr>
    </thead>
    <tbody>
<?php 
    $date = new DateTime(); 
    $today = $date->getTimestamp();

    foreach ($this->repairingItemList as $repairingItem): 

        $expected_finish = new DateTime($repairingItem['expected_finish']);

        if ($expected_finish->getTimestamp() <= $today) {
            $repairingItem['expected_finish'] = '<span class="overdue">'.$repairingItem['expected_finish'].'</span>';
        }
?>
    <tr>
        <td><a class="loaned-item" data-item-id='<?=$repairingItem['item_id'];?>' href="<?php echo html_escape($this->url("items/show/{$repairingItem['item_id']}")); ?>"><?php echo $repairingItem['item_id']; ?></a></td>
        <td><a class='colorbox-ajax' href="<?php echo html_escape($this->url("item-loans/index/show-item?status={$repairingItem['status']}&item_id={$repairingItem['item_id']}")); ?>" title=""><?php echo $repairingItem['title']; ?></a></td>
        <td><?php echo $repairingItem['status']; ?></td>
        <td><?php echo $repairingItem['timestamp']; ?></td>
        <td><?php echo $repairingItem['expected_finish']; ?></td>
    </tr>
<?php endforeach; ?>
    </tbody>
</table>

<script type="text/javascript">
//<![CDATA[
    jQuery(document).ready(function () {
        var status = 'Repairing';
        
        jQuery('.colorbox-ajax').colorbox(); 

    });
//]]>
</script>

<style>
.overdue {
    color: #FF0000;
}
</style>

<?php echo foot(); ?>
