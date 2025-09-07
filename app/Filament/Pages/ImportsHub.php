<?php

namespace App\Filament\Pages;

use App\Jobs\ParseEntityImport;
use App\Models\IngestionJob;
use App\Models\MappingProfile;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class ImportsHub extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected string $view = 'filament.pages.imports-hub';
    protected static \UnitEnum|string|null $navigationGroup = 'Imports';

    public ?int $tenant_id = null;
    public string $entity = 'accounts';
    public ?int $mapping_profile_id = null;
    public ?string $preset = null;
    public $file;

    protected function getForms(): array
    {
        return [
            'form' => Forms\Components\Form::make()
                ->schema([
                    Forms\Components\Select::make('tenant_id')
                        ->label('Tenant')
                        ->options(Tenant::query()->pluck('name','id'))
                        ->required(),
                    Forms\Components\Select::make('entity')
                        ->options([
                            'accounts' => 'Accounts',
                            'customers' => 'Customers',
                            'vendors' => 'Vendors',
                            'invoices' => 'Invoices',
                            'bills' => 'Bills',
                            'journals' => 'Journals',
                        ])->required(),
                    Forms\Components\Select::make('preset')
                        ->label('Preset Mapping')
                        ->options([
                            'qbo' => 'QuickBooks Online',
                            'xero' => 'Xero',
                        ])->helperText('Optional: choose a preset to auto-map columns.'),
                    Forms\Components\Select::make('mapping_profile_id')
                        ->label('Existing Mapping Profile')
                        ->options(fn() => MappingProfile::query()
                            ->when($this->tenant_id, fn($q) => $q->where('tenant_id', $this->tenant_id))
                            ->when($this->entity, fn($q) => $q->where('entity', $this->entity))
                            ->pluck('name','id')),
                    Forms\Components\FileUpload::make('file')->acceptedFileTypes(['text/csv','text/plain'])->required(),
                ])->statePath('data'),
        ];
    }

    public array $data = [];

    public function submit(): void
    {
        $this->tenant_id = (int) ($this->data['tenant_id'] ?? 0);
        $this->entity = (string) ($this->data['entity'] ?? '');
        $this->mapping_profile_id = $this->data['mapping_profile_id'] ?? null;
        $this->preset = $this->data['preset'] ?? null;
        $this->file = $this->data['file'] ?? null;

        if (!$this->tenant_id || !$this->entity || !$this->file) {
            Notification::make()->danger()->title('Missing fields')->body('Tenant, entity, and file are required.')->send();
            return;
        }

        // Create mapping profile from preset if needed
        if (!$this->mapping_profile_id && $this->preset) {
            $mapping = $this->presetMapping($this->preset, $this->entity);
            if ($mapping) {
                $mp = MappingProfile::create([
                    'tenant_id' => $this->tenant_id,
                    'name' => strtoupper($this->preset).' '.$this->entity.' preset',
                    'entity' => $this->entity,
                    'mapping' => $mapping,
                ]);
                $this->mapping_profile_id = $mp->id;
            }
        }

        // Store file and create job
        $uploaded = $this->file; // SplFileInfo path from Filament
        $contents = file_get_contents($uploaded->getRealPath());
        $dir = "imports/{$this->tenant_id}/{$this->entity}";
        $path = $dir.'/admin_'.uniqid().'.csv';
        Storage::put($path, $contents);

        $job = IngestionJob::create([
            'tenant_id' => $this->tenant_id,
            'path' => $path,
            'status' => 'queued',
            'meta' => [
                'import_type' => $this->entity,
                'mapping_profile_id' => $this->mapping_profile_id,
                'source' => 'filament',
            ],
        ]);

        ParseEntityImport::dispatch($this->tenant_id, $this->entity, $path, $job->id);

        Notification::make()
            ->success()
            ->title('Import queued')
            ->body('Job #'.$job->id.' queued for '.$this->entity)
            ->send();
    }

    private function presetMapping(string $preset, string $entity): ?array
    {
        // Minimal presets mapping external column names to our canonical keys
        $presets = [
            'qbo' => [
                'accounts' => [ 'code' => 'Number', 'name' => 'Name', 'type' => 'Type', 'parent_code' => 'Parent Number', 'is_active' => 'Active' ],
                'customers' => [ 'external_id' => 'Id', 'name' => 'Display Name', 'email' => 'Primary Email', 'phone' => 'Primary Phone' ],
                'vendors' => [ 'external_id' => 'Id', 'name' => 'Display Name', 'email' => 'Primary Email', 'phone' => 'Primary Phone' ],
                'invoices' => [ 'number' => 'DocNumber', 'date' => 'TxnDate', 'customer_name' => 'Customer', 'account_code' => 'Account', 'description' => 'Memo', 'qty' => 'Qty', 'unit_price' => 'Amount' ],
                'bills' => [ 'number' => 'DocNumber', 'date' => 'TxnDate', 'vendor_name' => 'Vendor', 'account_code' => 'Account', 'description' => 'Memo', 'qty' => 'Qty', 'unit_price' => 'Amount' ],
                'journals' => [ 'date' => 'TxnDate', 'memo' => 'Memo', 'account_code' => 'Account', 'debit' => 'Debit', 'credit' => 'Credit', 'contra_account_code' => 'Contra Account' ],
            ],
            'xero' => [
                'accounts' => [ 'code' => 'Code', 'name' => 'Name', 'type' => 'Type', 'is_active' => 'EnablePaymentsToAccount' ],
                'customers' => [ 'name' => 'ContactName', 'email' => 'EmailAddress' ],
                'vendors' => [ 'name' => 'ContactName', 'email' => 'EmailAddress' ],
                'invoices' => [ 'number' => 'InvoiceNumber', 'date' => 'InvoiceDate', 'customer_name' => 'ContactName', 'account_code' => 'AccountCode', 'description' => 'Description', 'qty' => 'Quantity', 'unit_price' => 'UnitAmount' ],
                'bills' => [ 'number' => 'InvoiceNumber', 'date' => 'InvoiceDate', 'vendor_name' => 'ContactName', 'account_code' => 'AccountCode', 'description' => 'Description', 'qty' => 'Quantity', 'unit_price' => 'UnitAmount' ],
                'journals' => [ 'date' => 'JournalDate', 'memo' => 'Narration', 'account_code' => 'AccountCode', 'debit' => 'Debit', 'credit' => 'Credit', 'contra_account_code' => 'Contra' ],
            ],
        ];

        return $presets[$preset][$entity] ?? null;
    }
}
