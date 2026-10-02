<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Filament\Resources\Exams\ExamResource;
use App\Imports\MarksImport;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Maatwebsite\Excel\Facades\Excel;

class ViewExam extends ViewRecord
{
    protected static string $resource = ExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('importMasterMarks')
                ->label('Upload Marks Sheet')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('primary')
                ->form([
                    FileUpload::make('marks_bundle')
                        ->label('Select Master Excel File')
                        ->disk('public')
                        ->directory('imports/master')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $filePath = storage_path('app/public/' . $data['marks_bundle']);

                    try {
                        Excel::import(new MarksImport($this->record->id, $this->record->branch_id), $filePath);

                        Notification::make()
                            ->success()
                            ->title('Master Import Complete')
                            ->body('All student marks have been updated across all classes.')
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Import Error')
                            ->body('Check sheet formatting: ' . $e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
