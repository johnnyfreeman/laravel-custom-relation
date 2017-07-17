<?php

namespace LaravelCustomRelation\Relations;

use Closure;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Custom extends Relation
{
    /**
    * The baseConstraints callback
    *
    * @var \Illuminate\Database\Eloquent\Model
    */
    protected $baseConstraints;

    /**
    * The eagerConstraints callback
    *
    * @var \Illuminate\Database\Eloquent\Model
    */
    protected $eagerConstraints;

    /**
    * Create a new belongs to relationship instance.
    *
    * @param  \Illuminate\Database\Eloquent\Builder  $query
    * @param  \Illuminate\Database\Eloquent\Model  $parent
    * @param  string  $baseConstraints
    * @return void
    */
    public function __construct(Builder $query, Model $parent, Closure $baseConstraints, Closure $eagerConstraints, array $eagerParentRelations = null, string $localKey = null)
    {
        $this->baseConstraints = $baseConstraints;
        $this->eagerConstraints = $eagerConstraints;

        if($eagerParentRelations != null){
            $parent->setEagerLoads($eagerParentRelations);
        }

        $this->localKey = $localKey;

        parent::__construct($query, $parent);
    }

    /**
    * Set the base constraints on the relation query.
    *
    * @return void
    */
    public function addConstraints()
    {
        if (static::$constraints) {
            call_user_func($this->baseConstraints, $this);
        }
    }

    /**
    * Set the constraints for an eager load of the relation.
    *
    * @param  array  $models
    * @return void
    */
    public function addEagerConstraints(array $models)
    {
        call_user_func($this->eagerConstraints, $this, $models);
    }

    /**
    * Initialize the relation on a set of models.
    *
    * @param  array   $models
    * @param  string  $relation
    * @return array
    */
    public function initRelation(array $models, $relation)
    {
        foreach ($models as $model) {
            $model->setRelation($relation, $this->related->newCollection());
        }

        return $models;
    }

    /**
    * Match the eagerly loaded results to their parents.
    *
    * @param  array   $models
    * @param  \Illuminate\Database\Eloquent\Collection  $results
    * @param  string  $relation
    * @return array
    */
    public function match(array $models, Collection $results, $relation)
    {
        //dd($models, $relation);
        $dictionary = $this->buildDictionary($results, $models);
        // Once we have an array dictionary of child objects we can easily match the
        // children back to their parent using the dictionary and the keys on the
        // the parent models. Then we will return the hydrated models back out.
        foreach ($models as $model) {
            if (isset($dictionary[$key = $model->getKey()])) {
                $collection = $this->related->newCollection($dictionary[$key]);
                $model->setRelation($relation, $collection);
            }
        }

        return $models;
    }

    /**
    * Build model dictionary keyed by the relation's foreign key.
    *
    * @param  \Illuminate\Database\Eloquent\Collection  $results
    * @return array
    */
    protected function buildDictionary(Collection $results, $models)
    {
        //dd($results);
        // First we will build a dictionary of child models keyed by the foreign key
        // of the relation so that we will easily and quickly match them to their
        // parents without having a possibly slow inner loops for every models.
        $dictionary = [];
        foreach($models as $model){
            $dictionary[$model->getKey()] = $results->where($this->localKey ?: $model->getKeyName(), $model->getKey())->all();
        }
        return $dictionary;
    }

    /**
    * Get the results of the relationship.
    *
    * @return mixed
    */
    public function getResults()
    {
        return $this->get();
    }

    /**
    * Execute the query as a "select" statement.
    *
    * @param  array  $columns
    * @return \Illuminate\Database\Eloquent\Collection
    */
    public function get($columns = ['*'])
    {
        // First we'll add the proper select columns onto the query so it is run with
        // the proper columns. Then, we will get the results and hydrate out pivot
        // models with the result of those columns as a separate model relation.
        $columns = $this->query->getQuery()->columns ? [] : $columns;

        if ($columns == ['*']) {
            $columns = [$this->related->getTable().'.*'];
        }

        $builder = $this->query->applyScopes();

        $models = $builder->addSelect($columns)->getModels();

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded. This will solve the
        // n + 1 query problem for the developer and also increase performance.
        if (count($models) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return $this->related->newCollection($models);
    }
}
