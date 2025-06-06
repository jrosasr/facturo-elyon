<?php

namespace App\Observers;

class Client
{
    public function creating(\App\Models\Client $record): void
    {
        if (auth()->hasUser()) {
            $record->team_id = auth()->user()->currentTeam()->id;
            // or with a `team` relationship defined:
            $record->team()->associate(auth()->user()->team);
        }
    }
}
