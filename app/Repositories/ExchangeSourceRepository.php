<?php

namespace App\Repositories;

use App\Models\ExchangeSource;
use App\Repositories\BaseRepository;

/**
 * Class ExchangeSourceRepository
 * @package App\Repositories
 * @version December 5, 2025, 6:45 pm UTC
*/

class ExchangeSourceRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        
    ];

    /**
     * Return searchable fields
     *
     * @return array
     */
    public function getFieldsSearchable()
    {
        return $this->fieldSearchable;
    }

    /**
     * Configure the Model
     **/
    public function model()
    {
        return ExchangeSource::class;
    }
}
