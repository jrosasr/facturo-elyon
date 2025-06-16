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
    @include('pdf.partials._styles') {{-- Incluye los estilos aquí --}}
</head>

<body>
    @include('pdf.partials._header', [
        'base64Logo' => $base64Logo,
        'teamName' => $teamName,
        'teamRif' => $teamRif,
        'teamAddress' => $teamAddress,
        'invoice' => $invoice,
        'formatDate' => function($date) { return Carbon\Carbon::parse($date)->format('d/m/Y'); }
    ])

    {{-- ... el resto de tu contenido de factura ... --}}

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

    @include('pdf.partials._footer')
</body>
</html>
