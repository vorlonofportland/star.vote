<div id="yourVoterID">Your voter ID: <span id="yourVoterIDActual"><?php echo $this->voter->voterID; ?></span></div>
<div class="clear"></div>
<?php foreach ($this->survey->polls as $poll) { ?>
	<table class="yourVoteTable">
		<tr><th colspan="3" class="pollHeader"><?php echo $poll->question; ?></th></tr>
		<tr><th>#</th><th>Option</th><th>Vote</th></tr>
		<?php foreach ($this->yourVotes[$poll->pollID] as $answer) {
			$i++;
			echo '<tr><td class="orderCell">'.$i.'</td><td>'.$answer->text.'</td><td class="number">'.$answer->vote.'</td></tr>';
		} ?>
	</table>
<?php } ?>
