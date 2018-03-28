<?php
class SurveyModel extends Model
{
	public function getSurveyByID($surveyID)
	{
		$this->query = "SELECT *
						FROM `surveys`
						WHERE `surveyID` LIKE '".$this->escapeString($surveyID)."'
						LIMIT 0,1;";
		$this->doSelectQuery();
		return $this->results[0];
	}
	
	public function getSurveyByCustomSlug($slug)
	{
		$this->query = "SELECT *
						FROM `surveys`
						WHERE `customSlug` LIKE '".$this->escapeString($slug)."'
						LIMIT 0,1;";
		$this->doSelectQuery();
		return $this->results[0];
	}
	
	public function getAllAnswersBySurveyID($surveyID)
	{
		$polls = $this->getPollsBySurveyID($surveyID);
		foreach ($polls as $poll) {
			$this->query = "SELECT `answerID`, `text`, `votes`, `points`
							FROM `answers`
							WHERE `answers`.`pollID` LIKE '".$poll->pollID."'
							ORDER BY `answerID` ASC;";
			$this->doSelectQuery();
			$return = array_merge($return, $this->results);
		}
		return $return;
	}
	
	public function getPollsBySurveyID($surveyID)
	{
		$this->query = "SELECT *
						FROM `polls`
						WHERE `surveyID` LIKE '".$this->escapeString($surveyID)."'
						ORDER BY `created` ASC;";
		$this->doSelectQuery();
		return $this->results;
	}
	
	public function getMostPopularSurveys($index, $limit)
	{
		$this->query = "SELECT *
						FROM `polls`
						WHERE `private` = 0
						ORDER BY `votes` DESC, `created` DESC
						LIMIT $index,$limit;";
		$this->doSelectQuery();
		return $this->results;
	}
	
	public function getMostRecentSurveys($index, $limit)
	{
		// Be sure you don't grab ones that are marked private
		if (!$limit || $limit < 1) $limit = 10;
		$this->query = "SELECT *
						FROM `polls`
						WHERE `private` = 0
						ORDER BY `polls`.`created` DESC
						LIMIT $index,$limit;";
		$this->doSelectQuery();
		return $this->results;
	}
	
	public function getVoterKeysBySurveyID($surveyID)
	{
		$this->query = "SELECT *
						FROM `surveyVoterKeys`
						WHERE `surveyID` LIKE '".$this->escapeString($surveyID)."'
						ORDER BY `createdTime` ASC;";
		$this->doSelectQuery();
		return $this->results;
	}
	
	public function getUsedVoterKeyCountBySurveyID($surveyID)
	{
		$this->query = "SELECT COUNT(`surveyID`) as `ct`
						FROM `surveyVoterKeys`
						WHERE `surveyID` LIKE '".$this->escapeString($surveyID)."'
						AND `voteTime` IS NOT NULL;";
		$this->doSelectQuery();
		return $this->results[0]->ct;
	}
	
	public function verifyVoterKey($voterKey, $surveyID)
	{
		$this->query = "SELECT `surveyID`, `voterID`, `voteTime`
						FROM `surveyVoterKeys`
						WHERE `surveyID` LIKE '".$this->escapeString($surveyID)."'
						AND `voterKey` LIKE '".$this->escapeString($voterKey)."'
						LIMIT 0,1;";
		$this->doSelectQuery();
		if (!empty($this->results[0])) {
			return $this->results[0];
		} else return false;
	}
	
	public function insertVoterKey($surveyID, $key)
	{
		if (!empty($surveyID) && !empty($key)) {
			$this->query = "INSERT INTO `surveyVoterKeys` (`surveyID`, `voterKey`, `createdTime`, `voteTime`, `voterID`, `invalid`)
						VALUES ('".$surveyID."', '".$key."', '".date('Y-m-d H:i:s')."', null, 0, 0)";
			// Insert
			$this->doInsertQuery();
		}
	}
	
	public function isSlugTaken($slug)
	{
		$this->query = "SELECT *
						FROM `surveys`
						WHERE `customSlug` LIKE '".$this->escapeString($slug)."'
						LIMIT 0,1;";
		$this->doSelectQuery();
		if (!empty($this->results)) {
			return true;
		} else {
			$this->query = "SELECT *
							FROM `polls`
							WHERE `polls`.`customSlug` LIKE '".$this->escapeString($slug)."'
							LIMIT 0,1;";
			$this->doSelectQuery();
			if (!empty($this->results)) {
				return true;
			}
		}
		return false;
	}
	
	public function isSurveyIDTaken($surveyID)
	{
		$this->query = "SELECT `surveyID`
						FROM `surveys`
						WHERE `surveyID` LIKE '".$surveyID."'
						LIMIT 0,1;";
		$this->doSelectQuery();
		if (!empty($this->results[0]->surveyID)) {
			return true;
		} else return false;
	}
	
	public function insertSurvey($surveyID, $title, $created, $randomOrder, $private, $creatorIP, $customSlug, $verifiedVoting, $verifiedVotingType, $userID, $verbage)
	{
		// Poll first
		$this->query = "INSERT INTO `surveys` (`surveyID`, `title`, `created`, `private`, `verifiedVoting`, `verifiedVotingType`, `randomOrder`, `creatorIP`, `customSlug`, `userID`, `verbage`)
						VALUES ('".$surveyID."', '".$title."', '".date('Y-m-d H:i:s')."', ".$private.", ".$verifiedVoting.", '".$verifiedVotingType."', ".$randomOrder.", '".$creatorIP."', '".$customSlug."', '".$userID."', '".$verbage."')";
		// Insert
		$this->doInsertQuery();
	}
	
	public function isGeneratedIDTaken($table, $column, $newID)
	{
		$this->query = "SELECT `$table`.`$column`
						FROM `$table`
						WHERE `$column` LIKE '".$newID."'
						LIMIT 0,1;";
		$this->doSelectQuery();
		if (!empty($this->results)) {
			return true;
		} else return false;
	}
}
?>