<div class="table-responsive">
    <table class="table" id="alerts-table">
        <thead>
        <tr>
            <th>User Id</th>
        <th>Guest Id</th>
        <th>Exchange Source Id</th>
        <th>Target Price</th>
        <th>Condition</th>
        <th>Channel</th>
        <th>Contact Detail</th>
        <th>Status</th>
        <th>Frequency</th>
            <th colspan="3">Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($alerts as $alert)
            <tr>
                <td>{{ $alert->user_id }}</td>
            <td>{{ $alert->guest_id }}</td>
            <td>{{ $alert->exchange_source_id }}</td>
            <td>{{ $alert->target_price }}</td>
            <td>{{ $alert->condition }}</td>
            <td>{{ $alert->channel }}</td>
            <td>{{ $alert->contact_detail }}</td>
            <td>{{ $alert->status }}</td>
            <td>{{ $alert->frequency }}</td>
                <td width="120">
                    {!! Form::open(['route' => ['alerts.destroy', $alert->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('alerts.show', [$alert->id]) }}"
                           class='btn btn-default btn-xs'>
                            <i class="far fa-eye"></i>
                        </a>
                        <a href="{{ route('alerts.edit', [$alert->id]) }}"
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
