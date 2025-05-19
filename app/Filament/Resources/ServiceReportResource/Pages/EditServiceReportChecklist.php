<?php

namespace App\Filament\Resources\ServiceReportResource\Pages;

use App\Filament\Resources\ServiceReportResource;
use App\Models\ServiceReport;
use App\Models\ServiceReportChecklistItem;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\HtmlString;

class EditServiceReportChecklist extends Page
{
    protected static string $resource = ServiceReportResource::class;

    protected static string $view = 'filament.resources.service-report-resource.pages.edit-service-report-checklist';

    public ?array $data = [];

    public ServiceReport $record;

    public function mount(ServiceReport $record): void
    {
        $this->record = $record;
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Checklist Pemeriksaan 50 Titik')
                    ->description('Isi status dan catatan untuk setiap titik pemeriksaan')
                    ->schema([
                        Forms\Components\Repeater::make('checklist_items')
                            ->label('')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\TextInput::make('inspection_point')
                                    ->label('Titik Pemeriksaan')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'ok' => 'OK',
                                        'warning' => 'Waspada',
                                        'needs_repair' => 'Harus Diperbaiki',
                                    ])
                                    ->default('ok')
                                    ->required(),
                                Forms\Components\Textarea::make('notes')
                                    ->label('Catatan')
                                    ->rows(1),
                            ])
                            ->columns(4)
                            ->itemLabel(fn (array $state): ?string => $state['inspection_point'] ?? null)
                            ->collapsible()
                            ->defaultItems(0)
                            ->reorderable()
                            ->reorderableWithDragAndDrop()
                            ->addActionLabel('Tambah Titik Pemeriksaan')
                    ]),
            ]);
    }

    public function getFormStatePath(): string
    {
        return 'data';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Simpan Checklist')
                ->submit('save'),
            Actions\Action::make('share')
                ->label('Bagikan')
                ->icon('heroicon-o-share')
                ->color('primary')
                ->action(function () {
                    return Notification::make()
                        ->title('Link laporan disalin ke clipboard')
                        ->body(new HtmlString('Link: <strong>' . $this->record->getUrl() . '</strong>'))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('preview')
                ->label('Pratinjau')
                ->icon('heroicon-o-eye')
                ->color('success')
                ->url(fn () => $this->record->getUrl())
                ->openUrlInNewTab(),
        ];
    }

    public function save(): void
    {
        $items = $this->form->getState()['checklist_items'] ?? [];

        // Delete existing items
        $this->record->checklistItems()->delete();

        // Create new items
        foreach ($items as $index => $item) {
            $this->record->checklistItems()->create([
                'order' => $index + 1,
                'inspection_point' => $item['inspection_point'],
                'status' => $item['status'],
                'notes' => $item['notes'] ?? '',
            ]);
        }

        Notification::make()
            ->title('Checklist berhasil disimpan')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $checklistItems = $this->record->checklistItems()
            ->orderBy('order')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'inspection_point' => $item->inspection_point,
                    'status' => $item->status,
                    'notes' => $item->notes,
                ];
            })
            ->toArray();

        $this->form->fill([
            'checklist_items' => $checklistItems,
        ]);

        return [
            'record' => $this->record,
        ];
    }
}
