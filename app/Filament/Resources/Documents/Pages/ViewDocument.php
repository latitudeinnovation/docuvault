<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Enums\ExtractedFieldStatus;
use App\Filament\Resources\Documents\DocumentResource;
use App\Jobs\ProcessDocumentWithRaraxuan;
use App\Models\Company;
use App\Models\Document;
use App\Services\Documents\BankAccountExtractor;
use App\Services\Documents\DirectorExtractor;
use App\Services\Documents\DocumentClassifier;
use App\Services\Documents\ShareholderExtractor;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->icon(Heroicon::CheckCircle)
                ->color('success')
                ->button()
                ->requiresConfirmation()
                ->visible(fn (Document $record): bool => $record->extractedFields()->exists()
                    && ! $record->extractedFields()->where('status', ExtractedFieldStatus::Pending->value)->exists()
                    && $record->status !== DocumentStatus::Approved)
                ->action(function (Document $record): void {
                    $record->update(['status' => DocumentStatus::Approved]);

                    Notification::make()
                        ->success()
                        ->title('Document approved')
                        ->send();
                }),
            Action::make('linkSync')
                ->label('Link & Sync')
                ->icon(Heroicon::Link)
                ->color('warning')
                ->button()
                ->visible(fn (Document $record): bool => $record->extractedFields()->exists()
                    && ! $record->extractedFields()->where('status', ExtractedFieldStatus::Pending->value)->exists())
                ->schema(fn (Document $record): array => [
                    Select::make('company_id')
                        ->label('Company')
                        ->options(fn (): array => Company::query()
                            ->where('user_id', $record->user_id)
                            ->pluck('name', 'id')
                            ->all())
                        ->default($record->company_id)
                        ->searchable()
                        ->required()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Company Name')
                                ->default(app(DocumentClassifier::class)->extractedCompanyName($record))
                                ->required()
                                ->maxLength(255),
                            TextInput::make('registration_no')
                                ->label('Registration No.')
                                ->default(app(DocumentClassifier::class)->extractedRegistrationNo($record)),
                        ])
                        ->createOptionUsing(fn (array $data): int => Company::create([
                            'user_id' => $record->user_id,
                            'name' => $data['name'],
                            'slug' => Company::slugFor($data['name']),
                            'registration_no' => $data['registration_no'] ?? null,
                        ])->getKey()),
                ])
                ->action(function (Document $record, array $data): void {
                    $company = Company::query()->findOrFail($data['company_id']);

                    $record->update(['company_id' => $company->getKey()]);

                    if ($record->document_type === DocumentType::Ssm->value) {
                        app(DirectorExtractor::class)->syncFromDocument($record, $company);
                        app(ShareholderExtractor::class)->syncFromDocument($record, $company);
                    } elseif ($record->document_type === DocumentType::BankAccount->value) {
                        app(BankAccountExtractor::class)->syncFromDocument($record, $company);
                    }

                    Notification::make()
                        ->success()
                        ->title("Linked to {$company->name} and synced.")
                        ->send();
                }),
            Action::make('reprocess')
                ->label('Process')
                ->icon(Heroicon::ArrowPath)
                ->color('info')
                ->button()
                ->requiresConfirmation()
                ->action(function (Document $record): void {
                    if (! $record->shouldExtract()) {
                        Notification::make()
                            ->success()
                            ->title('General document — no extraction needed')
                            ->send();

                        return;
                    }

                    $record->forceFill([
                        'status' => DocumentStatus::Processing,
                        'failure_reason' => null,
                    ])->save();

                    ProcessDocumentWithRaraxuan::dispatch($record);

                    Notification::make()
                        ->success()
                        ->title('Document queued for processing')
                        ->send();
                }),
            Action::make('download')
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->button()
                ->action(fn (Document $record) => Storage::disk($record->file_disk)->download(
                    $record->file_path,
                    $record->original_file_name ?: basename($record->file_path),
                )),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
