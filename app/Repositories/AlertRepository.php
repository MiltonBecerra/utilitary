<?php

namespace App\Repositories;

use App\Models\Alert;
use App\Repositories\BaseRepository;

/**
 * Class AlertRepository
 * @package App\Repositories
 * @version December 5, 2025, 6:47 pm UTC
*/

class AlertRepository extends BaseRepository
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
        return Alert::class;
    }
}
