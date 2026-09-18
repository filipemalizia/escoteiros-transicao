<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class ImagemUploadField
{
    /**
     * Campo de upload de imagem (via Media Library) + opção de colar uma URL
     * pro sistema baixar sozinho. O "Baixar da URL" só aparece em registros
     * já salvos (precisa de um model existente pra anexar a mídia).
     *
     * @return array<int, Component>
     */
    public static function make(string $collection, string $label): array
    {
        return [
            SpatieMediaLibraryFileUpload::make($collection)
                ->collection($collection)
                // Sem isso, cai no default_filesystem_disk do Filament
                // ('local', não servido publicamente) em vez do disco
                // 'public' usado pelo resto do Media Library (ex.:
                // addMediaFromUrl() abaixo, que já usa 'public' por padrão).
                ->disk('public')
                ->label($label)
                ->image()
                ->imagePreviewHeight('120')
                ->columnSpanFull(),

            TextInput::make("{$collection}_url")
                ->label("{$label} (colar URL pra baixar)")
                ->url()
                ->dehydrated(false)
                ->suffixAction(
                    Action::make("baixar_{$collection}")
                        ->label('Baixar da URL')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->visible(fn (?Model $record): bool => filled($record))
                        ->action(function (Model $record, mixed $state) use ($collection): void {
                            if (blank($state)) {
                                return;
                            }

                            try {
                                $record->addMediaFromUrl($state)->toMediaCollection($collection);

                                Notification::make()
                                    ->title('Imagem baixada com sucesso')
                                    ->success()
                                    ->send();
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title('Não foi possível baixar a imagem')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                )
                ->columnSpanFull(),
        ];
    }
}
