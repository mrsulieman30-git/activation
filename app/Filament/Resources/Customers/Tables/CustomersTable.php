<?php
 
 namespace App\Filament\Resources\Customers\Tables;
 
 use Filament\Actions;
 use Filament\Tables;
 use Filament\Tables\Columns\TextColumn;
 use Filament\Tables\Table;
 
 class CustomersTable
 {
     public static function configure(Table $table): Table
     {
         return $table
             ->columns([
                 TextColumn::make('name')
                     ->searchable()
                     ->sortable(),
                 TextColumn::make('code')
                     ->searchable()
                     ->copyable()
                     ->badge()
                     ->color('gray'),
                 TextColumn::make('contact_name')
                     ->searchable(),
                 TextColumn::make('contact_email')
                     ->searchable(),
                 TextColumn::make('contact_phone')
                     ->searchable()
                     ->toggleable(isToggledHiddenByDefault: true),
                 TextColumn::make('hms_server_url')
                     ->label('HMS Server')
                     ->searchable()
                     ->toggleable(isToggledHiddenByDefault: true),
                 TextColumn::make('max_devices')
                     ->numeric()
                     ->sortable(),
                 TextColumn::make('license_type')
                     ->badge()
                     ->color(fn (string $state): string => match ($state) {
                         'single' => 'gray',
                         'multi' => 'info',
                         'clinic' => 'primary',
                         'hospital' => 'success',
                         'unlimited' => 'warning',
                         default => 'gray',
                     }),
                 TextColumn::make('status')
                     ->badge()
                     ->color(fn (string $state): string => match ($state) {
                         'active' => 'success',
                         'suspended' => 'warning',
                         'terminated' => 'danger',
                         default => 'gray',
                     }),
                 TextColumn::make('created_at')
                     ->dateTime()
                     ->sortable()
                     ->toggleable(isToggledHiddenByDefault: true),
             ])
             ->filters([
                 Tables\Filters\SelectFilter::make('status')
                     ->options([
                         'active' => 'Active',
                         'suspended' => 'Suspended',
                         'terminated' => 'Terminated',
                     ]),
                 Tables\Filters\SelectFilter::make('license_type')
                     ->options([
                         'single' => 'Single Terminal',
                         'multi' => 'Multi Terminal',
                         'clinic' => 'Clinic',
                         'hospital' => 'Hospital',
                         'unlimited' => 'Unlimited',
                     ]),
             ])
             ->actions([
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
