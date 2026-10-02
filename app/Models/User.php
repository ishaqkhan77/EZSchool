<?php
namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Wirechat\Wirechat\Traits\InteractsWithWirechat;;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;
use Wirechat\Wirechat\Contracts\WirechatUser;
use Wirechat\Wirechat\Enums\ConversationType;
use Wirechat\Wirechat\Models\Conversation;
use Wirechat\Wirechat\Panel as WirechatPanel;
use App\Services\ChatAccessService;

class User extends Authenticatable implements FilamentUser, HasTenants, HasDefaultTenant, WirechatUser
{
    use HasRoles;
    use InteractsWithWirechat {
        createConversationWith as private createWirechatConversationWith;
        belongsToConversation as private belongsToWirechatConversation;
    }
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canCreateGroups(): bool
    {
        return false;
    }

    public function canCreateChats(): bool
    {
        return app(ChatAccessService::class)->canAccessChat($this);
    }

    public function canAccessWirechatPanel(WirechatPanel $panel): bool
    {
        return $this->canCreateChats();
    }

    public function createConversationWith(\Illuminate\Database\Eloquent\Model $peer, ?string $message = null): ?Conversation
    {
        abort_unless(app(ChatAccessService::class)->canStartConversation($this, $peer), 403);

        return $this->createWirechatConversationWith($peer, $message);
    }

    public function belongsToConversation(Conversation $conversation, bool $withoutGlobalScopes = false): bool
    {
        if (!$this->belongsToWirechatConversation($conversation, $withoutGlobalScopes)) {
            return false;
        }

        if ($conversation->type === ConversationType::SELF) {
            return true;
        }

        if ($conversation->type !== ConversationType::PRIVATE) {
            return false;
        }

        $peer = $conversation->participants()
            ->where('participantable_id', '!=', $this->getKey())
            ->where('participantable_type', $this->getMorphClass())
            ->first()?->participantable;

        return $peer instanceof self
            && app(ChatAccessService::class)->canStartConversation($this, $peer);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'developer') {
            return $this->email === 'developer@dev.com';
        }

        if ($panel->getId() === 'admin') {
            /** @var ChatAccessService $access */
            $access = app(ChatAccessService::class);

            return $access->canAccessAdminPanel($this);
        }

        if ($panel->getId() === 'parent') {
            return $this->hasRole('Parent');
        }

        return false;
    }

    public function branches()
    {
        return $this->belongsToMany(Branch::class);
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->branches()->get();
    }

    public function getPermissionTeamId(): ?string
    {
        return filament()->getTenant()?->id ?? null;
    }

    public function roles(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        $relationship = $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            'role_id'
        );

        if (filament()->hasTenancy() && filament()->getTenant()) {
            return $relationship->wherePivot('branch_id', filament()->getTenant()->id);
        }

        return $relationship;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->branches->contains($tenant);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        return $this->branches()->first();
    }

    public function parentProfile(): HasOne
    {
        return $this->hasOne(ParentModel::class);
    }
}
