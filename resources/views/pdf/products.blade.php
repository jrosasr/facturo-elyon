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
    <title>Lista de Productos por Categoría</title>
    @include('pdf.partials._styles') {{-- Incluye los estilos aquí --}}
</head>

<body>
    @include('pdf.partials._header', [
        'base64Logo' => $base64Logo ?? null,
        'teamName' => $teamName ?? null,
        'teamRif' => $teamRif ?? null,
        'teamAddress' => $teamAddress ?? null,
        'invoice' => $invoice ?? ['date' => now()], // Asegúrate de pasar el array $invoice
        'formatDate' => function($date) { return Carbon\Carbon::parse($date)->format('d/m/Y'); }
    ])

    <h1>Lista de Productos</h1>

    @foreach ($productsByCategory as $category)
        <h2>{{ $category->name }}</h2>
        <table>
            <thead>
                <tr>
                    <th>Nombre del Producto</th>
                    <th>Precio</th>
                    <th>Disponibilidad</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($category->products as $product)
                    <tr>
                        <td>{{ $product->name }}</td>
                        <td><strong>$ {{ $product->price }}</strong></td>
                        <td>{{ $product->stock > 0 ? 'Disponible' : 'Agotado' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    @include('pdf.partials._footer')
</body>
</html>
