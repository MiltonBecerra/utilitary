<?php

namespace App\Modules\Core\Http\Controllers;

use App\Http\Requests\CreateExchangeRateRequest;
use App\Http\Requests\UpdateExchangeRateRequest;
use App\Repositories\ExchangeRateRepository;
use App\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Flash;
use Response;

class ExchangeRateController extends AppBaseController
{
    /** @var ExchangeRateRepository $exchangeRateRepository*/
    private $exchangeRateRepository;

    public function __construct(ExchangeRateRepository $exchangeRateRepo)
    {
        $this->exchangeRateRepository = $exchangeRateRepo;
    }

    /**
     * Display a listing of the ExchangeRate.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $exchangeRates = $this->exchangeRateRepository->all();

        return view('exchange_rates.index')
            ->with('exchangeRates', $exchangeRates);
    }

    /**
     * Show the form for creating a new ExchangeRate.
     *
     * @return Response
     */
    public function create()
    {
        return view('exchange_rates.create');
    }

    /**
     * Store a newly created ExchangeRate in storage.
     *
     * @param CreateExchangeRateRequest $request
     *
     * @return Response
     */
    public function store(CreateExchangeRateRequest $request)
    {
        $input = $request->all();

        $exchangeRate = $this->exchangeRateRepository->create($input);

        Flash::success('Exchange Rate saved successfully.');

        return redirect(route('exchangeRates.index'));
    }

    /**
     * Display the specified ExchangeRate.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $exchangeRate = $this->exchangeRateRepository->find($id);

        if (empty($exchangeRate)) {
            Flash::error('Exchange Rate not found');

            return redirect(route('exchangeRates.index'));
        }

        return view('exchange_rates.show')->with('exchangeRate', $exchangeRate);
    }

    /**
     * Show the form for editing the specified ExchangeRate.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $exchangeRate = $this->exchangeRateRepository->find($id);

        if (empty($exchangeRate)) {
            Flash::error('Exchange Rate not found');

            return redirect(route('exchangeRates.index'));
        }

        return view('exchange_rates.edit')->with('exchangeRate', $exchangeRate);
    }

    /**
     * Update the specified ExchangeRate in storage.
     *
     * @param int $id
     * @param UpdateExchangeRateRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateExchangeRateRequest $request)
    {
        $exchangeRate = $this->exchangeRateRepository->find($id);

        if (empty($exchangeRate)) {
            Flash::error('Exchange Rate not found');

            return redirect(route('exchangeRates.index'));
        }

        $exchangeRate = $this->exchangeRateRepository->update($request->all(), $id);

        Flash::success('Exchange Rate updated successfully.');

        return redirect(route('exchangeRates.index'));
    }

    /**
     * Remove the specified ExchangeRate from storage.
     *
     * @param int $id
     *
     * @throws \Exception
     *
     * @return Response
     */
    public function destroy($id)
    {
        $exchangeRate = $this->exchangeRateRepository->find($id);

        if (empty($exchangeRate)) {
            Flash::error('Exchange Rate not found');

            return redirect(route('exchangeRates.index'));
        }

        $this->exchangeRateRepository->delete($id);

        Flash::success('Exchange Rate deleted successfully.');

        return redirect(route('exchangeRates.index'));
    }
}

