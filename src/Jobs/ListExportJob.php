<?php

namespace H2o\ComplexSearch\Jobs;

use App\Jobs\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Arr;

/**
 * Author: h2odumpling
 * Date: 2023/7/24 17:19
 * Description: 导出基方法
 */
class ListExportJob extends Job implements ShouldQueue
{
    public $queue = 'export';
    public $tries = 1;

    protected $singleLimit = 5000;

    protected $modelClass;
    protected $headers;
    protected $code;
    protected $ids;
    protected $fields;




    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $code, string $modelClass, array $ids, array $headers, array $fields = ['*'])
    {
        $this->code = $code;
        $this->modelClass = $modelClass;
        $this->ids = $ids;
        $this->headers = $headers;
        $this->filterFields($fields);
    }

    protected function filterFields(array $fields){
        $this->fields = array_intersect($fields, Arr::pluck($this->headers, 'value'));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        info('--------------' . $this->modelClass . '导出：开始--------------');

        $id_chunks = array_chunk($this->ids, $this->singleLimit);

        $file = fopen(storage_path('export/' . $this->generateFileName() . '.csv'), 'w');

        $first_row = Arr::pluck($this->headers, 'label', 'value');

        $this->headers = Arr::pluck($this->headers,'options','value');
        array_walk($this->headers, function (&$val){
            is_array($val) && $val = Arr::pluck($val,'label','value');
            return $val;
        });

        fwrite($file, pack('CCC', 0xef, 0xbb, 0xbf));

        fputcsv($file, $this->transRow($first_row, true));

        foreach ($id_chunks as $ids){
            $data = $this->bulider($ids);

            foreach ($this->ids as $v){
                fputcsv($file, $data[$v]);
            }
        }

        fclose($file);
        info('--------------' . $this->modelClass . '导出：结束--------------');
    }

    protected function bulider(array $ids){
        $rs = [];
        $joins = [];

        $data = new $this->modelClass;

        foreach ($this->fields as $v){
            if(strstr($v,'.')){
                $field = explode('.', $v);
                if(!isset($rs[$field[0]])){
                    $rs[$field[0]] = [];
                    $joins[] =$field[0];
                }
                $rs[$field[0]][] = $field[1];
            }else{
                !isset($rs['base']) && $rs['base'] = [];
                $rs['base'][] = $v;
            }
        }

        $relations = $this->getJoinsByModel($data, $joins);

        foreach ($relations as $r_name => $v){
            foreach ($v as $r){
                $rs['base'][] = explode('.', $r[1])[1];
                $rs[$r_name][] = explode('.', $r[0])[1];
            }
        }

        foreach ($rs as $k => $r){
            if($k == 'base'){
                $data = $data->select($r);
            }else{
                $data = $data->with([$k => function ($query) use ($r){
                    $query->select($r);
                }]);
            }
        }

        $data = $data->select($rs['base']);

        return $data->get()
            ->keyBy('id')
            ->transform(function ($v){
                return $this->transRow($v);
            })
            ->toArray();
    }

    protected function transRow($row, $is_header = false){
        $value = [];
        foreach ($this->fields as $field){
            if(!$is_header && strstr($field, '.')){
                list($relation, $name) = explode('.', $field);
                $value[] = $this->transValue($row->{$relation}[$name], $field);
            }else{
                $value[] = $this->transValue($row[$field], $field);
            }
        }
        return $value;
    }

    protected function transValue($value, $field){
        return is_array($this->headers[$field]) ?
            ($this->headers[$field][$value] ?? $value) :
            $value;
    }

    protected function generateFileName(){
        return $this->code;
    }

    private function getJoinsByModel($model, $joins)
    {
        $relations = [];
        foreach ($joins as $key => $value) {
            $fn_name = is_int($key) ? $value : $key;
            $relation = $model->$fn_name();

            $version = $this->getLaravelVersion();

            if ($relation instanceof BelongsTo) {
                if ($version < '5.6') {
                    $relations[$fn_name] = [[$relation->getQualifiedOtherKeyName(), $relation->getQualifiedForeignKey()]];
                } elseif ($version >= '5.7' && $version < '5.8') {
                    $relations[$fn_name] = [[$relation->getQualifiedOwnerKeyName(), $relation->getQualifiedForeignKey()]];
                } else {
                    $relations[$fn_name] = [[$relation->getQualifiedOwnerKeyName(), $relation->getQualifiedForeignKeyName()]];
                }

            } elseif ($relation instanceof HasOne || $relation instanceof HasMany) {

                if ($version < '5.6') {
                    $relations[$fn_name] = [[$relation->getForeignKey(), $relation->getQualifiedParentKeyName()]];
                } else {
                    $relations[$fn_name] = [[$relation->getQualifiedForeignKeyName(), $relation->getQualifiedParentKeyName()]];
                }

            } elseif ($relation instanceof BelongsToMany) {

                if ($version < '5.6') {
                    $relations[$fn_name] = [[$relation->getTable(), $relation->getForeignKey(), $relation->getQualifiedParentKeyName()],
                        [$relation->getRelated()->getQualifiedKeyName(), $relation->getOtherKey()]];
                } else {
                    $relations[$fn_name] = [[$relation->getTable(), $relation->getForeignPivotKeyName(), $relation->getQualifiedParentKeyName()],
                        [$relation->getRelated()->getQualifiedKeyName(), $relation->getQualifiedRelatedPivotKeyName()]];
                }

            } elseif ($relation instanceof HasManyThrough) {

                if ($version < '5.6') {
                    $localKey = is_int($key) ? $relation->getHasCompareKey() : $model->getTable() . '.' . $value;
                    $relations[$fn_name] = [[$relation->getParent(), $localKey, $relation->getThroughKey()],
                        [$relation->getQualifiedParentKeyName(), $relation->getForeignKey()]];
                } else {
//                    $localKey = is_int($key) ? $relation->getHasCompareKey() : $model->getTable() . '.' . $value;
                    $relations[$fn_name] = [[$relation->getParent(), $relation->getQualifiedFirstKeyName(), $relation->getQualifiedLocalKeyName()],
                        [$relation->getQualifiedForeignKeyName(), $relation->getQualifiedParentKeyName()]];
                }
            }
        }
        return $relations;
    }

    public function getLaravelVersion(){
        if (env('LARAVEL_VERSION'))
            return env('LARAVEL_VERSION');

        preg_match('/lumen\s*\((\d\.\d\.[\d|\*])\)\s*\(laravel\scomponents\s(\d\.\d)\.[\d|\*]\)/is', app()->version(), $match);
        if($match)
            return $match[2];

        return app()->version();
    }
}
