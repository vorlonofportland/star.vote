<?php
class IndexController extends Controller
{
	public $pollSet;
	
	public function index()
	{
		$oPoll = new PollController();
		$mPoll = new PollModel;
		$this->mostRecentPolls = $mPoll->getMostRecentPolls(0, 10);
		$oPoll->processPollSet($this->mostRecentPolls);
		$this->mostPopularPolls = $mPoll->getMostPopularPolls(0, 10);
		$oPoll->processPollSet($this->mostPopularPolls);
		unset($oPoll, $mPoll);
		$oSurvey = new SurveyController();
		$mSurvey = new SurveyModel();
		$this->mostPopularSurveys = $mSurvey->getMostPopularSurveys(0, 10);
		$this->mostRecentSurveys = $mSurvey->getMostRecentSurveys(0, 10);
		unset($oSurvey, $mSurvey);
	}
}
?>