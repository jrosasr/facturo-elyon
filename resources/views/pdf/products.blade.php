// resources/views/pdf/products.blade.php
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Productos por Categoría</title>
    <style>
        body {
            font-family: sans-serif;
        }

        h2 {
            font-size: 20px;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th,
        td {
            font-size: 12px;
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>
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
</body>

</html>
