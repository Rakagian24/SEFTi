<?php

namespace App\Models\Concerns;

trait HasActiveStatus
{
    /**
     * Local scope to filter only records with status 'active'.
     */
    public function scopeActive($query)
    {
        return $query->where($this->getTable() . '.status', 'active');
    }
}


