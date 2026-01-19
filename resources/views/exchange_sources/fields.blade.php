<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control']) !!}
</div>

<!-- Url Field -->
<div class="form-group col-sm-6">
    {!! Form::label('url', 'Url:') !!}
    {!! Form::text('url', null, ['class' => 'form-control']) !!}
</div>

<!-- Selector Buy Field -->
<div class="form-group col-sm-6">
    {!! Form::label('selector_buy', 'Selector Buy:') !!}
    {!! Form::text('selector_buy', null, ['class' => 'form-control']) !!}
</div>

<!-- Selector Sell Field -->
<div class="form-group col-sm-6">
    {!! Form::label('selector_sell', 'Selector Sell:') !!}
    {!! Form::text('selector_sell', null, ['class' => 'form-control']) !!}
</div>

<!-- Is Active Field -->
<div class="form-group col-sm-6">
    <div class="form-check">
        {!! Form::hidden('is_active', 0, ['class' => 'form-check-input']) !!}
        {!! Form::checkbox('is_active', '1', null, ['class' => 'form-check-input']) !!}
        {!! Form::label('is_active', 'Is Active', ['class' => 'form-check-label']) !!}
    </div>
</div>
