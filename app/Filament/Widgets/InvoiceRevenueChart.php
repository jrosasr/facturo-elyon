<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Invoice; // Import your Invoice model
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Carbon\Carbon; // For date formatting
use Filament\Support\RawJs; // For advanced Chart.js options
// DB is no longer needed for revenue calculation if using 'total' column directly

class InvoiceRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Actividad y Monto de Facturación por Fecha'; // Updated heading to reflect both metrics

    protected static string $color = 'primary'; // Default color for the chart

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = '30_days'; // Default filter to last 30 days

    protected function getType(): string
    {
        return 'line'; // Set to line chart
    }

    protected function getData(): array
    {
        // Determine the start date based on the selected filter
        $startDate = match ($this->filter) {
            '7_days' => now()->subDays(6),
            '30_days' => now()->subDays(29), // Last 30 days including today
            '3_months' => now()->subMonths(3), // Last 3 full months
            '6_months' => now()->subMonths(6), // Last 6 full months
            '1_year' => now()->subYear(), // Last full year
            default => now()->subDays(29), // Fallback to last 30 days
        };

        // --- Dataset 1: Count of Invoices ---
        $invoiceCounts = Trend::model(Invoice::class) // Target the Invoice model
            ->dateColumn('date') // Specify 'date' column for aggregation
            ->between(
                start: $startDate,
                end: now(),
            )
            ->perDay() // Group counts by day
            ->count(); // Count the number of invoices

        // --- Dataset 2: Total Revenue (Monto Facturado) ---
        $revenueData = Trend::model(Invoice::class)
            ->dateColumn('date') // Specify 'date' column for aggregation
            ->between(
                start: $startDate,
                end: now(),
            )
            ->perDay()
            ->sum('total'); // Sum the 'total' column directly

        // --- Combine and prepare data for Chart.js ---
        // Initialize daily data with all dates in the range, setting default counts and revenues to 0
        $dailyData = collect();
        $currentDate = clone $startDate->startOfDay();
        $endDate = now()->endOfDay();

        while ($currentDate <= $endDate) {
            $dateString = $currentDate->toDateString();
            $dailyData->put($dateString, [
                'date' => $dateString,
                'count' => 0,
                'revenue' => 0,
            ]);
            $currentDate->addDay();
        }

        // Merge actual invoice counts into the dailyData collection
        foreach ($invoiceCounts as $trendValue) {
            $dateString = Carbon::parse($trendValue->date)->toDateString();
            if ($dailyData->has($dateString)) {
                $item = $dailyData->get($dateString); // Retrieve the item
                $item['count'] = $trendValue->aggregate; // Modify the copy
                $dailyData->put($dateString, $item); // Put the modified copy back
            }
        }

        // Merge actual revenue data into the dailyData collection
        foreach ($revenueData as $trendValue) {
            $dateString = Carbon::parse($trendValue->date)->toDateString();
            if ($dailyData->has($dateString)) {
                $item = $dailyData->get($dateString); // Retrieve the item
                $item['revenue'] = $trendValue->aggregate; // Modify the copy
                $dailyData->put($dateString, $item); // Put the modified copy back
            }
        }

        // Prepare labels and data for Chart.js from the combined dailyData
        $labels = $dailyData->keys()->map(fn ($date) => Carbon::parse($date)->isoFormat('DD MMM'))->toArray();
        $invoiceCountChartData = $dailyData->values()->map(fn ($data) => $data['count'])->toArray();
        $revenueChartData = $dailyData->values()->map(fn ($data) => $data['revenue'])->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Número de Facturas',
                    'data' => $invoiceCountChartData,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)', // Light blue fill
                    'borderColor' => '#36A2EB', // Blue line
                    'fill' => true,
                    'tension' => 0.4,
                    'pointRadius' => 3,
                    'pointBackgroundColor' => '#36A2EB',
                    'yAxisID' => 'y', // Assign to primary Y-axis
                ],
                [
                    'label' => 'Monto Facturado',
                    'data' => $revenueChartData,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)', // Light red fill
                    'borderColor' => '#FF6384', // Red line
                    'fill' => true,
                    'tension' => 0.4,
                    'pointRadius' => 3,
                    'pointBackgroundColor' => '#FF6384',
                    'yAxisID' => 'y1', // Assign to secondary Y-axis
                ],
            ],
            'labels' => $labels,
        ];
    }

    // Define the filter options for the chart
    protected function getFilters(): ?array
    {
        return [
            '7_days' => 'Últimos 7 días',
            '30_days' => 'Últimos 30 días',
            '3_months' => 'Últimos 3 meses',
            '6_months' => 'Últimos 6 meses',
            '1_year' => 'Último año',
        ];
    }

    // Custom Chart.js options for multi-axis line chart and enhanced tooltip
    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
            {
                responsive: true,
                maintainAspectRatio: false, // Allow chart to take available space
                scales: {
                    y: { // Primary Y-axis for Invoice Count
                        type: 'linear',
                        display: true,
                        position: 'left',
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: (value) => value % 1 === 0 ? value : '', // Show only whole numbers for counts
                        },
                        title: {
                            display: true,
                            text: 'Número de Facturas'
                        }
                    },
                    y1: { // Secondary Y-axis for Revenue
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false, // Only draw grid lines for the first Y-axis
                        },
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => {
                                // Assuming values are stored as integers (e.g., cents), divide by 100 for display
                                return (value / 100).toLocaleString('es-VE', { style: 'currency', currency: 'USD' });
                            },
                        },
                        title: {
                            display: true,
                            text: 'Monto Facturado'
                        }
                    },
                    x: {
                        // Options for X-axis if needed
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index', // Show all items for the hovered index
                        intersect: false, // Show tooltip even if not directly over a point
                        callbacks: {
                            label: (context) => {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                // Format based on the dataset label
                                if (context.dataset.label === 'Monto Facturado') {
                                    // Assuming raw value is in cents, convert to currency string
                                    return label + (context.raw / 100).toLocaleString('es-VE', { style: 'currency', currency: 'USD' });
                                }
                                return label + context.raw + ' facturas'; // For invoice counts
                            }
                        }
                    }
                }
            }
        JS);
    }
}