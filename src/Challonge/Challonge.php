<?php

namespace Interludic\Challonge;

use Interludic\Challonge\Models\Match;
use Interludic\Challonge\Helpers\Guzzle;
use Interludic\Challonge\Models\Tournament;
use Interludic\Challonge\Models\Participant;

class Challonge
{
	/**
	 * ChallongePHP version.
	 */
	const VERSION = '1.0.3';

	/**
	 * Instantiate an instance with the API key.
	 *
	 * @param string $api_key
	 */
	public function __construct($api_key = '', $verifySsl = true)
	{
		@define("CHALLONGE_VERSION", self::VERSION);
		@define("CHALLONGE_KEY", env('CHALLONGE_KEY'));
		@define("CHALLONGE_SSL", $verifySsl);
	}

	/**
	 * Set whether we want to verify the SSL certificate.
	 *
	 * @param  boolean $verifySsl
	 * @return $this
	 */
	public function setSsl($verifySsl)
	{
		@define("CHALLONGE_SSL", $verifySsl);
		return $this;
	}

	/**
	 * Retrieve a set of tournaments created with your account.
	 *
	 * @return array
	 */
	public function getTournaments() {
		$response = Guzzle::get('tournaments');

		$tournaments = [];
		foreach ($response as $tourney) {
			$tournaments[] = new Tournament($tourney->tournament);
		}

		return $tournaments;
	}

	/**
	 * Create a new tournament.
	 *
	 * @param  array $params
	 * @return Tournament
	 */
	public function createTournament($params)
	{
		$response = Guzzle::post("tournaments", $params);
		return new Tournament($response->tournament);
	}

	/**
	 * Retrieve a single tournament record created with your account.
	 *
	 * @param  string $tournament
	 * @return Tournament
	 */
	public function getTournament($tournament, $include_participants=0, $include_matches=0)
	{
		$response = Guzzle::get("tournaments/{$tournament}");
		// $response = Guzzle::get("tournaments/{$tournament}", ['include_matches'=>$include_matches, 'include_participants'=>$include_participants]);
		// $response = Guzzle::get("tournaments/{$tournament}/include_matches/{$include_matches}/include_participants/{$include_participants}");
		return new Tournament($response->tournament);
	}

	/**
	 * Retrieve a tournament's participant list.
	 *
	 * @param  string $tournament
	 * @return array
	 */
	public function getParticipants($tournament)
	{
		$response = Guzzle::get("tournaments/{$tournament}/participants");

		$participants = [];
		foreach ($response as $team) {
			$participant = new Participant($team->participant);
			$participant->tournament_slug = $tournament;
			$participants[] = $participant;
		}

		return $participants;
	}

	/**
	 * Randomize seeds among participants.
	 *
	 * @param  string $tournament
	 * @return array
	 */
	public function randomizeParticipants($tournament)
	{
		$response = Guzzle::post("tournaments/{$tournament}/participants/randomize");

		$participants = [];
		foreach ($response as $team) {
			$participant = new Participant($team->participant);
			$participant->tournament_slug = $tournament;
			$participants[] = $participant;
		}

		return $participants;
	}

	/**
	 * Retrieve a single participant record for a tournament.
	 *
	 * @param  string $tournament
	 * @param  string $participant
	 * @return array
	 */
	public function getParticipant($tournament, $participant)
	{
		$response = Guzzle::get("tournaments/{$tournament}/participants/{$participant}");

		$participant = new Participant($response->participant);
		$participant->tournament_slug = $tournament;

		return $participant;
	}

	/**
	 * Retrieve a tournament's match list.
	 *
	 * @param  string $tournament
	 * @return array
	 */
	public function getMatches($tournament)
	{
		$response = Guzzle::get("tournaments/{$tournament}/matches");
// dd($response);
		$matches = [];
		foreach ($response as $match) {
			$matchModel = new Match($match->match);
			$matchModel->tournament_slug = $tournament;
			$matches[] = $matchModel;
		}

		return $matches;
	}

	/**
	 * Retrieve a single match record for a tournament.
	 *
	 * @param  string $tournament
	 * @param  string $match
	 * @return array
	 */
	public function getMatch($tournament, $match)
	{
		$response = Guzzle::get("tournaments/{$tournament}/matches/{$match}");

		$match = new Match($response->match);
		$match->tournament_slug = $tournament;

		return $match;
	}


// TODO seperate groups and finals

	/**
	 * Retrieve a leaderboard listing for a tournament.
	 *
	 * @param  string $tournament
	 * @param  string $match
	 * @return array
	 */
	public function getStandings($tournament)
	{
		$participants = collect($this->getParticipants($tournament));
		$matches = collect($this->getMatches($tournament));		
		$group = [];

		foreach ($participants as $team) {
			$teamWithResults = $this->getStanding($team, $matches);
			$finals[] = $teamWithResults->final['results'];
			if(!empty($teamWithResults->groups[0]))	$group[] = $teamWithResults->groups[0]['results']; 
			//TODO extend to loop over n groups "$group[group_id]"
			// $teamsWithResults[] = $teamWithResults;
		}
		((!empty($finals))? $result['final'] = collect($finals)->sortByDesc('win') : $finals = null);
		((!empty($group))? $result['groups'] = collect($group)->sortByDesc('win') : $group = null);

		return $result;

		// return $teamsWithResults;
	}


	/**
	 * Get standing for participant accross all groups and matches
	 *
	 * @return void
	 * @author 
	 **/
	public function getStanding($participant, $matches)
	{
		$participantGroups = [];

		foreach ($participant->group_player_ids as $playerGroupId) {
			$data = $matches->filter(function ($item) use ($playerGroupId) {		
				  if(in_array($playerGroupId, [$item->player1_id, $item->player2_id]))	return true; 
			});
			$participantGroup['matches'] = $data;
			$participantGroup['results'] = $this->matchResults($data, $playerGroupId, $participant->name);
			$participantGroups[] = $participantGroup;

		}	
		
		$participantFinal['matches'] = $matches->filter(function ($item) use ($participant) {		
			return (($item->player1_id == $participant->id) || ($item->player2_id == $participant->id));
		});
		$participantFinal['results'] = $this->matchResults($participantFinal['matches'], $participant->id, $participant->name);

		$participant->groups = $participantGroups;
		$participant->final = $participantFinal;

		return $participant;		
	}


	/**
	 * matchResults function
	 *
	 * @return array
	 * @author 
	 **/
	public function matchResults($matches, $playerId, $participantName)
	{
		$result = ['win'=>0, 'lose'=>0, 'tie'=>0, 'pts'=>0, 'history'=>[], 'name'=>$participantName];
		// $history = [];

		foreach ($matches as $match) {			
			// dump($match);
			if($match->winner_id == $playerId){
				$result['win'] += 1;
				$result['history'][] = "W";
			} 

			if($match->loser_id == $playerId){
				$result['lose'] += 1;
				$result['history'][] = "L";
			}

			if($match->loser_id == null){
				$result['tie'] += 1;
				$result['history'][] = "T";
			}

			$pts = $this->getMatchPts($match, $playerId);
			$result['pts'] += $pts->where('type', 'player')->pluck('score')->first();

		}
		// array_push($result, $history);
		return $result;
	}

	/**
	 * Get match points for user function
	 *
	 * @return void
	 * @author 
	 **/
	public function getMatchPts($match, $playerId){				
		$playerScore = 0;
		$scores = [0,0];

		if(empty($match->scores_csv)){
			// dump($match); team forfiet = $match->loser_id
		} else{
			$scores = explode("-", $match->scores_csv);
			sort($scores);			
		}
		
		if($match->loser_id == $playerId)	$playerScore = $scores[0];
		if($match->winner_id == $playerId)	$playerScore = $scores[1]; 
		if($match->loser_id == null)	$playerScore = $scores[0];

		$result[] = ['type'=>'loser', 'id'=>$match->loser_id, 'score'=>$scores[0]];
		$result[] = ['type'=>'winner', 'id'=>$match->winner_id, 'score'=>$scores[1]];
		$result[] = ['type'=>'player', 'id'=>$playerId, 'score'=>$playerScore];

		return collect($result);
	}
	

}
