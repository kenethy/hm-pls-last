<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Services\MechanicRatingService;
use App\Models\Mechanic;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MechanicPerformanceAnalytics extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Analisis Performa Montir';
    protected static ?string $title = 'Analisis Performa Montir';
    protected static ?string $navigationGroup = 'Laporan & Analisis';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.mechanic-performance-analytics';

    public ?array $data = [];
    public $startDate = null;
    public $endDate = null;
    public $selectedMechanic = null;
    public $analytics = null;
    public $comparativeData = null;
    public $ratingStatistics = null;

    protected MechanicRatingService $ratingService;

    public function boot(MechanicRatingService $ratingService)
    {
        $this->ratingService = $ratingService;
    }

    public function mount(): void
    {
        // Set default date range (last 30 days)
        $this->startDate = now()->subDays(30)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');

        $this->form->fill([
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'mechanic_id' => null
        ]);

        $this->loadAnalytics();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->default(now()->subDays(30))
                    ->maxDate(now())
                    ->reactive()
                    ->afterStateUpdated(fn() => $this->loadAnalytics()),

                DatePicker::make('end_date')
                    ->label('Tanggal Akhir')
                    ->default(now())
                    ->maxDate(now())
                    ->reactive()
                    ->afterStateUpdated(fn() => $this->loadAnalytics()),

                Select::make('mechanic_id')
                    ->label('Pilih Montir (Opsional)')
                    ->placeholder('Semua Montir')
                    ->options(Mechanic::active()->pluck('name', 'id'))
                    ->reactive()
                    ->afterStateUpdated(fn() => $this->loadAnalytics()),
            ])
            ->columns(3)
            ->statePath('data');
    }

    public function loadAnalytics(): void
    {
        $startDate = $this->data['start_date'] ?? $this->startDate;
        $endDate = $this->data['end_date'] ?? $this->endDate;
        $mechanicId = $this->data['mechanic_id'] ?? null;

        // Load rating statistics
        $this->ratingStatistics = $this->ratingService->getRatingStatistics($startDate, $endDate);

        if ($mechanicId) {
            // Load individual mechanic analytics
            $this->analytics = $this->ratingService->getMechanicPerformanceAnalytics($mechanicId, $startDate, $endDate);
            $this->comparativeData = null;
        } else {
            // Load comparative analytics for all mechanics
            $this->comparativeData = $this->ratingService->getComparativeAnalytics($startDate, $endDate);
            $this->analytics = null;
        }
    }

    public function exportData()
    {
        $startDate = $this->data['start_date'] ?? null;
        $endDate = $this->data['end_date'] ?? null;

        $data = $this->ratingService->exportRatingsData($startDate, $endDate);

        $filename = 'rating-data-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($data) {
            $file = fopen('php://output', 'w');

            // Add BOM for UTF-8
            fwrite($file, "\xEF\xBB\xBF");

            // Add headers
            if ($data->isNotEmpty()) {
                fputcsv($file, array_keys($data->first()));

                // Add data
                foreach ($data as $row) {
                    fputcsv($file, $row);
                }
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    public function getQuickDateRanges(): array
    {
        return [
            [
                'label' => '7 Hari Terakhir',
                'start' => now()->subDays(7)->format('Y-m-d'),
                'end' => now()->format('Y-m-d')
            ],
            [
                'label' => '30 Hari Terakhir',
                'start' => now()->subDays(30)->format('Y-m-d'),
                'end' => now()->format('Y-m-d')
            ],
            [
                'label' => '3 Bulan Terakhir',
                'start' => now()->subMonths(3)->format('Y-m-d'),
                'end' => now()->format('Y-m-d')
            ],
            [
                'label' => 'Tahun Ini',
                'start' => now()->startOfYear()->format('Y-m-d'),
                'end' => now()->format('Y-m-d')
            ]
        ];
    }

    public function setQuickDateRange(string $start, string $end): void
    {
        $this->form->fill([
            'start_date' => $start,
            'end_date' => $end,
            'mechanic_id' => $this->data['mechanic_id'] ?? null
        ]);

        $this->loadAnalytics();
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->email === 'admin@hartonomotor.com';
    }
}
