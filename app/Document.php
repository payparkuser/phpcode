<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{	
    /**
     * Get the Provider Document record associated with Document.
     */
    public function providerDocuments() {
        
        return $this->hasMany(ProviderDocument::class, 'document_id');
    }

    /**
     * Scope a query to common response.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCommonResponse($query) {

        return $query->select(
            'documents.id as document_id',
            'documents.name as document_name',
            'documents.picture as preview',
            'documents.description as document_description'
            );
    
    }


    public static function boot() {

        parent::boot();

        static::deleting(function($model) {

            $model->providerDocuments()->delete();

        });

    }
}
