<div class="table-responsive">
    <table class="table" id="exchangeSources-table">
        <thead>
        <tr>
            <th>Name</th>
        <th>Url</th>
        <th>Selector Buy</th>
        <th>Selector Sell</th>
        <th>Is Active</th>
            <th colspan="3">Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($exchangeSources as $exchangeSource)
            <tr>
                <td>{{ $exchangeSource->name }}</td>
            <td>{{ $exchangeSource->url }}</td>
            <td>{{ $exchangeSource->selector_buy }}</td>
            <td>{{ $exchangeSource->selector_sell }}</td>
            <td>{{ $exchangeSource->is_active }}</td>
                <td width="120">
                    {!! Form::open(['route' => ['exchangeSources.destroy', $exchangeSource->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('exchangeSources.show', [$exchangeSource->id]) }}"
                           class='btn btn-default btn-xs'>
                            <i class="far fa-eye"></i>
                        </a>
                        <a href="{{ route('exchangeSources.edit', [$exchangeSource->id]) }}"
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
