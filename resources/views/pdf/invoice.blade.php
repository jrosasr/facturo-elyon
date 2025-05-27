<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura #{{ $invoice['id'] }}</title>
    <style>
        /* Estilos CSS para tu factura */
        body {
            font-family: sans-serif;
        }

        .invoice-header {
            text-align: center;
        }

        .invoice-details {
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }

        .text-footer {
            font-size: 12px;
            color: #555;
        }
    </style>
</head>

<body>
    <div class="invoice-header">
        <h1>Factura #{{ $invoice['id'] }}</h1>
        <p>Fecha: {{ $invoice['date'] }}</p>
    </div>

    <div class="client-details">
        <h2>Cliente</h2>
        <p>Nombre: {{ $client['name'] }}</p>
        <p>Dirección: {{ $client['address'] }}</p>
    </div>

    <div class="invoice-details">
        <h2>Detalles de la Factura</h2>
        <p>Detalles: {{ $invoice['details'] }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Precio Unitario</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td>{{ $product['name'] }}</td>
                    <td>{{ $product['pivot']['quantity'] }}</td>
                    <td>{{ number_format($product['pivot']['price'] / 100, 2) }}</td>
                    <td>{{ number_format(($product['pivot']['quantity'] * $product['pivot']['price']) / 100, 2) }}</td>
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
