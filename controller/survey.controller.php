<?php
class SurveyController extends Controller
{
	// Admin Levels
	/*public $adminLevel = array(
		'index' => 2
	);*/
	public $survey;
	
	/*public function __construct()
	{
		
	}*/
	
	public function index()
	{
		$this->title = 'Your Surveys';
	}
	
	public function create()
	{
		$this->title = 'Create Survey';
	}
	
	public function results()
	{
		// Views single survey results
		if ($this->URLdata != '') {
			if (strlen($this->URLdata) < 8 || strlen($this->URLdata) > 8) {
				$this->error = 'Invalid survey ID (length)';
			} else if (!ctype_alnum($this->URLdata)) {
				$this->error = 'Invalid survey ID (characters)';
			} else {
				$this->survey = $this->model->getSurveyByID($this->URLdata);
				if ($this->survey) {
					// Set title
					$this->title = $this->survey->question;
					// Get polls
					$this->survey->polls = $this->model->getPollsBySurveyID($this->survey->surveyID);
					// Populate polls
					$mPoll = new PollModel();
					foreach ($this->survey->polls as $tPoll) {
						$tPoll->answers = $mPoll->getAnswersByPollID($tPoll->pollID);
						$tPoll->voterCount = $mPoll->getPollVoterCount($tPoll->pollID);
						
						
						
						// Get top answers
						$tPoll->topAnswers = $mPoll->getTopAnswersByPollID($tPoll->pollID);
						foreach ($tPoll->topAnswers as $index => $answer) {
							$tPoll->topAnswers[$index]->avgVote = $mPoll->getAvgVoteByAnswerID($answer->answerID);
						}
						$tPoll->runoffResults = $mPoll->getRunoffResultsByAnswerID($tPoll->pollID, $tPoll->topAnswers[0]->answerID, $tPoll->topAnswers[1]->answerID);
						if ($tPoll->runoffResults['first']['answerID'] == $tPoll->topAnswers[0]->answerID) {
							$tPoll->runoffResults['first']['question'] = $tPoll->topAnswers[0]->text;
							$tPoll->runoffResults['second']['question'] = $tPoll->topAnswers[1]->text;
						} else {
							$tPoll->runoffResults['first']['question'] = $tPoll->topAnswers[1]->text;
							$tPoll->runoffResults['second']['question'] = $tPoll->topAnswers[0]->text;
						}
						// Check for ties beyond this
						if ($tPoll->runoffResults['tie']) {
							$ignoreFirstTwo = 1;
							$tPoll->runoffResults['tieEndsAt'] = 2;
							foreach ($tPoll->topAnswers as $topAnswer) {
								if ($ignoreFirstTwo > 2 && $tPoll->runoffResults['second']['votes'] == $topAnswer->votes) {
									$tPoll->runoffResults['tieEndsAt']++;
								}
								$ignoreFirstTwo++;
							}
						}
						// Runoff matrix
						$tPoll->rawRunoff = $mPoll->getRunoffResultsRawByPollID($tPoll->pollID);
						foreach ($tPoll->topAnswers as $index => $answer) {
							$tPoll->runoffAnswerArray[$answer->answerID] = $answer;
						}
						foreach ($tPoll->rawRunoff as $runoff) {
							$tPoll->orderedRunoff[$runoff->gtID][$runoff->ltID] = $runoff;
						}
						// Voter and point counts
						$tPoll->totalVoterCount = $mPoll->getPollVoterCount($tPoll->pollID);
						$tPoll->totalPointCount = $mPoll->getPollPointCount($tPoll->pollID);
						if ($tPoll->runoffResults['tie']) {
							$tPoll->noPreferenceCount = $tPoll->totalVoterCount - ($tPoll->runoffResults['first']['votes'] * 2);
						} else {
							$tPoll->noPreferenceCount = $tPoll->totalVoterCount - ($tPoll->runoffResults['first']['votes'] + $tPoll->runoffResults['second']['votes']);
						}
						// Condorcet
						$tPoll->condorcet = true;
						foreach ($tPoll->orderedRunoff[$tPoll->runoffResults['first']['answerID']] as $comIndex => $item) {
							$comVotes = $tPoll->orderedRunoff[$comIndex][$tPoll->runoffResults['first']['answerID']]->votes;
							if ($item->votes <= $comVotes) {
								$tPoll->condorcet = false;
							}
						}
						
						
						
						
					}
					unset($mPoll);
					// Init voter
					$this->voter = new VoterController();
					$this->voter->model = new VoterModel();
					$this->voter->initVoter(null);
					// Determine whether user has voted
					foreach ($this->survey->polls as $zPoll) {
						$existingVote = $this->voter->model->getYourVote($this->voter->voterID, $zPoll->pollID);
						if (count($existingVote) > 0 && $existingVote[0]->answerID != '') $this->yourVotes[$zPoll->pollID] = $existingVote;
					}
					if (!empty($this->yourVotes)) {
						$this->hasVoted = true;
					} else $this->hasVoted = false;
					// Randomize answers if necessary
					if ($this->survey->randomOrder && $this->hasVoted == false) {
						foreach ($this->survey->polls as $poll) {
							shuffle($poll->answers);
						}
					}
				} else {
					$this->error = "Survey does not exist";
				}
			}
		} else {
			$this->error = 'Must provide survey ID';
		}
	}
	
	public function ajaxresults()
	{
		$this->URLdata = $_POST['surveyID'];
		$this->results();
		// Is eligible to see the results?
		if (($this->survey->verifiedVoting && $this->user->userID == $this->survey->userID && $this->survey->userID > 0) || ($this->survey->verifiedVoting && $this->hasVoted) || $this->survey->verifiedVoting == false) {
			$return['results'] = $this->ajaxInclude('view/survey/pollresultsactual.view.php');
			//$return['runoffmatrix'] = $this->ajaxInclude('view/survey/runoffmatrix.view.php');
		} else {
			$return['results'] = 'Results cannot be viewed yet';
			$return['runoffmatrix'] = '';
		}
		echo json_encode($return);
	}
	
	public function voterkeys()
	{
		$this->surveyID = $this->URLdata;
		if (!empty($this->surveyID)) {
			$this->survey = $this->model->getSurveyByID($this->surveyID);
			if ($this->survey) {
				$this->title = 'Voter Keys for "'.$this->survey->title.'"';
				$this->voterKeys = $this->model->getVoterKeysBySurveyID($this->surveyID);
				$this->voterKeyCount = count($this->voterKeys);
				$this->usedVoterKeyCount = $this->model->getUsedVoterKeyCountBySurveyID($this->surveyID);
			} else $this->error = 'Invalid poll ID';
		} else $this->error = 'Must provide a poll ID';
	}
	
	public function ajaxgeneratevoterkeys()
	{
		if (!empty($_POST['surveyID'])) {
			// Get survey
			$this->survey = $this->model->getSurveyByID($_POST['surveyID']);
			if ($this->survey) {
				if ($this->user->userID == $this->survey->userID) {
					$keys = $this->generateVoterKeys($this->survey->surveyID, 16, $_POST['numKeys']);
					$return['keysGenerated'] = $_POST['numKeys'];
					// Fetch codes fresh from DB in case something didn't make it in
					$this->voterKeys = $this->model->getVoterKeysBySurveyID($this->survey->surveyID);
					$this->voterKeyCount = count($this->voterKeys);
					$return['html'] .= $this->ajaxInclude('view/survey/existingvoterkeys.view.php');
					$return['html'] .= '</p>';
				} else $return['error'] = 'Not admin of requested survey';
			} else $return['error'] = 'Invalid survey ID';
		} else $return['error'] = 'Invalid survey ID';
		echo json_encode($return);
	}
	
	private function generateVoterKeys($surveyID, $keyLength, $numKeys)
	{
		if (!$keyLength || $keyLength < 8) $keyLength = 8;
		for ($i = 0; $i < $numKeys; $i++) {
			$key = bin2hex(random_bytes($keyLength / 2));
			$keys[] = $key;
			$this->model->insertVoterKey($surveyID, $key);
		}
		return $keys;
	}
	
	public function ajaxcheckcustomsurveyslug()
	{
		$regexResult = preg_match('/^[a-z0-9]{4,16}$/', $_POST['slug']);
		if ($regexResult === 0 || $regexResult === false) {
			if (strlen($_POST['slug']) < 4) {
				$return['html'] = 'Slug too short; must be 4-16 characters';
				$return['returncode'] = '0';
			} else if (strlen($_POST['slug']) > 16) {
				$return['html'] = 'Slug too long; must be 4-16 characters';
				$return['returncode'] = '0';
			} else {
				$return['html'] = 'Slug may only contain a-z (lower case) and 0-9';
				$return['returncode'] = '0';
			}
		} else {
			// Passes regex
			$mPoll = new PollModel();
			$pollBySlug = $mPoll->getPollByCustomSlug($_POST['slug']);
			$pollByID = $mPoll->getPollByID($_POST['slug']);
			$surveyBySlug = $this->model->getSurveyByCustomSlug($_POST['slug']);
			$surveyByID = $this->model->getSurveyByID($_POST['slug']);
			if (!empty($pollBySlug)) {
				$return['html'] = 'Slug taken by poll';
				$return['returncode'] = '0';
			} else if (!empty($pollByID)) {
				$return['html'] = 'Slug not available, matches existing poll ID';
				$return['returncode'] = '0';
			} else if (!empty($surveyBySlug)) {
				$return['html'] = 'Slug taken by survey';
				$return['returncode'] = '0';
			} else if (!empty($surveyByID)) {
				$return['html'] = 'Slug not available, matches existing survey ID';
				$return['returncode'] = '0';
			} else {
				$return['html'] = 'Slug available';
				$return['returncode'] = '1';
			}
		}
		echo json_encode($return);
	}
	
	public function ajaxinsertsurvey()
	{
		$this->verifiedVotingTypes = array('gkc', 'eml', 'gau');
		// Actually saves the survey
		if ($_POST['surveyTitle'] != "") {
			$this->surveyTitle = $_POST['surveyTitle'];
			// Save the survey, save the world
			// Generate ID
			$oUtility = new UtilityController();
			$surveyIDIsTaken = true;
			while ($surveyIDIsTaken) {
				$newSurveyID = $oUtility->generateRandomString($type = 'distinctlower', $length = 8);
				$surveyIDIsTaken = $this->model->isSurveyIDTaken($newSurveyID);
			}
			if (!empty($_POST['fsCustomSlug'])) {
				$return['customSlug'] = $_POST['fsCustomSlug'];
			}
			$return['surveyID'] = $newSurveyID;
			if ($this->user->userID > 0) {
				$userID = $this->user->userID;
			} else $userID = 0;
			// Cleanup type if needed
			if (!in_array($_POST['fsVerifiedVotingType'], $this->verifiedVotingTypes)) $_POST['fsVerifiedVotingType'] = 'gkc';
			$dt = new DateTime();
			// Insert actual
			$this->model->insertSurvey($newSurveyID, $this->surveyTitle, $dt->format('Y-m-d H:i:s'), $_POST['fsRandomOrder'], $_POST['fsPrivate'], $_SERVER['REMOTE_ADDR'], $_POST['fsCustomSlug'], $_POST['fsVerifiedVoting'], $_POST['fsVerifiedVotingType'], $userID, $_POST['fsVerbage']);
			unset($dt);
			$return['html'] .= 'Survey saved! Loading results...';
		} else {
			$return['error'] = 'Must provide a title';
		}
		echo json_encode($return);
	}
	
	public function createpoll()
	{
		// Display form for poll creation
		$this->surveyID = $this->URLdata;
		$this->survey = $this->model->getSurveyByID($this->surveyID);
		$this->title = 'Create Poll For Survey "'.$this->survey->title.'"';
	}
	
	public function ajaxinsertpoll()
	{
		// Actually saves the poll
		if ($_POST['pollQuestion'] != "") {
			$this->pollQuestion = $_POST['pollQuestion'];
			// Parse form string
			parse_str($_POST['pollAnswers'], $this->pollAnswerSet);
			// Rearrange into a more useful array
			foreach ($this->pollAnswerSet as $pollInputName => $pollAnswer) {
				$inBoom = explode('answer', $pollInputName);
				if (trim($pollAnswer) != "") $this->pollAnswers[$inBoom[1]] = $pollAnswer;
			}
			unset($this->pollAnswerSet);
			// See that we have some answers and they aren't blank
			$answerCount = 0;
			foreach ($this->pollAnswers as $index => $answer) {
				if ($answer == '') {
					// Last question exception
					if (array_key_exists($index+1, $this->pollAnswers)) {
						// Next item exists, this one can't be blank
						$return['error'] = 'Answers cannot be blank';
						break;
					}
				} else {
					$answerCount++;
				}
			}
			if ($answerCount >= 2 && !$return['error']) {
				// ALL SET, let's save this poll
				// Generate ID
				$oUtility = new UtilityController();
				$pollIDIsTaken = true;
				$mPoll = new PollModel();
				while ($pollIDIsTaken) {
					$newPollID = $oUtility->generateRandomString($type = 'distinctlower', $length = 8);
					$pollIDIsTaken = $mPoll->isPollIDTaken($newPollID);
				}
				$return['pollID'] = $newPollID;
				if ($this->user->userID > 0) {
					$userID = $this->user->userID;
				} else $userID = 0;
				if (strlen($_POST['surveyID']) > 0) {
					$surveyID = $_POST['surveyID'];
				} else $surveyID = '';
				$oDate = new DateTime();
				// Insert actual
				$mPoll->insertPoll($newPollID, $this->pollQuestion, $this->pollAnswers, 0, 1, $_SERVER['REMOTE_ADDR'], "", 0, "gkc", $userID, $surveyID);
				//$return['html'] = $newPollID.', '.$this->pollQuestion.', '.$this->pollAnswers.', 0, 1, '.$_SERVER['REMOTE_ADDR'].', "", 0, "gkc", '.$userID.', '.$surveyID;
				unset($mPoll);
				$return['html'] .= 'Poll saved! Loading results...';
			} else {
				$return['error'] = 'Must provide at least two possible answers';
			}
		} else {
			$return['error'] = 'Must provide a question';
		}
		echo json_encode($return);
	}
	
	public function ajaxcheckvoterkey()
	{
		$this->survey = $this->model->getSurveyByID($_POST['surveyID']);
		if (!empty($this->survey)) {
			if ($this->survey->verifiedVoting) {
				if (strlen($_POST['voterKey']) < 16) {
					$return['html'] = 'Key too short; must be 16 characters';
					$return['returncode'] = '0';
				} else if (strlen($_POST['voterKey']) > 16) {
					$return['html'] = 'Key too long; must be 16 characters';
					$return['returncode'] = '0';
				} else {
					$regexResult = preg_match('/^[a-z0-9]{16}$/', $_POST['voterKey']);
					if ($regexResult === 0 || $regexResult === false) {
						$return['html'] = 'Key will only contain a-z (lower case) and 0-9';
						$return['returncode'] = '0';
					} else {
						// Passes regex
						$voterKeyResult = $this->model->verifyVoterKey($_POST['voterKey'], $_POST['surveyID']);
						if (!empty($voterKeyResult->surveyID)) {
							// Valid key, see if used already
							if (!empty($voterKeyResult->voteTime)) {
								$return['html'] = 'Voter key already used';
								$return['returncode'] = '0';
							} else {
								$return['html'] = 'Voter key valid';
								$return['returncode'] = '1';
							}
						} else {
							$return['html'] = 'Voter key invalid';
							$return['returncode'] = '0';
						}
					}
				}
			} else {
				$return['html'] = 'Survey valid, no key required';
				$return['returncode'] = '1';
			}
		} else {
			$return['html'] = 'Survey ID invalid';
			$return['returncode'] = '0';
		}
		
		echo json_encode($return);
	}
	
	public function ajaxvote()
	{
		// Initialize voter (will provide $this->voterID)
		$this->voter = new VoterController();
		$this->voter->model = new VoterModel();
		$this->voter->initVoter($_POST['voterID']);
		$this->surveyID = $_POST['surveyID'];
		$this->survey = $this->model->getSurveyByID($this->surveyID);
		$this->survey->polls = $this->model->getPollsBySurveyID($this->survey->surveyID);
		$mPoll = new PollModel();
		foreach ($this->survey->polls as $poll) {
			$poll->answers = $mPoll->getAnswersByPollID($poll->pollID);
		}
		unset($mPoll);
		foreach ($this->survey->polls as $xPoll) {
			foreach ($xPoll->answers as $xAnswer) {
				$this->answerToPollArray[$xAnswer->answerID] = $xPoll->pollID;
			}
		}
		$voterKeyResult = $this->model->verifyVoterKey($_POST['voterKey'], $this->surveyID);
		// Determine eligibility if necessary
		if (($this->survey->verifiedVoting && $voterKeyResult->surveyID) || $this->survey->verifiedVoting == false) {
			// Determine whether this key has voted
			if (empty($voterKeyResult->voteTime)) {
				parse_str($_POST['votes'], $dirtyVoteArray);
				// Cleanup array
				foreach ($dirtyVoteArray as $index => $vote) {
					$indexBoom = explode('|', $index);
					$answerID = $indexBoom[1];
					unset($indexBoom);
					$voteArray[$answerID] = $vote;
				}
				$voteArrayToDestroy = $voteArray;
				// Verify no vote has been entered for this voter on this poll
				foreach ($this->survey->polls as $zPoll) {
					$existingVote = $this->voter->model->getYourVote($this->voter->voterID, $zPoll->pollID);
					if (count($existingVote) > 0 && $existingVote[0]->answerID != '') $this->yourVotes[$zPoll->pollID] = $existingVote;
				}
				if (!empty($this->yourVotes)) {
					$this->hasVoted = true;
				} else $this->hasVoted = false;
				if (!$this->hasVoted) {
					// No vote, get the answers to make sure we have a score for each
					$this->survey->allAnswers = $this->model->getAllAnswersBySurveyID($this->surveyID);
					foreach ($this->survey->allAnswers as $answer) {
						if (!array_key_exists($answer->answerID, $voteArray) || $voteArray[$answer->answerID] == '') {
							$voteArray[$answer->answerID] = 0;
						}
					}
					$oDate = new DateTime();	
					$voteTime = $oDate->format("Y-m-d H:i:s");
					// Populate an array with 
					// Submit the votes and update matrices
					foreach ($voteArray as $answerID => $vote) {
						$this->votes[] = $vote;
						// Determine pollID
						$pollID = $this->answerToPollArray[$answerID];
						// Insert vote
						$this->model->insertVote($pollID, $this->voter->voterID, $answerID, $vote, $voteTime);
						// Update the matrix; maybe replace the windows with bricks?
						foreach ($voteArrayToDestroy as $answerID2 => $vote2) {
							if ($answerID != $answerID2) {
								if ($vote > $vote2) {
									$this->model->updateVoteMatrix($pollID, $answerID, $answerID2);
								} else if ($vote < $vote2) {
									$this->model->updateVoteMatrix($pollID, $answerID2, $answerID);
								} // and do nothing if they're equal
							}
						}
						unset($voteArrayToDestroy[$answerID]);
					}
					$this->model->incrementSurveyVoteCount($this->surveyID);
					// If a verified vote, write extra db info
					if ($this->survey->verifiedVoting) {
						$this->model->updateVoterKeyEntry($_POST['voterKey'], $this->surveyID, $this->voter->voterID, $voteTime);
					}
					unset($voteTime, $oDate);
				} else {
					$return['caution'] = 'Your vote had already been recorded for this poll';
				}
				// Load the survey fresh
				$this->survey = $this->model->getSurveyByID($this->surveyID);
				$this->survey->polls = $this->model->getPollsBySurveyID($this->survey->surveyID);
				$mPoll = new PollModel();
				foreach ($this->survey->polls as $poll) {
					$poll->answers = $mPoll->getAnswersByPollID($poll->pollID);
				}
				unset($mPoll);
				foreach ($this->survey->polls as $zPoll) {
					$existingVote = $this->voter->model->getYourVote($this->voter->voterID, $zPoll->pollID);
					if (count($existingVote) > 0 && $existingVote[0]->answerID != '') $this->yourVotes[$zPoll->pollID] = $existingVote;
				}
				$return['html'] .= $this->ajaxInclude('view/survey/yourvote.view.php');
			} else {
				$return['caution'] = 'This key has already been used to record a vote on this poll';
			}
		} else {
			// Failed eligibility
			$return['error'] .= 'Invalid voter key';
		}
		echo json_encode($return);
	}
	
	public function ajaxrunoffmatrix()
	{
		$surveyID = $_POST['surveyID'];
		$this->survey = $this->model->getSurveyByID($surveyID);
		$oVoter = new VoterController();
		$oVoter->model = new VoterModel();
		$oVoter->initVoter(false);
		$mPoll = new PollModel();
		// Is eligible to see the results?
		if (($this->survey->verifiedVoting && $this->user->userID == $this->survey->userID && $this->user->userID > 0) || ($this->survey->verifiedVoting && $this->model->userHasVoted($this->voterID, $surveyID)) || $this->survey->verifiedVoting == false) {
			$this->survey->polls = $this->model->getPollsBySurveyID($surveyID);
			foreach ($this->survey->polls as $qPoll) {
				$qPoll->rawRunoff = $mPoll->getRunoffResultsRawByPollID($qPoll->pollID);
				$qPoll->voterCount = $mPoll->getPollVoterCount($qPoll->pollID);
				$qPoll->answers = $mPoll->getAnswerByPollIDScoreOrder($qPoll->pollID);
				foreach ($qPoll->answers as $index => $answer) {
					$qPoll->runoffAnswerArray[$answer->answerID] = $answer;
				}
				foreach ($qPoll->rawRunoff as $runoff) {
					$qPoll->orderedRunoff[$runoff->gtID][$runoff->ltID] = $runoff;
				}
			}
			$return['html'] .= $this->ajaxInclude('view/survey/runoffmatrix.view.php');
		} else {
			$return['html'] = 'Results cannot be viewed yet';
		}
		echo json_encode($return);
	}
	
	/*public function csv()
	{
		$this->ajax = 1;
		$this->doHeader = 0;
		$this->doFooter = 0;
		$this->survey = $this->model->getSurveyByID($this->URLdata);
		if (!empty($this->survey)) {
			// Get polls
			$this->survey->polls;
			foreach ($this->survey->polls) {
				$this->poll->answers = $this->model->getAnswersByPollID($this->URLdata);
				$this->poll->ballots = $this->model->getBallotsByPollID($this->URLdata);
				// Process ballots into a single, cohesive array
				$this->poll->processedBallots = $this->processBallots($this->poll->ballots);
			}
		} else $this->error = 'Poll not found';
	}*/
	
	public function voterkeyscsv()
	{
		$this->ajax = 1;
		$this->doHeader = 0;
		$this->doFooter = 0;
		$this->survey = $this->model->getSurveyByID($this->URLdata); 
		if (!empty($this->survey->surveyID)) {
			$this->voterKeys = $this->model->getVoterKeysBysurveyID($this->survey->surveyID);
		} else $this->error = 'Poll not found';
	}
	
	public function ajaxcvr()
	{
		$this->ajax = 1;
		$this->doHeader = 0;
		$this->doFooter = 0;
		$this->survey = $this->model->getSurveyByID($_POST['surveyID']);
		$this->survey->polls = $this->model->getPollsBySurveyID($this->survey->surveyID);
		$oVoter = new VoterController();
		$oVoter->model = new VoterModel();
		$oVoter->initVoter(false);
		$mPoll = new PollModel();
		if (($this->survey->verifiedVoting && $this->user->userID == $this->survey->userID && $this->user->userID > 0) || ($this->survey->verifiedVoting && $this->model->userHasVoted($this->voterID, $this->survey->surveyID)) || $this->survey->verifiedVoting == false) {
			foreach ($this->survey->polls as $zPoll) {
				if (!empty($zPoll)) {
					$zPoll->answers = $mPoll->getAnswersByPollID($zPoll->pollID);
					$zPoll->ballots = $mPoll->getBallotsByPollID($zPoll->pollID);
					// Process ballots into a single, cohesive array
					$zPoll->processedBallots = $this->processBallots($zPoll->ballots);
				} else $return['error'] = 'Poll not found';
			}
			$return['html'] = $this->ajaxInclude('view/survey/cvrhtml.view.php');
		} else {
			$return['html'] = 'Results cannot be viewed yet';
		}
		echo json_encode($return);
	}
	
	private function processBallots($ballots)
	{
		foreach ($ballots as $ballot) {
			if (empty($return[$ballot->voterID])) {
				// New, establish a base and populate first vote
				$return[$ballot->voterID]['voteTime'] = $ballot->voteTime;
				$return[$ballot->voterID]['pollID'] = $ballot->pollID;
				$return[$ballot->voterID]['votes'][$ballot->answerID] = $ballot->vote;
			} else {
				// Exists, populate
				$return[$ballot->voterID]['votes'][$ballot->answerID] = $ballot->vote;
			}
		}
		return $return;
	}
	
	private function generateUniqueID($length, $table, $column)
	{
		if ($length < 1) $length = 8;
		if (strlen($table) > 0 && strlen($column) > 0) {
			// Generate ID
			$oUtility = new UtilityController();
			$generatedIDIsTaken = true;
			while ($generatedIDIsTaken) {
				$newID = $oUtility->generateRandomString($type = 'distinctlower', $length);
				$generatedIDIsTaken = $this->model->isGeneratedIDTaken($table, $column, $newID);
			}
			return $newID;
		} else return false;
	}
}
?>
