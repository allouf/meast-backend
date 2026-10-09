<?php
namespace Modules\Store\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class StoreCategory extends Model
{
    use HasFactory;
    protected $fillable = [
        'store_id',
        'category_id',
    ];
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
