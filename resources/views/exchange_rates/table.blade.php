<div class="table-responsive">
    <table class="table" id="exchangeRates-table">
        <thead>
        <tr>
            <th>Exchange Source Id</th>
        <th>Buy Price</th>
        <th>Sell Price</th>
        <th>Currency From</th>
        <th>Currency To</th>
            <th colspan="3">Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($exchangeRates as $exchangeRate)
            <tr>
                <td>{{ $exchangeRate->exchange_source_id }}</td>
            <td>{{ number_format($exchangeRate->buy_price, 3) }}</td>
            <td>{{ number_format($exchangeRate->sell_price, 3) }}</td>
            <td>{{ $exchangeRate->currency_from }}</td>
            <td>{{ $exchangeRate->currency_to }}</td>
                <td width="120">
                    {!! Form::open(['route' => ['exchangeRates.destroy', $exchangeRate->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('exchangeRates.show', [$exchangeRate->id]) }}"
                           class='btn btn-default btn-xs'>
                            <i class="far fa-eye"></i>
                        </a>
                        <a href="{{ route('exchangeRates.edit', [$exchangeRate->id]) }}"
                           class='btn btn-default btn-xs'>
                            <i class="far fa-edit"></i>
                        </a>
                        {!! Form::button('<i class="far fa-trash-alt"></i>', ['type' => 'submit', 'class' => 'btn btn-danger btn-xs', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
