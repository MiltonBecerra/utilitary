<div class="table-responsive">
    <table class="table" id="subscriptions-table">
        <thead>
        <tr>
            <th>User Id</th>
        <th>Plan Type</th>
        <th>Starts At</th>
        <th>Ends At</th>
            <th colspan="3">Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($subscriptions as $subscription)
            <tr>
                <td>{{ $subscription->user_id }}</td>
            <td>{{ $subscription->plan_type }}</td>
            <td>{{ $subscription->starts_at }}</td>
            <td>{{ $subscription->ends_at }}</td>
                <td width="120">
                    {!! Form::open(['route' => ['subscriptions.destroy', $subscription->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('subscriptions.show', [$subscription->id]) }}"
                           class='btn btn-default btn-xs'>
                            <i class="far fa-eye"></i>
                        </a>
                        <a href="{{ route('subscriptions.edit', [$subscription->id]) }}"
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
