<?php

namespace App\Filament\Resources\Exams\Pages;

use App\Exports\MarksTemplateExport;
use App\Filament\Resources\Exams\ExamResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListExams extends ListRecords
{
    protected static string $resource = ExamResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    return Excel::download(
                        new MarksTemplateExport(),
                        'marks_template.xlsx'
                    );
                }),
        ];
    }
}
