<div class="table-responsive">
    <table class="table" id="utilities-table">
        <thead>
        <tr>
            <th>Name</th>
        <th>Slug</th>
        <th>Description</th>
        <th>Icon</th>
        <th>Is Active</th>
            <th colspan="3">Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($utilities as $utility)
            <tr>
                <td>{{ $utility->name }}</td>
            <td>{{ $utility->slug }}</td>
            <td>{{ $utility->description }}</td>
            <td>{{ $utility->icon }}</td>
            <td>{{ $utility->is_active }}</td>
                <td width="120">
                    {!! Form::open(['route' => ['utilities.destroy', $utility->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('utilities.show', [$utility->id]) }}"
                           class='btn btn-default btn-xs'>
                            <i class="far fa-eye"></i>
                        </a>
                        <a href="{{ route('utilities.edit', [$utility->id]) }}"
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
