<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Traits\HasImages;

class Event extends Model
{
    use HasFactory, HasImages;

    protected $fillable = [
        'organizer_id',
        'category_id',
        'venue_id',
        'title',
        'slug',
        'description',
        'image_url',
        'image_file',
        'status',
        'use_variable_pricing',
        'commission_percentage',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'use_variable_pricing' => 'boolean',
        'commission_percentage' => 'decimal:2',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'image'
    ];

    /**
     * Get the organizer that owns the event.
     */
    public function organizer(): BelongsTo
    {
        return $this->belongsTo(Organizer::class, 'organizer_id');
    }

    /**
     * Get the category that owns the event.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }

    /**
     * Get the venue that owns the event.
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'venue_id');
    }

    /**
     * Get the schedules for the event.
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(EventSchedule::class, 'event_id');
    }

    /**
     * Get the ticket types for the event.
     */
    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class, 'event_id');
    }

    /**
     * Get all tickets for the event.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'event_id');
    }

    /**
     * Get the user who created the event.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the event.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the recurrence rule for this event.
     */
    public function recurrenceRule(): HasMany
    {
        return $this->hasMany(EventRecurrenceRule::class, 'event_id');
    }

    /**
     * Check if this event has recurrence rules.
     */
    public function isRecurring(): bool
    {
        return $this->recurrenceRule()->exists();
    }

    /**
     * Scope a query to only include published events.
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                     ->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    /**
     * Scope a query to only include active events.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'published')
                     ->whereHas('schedules', function ($q) {
                         $q->where('ends_at', '>=', now());
                     });
    }

    /**
     * Scope: événements en attente de validation admin.
     */
    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', 'pending');
    }

    /**
     * Taux de commission effectif (%) appliqué à cet événement.
     *
     * Override par événement si défini, sinon défaut de l'organisateur,
     * sinon 10% de sécurité. Modèle DÉDUIT : ce % est retenu sur le prix
     * de base, l'organisateur reçoit le reste.
     */
    public function effectiveCommission(): float
    {
        if ($this->commission_percentage !== null) {
            return (float) $this->commission_percentage;
        }

        $organizerDefault = $this->organizer?->default_commission_percentage;

        return (float) ($organizerDefault ?? 10.00);
    }

    /**
     * L'événement est-il approuvé par l'admin (condition de vente) ?
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * L'événement peut-il vendre des billets ?
     * (publié ET approuvé par l'admin)
     */
    public function canSellTickets(): bool
    {
        return $this->status === 'published' && $this->isApproved();
    }

    /**
     * Get URL attribute.
     */
    public function getUrlAttribute()
    {
        return config('app.url') . '/events/' . $this->slug;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title);
                
                // Ensure uniqueness
                $originalSlug = $event->slug;
                $counter = 1;
                while (static::where('slug', $event->slug)->exists()) {
                    $event->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });

        static::updating(function ($event) {
            if ($event->isDirty('title') && empty($event->slug)) {
                $event->slug = Str::slug($event->title);
                
                // Ensure uniqueness
                $originalSlug = $event->slug;
                $counter = 1;
                while (static::where('slug', $event->slug)->where('id', '!=', $event->id)->exists()) {
                    $event->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Méthodes pour le trait HasImages
     */
    protected function getImageType(): string
    {
        return 'events';
    }

    protected function getImageUrlAttribute(): ?string
    {
        return $this->attributes['image_url'] ?? null;
    }

    protected function getImageFileAttribute(): ?string
    {
        return $this->attributes['image_file'] ?? null;
    }

    protected function setImageUrl(?string $url): void
    {
        $this->attributes['image_url'] = $url;
    }

    protected function setImageFile(?string $filename): void
    {
        $this->attributes['image_file'] = $filename;
    }

    /**
     * Accesseur pour l'URL de l'image à retourner dans l'API
     */
    public function getImageAttribute(): ?string
    {
        // Si on a une URL externe, on la retourne telle quelle
        if ($this->image_url && filter_var($this->image_url, FILTER_VALIDATE_URL)) {
            return $this->image_url;
        }
        
        // Si on a un fichier local, on construit l'URL complète
        if ($this->image_file) {
            return asset('storage/images/events/' . $this->image_file);
        }
        
        // Si on a une URL mais pas valide (chemin relatif par exemple)
        if ($this->image_url) {
            return $this->image_url;
        }
        
        return null;
    }
}
