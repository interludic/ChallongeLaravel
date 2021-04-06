<?php

namespace Interludic\Challonge;

use Reflex\Challonge\Models\Match;
use Reflex\Challonge\Helpers\Guzzle;
use Reflex\Challonge\Models\Tournament;
use Reflex\Challonge\Models\Participant;



class Challonge
{

	/**
	 * Instantiate an instance with the API key.
	 *
	 * @param string $api_key
	 */
	// public function __construct($api_key = '', $verifySsl = true)
	// {
	// 	@define("CHALLONGE_VERSION", self::VERSION);
	// 	@define("CHALLONGE_KEY", env('CHALLONGE_KEY'));
	// 	@define("CHALLONGE_SSL", $verifySsl);
	// }


	public function __construct()
	{
		@define("CHALLONGE_KEY", env('CHALLONGE_KEY'));
	}


	/**
	 * Set whether we want to verify the SSL certificate.
	 *
	 * @param  boolean $verifySsl
	 * @return $this
	 */
	// public function setSsl($verifySsl)
	// {
	// 	@define("CHALLONGE_SSL", $verifySsl);
	// 	return $this;
	// }

	/**
	 * Retrieve a set of tournaments created with your account.
	 *
	 * @return array
	 */
	public function getTournaments()
	{
		$challonge = new \Reflex\Challonge\Challonge(CHALLONGE_KEY, true);
		return $challonge->getTournaments();
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
		$matchesComplete = count($matches->where('state', 'complete'));
		$result['progress'] = (($matchesComplete > 0) ? round(($matchesComplete / count($matches) * 100)) : 0);
		$group = [];

		foreach ($participants as $team) {
			$teamWithResults = $this->getStanding($team, $matches);
			$finals[] = $teamWithResults->final['results'];
			if (!empty($teamWithResults->groups[0]))	$group[] = $teamWithResults->groups[0]['results'];
			//TODO extend to loop over n groups "$group[group_id]"
			// $teamsWithResults[] = $teamWithResults;
		}
		((!empty($finals)) ? $result['final'] = collect($finals)->sortByDesc('win') : $finals = null);
		((!empty($group)) ? $result['groups'] = collect($group)->sortByDesc('win') : $group = null);

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
				if (in_array($playerGroupId, [$item->player1_id, $item->player2_id]))	return true;
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
		$result = ['win' => 0, 'lose' => 0, 'tie' => 0, 'pts' => 0, 'history' => [], 'name' => $participantName];
		// $history = [];

		foreach ($matches as $match) {
			// dump($match);
			if ($match->winner_id == $playerId) {
				$result['win'] += 1;
				$result['history'][] = "W";
			}

			if ($match->loser_id == $playerId) {
				$result['lose'] += 1;
				$result['history'][] = "L";
			}

			if ($match->loser_id == null) {
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
	public function getMatchPts($match, $playerId)
	{
		$playerScore = 0;
		$scores = [0, 0];

		if (empty($match->scores_csv)) {
			// dump($match); team forfiet = $match->loser_id
		} else {
			$scores = explode("-", $match->scores_csv);
			sort($scores);
		}

		if ($match->loser_id == $playerId)	$playerScore = $scores[0];
		if ($match->winner_id == $playerId)	$playerScore = $scores[1];
		if ($match->loser_id == null)	$playerScore = $scores[0];

		$result[] = ['type' => 'loser', 'id' => $match->loser_id, 'score' => $scores[0]];
		$result[] = ['type' => 'winner', 'id' => $match->winner_id, 'score' => $scores[1]];
		$result[] = ['type' => 'player', 'id' => $playerId, 'score' => $playerScore];

		return collect($result);
	}
}
