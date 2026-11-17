<?php

namespace App\Models\Internal;

use Illuminate\Database\Eloquent\Model;

class OrgNode extends Model
{
    protected $table = 'internal_org_structure';

    protected $fillable = ['name', 'parent_id', 'user_id', 'sort'];
}
