<?php

namespace App\Observers;

class Category
{
    public function creating(\App\Models\Category $record): void
    {
        if (auth()->hasUser()) {
            $record->team_id = auth()->user()->currentTeam()->id;
            // or with a `team` relationship defined:
            $record->team()->associate(auth()->user()->team);
        }
    }
}
