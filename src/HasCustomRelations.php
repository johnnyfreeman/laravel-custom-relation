<?php

namespace LaravelCustomRelation;

use LaravelCustomRelation\Relations\Custom;
use Closure;

trait HasCustomRelations
{

  /**
   * Create a new model instance for a related model.
   *
   * @param  string  $class
   * @return mixed
   */
  protected function newRelatedInstance($class)
  {
      return tap(new $class, function ($instance) {
          if (! $instance->getConnectionName()) {
              $instance->setConnection($this->connection);
          }
      });
  }
    /**
     * Define a custom relationship.
     *
     * @param  string  $related
     * @param  string  $baseConstraints
     * @param  string  $eagerConstraints
     * @return \App\Services\Database\Relations\Custom
     */
    public function custom($related, Closure $baseConstraints, Closure $eagerConstraints,  array $eagerParentRelations = null, string $localKey = null)
    {
        $instance = $this->newRelatedInstance($related);
        $query = $instance->newQuery();

        return new Custom($query, $this, $baseConstraints, $eagerConstraints, $eagerParentRelations, $localKey);
    }


}
