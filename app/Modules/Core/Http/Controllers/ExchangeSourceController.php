<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Requests\CreateExchangeSourceRequest;
use App\Http\Requests\UpdateExchangeSourceRequest;
use App\Repositories\ExchangeSourceRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class ExchangeSourceController extends AppBaseController
{
    /** @var ExchangeSourceRepository $exchangeSourceRepository*/
    private $exchangeSourceRepository;

    public function __construct(ExchangeSourceRepository $exchangeSourceRepo)
    {
        $this->exchangeSourceRepository = $exchangeSourceRepo;
    }

    /**
     * Display a listing of the ExchangeSource.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $exchangeSources = $this->exchangeSourceRepository->all();

        return view('exchange_sources.index')
            ->with('exchangeSources', $exchangeSources);
    }

    /**
     * Show the form for creating a new ExchangeSource.
     *
     * @return Response
     */
    public function create()
    {
        return view('exchange_sources.create');
    }

    /**
     * Store a newly created ExchangeSource in storage.
     *
     * @param CreateExchangeSourceRequest $request
     *
     * @return Response
     */
    public function store(CreateExchangeSourceRequest $request)
    {
        $input = $request->all();

        $exchangeSource = $this->exchangeSourceRepository->create($input);

        Flash::success('Exchange Source saved successfully.');

        return redirect(route('exchangeSources.index'));
    }

    /**
     * Display the specified ExchangeSource.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $exchangeSource = $this->exchangeSourceRepository->find($id);

        if (empty($exchangeSource)) {
            Flash::error('Exchange Source not found');

            return redirect(route('exchangeSources.index'));
        }

        return view('exchange_sources.show')->with('exchangeSource', $exchangeSource);
    }

    /**
     * Show the form for editing the specified ExchangeSource.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $exchangeSource = $this->exchangeSourceRepository->find($id);

        if (empty($exchangeSource)) {
            Flash::error('Exchange Source not found');

            return redirect(route('exchangeSources.index'));
        }

        return view('exchange_sources.edit')->with('exchangeSource', $exchangeSource);
    }

    /**
     * Update the specified ExchangeSource in storage.
     *
     * @param int $id
     * @param UpdateExchangeSourceRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateExchangeSourceRequest $request)
    {
        $exchangeSource = $this->exchangeSourceRepository->find($id);

        if (empty($exchangeSource)) {
            Flash::error('Exchange Source not found');

            return redirect(route('exchangeSources.index'));
        }

        $exchangeSource = $this->exchangeSourceRepository->update($request->all(), $id);

        Flash::success('Exchange Source updated successfully.');

        return redirect(route('exchangeSources.index'));
    }

    /**
     * Remove the specified ExchangeSource from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $exchangeSource = $this->exchangeSourceRepository->find($id);

        if (empty($exchangeSource)) {
            Flash::error('Exchange Source not found');

            return redirect(route('exchangeSources.index'));
        }

        $this->exchangeSourceRepository->delete($id);

        Flash::success('Exchange Source deleted successfully.');

        return redirect(route('exchangeSources.index'));
    }
}

