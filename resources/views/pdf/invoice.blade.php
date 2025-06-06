<!DOCTYPE html>
<html lang="es">

@php
    function formatDate($date): string {
        return Carbon\Carbon::parse($date)->format('d/m/Y');
    }
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura #{{ $invoice['id'] }}</title>
    <style>
        body {
            font-family: sans-serif;
            margin: 0;
            padding: 20px; /* Add some padding around the content for better look */
            color: #333; /* Softer general text color */
        }

        .invoice-header {
            text-align: center;
            margin-top: 20px;
            color: #000;
        }

        /* --- Header Layout Styles (No Borders) --- */
        .header-container {
            overflow: hidden; /* Clearfix for floated elements */
            padding-bottom: 10px; /* Space below the header content */
            margin-bottom: 30px; /* Space between header and "Factura #" */
            padding-top: 10px;
        }

        .left-header-block {
            float: left;
            width: 65%;
        }

        .team-logo {
            float: left;
            margin-right: 15px;
        }

        .team-details {
            overflow: hidden;
            padding-top: -6px;
            margin-left: 5rem;
        }

        .team-details p {
            margin: 0;
            line-height: 1.4;
            font-size: 14px;
        }

        .team-details p strong {
            font-size: 16px;
        }

        .right-header-block {
            float: right;
            width: 30%;
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            margin-top: -1rem;
        }

        /* Clearfix utility */
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        /* --- New & Improved Section Styling --- */
        .section-separator {
            border-bottom: 1px solid #eee; /* Light separator line */
            margin: 25px 0; /* Space above and below separator */
        }

        .details-group {
            display: table; /* Use table display for robust two-column layout */
            width: 100%;
            margin-bottom: 4px; /* Space between groups */
        }

        .details-row {
            display: table-row;
        }

        .details-label,
        .details-value {
            display: table-cell;
            padding: 0px 0; /* Compact padding */
            vertical-align: top; /* Align content at the top */
            font-size: 14px;
        }

        .details-label {
            width: 80px; /* Fixed width for labels */
            font-weight: bold;
            color: #555;
        }

        .details-value {
            color: #333;
        }

        /* Specific styles for Client and Invoice Details blocks */
        .client-info,
        .invoice-info {
            background-color: #f9f9f9; /* Light background */
            border: 1px solid #eee; /* Subtle border */
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .client-info .section-title,
        .invoice-info .section-title {
            margin-top: 0; /* Remove top margin from title inside block */
            margin-bottom: 0px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd; /* A bit darker line for title within block */
        }


        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            border: 1px solid #bdbdbd; /* Outer table border */
        }

        th,
        td {
            border: 1px solid #eee; /* Lighter internal borders */
            padding: 4px 4px; /* More padding for cells */
            text-align: left;
            font-size: 13px; /* Slightly smaller font for table content */
        }

        th {
            background-color: #bdbdbd;
            font-weight: bold;
            color: #2a2a2a;
            text-transform: uppercase;
        }

        tr:nth-child(even) {
            background-color: #fdfdfd; /* Subtle zebra striping */
        }

        .total {
            text-align: right;
            margin-top: 20px;
            font-size: 18px;
            padding: 10px 0;
            border-top: 2px solid #eee;
            margin-bottom: 20px; /* Space before footer */
        }

        .total strong {
            color: #000;
            font-size: 20px;
        }

        .user-details.text-footer {
            text-align: center;
            margin-top: 40px;
            font-size: 11px;
            color: #777;
        }
        .user-details.text-footer a {
            color: #ee7a15;
            text-decoration: none;
        }
        .user-details.text-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
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
            <p>{{ formatDate($invoice['date']) }}</p>
        </div>

        <div class="clearfix"></div>
    </div>

    <div class="invoice-header">
        <h2>Factura #{{ $invoice['id'] }}</h2>
    </div>

    <div class="client-info">
        <h3 class="section-title">Cliente</h3>
        <div class="details-group">
            <div class="details-row">
                <div class="details-label">Nombre:</div>
                <div class="details-value">{{ $client['name'] }}</div>
            </div>
            <div class="details-row">
                <div class="details-label">Dirección:</div>
                <div class="details-value">{{ $client['address'] }}</div>
            </div>
            {{-- Añadir más detalles del cliente aquí si los tienes, usando el mismo patrón --}}
        </div>
    </div>

    @if ($invoice['details'])
        <div class="invoice-info">
            <h3 class="section-title">Detalles de la Factura</h3>
            <div class="details-group">
                <div class="details-row">
                    <div class="details-label">Detalles:</div>
                    <div class="details-value">{{ $invoice['details'] }}</div>
                </div>
                {{-- Añadir otros detalles de la factura aquí si los tienes --}}
            </div>
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Cantidad</th>
                <th>Producto</th>
                <th>Precio Unitario</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td width="50">{{ $product['pivot']['quantity'] }}</td>
                    <td>{{ $product['name'] }}</td>
                    <td width="100">{{ number_format($product['pivot']['price'] / 100, 2) }}</td>
                    <td width="75">{{ number_format(($product['pivot']['quantity'] * $product['pivot']['price']) / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total">
        <p>Total:
            <strong>{{ number_format(collect($products)->sum(function ($product) {return ($product['pivot']['quantity'] * $product['pivot']['price']) / 100;}),2) }}</strong>
        </p>
    </div>

    <div class="user-details text-footer">
        <p>
            Factura generada por:
            <a href='https://facturo.soluciones-elyon.com' target='_blank'>facturo.soluciones-elyon.com
            </a>
        </p>
    </div>
</body>

</html>