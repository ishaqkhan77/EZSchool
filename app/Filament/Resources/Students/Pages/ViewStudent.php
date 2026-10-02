<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Filament\Pages\Chat as MessagesPage;
use App\Models\User;
use App\Services\ChatAccessService;
use App\Filament\Resources\Students\Widgets\StudentAcademicTrend;
use App\Filament\Resources\Students\Widgets\StudentOverview;
use App\Models\Student;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ViewRecord;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;
    public ?string $attendanceMonth = null;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('messageParent')
                ->label('Message parent')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->visible(function (): bool {
                    $user = Auth::user();
                    $branchId = Filament::getTenant()?->getKey();

                    return $user instanceof User
                        && $branchId
                        && app(ChatAccessService::class)->hasRoleInBranch($user, 'Teacher', (string) $branchId);
                })
                ->form([
                    Select::make('parent_id')
                        ->label('Parent or guardian')
                        ->options(fn (): array => $this->record->parents()
                            ->where('parents.branch_id', $this->record->branch_id)
                            ->whereHas('user')
                            ->orderBy('full_name')
                            ->get()
                            ->mapWithKeys(fn ($parent) => [
                                $parent->id => $parent->full_name . ' (' . ucfirst($parent->pivot->relationship_type ?: 'Guardian') . ')',
                            ])
                            ->all())
                        ->required()
                        ->searchable(),
                ])
                ->action(function (array $data): void {
                    $user = Auth::user();
                    abort_unless($user instanceof User, 403);

                    $access = app(ChatAccessService::class);
                    abort_unless($access->teacherCanMessageStudentParent($user, $this->record, $data['parent_id']), 403);

                    $parent = $this->record->parents()->whereKey($data['parent_id'])->firstOrFail();
                    abort_unless($parent->user instanceof User, 404);

                    $conversation = $access->startConversation($user, $parent->user);

                    $this->redirect(MessagesPage::getUrl([
                        'student_id' => $this->record->id,
                        'conversation_id' => $conversation->id,
                    ]));
                }),
            Action::make('viewStats')
                ->label('View Stats')
                ->icon('heroicon-m-chart-bar')
                ->url(fn (): string => static::getResource()::getUrl('stats', ['record' => $this->record])),
            ];
    }

    public function getTitle(): string
    {
        return $this->record->first_name . " " . $this->record->last_name;
    }
}
