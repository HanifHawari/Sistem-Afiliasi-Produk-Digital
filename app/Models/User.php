<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Nilai default, sama dengan default kolom di migration.
     */
    protected $attributes = [
        'role' => 'customer',
        'is_active' => true,
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
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isAffiliate(): bool
    {
        return $this->role === UserRole::Affiliate;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    /**
     * Akun yang sudah punya riwayat transaksi tidak boleh dihapus (data keuangan harus tetap utuh),
     * dan admin tidak boleh menghapus akunnya sendiri.
     */
    public function canDeleteAccount(): bool
    {
        return ! $this->isAdmin()
            && ! $this->orders()->exists()
            && ! $this->commissions()->exists()
            && ! $this->withdrawals()->exists();
    }

    /**
     * Area layout sesuai role: admin | affiliate | shop
     */
    public function layoutArea(): string
    {
        return match ($this->role) {
            UserRole::Admin => 'admin',
            UserRole::Affiliate => 'affiliate',
            UserRole::Customer => 'shop',
        };
    }

    /**
     * Halaman utama sesuai role, dipakai setelah login.
     */
    public function homeRoute(): string
    {
        return match ($this->role) {
            UserRole::Admin => route('admin.dashboard'),
            UserRole::Affiliate => route('affiliate.dashboard'),
            UserRole::Customer => route('shop.orders.index'),
        };
    }

    // --- Afiliator ---

    public function affiliateProfile(): HasOne
    {
        return $this->hasOne(AffiliateProfile::class);
    }

    public function referralLinks(): HasMany
    {
        return $this->hasMany(ReferralLink::class, 'affiliate_id');
    }

    /**
     * Semua klik dari link referral milik afiliator ini.
     */
    public function referralClicks(): HasManyThrough
    {
        return $this->hasManyThrough(ReferralClick::class, ReferralLink::class, 'affiliate_id');
    }

    /**
     * Semua order yang datang dari link referral afiliator ini.
     */
    public function referralOrders(): HasManyThrough
    {
        return $this->hasManyThrough(Order::class, ReferralLink::class, 'affiliate_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'affiliate_id');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class, 'affiliate_id');
    }

    // --- Customer ---

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }
}
