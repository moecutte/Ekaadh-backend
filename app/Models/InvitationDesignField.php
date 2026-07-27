<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationDesignField extends Model
{
    protected $fillable = [
        'invitation_design_id',
        'field_key',
        'label',
        'field_type',
        'is_required',
        'placeholder',
        'default_text',
        'maps_to_couple',
        'show_on_card',
        'pos_x',
        'pos_y',
        'box_width',
        'font_size',
        'font_family',
        'font_weight',
        'font_style',
        'color',
        'text_align',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'maps_to_couple' => 'boolean',
            'show_on_card' => 'boolean',
            'pos_x' => 'float',
            'pos_y' => 'float',
            'box_width' => 'float',
            'font_size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(InvitationDesign::class, 'invitation_design_id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toCatalogArray(): array
    {
        return [
            'id' => $this->id,
            'field_key' => $this->field_key,
            'label' => $this->label,
            'field_type' => $this->field_type,
            'is_required' => (bool) $this->is_required,
            'placeholder' => $this->placeholder,
            'default_text' => $this->default_text,
            'maps_to_couple' => (bool) $this->maps_to_couple,
            'show_on_card' => (bool) $this->show_on_card,
            'pos_x' => $this->pos_x,
            'pos_y' => $this->pos_y,
            'box_width' => $this->box_width,
            'font_size' => $this->font_size,
            'font_family' => $this->font_family,
            'font_weight' => $this->font_weight,
            'font_style' => $this->font_style,
            'color' => $this->color,
            'text_align' => $this->text_align,
            'sort_order' => $this->sort_order,
        ];
    }
}
