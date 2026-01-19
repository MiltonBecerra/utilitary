<!-- Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $exchangeSource->name }}</p>
</div>

<!-- Url Field -->
<div class="col-sm-12">
    {!! Form::label('url', 'Url:') !!}
    <p>{{ $exchangeSource->url }}</p>
</div>

<!-- Selector Buy Field -->
<div class="col-sm-12">
    {!! Form::label('selector_buy', 'Selector Buy:') !!}
    <p>{{ $exchangeSource->selector_buy }}</p>
</div>

<!-- Selector Sell Field -->
<div class="col-sm-12">
    {!! Form::label('selector_sell', 'Selector Sell:') !!}
    <p>{{ $exchangeSource->selector_sell }}</p>
</div>

<!-- Is Active Field -->
<div class="col-sm-12">
    {!! Form::label('is_active', 'Is Active:') !!}
    <p>{{ $exchangeSource->is_active }}</p>
</div>

