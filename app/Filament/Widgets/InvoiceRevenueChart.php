<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Invoice; // Import your Invoice model
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Carbon\Carbon; // For date formatting
use Filament\Support\RawJs; // For advanced Chart.js options

class InvoiceRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Conteo de Facturas por Fecha'; // Heading for the chart

    protected static string $color = 'primary'; // Default color for the chart

    // Remove maxHeight to allow it to take maximum vertical space if content allows,
    // although charts typically auto-adjust to width.
    // protected static ?string $maxHeight = '300px';

    // No specific width property for Filament widgets, they typically fill their container.
    // Maximize screen usage implies letting the container stretch.

    public ?string $filter = '30_days'; // Default filter to last 30 days

    protected function getType(): string
    {
        return 'line'; // Set to line chart as per requirement
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

        // Use laravel-trend to count invoices, grouped by the 'date' column
        $data = Trend::model(Invoice::class) // Target the Invoice model
            ->dateColumn('date') // Specify 'date' column for aggregation as per requirement
            ->between(
                start: $startDate,
                end: now(),
            )
            ->perDay() // Group counts by day
            ->count(); // Count the number of invoices

        // Ensure all dates in the range are present, even if their count is 0
        $allDates = collect();
        $currentDate = clone $startDate->startOfDay();
        $endDate = now()->endOfDay();

        while ($currentDate <= $endDate) {
            $allDates->put($currentDate->toDateString(), 0);
            $currentDate->addDay();
        }

        // Merge actual data with all dates, filling in zeros for missing dates
        foreach ($data as $trendValue) {
            $dateString = Carbon::parse($trendValue->date)->toDateString();
            $allDates->put($dateString, $trendValue->aggregate);
        }

        // Prepare labels and data for Chart.js
        $labels = $allDates->keys()->map(fn ($date) => Carbon::parse($date)->isoFormat('DD MMM'))->toArray();
        $chartData = $allDates->values()->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Número de Facturas',
                    'data' => $chartData,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)', // Light blue fill for line chart area
                    'borderColor' => '#36A2EB', // Blue line
                    'fill' => true, // Fill the area under the line
                    'tension' => 0.4, // Smooth the line
                    'pointRadius' => 3, // Size of data points
                    'pointBackgroundColor' => '#36A2EB', // Color of data points
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

    // Custom Chart.js options for better presentation of counts
    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
            {
                responsive: true, // Chart responsiveness
                maintainAspectRatio: false, // Allow chart to take available space
                scales: {
                    y: {
                        beginAtZero: true, // Start Y-axis from zero
                        ticks: {
                            stepSize: 1, // Ensure whole numbers for invoice counts
                            callback: (value) => value % 1 === 0 ? value : '', // Show only whole number ticks
                        },
                    },
                    x: {
                        // Options for X-axis if needed (e.g., to rotate labels)
                    }
                },
                plugins: {
                    legend: {
                        display: true, // Display legend
                        position: 'top', // Position legend at the top
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                return label + context.raw + ' facturas';
                            }
                        }
                    }
                }
            }
        JS);
    }
}