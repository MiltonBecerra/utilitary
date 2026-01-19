<?php

namespace App\Repositories;

use App\Models\Utility;
use App\Repositories\BaseRepository;

/**
 * Class UtilityRepository
 * @package App\Repositories
 * @version December 5, 2025, 6:45 pm UTC
*/

class UtilityRepository extends BaseRepository
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
        return Utility::class;
    }
}
