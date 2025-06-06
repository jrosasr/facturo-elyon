<?php

namespace App\Observers;

class Invoice
{
    public function creating(\App\Models\Invoice $record): void
    {
        if (auth()->hasUser()) {
            $record->team_id = auth()->user()->currentTeam()->id;
            // or with a `team` relationship defined:
            $record->team()->associate(auth()->user()->team);
        }
    }
}
