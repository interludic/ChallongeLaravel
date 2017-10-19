# ChallongeLaravel
Package for interfacing with the [Challonge] API with Laravel 5.x

## Installation

`composer require interludic/challonge-laravel`

add `CHALLONGE_KEY=<your key>` to your .env 

update your config\app.php


Providers
```   	Interludic\Challonge\ChallongeServiceProvider::class,```


Facade
```		 'Challonge' => 'Interludic\Challonge\Facades\Challonge'```


## Usage

```

	try {
				$comp = Challonge::getTournament($challongeId);
				if((!empty($comp)) && (($comp->state == "complete") || ($comp->state == "underway") || ($comp->state == "group_stages_underway"))){					
					$standings = Challonge::getStandings($challongeId);
					// $standings['progress'] //Progress of group stage
				}
			} catch (Exception $e) {
				Log::warning('Challonge failed to load standings!', ['challonge'=>$challongeId]);
			}

```
![Example leaderboard / standings](http://interludic.com.au/assets/img/portfolio/leaderboard.jpg "Example leaderboard / standings")

## TODO
* Config Settings
* Add support for more than 1 group stage


Interludic - [interludic.com.au](https://interludic.com.au)
Forked from - [team-reflex.com](https://team-reflex.com)
[Challonge]: <http://api.challonge.com/v1>
