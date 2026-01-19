<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Requests\CreateUtilityRequest;
use App\Http\Requests\UpdateUtilityRequest;
use App\Repositories\UtilityRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class UtilityController extends AppBaseController
{
    /** @var UtilityRepository $utilityRepository*/
    private $utilityRepository;

    public function __construct(UtilityRepository $utilityRepo)
    {
        $this->utilityRepository = $utilityRepo;
    }

    /**
     * Display a listing of the Utility.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $utilities = $this->utilityRepository->all();

        return view('utilities.index')
            ->with('utilities', $utilities);
    }

    /**
     * Show the form for creating a new Utility.
     *
     * @return Response
     */
    public function create()
    {
        return view('utilities.create');
    }

    /**
     * Store a newly created Utility in storage.
     *
     * @param CreateUtilityRequest $request
     *
     * @return Response
     */
    public function store(CreateUtilityRequest $request)
    {
        $input = $request->all();

        $utility = $this->utilityRepository->create($input);

        Flash::success('Utility saved successfully.');

        return redirect(route('utilities.index'));
    }

    /**
     * Display the specified Utility.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $utility = $this->utilityRepository->find($id);

        if (empty($utility)) {
            Flash::error('Utility not found');

            return redirect(route('utilities.index'));
        }

        return view('utilities.show')->with('utility', $utility);
    }

    /**
     * Show the form for editing the specified Utility.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $utility = $this->utilityRepository->find($id);

        if (empty($utility)) {
            Flash::error('Utility not found');

            return redirect(route('utilities.index'));
        }

        return view('utilities.edit')->with('utility', $utility);
    }

    /**
     * Update the specified Utility in storage.
     *
     * @param int $id
     * @param UpdateUtilityRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateUtilityRequest $request)
    {
        $utility = $this->utilityRepository->find($id);

        if (empty($utility)) {
            Flash::error('Utility not found');

            return redirect(route('utilities.index'));
        }

        $utility = $this->utilityRepository->update($request->all(), $id);

        Flash::success('Utility updated successfully.');

        return redirect(route('utilities.index'));
    }

    /**
     * Remove the specified Utility from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $utility = $this->utilityRepository->find($id);

        if (empty($utility)) {
            Flash::error('Utility not found');

            return redirect(route('utilities.index'));
        }

        $this->utilityRepository->delete($id);

        Flash::success('Utility deleted successfully.');

        return redirect(route('utilities.index'));
    }
}

