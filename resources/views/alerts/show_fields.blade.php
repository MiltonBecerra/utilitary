<!-- User Id Field -->
<div class="col-sm-12">
    {!! Form::label('user_id', 'User Id:') !!}
    <p>{{ $alert->user_id }}</p>
</div>

<!-- Guest Id Field -->
<div class="col-sm-12">
    {!! Form::label('guest_id', 'Guest Id:') !!}
    <p>{{ $alert->guest_id }}</p>
</div>

<!-- Exchange Source Id Field -->
<div class="col-sm-12">
    {!! Form::label('exchange_source_id', 'Exchange Source Id:') !!}
    <p>{{ $alert->exchange_source_id }}</p>
</div>

<!-- Target Price Field -->
<div class="col-sm-12">
    {!! Form::label('target_price', 'Target Price:') !!}
    <p>{{ $alert->target_price }}</p>
</div>

<!-- Condition Field -->
<div class="col-sm-12">
    {!! Form::label('condition', 'Condition:') !!}
    <p>{{ $alert->condition }}</p>
</div>

<!-- Channel Field -->
<div class="col-sm-12">
    {!! Form::label('channel', 'Channel:') !!}
    <p>{{ $alert->channel }}</p>
</div>

<!-- Contact Detail Field -->
<div class="col-sm-12">
    {!! Form::label('contact_detail', 'Contact Detail:') !!}
    <p>{{ $alert->contact_detail }}</p>
</div>

<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $alert->status }}</p>
</div>

<!-- Frequency Field -->
<div class="col-sm-12">
    {!! Form::label('frequency', 'Frequency:') !!}
    <p>{{ $alert->frequency }}</p>
</div>

