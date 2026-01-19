<!-- User Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('user_id', 'User Id:') !!}
    {!! Form::select('user_id', ], null, ['class' => 'form-control custom-select']) !!}
</div>


<!-- Plan Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('plan_type', 'Plan Type:') !!}
    {!! Form::select('plan_type', ], null, ['class' => 'form-control custom-select']) !!}
</div>


<!-- Starts At Field -->
<div class="form-group col-sm-6">
    {!! Form::label('starts_at', 'Starts At:') !!}
    {!! Form::text('starts_at', null, ['class' => 'form-control','id'=>'starts_at']) !!}
</div>

@push('page_scripts')
    <script type="text/javascript">
        $('#starts_at').datetimepicker({
            format: 'YYYY-MM-DD HH:mm:ss',
            useCurrent: true,
            sideBySide: true
        })
    </script>
@endpush

<!-- Ends At Field -->
<div class="form-group col-sm-6">
    {!! Form::label('ends_at', 'Ends At:') !!}
    {!! Form::text('ends_at', null, ['class' => 'form-control','id'=>'ends_at']) !!}
</div>

@push('page_scripts')
    <script type="text/javascript">
        $('#ends_at').datetimepicker({
            format: 'YYYY-MM-DD HH:mm:ss',
            useCurrent: true,
            sideBySide: true
        })
    </script>
@endpush