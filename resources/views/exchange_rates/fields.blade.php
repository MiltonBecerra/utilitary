<!-- Exchange Source Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('exchange_source_id', 'Exchange Source Id:') !!}
    {!! Form::select('exchange_source_id', ], null, ['class' => 'form-control custom-select']) !!}
</div>


<!-- Buy Price Field -->
<div class="form-group col-sm-6">
    {!! Form::label('buy_price', 'Buy Price:') !!}
    {!! Form::number('buy_price', null, ['class' => 'form-control', 'step' => '0.001']) !!}
</div>

<!-- Sell Price Field -->
<div class="form-group col-sm-6">
    {!! Form::label('sell_price', 'Sell Price:') !!}
    {!! Form::number('sell_price', null, ['class' => 'form-control', 'step' => '0.001']) !!}
</div>

<!-- Currency From Field -->
<div class="form-group col-sm-6">
    {!! Form::label('currency_from', 'Currency From:') !!}
    {!! Form::text('currency_from', null, ['class' => 'form-control']) !!}
</div>

<!-- Currency To Field -->
<div class="form-group col-sm-6">
    {!! Form::label('currency_to', 'Currency To:') !!}
    {!! Form::text('currency_to', null, ['class' => 'form-control']) !!}
</div>
