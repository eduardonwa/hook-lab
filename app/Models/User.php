<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Cycle;
use App\Models\Hook;
use App\Models\QuickTriggerState;
use App\Models\TriggerGroup;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    public function customHooks(): HasMany
    {
        return $this->hasMany(Hook::class);
    }

    public function triggerGroups(): HasMany
    {
        return $this->hasMany(TriggerGroup::class);
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(Cycle::class);
    }

    public function quickTriggerState(): HasOne
    {
        return $this->hasOne(QuickTriggerState::class);
    }

    public function canAccessPanel(Panel $panel):bool
    {
        return true;
    }

    public function isPro(): bool
    {
        if (app()->environment('local') && config('services.stripe.force_pro_users')) {
            return true;
        }

        if (! config('services.stripe.billing_enabled')) {
            return false;
        }
        
        return $this->subscribed('default');
    }

    public function planName(): string
    {
        return $this->isPro() ? 'pro' : 'free';
    }

    public function limits(): array
    {
        return config('plans.' . $this->planName(), []);
    }
}