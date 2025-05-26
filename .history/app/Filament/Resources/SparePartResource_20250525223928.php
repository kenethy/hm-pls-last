<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SparePartResource\Pages;
use App\Models\SparePart;
use App\Models\SparePartCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Filament\Support\Enums\FontWeight;

class SparePartResource extends Resource
{
    protected static ?string $model = SparePart::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Sparepart';

    protected static ?string $modelLabel = 'Sparepart';

    protected static ?string $pluralModelLabel = 'Sparepart';

    protected static ?string $navigationGroup = 'Manajemen Sparepart';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produk')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                fn(string $context, $state, Forms\Set $set) =>
                                $context === 'create' ? $set('slug', Str::slug($state)) : null
                            ),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(SparePart::class, 'slug', ignoreRecord: true)
                            ->rules(['alpha_dash']),

                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Kategori')
                                    ->required(),
                                Forms\Components\TextInput::make('slug')
                                    ->label('Slug')
                                    ->required(),
                                Forms\Components\ColorPicker::make('color')
                                    ->label('Warna')
                                    ->default('#dc2626'),
                            ]),

                        Forms\Components\TextInput::make('brand')
                            ->label('Merek')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('part_number')
                            ->label('Nomor Part')
                            ->maxLength(255),

                        Forms\Components\Select::make('condition')
                            ->label('Kondisi')
                            ->options([
                                'new' => 'Baru',
                                'original' => 'Original',
                                'aftermarket' => 'Aftermarket',
                            ])
                            ->default('new')
                            ->required(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Deskripsi')
                    ->schema([
                        Forms\Components\Textarea::make('short_description')
                            ->label('Deskripsi Singkat')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Deskripsi singkat untuk tampilan card produk'),

                        Forms\Components\RichEditor::make('description')
                            ->label('Deskripsi Lengkap')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Harga & Stok')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('Harga')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0),

                        Forms\Components\TextInput::make('original_price')
                            ->label('Harga Asli (Opsional)')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->helperText('Untuk menampilkan diskon'),

                        Forms\Components\TextInput::make('stock_quantity')
                            ->label('Jumlah Stok')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),

                        Forms\Components\TextInput::make('minimum_stock')
                            ->label('Stok Minimum')
                            ->numeric()
                            ->minValue(0)
                            ->default(5)
                            ->helperText('Peringatan stok rendah'),

                        Forms\Components\TextInput::make('warranty_period')
                            ->label('Periode Garansi')
                            ->placeholder('1 tahun, 6 bulan, dll')
                            ->maxLength(255),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                                'out_of_stock' => 'Stok Habis',
                            ])
                            ->default('active')
                            ->required(),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Gambar Produk')
                    ->schema([
                        Forms\Components\FileUpload::make('featured_image')
                            ->label('Gambar Utama')
                            ->image()
                            ->directory('spare-parts/featured')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ]),

                        Forms\Components\FileUpload::make('images')
                            ->label('Galeri Gambar')
                            ->image()
                            ->multiple()
                            ->directory('spare-parts/gallery')
                            ->visibility('public')
                            ->imageEditor()
                            ->reorderable()
                            ->maxFiles(10)
                            ->helperText('Maksimal 10 gambar'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Link Marketplace')
                    ->schema([
                        Forms\Components\Repeater::make('marketplace_links')
                            ->label('Link Marketplace')
                            ->schema([
                                Forms\Components\Select::make('platform')
                                    ->label('Platform')
                                    ->options([
                                        'shopee' => 'Shopee',
                                        'tokopedia' => 'Tokopedia',
                                        'lazada' => 'Lazada',
                                        'bukalapak' => 'Bukalapak',
                                    ])
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $platforms = \App\Models\SparePart::getMarketplacePlatforms();
                                        if (isset($platforms[$state])) {
                                            $set('placeholder', $platforms[$state]['placeholder']);
                                        }
                                    }),

                                Forms\Components\TextInput::make('url')
                                    ->label('URL Produk')
                                    ->url()
                                    ->required()
                                    ->placeholder(fn (Forms\Get $get) => {
                                        $platforms = \App\Models\SparePart::getMarketplacePlatforms();
                                        $platform = $get('platform');
                                        return $platform && isset($platforms[$platform])
                                            ? $platforms[$platform]['placeholder']
                                            : 'https://...';
                                    })
                                    ->helperText('Masukkan URL lengkap produk di marketplace'),

                                Forms\Components\Hidden::make('placeholder'),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->defaultItems(0)
                            ->addActionLabel('Tambah Link Marketplace')
                            ->helperText('Tambahkan link produk di berbagai marketplace untuk memudahkan pelanggan berbelanja online'),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Forms\Components\Section::make('Pengaturan Lanjutan')
                    ->schema([
                        Forms\Components\Repeater::make('specifications')
                            ->label('Spesifikasi Teknis')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Spesifikasi')
                                    ->required(),
                                Forms\Components\TextInput::make('value')
                                    ->label('Nilai')
                                    ->required(),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->defaultItems(0),

                        Forms\Components\Repeater::make('compatibility')
                            ->label('Kompatibilitas Kendaraan')
                            ->schema([
                                Forms\Components\TextInput::make('brand')
                                    ->label('Merek')
                                    ->required(),
                                Forms\Components\TextInput::make('model')
                                    ->label('Model')
                                    ->required(),
                                Forms\Components\TextInput::make('year')
                                    ->label('Tahun')
                                    ->placeholder('2020-2023'),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->defaultItems(0),

                        Forms\Components\Textarea::make('installation_notes')
                            ->label('Catatan Instalasi')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Forms\Components\Section::make('Pengaturan Tampilan')
                    ->schema([
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Produk Unggulan')
                            ->helperText('Tampilkan di bagian produk unggulan'),

                        Forms\Components\Toggle::make('is_best_seller')
                            ->label('Best Seller')
                            ->helperText('Tampilkan badge best seller'),

                        Forms\Components\Toggle::make('is_original')
                            ->label('Original')
                            ->helperText('Tampilkan badge original'),

                        Forms\Components\TextInput::make('order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->label('Meta Title')
                            ->maxLength(60)
                            ->helperText('Maksimal 60 karakter untuk SEO optimal'),

                        Forms\Components\Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->maxLength(160)
                            ->rows(3)
                            ->helperText('Maksimal 160 karakter untuk SEO optimal'),

                        Forms\Components\TextInput::make('meta_keywords')
                            ->label('Meta Keywords')
                            ->placeholder('keyword1, keyword2, keyword3')
                            ->helperText('Pisahkan dengan koma'),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('featured_image')
                    ->label('Gambar')
                    ->circular()
                    ->defaultImageUrl(asset('images/sparepart/sparepart.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn($record) => $record->category?->color ?? 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('brand')
                    ->label('Merek')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->label('Stok')
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Unggulan')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_best_seller')
                    ->label('Best Seller')
                    ->boolean()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        'out_of_stock' => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'out_of_stock' => 'Stok Habis',
                    ]),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Produk Unggulan'),

                Tables\Filters\TernaryFilter::make('is_best_seller')
                    ->label('Best Seller'),

                Tables\Filters\Filter::make('low_stock')
                    ->label('Stok Rendah')
                    ->query(fn(Builder $query): Builder => $query->whereColumn('stock_quantity', '<=', 'minimum_stock')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpareParts::route('/'),
            'create' => Pages\CreateSparePart::route('/create'),
            'view' => Pages\ViewSparePart::route('/{record}'),
            'edit' => Pages\EditSparePart::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['category']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'brand', 'part_number', 'category.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Kategori' => $record->category?->name,
            'Merek' => $record->brand,
            'Harga' => $record->formatted_price,
        ];
    }
}
