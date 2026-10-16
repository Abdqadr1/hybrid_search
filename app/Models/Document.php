<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'content'])]
#[Hidden(['embedding', 'search_vector'])]
class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;
}
