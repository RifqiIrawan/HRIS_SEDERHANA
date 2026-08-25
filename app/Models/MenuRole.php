<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * The role → menu grant, with the verbs it covers.
 *
 * A pivot class rather than a bare withPivot() so `actions` arrives as an array
 * everywhere it is read. The access check runs on every request; a JSON string
 * that only some call sites remembered to decode would fail open.
 */
class MenuRole extends Pivot
{
    protected $table = 'menu_role';

    public $incrementing = true;

    protected $casts = [
        'actions' => 'array',
    ];
}
