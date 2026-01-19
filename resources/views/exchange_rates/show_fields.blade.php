<!-- Exchange Source Id Field -->
<div class="col-sm-12">
    {!! Form::label('exchange_source_id', 'Exchange Source Id:') !!}
    <p>{{ $exchangeRate->exchange_source_id }}</p>
</div>

<!-- Buy Price Field -->
<div class="col-sm-12">
    {!! Form::label('buy_price', 'Buy Price:') !!}
<p>{{ number_format($exchangeRate->buy_price, 3) }}</p>
</div>

<!-- Sell Price Field -->
<div class="col-sm-12">
    {!! Form::label('sell_price', 'Sell Price:') !!}
<p>{{ number_format($exchangeRate->sell_price, 3) }}</p>
</div>

<!-- Currency From Field -->
<div class="col-sm-12">
    {!! Form::label('currency_from', 'Currency From:') !!}
    <p>{{ $exchangeRate->currency_from }}</p>
</div>

<!-- Currency To Field -->
<div class="col-sm-12">
    {!! Form::label('currency_to', 'Currency To:') !!}
    <p>{{ $exchangeRate->currency_to }}</p>
</div>
