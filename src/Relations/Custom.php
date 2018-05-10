<?php

namespace LaravelCustomRelation\Relations;

use Closure;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Custom extends Relation
{
    /**
     * Undocumented variable
     *
     * @var string
     */
    protected $accessor = 'relation_key';
    protected $modelKeys;
    protected $resultKeys;
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

     protected $resultIsPlural;

    public function __construct(Builder $query, Model $parent, Closure $baseConstraints, Closure $eagerConstraints, $modelKeys, $resultKeys, $resultIsPlural = true)
    {
        $this->baseConstraints = $baseConstraints;
        $this->eagerConstraints = $eagerConstraints;

        $this->modelKeys = is_array($modelKeys) ? $modelKeys : [$modelKeys];
        $this->resultKeys = is_array($resultKeys) ? $resultKeys : [$resultKeys];

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

    public function getKeys(array $models, $key = null)
    {
        return parent::getKeys($models, $key);
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
        $dictionary = $this->buildDictionary($results);

        // Once we have an array dictionary of child objects we can easily match the
        // children back to their parent using the dictionary and the keys on the
        // the parent models. Then we will return the hydrated models back out.
        foreach ($models as $model) {
            $key = $this->getModelRelationshipKey($model);
            
            if (isset($dictionary[$key])) {
               
                $collection = $this->related->newCollection($dictionary[$key]);

                if( !$this->resultIsPlural ) {
                    $collection = $collection->first();
                }
                $model->setRelation($relation, $collection);
            }
        }
        // dd2($models);
        return $models;
    }
    
    protected function getModelRelationshipKey($model)
    {
        $outputKey = '';
        foreach ($this->modelKeys as $key) {
            $outputKey .=$model->$key.',';
        }
        return substr($outputKey, 0, -1);
    }

    protected function buildDictionary(Collection $results)
    {
        // First we will build a dictionary of child models keyed by the foreign key
        // of the relation so that we will easily and quickly match them to their
        // parents without having a possibly slow inner loops for every models.
        $dictionary = [];

        foreach ($results as $result) {
            $dictionary[$result->{$this->accessor}][] = $result;
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

        $models = $builder->addSelect($columns);
        $models = $builder->addSelect($this->aliasedPivotColumns())->getModels();

        // If we actually found models we will also eager load any relationships that
        // have been specified as needing to be eager loaded. This will solve the
        // n + 1 query problem for the developer and also increase performance.
        if (count($models) > 0) {
            $models = $builder->eagerLoadRelations($models);
        }

        return $this->related->newCollection($models);
    }

    

    protected function aliasedPivotColumns()
    {
        $resultKeys =implode(',', $this->resultKeys);
        return DB::raw("CONCAT_WS (',',$resultKeys) as {$this->accessor}");
        // return ["CONCAT ($resultKeys) as {$this->accessor}"];
    }
}
