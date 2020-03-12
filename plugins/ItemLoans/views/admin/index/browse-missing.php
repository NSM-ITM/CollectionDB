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
    </tr>
    </thead>
    <tbody>
<?php foreach ($this->missingItemList as $missingItem): ?>
    <tr>
        <td><a class="loaned-item" data-item-id='<?=$missingItem['item_id'];?>' href="<?php echo html_escape($this->url("items/show/{$missingItem['item_id']}")); ?>"><?php echo $missingItem['item_id']; ?></a></td>
        <td><a class='colorbox-ajax' href="<?php echo html_escape($this->url("item-loans/index/show-item?status={$missingItem['status']}&item_id={$missingItem['item_id']}")); ?>" title=""> <?php echo $missingItem['title']; ?></a></td>
        <td><?php echo $missingItem['status']; ?></td>
        <td><?php echo $missingItem['timestamp']; ?></td>
    </tr>
<?php endforeach; ?>
    </tbody>
</table>

<script type="text/javascript">
//<![CDATA[
    jQuery(document).ready(function () {
        var status = 'Missing';
        
        jQuery('.colorbox-ajax').colorbox();

    });
//]]>
</script>

<?php echo foot(); ?>
