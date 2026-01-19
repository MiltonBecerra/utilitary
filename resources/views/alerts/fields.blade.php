<!-- User Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('user_id', 'User Id:') !!}
    {!! Form::select('user_id', ], null, ['class' => 'form-control custom-select']) !!}
</div>


<!-- Guest Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('guest_id', 'Guest Id:') !!}
    {!! Form::text('guest_id', null, ['class' => 'form-control']) !!}
</div>

<!-- Exchange Source Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('exchange_source_id', 'Exchange Source Id:') !!}
    {!! Form::select('exchange_source_id', ], null, ['class' => 'form-control custom-select']) !!}
</div>


<!-- Target Price Field -->
<div class="form-group col-sm-6">
    {!! Form::label('target_price', 'Target Price:') !!}
    {!! Form::number('target_price', null, ['class' => 'form-control']) !!}
</div>

<!-- Condition Field -->
<div class="form-group col-sm-6">
    {!! Form::label('condition', 'Condition:') !!}
    {!! Form::select('condition', ], null, ['class' => 'form-control custom-select']) !!}
</div>


<!-- Channel Field -->
<div class="form-group col-sm-6">
    {!! Form::label('channel', 'Channel:') !!}
    {!! Form::select('channel', ], null, ['class' => 'form-control custom-select']) !!}
</div>


<!-- Contact Detail Field -->
<div class="form-group col-sm-6">
    {!! Form::label('contact_detail', 'Contact Detail:') !!}
    {!! Form::text('contact_detail', null, ['class' => 'form-control']) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:') !!}
    {!! Form::select('status', ], null, ['class' => 'form-control custom-select']) !!}
</div>


<!-- Frequency Field -->
<div class="form-group col-sm-6">
    {!! Form::label('frequency', 'Frequency:') !!}
    {!! Form::select('frequency', ], null, ['class' => 'form-control custom-select']) !!}
</div>
