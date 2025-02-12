<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Produit extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'image_produit',
        'nom_produit',
        'description_produit',
        'prix',
        'reference',
        'categorie_id'
    ];
    
    public function categorie()
    {
        return $this->belongsTo(Categorie::class);
    }

    public function image_url()
    {
        return Storage::url($this->image_produit);
    }
}
