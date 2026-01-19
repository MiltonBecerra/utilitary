<!-- User Id Field -->
<div class="col-sm-12">
    {!! Form::label('user_id', 'User Id:') !!}
    <p>{{ $subscription->user_id }}</p>
</div>

<!-- Plan Type Field -->
<div class="col-sm-12">
    {!! Form::label('plan_type', 'Plan Type:') !!}
    <p>{{ $subscription->plan_type }}</p>
</div>

<!-- Starts At Field -->
<div class="col-sm-12">
    {!! Form::label('starts_at', 'Starts At:') !!}
    <p>{{ $subscription->starts_at }}</p>
</div>

<!-- Ends At Field -->
<div class="col-sm-12">
    {!! Form::label('ends_at', 'Ends At:') !!}
    <p>{{ $subscription->ends_at }}</p>
</div>

