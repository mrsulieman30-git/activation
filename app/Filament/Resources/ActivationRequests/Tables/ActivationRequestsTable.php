<?php

namespace App\Filament\Resources\ActivationRequests\Tables;

use Filament\Actions;
use Filament\Tables;
use Filament\Forms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ActivationRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('request_id')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serialKey.key_value')
                    ->label('Serial Key')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('serialKey.customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('device_fingerprint')
                    ->label('Device Fingerprint')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('device_name')
                    ->searchable(),
                TextColumn::make('app_version')
                    ->searchable(),
                TextColumn::make('os_version')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'pending' => 'warning',
                        'rejected', 'revoked' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'revoked' => 'Revoked',
                    ]),
            ])
            ->actions([
                Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ]);

                        // Generate and sign certificate
                        $signer = app(\App\Services\CertificateSignerService::class);
                        $customer = $record->serialKey->customer;
                        $licenseId = Str::uuid()->toString();

                        $payload = [
                            'license_id' => $licenseId,
                            'customer_id' => $customer->code,
                            'customer_name' => $customer->name,
                            'license_type' => $customer->license_type,
                            'device_fingerprint' => $record->device_fingerprint,
                            'max_devices' => $customer->max_devices,
                            'issued_at' => now()->toIso8601String(),
                            'expires_at' => $record->serialKey->expires_at ? $record->serialKey->expires_at->toIso8601String() : null,
                            'features' => ['offline_mode', 'sync'], 
                        ];

                        $signedData = $signer->signCertificate($payload);

                        \App\Models\ActivationCertificate::create([
                            'activation_request_id' => $record->id,
                            'serial_key_id' => $record->serial_key_id,
                            'customer_id' => $customer->id,
                            'device_fingerprint' => $record->device_fingerprint,
                            'license_id' => $licenseId,
                            'server_url' => $customer->hms_server_url ?? '',
                            'api_url' => $customer->hms_api_url ?? '',
                            'certificate_data' => $signedData['payload'],
                            'digital_signature' => $signedData['signature'],
                            'issued_at' => now()
                        ]);
                    }),

                Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ]);
                    }),

                Actions\Action::make('revoke')
                    ->label('Revoke')
                    ->color('danger')
                    ->icon('heroicon-o-no-symbol')
                    ->visible(fn ($record) => $record->status === 'approved')
                    ->requiresConfirmation()
                    ->modalDescription('This will revoke the activation. The desktop app will be deactivated on its next heartbeat check.')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Revocation Reason')
                            ->placeholder('e.g., License expired, contract terminated, suspicious activity'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'revoked',
                            'rejection_reason' => $data['rejection_reason'] ?? 'License revoked by administrator.',
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ]);

                        // Also revoke the certificate if it exists
                        $certificate = \App\Models\ActivationCertificate::where('activation_request_id', $record->id)->first();
                        if ($certificate) {
                            $certificate->update(['revoked_at' => now()]);
                        }
                    }),

                Actions\Action::make('reactivate')
                    ->label('Re-activate')
                    ->color('success')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn ($record) => in_array($record->status, ['revoked', 'rejected']))
                    ->requiresConfirmation()
                    ->modalDescription('This will re-activate this device. The desktop app will regain access on its next heartbeat check.')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'rejection_reason' => null,
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                        ]);

                        // Un-revoke the certificate if it exists
                        $certificate = \App\Models\ActivationCertificate::where('activation_request_id', $record->id)->first();
                        if ($certificate) {
                            $certificate->update(['revoked_at' => null]);
                        }
                    }),

                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}

