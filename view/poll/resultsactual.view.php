<div id="pollResultsTitle">Results for "<?php echo $this->poll->question; ?>"</div>
<?php foreach ($this->poll->answers as $answer) { ?>
	<div><?php echo $answer->text; ?>: <?php echo $answer->points; ?> points</div>
<?php } ?>
<div id="runoffResults">
	Runoff (between top two by points):<br />
	<?php 
	// Figure out multi-way tie here, info comes from poll.controller
	if ($this->poll->runoffResults['tie']) {
		if ($this->poll->runoffResults['tieEndsAt'] > 2) {
			// Multi-way tie
			echo 'Tie between '.$this->poll->runoffResults['tieEndsAt'].' questions, '.$this->poll->runoffResults['first']['question'].' and '.$this->poll->runoffResults['second']['question'].' with '.$this->poll->runoffResults['first']['votes'].' votes each';
		} else {
			// Two-way tie
			echo 'Tie between '. $this->poll->runoffResults['first']['question'].' and '.$this->poll->runoffResults['second']['question'].' with '.$this->poll->runoffResults['first']['votes'].' votes each';
		}
	} else {
		?>
		1st: <?php echo $this->poll->runoffResults['first']['question']; ?>, preferred by <?php echo $this->poll->runoffResults['first']['votes']; ?><br />
		2nd: <?php echo $this->poll->runoffResults['second']['question']; ?>, preferred by <?php echo $this->poll->runoffResults['second']['votes']; ?>
		<?php
	}
	?>
</div>
<div class="clear"></div>
<?php
//echo '<pre>Selection Results: ';print_r($this->poll->topAnswers);echo '<br />Runoff Results: ';print_r($this->poll->runoffResults);echo '</pre>';
//echo '<pre>Selection Results: ';print_r($this->poll->topAnswers);echo '</pre>';
?>