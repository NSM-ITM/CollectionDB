<div class='item-loans-info'>
	<h2>Item Loans Information</h2>
	<div class='item-loans-transaction'>
		<div class='row'>
			<div class='column column-1'></div>
			<div class='column column-2'></div>
		</div>
	</div>

	<div class='item-loans-by-status'>
		<?php
			if($return_data['item_status'] == ItemLoansTransactions::Loaned):
				//print_r($return_data['loan_info'], true);
		?>
			<div class='row'>
				<div class='column column-1'>Loaned To</div>
				<div class='column column-2'><?=$return_data['loan_info']->loaned_to; ?></div>
			</div>
			<div class='row'>
				<div class='column column-1'>Purpose</div>
				<div class='column column-2'><?=$return_data['loan_info']->purpose; ?></div>
			</div>
			<div class='row'>
				<div class='column column-1'>Condition</div>
				<div class='column column-2'><?=$return_data['loan_info']->condition; ?></div>
			</div>
			<div class='row'>
				<div class='column column-1'>Note</div>
				<div class='column column-2'><?=$return_data['loan_info']->note; ?></div>
			</div>
			<div class='row'>
				<div class='column column-1'>Expected Returned</div>
				<div class='column column-2'><?=$return_data['loan_info']->expected_return; ?></div>
			</div>

		<?php elseif ($return_data['item_status'] == ItemLoansTransactions::Missing):
				//print_r($return_data['missing_info'], true);
		?>
			<div class='row'>
				<div class='column column-1'>Missing Note</div>
				<div class='column column-2'><?=$return_data['missing_info']->missing_note; ?></div>
			</div>
			<div class='row'>
				<div class='column column-1'>Missing Action Note</div>
				<div class='column column-2'><?=$return_data['missing_info']->missing_action_note; ?></div>
			</div>
			<?php if ($return_data['missing_info']->missing_recover_flag): ?>
				<div class='row'>
					<div class='column column-1'>Missing Recover Note</div>
					<div class='column column-2'><?=$return_data['missing_info']->missing_recover_note; ?></div>
				</div>
			<?php endif; ?>
		<?php elseif ($return_data['item_status'] == ItemLoansTransactions::Repairing):
				//print_r($return_data['repairing_info'], true);
		?>
			<div class='row'>
				<div class='column column-1'>Repair Note</div>
				<div class='column column-2'><?=$return_data['repairing_info']->repair_note; ?></div>
			</div>
			<?php if ($return_data['repairing_info']->repair_finish_flag): ?>
			<div class='row'>
				<div class='column column-1'>Repair Finish Note</div>
				<div class='column column-2'><?=$return_data['repairing_info']->repair_finish_note; ?></div>
			</div>
			<?php endif; ?>
		<?php elseif ($return_data['item_status'] == ItemLoansTransactions::Stored): ?>
			<div class='row'>
				<div class='column column-1'>Stored Date</div>
				<div class='column column-2'><?=$return_data['transaction']->timestamp; ?></div>
			</div>
			
		<?php
			endif;
		?>
	</div>

</div>

<style>
.item-loans-info {
	margin:	0 auto;
	width:	700px;
	background: #FFFFFF;
	margin-top:	50px;
	min-height: 350px;
	padding: 15px;
}
#colorbox {
	z-index: 20;
}
</style>