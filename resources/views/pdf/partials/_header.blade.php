<div class="header-container">
    <div class="left-header-block">
        <div class="team-logo">
            @if (!empty($base64Logo))
                <img src="{{ $base64Logo }}" alt="Team Logo" width="70" height="70">
            @endif
        </div>
        <div class="team-details">
            @if (!empty($teamName))
                <p><strong>{{ $teamName }}</strong></p>
            @endif
            @if (!empty($teamRif))
                <p>RIF: {{ $teamRif }}</p>
            @endif
            @if (!empty($teamAddress))
                <p>Dirección: {{ $teamAddress }}</p>
            @endif
        </div>
    </div>

    <div class="right-header-block">
        <p>{{ formatDate($invoice['date']) }}</p> {{-- NOTA: `formatDate` y `$invoice` necesitan ser pasados --}}
    </div>

    <div class="clearfix"></div>
</div>
