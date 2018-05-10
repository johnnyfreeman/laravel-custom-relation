<?php

namespace LaravelCustomRelation;

use LaravelCustomRelation\Relations\Custom;
use Closure;

trait HasCustomRelations
{
    /**
     * Define a custom relationship.
     *
     * @param  string  $related
     * @param  string  $baseConstraints
     * @param  string  $eagerConstraints
     * @return \App\Services\Database\Relations\Custom
     */
    public function custom($related, Closure $baseConstraints, Closure $eagerConstraints, $modelKeys, $resultKeys, $resultIsPlural = true)
    {
        $instance = new $related;
        $query = $instance->newQuery();

        return new Custom($query, $this, $baseConstraints, $eagerConstraints, $modelKeys, $resultKeys, $resultIsPlural);
    }
}
