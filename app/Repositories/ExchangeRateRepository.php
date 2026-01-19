<?php

namespace App\Repositories;

use App\Models\ExchangeRate;
use App\Repositories\BaseRepository;

/**
 * Class ExchangeRateRepository
 * @package App\Repositories
 * @version December 5, 2025, 6:46 pm UTC
*/

class ExchangeRateRepository extends BaseRepository
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
        return ExchangeRate::class;
    }
}
