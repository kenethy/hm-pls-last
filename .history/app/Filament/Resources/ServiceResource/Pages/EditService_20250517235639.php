<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use App\Models\Mechanic;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditService extends EditRecord
{
    protected static string $resource = ServiceResource::class;

    public function mount($record): void
    {
        parent::mount($record);

        // Log untuk debugging
        if (is_object($record) && method_exists($record, 'getKey')) {
            Log::info("EditService: Mounting edit page for service #{$record->getKey()}");
        } else {
            Log::info("EditService: Mounting edit page for service", ["record_type" => gettype($record)]);
        }

        // Pastikan mechanic_costs diisi dengan benar ya
        $this->fillMechanicCosts();
    }

    protected function fillMechanicCosts(): void
    {
        // Ambil data service
        $service = $this->record;

        // Jika tidak ada service atau bukan objek, keluar
        if (!$service || !is_object($service)) {
            Log::info("EditService: No valid service record found", ["record_type" => gettype($service)]);
            return;
        }

        // Log untuk debugging
        Log::info("EditService: Filling mechanic costs for service #{$service->getKey()}");

        // Ambil data form saat ini
        $data = $this->data;

        // Jika mechanic_costs sudah diisi, keluar
        if (isset($data['mechanic_costs']) && is_array($data['mechanic_costs']) && !empty($data['mechanic_costs'])) {
            Log::info("EditService: Mechanic costs already filled", $data['mechanic_costs']);
            return;
        }

        // Siapkan mechanic_costs berdasarkan montir yang ada di database
        if ($service->mechanics()->count() > 0) {
            $mechanicCosts = [];

            foreach ($service->mechanics as $mechanic) {
                $laborCost = $mechanic->pivot->labor_cost;

                // Pastikan labor_cost tidak 0, tapi jangan override nilai yang sudah diisi
                if (empty($laborCost) || $laborCost == 0) {
                    $laborCost = 50000; // Default labor cost
                } else {
                    // Gunakan nilai yang sudah diisi
                    Log::info("EditService: Using existing labor cost for mechanic #{$mechanic->id}: {$laborCost}");
                }

                $mechanicCosts[] = [
                    'mechanic_id' => $mechanic->id,
                    'labor_cost' => $laborCost,
                ];
            }

            // Log mechanic_costs yang akan diisi ke form
            Log::info("EditService: Setting mechanic costs in mount", $mechanicCosts);

            // Tambahkan mechanic_costs ke data
            $data['mechanic_costs'] = $mechanicCosts;

            // Pastikan mechanics juga diisi dengan benar
            if (!isset($data['mechanics']) || !is_array($data['mechanics']) || empty($data['mechanics'])) {
                $data['mechanics'] = $service->mechanics()->pluck('mechanic_id')->toArray();
                Log::info("EditService: Setting mechanics in mount", $data['mechanics']);
            }

            // Update data form
            $this->form->fill($data);
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Log data yang akan diisi ke form
        Log::info("EditService: Form data before fill", $data);

        // Ambil data service
        $service = $this->record;

        // Jika tidak ada service atau bukan objek, keluar
        if (!$service || !is_object($service)) {
            Log::info("EditService: No valid service record in mutateFormDataBeforeFill", ["record_type" => gettype($service)]);
            return $data;
        }

        // Siapkan mechanic_costs berdasarkan montir yang ada di database
        if (method_exists($service, 'mechanics') && $service->mechanics()->count() > 0) {
            $mechanicCosts = [];

            foreach ($service->mechanics as $mechanic) {
                $laborCost = $mechanic->pivot->labor_cost;

                // Pastikan labor_cost tidak 0, tapi jangan override nilai yang sudah diisi
                if (empty($laborCost) || $laborCost == 0) {
                    $laborCost = 50000; // Default labor cost
                } else {
                    // Gunakan nilai yang sudah diisi
                    Log::info("EditService: Using existing labor cost for mechanic #{$mechanic->id}: {$laborCost}");
                }

                $mechanicCosts[] = [
                    'mechanic_id' => $mechanic->id,
                    'labor_cost' => $laborCost,
                ];
            }

            // Log mechanic_costs yang akan diisi ke form
            Log::info("EditService: Mechanic costs data from database", $mechanicCosts);

            // Tambahkan mechanic_costs ke data
            $data['mechanic_costs'] = $mechanicCosts;

            // Pastikan mechanics juga diisi dengan benar
            if (!isset($data['mechanics']) || !is_array($data['mechanics']) || empty($data['mechanics'])) {
                $data['mechanics'] = $service->mechanics()->pluck('mechanic_id')->toArray();
                Log::info("EditService: Setting mechanics from database", $data['mechanics']);
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Ambil data service yang baru disimpan
        $service = $this->record;

        // Jika tidak ada service atau bukan objek, keluar
        if (!$service || !is_object($service)) {
            Log::info("EditService: No valid service record in afterSave", ["record_type" => gettype($service)]);
            return;
        }

        // Log untuk debugging
        Log::info("EditService: After save for service #{$service->getKey()}", [
            'status' => $service->status ?? 'unknown',
            'mechanics' => method_exists($service, 'mechanics') ? $service->mechanics()->pluck('mechanic_id')->toArray() : [],
        ]);

        // Jika status adalah completed, pastikan biaya jasa montir dipertahankan
        if ($service->status === 'completed') {
            // Ambil data form
            $formData = $this->data;

            // Log untuk debugging
            Log::info("EditService: Form data after save", $formData);

            // Periksa apakah ada mechanic_costs di form data
            if (isset($formData['mechanic_costs']) && is_array($formData['mechanic_costs'])) {
                // Dapatkan tanggal awal dan akhir minggu saat ini (Senin-Minggu)
                $now = now();
                $weekStart = $now->copy()->startOfWeek()->format('Y-m-d');
                $weekEnd = $now->copy()->endOfWeek()->format('Y-m-d');

                // Update pivot table dengan biaya jasa yang benar
                foreach ($formData['mechanic_costs'] as $costData) {
                    if (isset($costData['mechanic_id']) && isset($costData['labor_cost'])) {
                        $mechanicId = $costData['mechanic_id'];
                        $laborCost = (int)$costData['labor_cost'];

                        // Pastikan biaya jasa tidak 0, tapi jangan override nilai yang sudah diisi
                        if ($laborCost == 0) {
                            $laborCost = 50000; // Default labor cost
                        } else {
                            // Gunakan nilai yang sudah diisi
                            Log::info("Using existing labor cost for mechanic #{$mechanicId}: {$laborCost}");
                        }

                        Log::info("EditService: Updating labor cost for mechanic #{$mechanicId} to {$laborCost}");

                        // Update pivot table
                        $service->mechanics()->updateExistingPivot($mechanicId, [
                            'labor_cost' => $laborCost,
                            'week_start' => $weekStart,
                            'week_end' => $weekEnd,
                        ]);

                        // Hitung ulang total biaya jasa pada service
                        $totalLaborCost = 0;
                        foreach ($formData['mechanic_costs'] as $cost) {
                            if (isset($cost['labor_cost']) && $cost['labor_cost'] > 0) {
                                $totalLaborCost += (int)$cost['labor_cost'];
                                Log::info("EditService: Adding labor cost: " . (int)$cost['labor_cost'] . " for mechanic ID: " . ($cost['mechanic_id'] ?? 'unknown'));
                            }
                        }

                        // Update total biaya
                        $service->labor_cost = $totalLaborCost;
                        $service->total_cost = $totalLaborCost;
                        $service->save();

                        Log::info("EditService: Updated total labor cost for service #{$service->id} to {$totalLaborCost}");
                    }
                }

                // Jalankan command untuk memperbarui rekap montir
                try {
                    \Illuminate\Support\Facades\Artisan::call('mechanic:sync-reports', [
                        '--service_id' => $service->id,
                    ]);

                    Log::info("EditService: Mechanic reports synced for service #{$service->id}");
                } catch (\Exception $e) {
                    Log::error("EditService: Error syncing mechanic reports for service #{$service->id}: " . $e->getMessage());
                }
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refreshMechanicCosts')
                ->label('Refresh Biaya Jasa')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function () {
                    // Tidak perlu melakukan apa-apa, hanya untuk memicu refresh halaman
                    Notification::make()
                        ->title('Refresh biaya jasa berhasil')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('sendFollowUpWhatsApp')
                ->label('Kirim Follow-up WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success')
                ->visible(fn() => $this->record->status === 'completed')
                ->form(function () {
                    // Get all active templates
                    $templates = \App\Models\MessageTemplate::active()->get();

                    // Group templates by type
                    $templateOptions = [];
                    foreach ($templates as $template) {
                        $templateOptions[$template->id] = $template->name . ' (' . match ($template->type) {
                            'follow_up' => 'Follow-up',
                            'feedback' => 'Feedback',
                            'promo' => 'Promo',
                            'custom' => 'Kustom',
                            default => $template->type,
                        } . ')';
                    }

                    return [
                        Forms\Components\Select::make('template_id')
                            ->label('Template Pesan')
                            ->options($templateOptions)
                            ->default(function () {
                                // Get default follow-up template
                                $defaultTemplate = \App\Models\MessageTemplate::where('type', 'follow_up')
                                    ->where('is_default', true)
                                    ->where('is_active', true)
                                    ->first();

                                return $defaultTemplate ? $defaultTemplate->id : null;
                            })
                            ->selectablePlaceholder(false)
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $template = \App\Models\MessageTemplate::find($state);
                                    if ($template) {
                                        $set('preview', $template->content);
                                    }
                                }
                            }),

                        Forms\Components\Textarea::make('preview')
                            ->label('Preview Template')
                            ->disabled()
                            ->rows(6)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('custom_message')
                            ->label('Pesan Tambahan (Opsional)')
                            ->placeholder('Tambahkan pesan khusus di sini (opsional)')
                            ->rows(3)
                            ->columnSpanFull(),
                    ];
                })
                ->action(function (array $data): void {
                    // Format nomor telepon untuk WhatsApp
                    $phone = preg_replace('/[^0-9]/', '', $this->record->phone);
                    if (substr($phone, 0, 1) === '0') {
                        $phone = '62' . substr($phone, 1);
                    } elseif (substr($phone, 0, 2) !== '62') {
                        $phone = '62' . $phone;
                    }

                    // Get the selected template
                    $template = \App\Models\MessageTemplate::find($data['template_id']);
                    if (!$template) {
                        Notification::make()
                            ->title('Template tidak ditemukan')
                            ->body('Template yang dipilih tidak ditemukan. Silakan pilih template lain.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Get formatted content
                    $message = $template->getFormattedContent($this->record);

                    // Tambahkan pesan kustom jika ada
                    if (!empty($data['custom_message'])) {
                        $message .= "\n\n" . $data['custom_message'];
                    }

                    // Encode pesan untuk URL
                    $encodedMessage = urlencode($message);

                    // Buat URL WhatsApp
                    $whatsappUrl = "https://wa.me/{$phone}?text={$encodedMessage}";

                    // Tampilkan notifikasi sukses
                    Notification::make()
                        ->title('Pesan follow-up siap dikirim')
                        ->body('WhatsApp akan terbuka dengan pesan yang sudah disiapkan.')
                        ->success()
                        ->send();

                    // Redirect ke URL WhatsApp
                    redirect()->away($whatsappUrl);
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
