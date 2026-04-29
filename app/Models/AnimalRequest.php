<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'helper_id', 'title', 'body', 'status'])]
class AnimalRequest extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function helper()
    {
        return $this->belongsTo(User::class, 'helper_id');
    }
}
