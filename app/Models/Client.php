<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'user_id',
        'address',
        'plan_id',
        'shipping_content_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function shippingContent()
    {
        return $this->belongsTo(Content::class, 'shipping_content_id');
    }
}
